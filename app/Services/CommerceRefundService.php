<?php

namespace App\Services;

use App\Order;
use App\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\DisbursementService;

class CommerceRefundService
{
    public function __construct(
        protected ?FinancialLedgerService $ledger = null,
        protected ?FinancialEventService $financialEvents = null,
        protected ?CommerceSignalService $signals = null,
        protected ?SupportTicketService $supportTickets = null
    ) {}

    public function refundOrder(Order $order, string $reason = 'order_cancelled', array $context = []): array
    {
        $payment = $order->payment ?: Payment::where('order_id', $order->id)->latest('id')->first();

        if (!$payment) {
            return [
                'status' => 'noop',
                'message' => 'Aucun paiement à rembourser',
            ];
        }

        return $this->refundPayment($payment, $reason, array_merge($context, [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
        ]));
    }

    public function refundPayment(Payment $payment, string $reason, array $context = []): array
    {
        $payment->refresh();
        $meta = (array) ($payment->meta ?? []);
        $currentStatus = strtolower((string) ($meta['refund_status'] ?? ''));
        $idempotencyKey = $context['idempotency_key'] ?? ($payment->provider_reference ?: ('refund:' . $payment->id));

        if (in_array($currentStatus, ['refunded', 'pending', 'manual_required'], true) && ($meta['refund_idempotency_key'] ?? null) === $idempotencyKey) {
            return [
                'status' => $currentStatus,
                'message' => 'Remboursement déjà traité',
                'payment' => $payment->fresh(),
            ];
        }

        $amount = (float) ($context['amount'] ?? $payment->amount);
        $amount = min($amount, (float) $payment->amount);
        $provider = strtolower((string) ($payment->provider ?? 'manual'));
        $order = $payment->order()->with('restaurant')->first();
        $reference = $context['refund_reference'] ?? ('RF-' . strtoupper($provider) . '-' . $payment->id . '-' . now()->format('YmdHis'));
        $providerResult = $this->requestProviderRefund($payment, $amount, $reason, $context);
        return DB::transaction(function () use ($payment, $amount, $reason, $context, $provider, $order, $reference, $providerResult, $idempotencyKey) {
            $payment->refresh();
            $meta = (array) ($payment->meta ?? []);
            $manualRequired = strtolower((string) config('commerce.refunds.auto_mode', 'automatic')) !== 'automatic'
                || !($providerResult['success'] ?? false);
            $finalStatus = $manualRequired ? 'REFUND_PENDING' : 'REFUNDED';

            $meta = array_merge($meta, [
                'refund_reason' => $reason,
                'refund_reference' => $reference,
                'refund_idempotency_key' => $idempotencyKey,
                'refund_requested_at' => now()->toIso8601String(),
                'refund_provider' => $provider,
                'refund_status' => $manualRequired ? 'manual_required' : 'refunded',
                'refund_provider_response' => $providerResult,
                'refund_manual_required' => $manualRequired,
            ]);

            $payment->update([
                'status' => $finalStatus,
                'meta' => $meta,
            ]);

            if ($manualRequired && !empty(config('commerce.refunds.manual_fallback', true)) && !empty(config('commerce.support.auto_ticket_on_refund', true))) {
                $this->supportTickets()?->openFromPayment($payment->fresh(), 'refund', 'Remboursement manuel requis', 'Le provider n\'a pas accepté le remboursement automatique.', [
                    'opened_by_type' => $context['opened_by_type'] ?? 'system',
                    'opened_by_id' => $context['opened_by_id'] ?? null,
                    'priority' => 'high',
                    'status' => 'open',
                    'refund_reference' => $reference,
                    'refund_provider_response' => $providerResult,
                ]);
            }

            if (!$manualRequired) {
                $module = $context['module'] ?? ($payment->shipment_id ? 'colis' : ($payment->transport_booking_id ? 'transport' : 'food'));

                if ($order instanceof Order) {
                    $this->ledger()?->refund($order, $amount, [
                        'reference' => $reference,
                        'entry_type' => 'refund_completed',
                        'module' => $module,
                        'actor_type' => $context['actor_type'] ?? 'system',
                        'actor_id' => $context['actor_id'] ?? null,
                        'status' => 'posted',
                        'provider' => $provider,
                    ]);
                } else {
                    $this->ledger()?->record([
                        'module' => $module,
                        'entry_type' => 'refund_completed',
                        'direction' => 'debit',
                        'status' => 'posted',
                        'payment_id' => $payment->id,
                        'reference' => $reference,
                        'amount' => $amount,
                        'currency' => $payment->currency ?? config('commerce.currency', 'FCFA'),
                        'actor_type' => $context['actor_type'] ?? 'system',
                        'actor_id' => $context['actor_id'] ?? null,
                        'payload' => array_merge($context, ['provider' => $provider, 'provider_result' => $providerResult]),
                    ]);
                }
            } else {
                $this->ledger()?->record([
                    'module' => 'food',
                    'entry_type' => 'refund_requested',
                    'direction' => 'debit',
                    'status' => 'pending',
                    'order_id' => $payment->order_id,
                    'order_no' => optional($payment->order)->order_no,
                    'payment_id' => $payment->id,
                    'reference' => $reference,
                    'amount' => $amount,
                    'currency' => $payment->currency ?? config('commerce.currency', 'FCFA'),
                    'actor_type' => $context['actor_type'] ?? 'system',
                    'actor_id' => $context['actor_id'] ?? null,
                    'payload' => array_merge($context, ['provider_result' => $providerResult]),
                ]);
            }

            $this->financialEvents()?->recordForPayment($payment->fresh(), $manualRequired ? 'refund_pending' : 'refund_completed', [
                'reason' => $reason,
                'amount' => $amount,
                'provider' => $provider,
                'reference' => $reference,
                'provider_result' => $providerResult,
                'manual_required' => $manualRequired,
            ]);

            $this->signals()?->emit('payment.refund_' . ($manualRequired ? 'pending' : 'completed'), [
                'domain' => 'commerce',
                'module' => 'food',
                'severity' => $manualRequired ? 'warning' : 'info',
                'order_id' => $payment->order_id,
                'order_no' => optional($payment->order)->order_no,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'provider' => $provider,
                'reason' => $reason,
                'reference' => $reference,
                'manual_required' => $manualRequired,
            ]);

            return [
                'status' => $manualRequired ? 'manual_required' : 'refunded',
                'message' => $manualRequired
                    ? 'Remboursement demandé, validation manuelle requise'
                    : 'Remboursement effectué',
                'reference' => $reference,
                'provider_result' => $providerResult,
                'payment' => $payment->fresh(),
            ];
        });
    }

    protected function requestProviderRefund(Payment $payment, float $amount, string $reason, array $context = []): array
    {
        $provider = strtolower((string) ($payment->provider ?? 'manual'));

        // S4.2 — Remboursement direct via DisbursementService pour MTN MoMo et Airtel
        if (in_array($provider, ['momo', 'mtn', 'mtn_momo', 'mobile_money', 'airtel', 'airtel_money'], true)) {
            return $this->refundViaDisbursement($payment, $amount, $reason, $context);
        }

        // PayPal et autres — via endpoint externe configuré dans commerce.php
        $refundEndpoint = data_get(config('commerce.refunds.providers', []), "{$provider}.refund_endpoint");

        if (!$refundEndpoint) {
            return [
                'success' => false,
                'status'  => 'manual_required',
                'reason'  => 'refund_endpoint_missing',
            ];
        }

        try {
            $response = Http::timeout((int) config('commerce.refunds.provider_timeout', 20))
                ->acceptJson()
                ->post($refundEndpoint, [
                    'provider'           => $provider,
                    'provider_reference' => $payment->provider_reference,
                    'payment_id'         => $payment->id,
                    'order_id'           => $payment->order_id,
                    'order_no'           => optional($payment->order)->order_no,
                    'amount'             => $amount,
                    'currency'           => $payment->currency ?? config('commerce.currency', 'FCFA'),
                    'reason'             => $reason,
                    'context'            => $context,
                    'requested_at'       => now()->toIso8601String(),
                ]);

            if ($response->successful()) {
                return [
                    'success'  => true,
                    'status'   => 'accepted',
                    'response' => $response->json(),
                ];
            }

            Log::warning('Refund provider rejected request', [
                'payment_id' => $payment->id,
                'provider' => $provider,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'status' => 'rejected',
                'response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Refund provider exception', [
                'payment_id' => $payment->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * S4.2 — Remboursement Mobile Money via DisbursementService (MTN MoMo / Airtel).
     * Utilise le numéro de téléphone du paiement ou de l'utilisateur.
     */
    protected function refundViaDisbursement(Payment $payment, float $amount, string $reason, array $context = []): array
    {
        // Récupérer le numéro de téléphone depuis le meta du paiement ou l'utilisateur
        $phone = data_get($payment->meta, 'phone')
            ?? data_get($payment->meta, 'payer_phone')
            ?? optional($payment->user)->phone
            ?? null;

        if (!$phone) {
            Log::warning('CommerceRefundService: numéro de téléphone manquant pour remboursement Mobile Money', [
                'payment_id' => $payment->id,
            ]);
            return [
                'success' => false,
                'status'  => 'manual_required',
                'reason'  => 'phone_missing_for_mobile_money_refund',
            ];
        }

        try {
            $result = DisbursementService::initiateDisbursement($phone, (int) round($amount), array_merge($context, [
                'payment_id'         => $payment->id,
                'order_id'           => $payment->order_id,
                'order_no'           => optional($payment->order)->order_no,
                'reason'             => $reason,
                'type'               => 'refund',
                'idempotency_key'    => 'refund-' . $payment->id . '-' . sha1($reason . $amount),
            ]));

            if ($result['success'] ?? false) {
                Log::info('CommerceRefundService: remboursement Mobile Money initié', [
                    'payment_id' => $payment->id,
                    'phone'      => $phone,
                    'amount'     => $amount,
                    'reference'  => $result['reference'] ?? null,
                ]);
                return [
                    'success'   => true,
                    'status'    => 'accepted',
                    'reference' => $result['reference'] ?? null,
                    'response'  => $result,
                ];
            }

            Log::warning('CommerceRefundService: remboursement Mobile Money refusé', [
                'payment_id' => $payment->id,
                'error'      => $result['error'] ?? 'unknown',
            ]);
            return [
                'success' => false,
                'status'  => 'manual_required',
                'reason'  => $result['error'] ?? 'disbursement_failed',
            ];
        } catch (\Throwable $e) {
            Log::error('CommerceRefundService: exception remboursement Mobile Money', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'status'  => 'error',
                'error'   => $e->getMessage(),
            ];
        }
    }

    protected function ledger(): ?FinancialLedgerService
    {
        return $this->ledger ?: app(FinancialLedgerService::class);
    }

    protected function financialEvents(): ?FinancialEventService
    {
        return $this->financialEvents ?: app(FinancialEventService::class);
    }

    protected function signals(): ?CommerceSignalService
    {
        return $this->signals ?: app(CommerceSignalService::class);
    }

    protected function supportTickets(): ?SupportTicketService
    {
        return $this->supportTickets ?: app(SupportTicketService::class);
    }
}
