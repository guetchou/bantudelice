<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Payment;
use App\Services\FinancialEventService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShipmentPaymentService
{
    public function __construct(
        protected ?FinancialEventService $financialEventService = null
    ) {}

    public function initiatePayment(Shipment $shipment, string $provider, array $context = []): array
    {
        $paymentService = app(PaymentService::class);

        $paymentFlow = $paymentService->startManagedPayment([
            'user_id' => $shipment->customer_id,
            'order_id' => null,
            'shipment_id' => $shipment->id,
            'provider' => $provider,
            'amount' => $this->normalizePaymentAmount($shipment->total_price),
            'currency' => $shipment->currency,
        ], [
            'type' => 'colis',
            'phone' => $context['phone'] ?? optional($shipment->customer)->phone,
            'shipment_id' => $shipment->id,
            'shipment_payment_context' => [
                'tracking_number' => $shipment->tracking_number,
                'payment_method' => $provider,
            ],
        ], [
            'tracking_number' => $shipment->tracking_number,
            'type' => 'colis',
        ]);

        $payment = $paymentFlow['payment'];

        $this->financialEvents()->recordForPayment($payment, 'shipment_payment_initiated', [
            'tracking_number' => $shipment->tracking_number,
            'shipment_id' => $shipment->id,
        ]);

        return [
            'payment_id' => $payment->id,
            'checkout_url' => url("/api/v1/payments/process/{$payment->id}"),
            'status' => 'pending',
            'redirect_url' => $paymentFlow['payment_payload']['redirect_url'] ?? null,
        ];
    }

    public function finalizePayment(Shipment $shipment, Payment $payment): bool
    {
        return DB::transaction(function () use ($shipment, $payment) {
            $stateMachine = app(ShipmentStateMachine::class);
            $currentStatus = $shipment->status;
            $transitionedToPaid = false;

            $shipment->update([
                'payment_status' => 'paid',
            ]);

            if ($stateMachine->canTransition($currentStatus, ShipmentStatus::PAID)) {
                $stateMachine->transitionTo($shipment, ShipmentStatus::PAID, [
                    'actor_type' => 'system',
                    'notes' => "Paiement confirmé via {$payment->provider}.",
                    'meta' => [
                        'payment_id' => $payment->id,
                        'provider' => $payment->provider,
                    ],
                ]);
                $transitionedToPaid = true;
            }

            $this->financialEvents()->recordForPayment($payment->fresh(), 'shipment_payment_paid', [
                'tracking_number' => $shipment->tracking_number,
                'shipment_id' => $shipment->id,
                'shipment_status' => $shipment->fresh()->status->value,
                'shipment_previous_status' => $currentStatus->value,
                'shipment_transition_to_paid' => $transitionedToPaid,
            ]);

            return true;
        });
    }

    public function handleCOD(Shipment $shipment): void
    {
        $stateMachine = app(ShipmentStateMachine::class);
        $currentStatus = $shipment->status;
        $transitionedToPaid = false;

        $shipment->update([
            'payment_status' => 'cod_pending',
        ]);

        if ($stateMachine->canTransition($currentStatus, ShipmentStatus::PAID)) {
            $stateMachine->transitionTo($shipment, ShipmentStatus::PAID, [
                'actor_type' => 'customer',
                'notes' => 'Option Paiement à la livraison choisie.',
                'meta' => [
                    'payment_mode' => 'cod',
                ],
            ]);
            $transitionedToPaid = true;
        }

        $this->financialEvents()->record([
            'order_no' => $shipment->tracking_number,
            'order_id' => null,
            'payment_id' => null,
            'user_id' => $shipment->customer_id,
            'event_type' => 'shipment_cod_selected',
            'provider' => 'cod',
            'amount' => (float) $shipment->cod_amount,
            'currency' => $shipment->currency,
            'status' => $shipment->payment_status,
            'meta' => [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'shipment_previous_status' => $currentStatus->value,
                'shipment_transition_to_paid' => $transitionedToPaid,
            ],
        ]);
    }

    public function markCodCollected(Shipment $shipment, array $context = []): void
    {
        DB::transaction(function () use ($shipment, $context) {
            $shipment->update([
                'payment_status' => 'paid',
                'cod_collected_at' => now(),
            ]);

            $payment = Payment::where('shipment_id', $shipment->id)->latest('id')->first();
            if ($payment && strtoupper((string) $payment->status) !== 'PAID') {
                $payment->update([
                    'status' => 'PAID',
                    'meta' => array_merge($payment->meta ?? [], [
                        'cod_collected_at' => now()->toIso8601String(),
                        'collected_by_courier_id' => $context['courier_id'] ?? null,
                    ]),
                ]);

                $this->financialEvents()->recordForPayment($payment->fresh(), 'shipment_cod_collected', [
                    'tracking_number' => $shipment->tracking_number,
                    'shipment_id' => $shipment->id,
                    'courier_id' => $context['courier_id'] ?? null,
                ]);
            } else {
                $this->financialEvents()->record([
                    'order_no' => $shipment->tracking_number,
                    'order_id' => null,
                    'payment_id' => null,
                    'user_id' => $shipment->customer_id,
                    'event_type' => 'shipment_cod_collected',
                    'provider' => 'cod',
                    'amount' => (float) $shipment->cod_amount,
                    'currency' => $shipment->currency,
                    'status' => $shipment->payment_status,
                    'meta' => [
                        'shipment_id' => $shipment->id,
                        'courier_id' => $context['courier_id'] ?? null,
                    ],
                ]);
            }
        });
    }

    public function getPendingCourierCOD(int $courierId): array
    {
        $shipments = Shipment::where('assigned_courier_id', $courierId)
            ->where('status', ShipmentStatus::DELIVERED)
            ->where('payment_status', 'cod_pending')
            ->where('cod_amount', '>', 0)
            ->get();

        return [
            'count' => $shipments->count(),
            'total_amount' => $shipments->sum('cod_amount'),
            'shipment_ids' => $shipments->pluck('id')->toArray(),
        ];
    }

    public function reconcileCourier(int $courierId, int $adminId, array $shipmentIds, float $amount): \App\Domain\Colis\Models\ShipmentReconciliation
    {
        return DB::transaction(function () use ($courierId, $adminId, $shipmentIds, $amount) {
            $reconciliation = \App\Domain\Colis\Models\ShipmentReconciliation::create([
                'courier_id' => $courierId,
                'admin_id' => $adminId,
                'amount_collected' => $amount,
                'amount_reconciled' => $amount,
                'shipment_ids' => $shipmentIds,
                'status' => 'completed',
                'notes' => "Réconciliation manuelle effectuée par l'admin.",
            ]);

            Shipment::whereIn('id', $shipmentIds)->update([
                'payment_status' => 'paid',
            ]);

            $shipments = Shipment::whereIn('id', $shipmentIds)->get();
            foreach ($shipments as $shipment) {
                if (Schema::hasColumn('shipments', 'cod_collected_at') && !$shipment->cod_collected_at) {
                    $shipment->update(['cod_collected_at' => now()]);
                }

                $this->financialEvents()->record([
                    'order_no' => $shipment->tracking_number,
                    'order_id' => null,
                    'payment_id' => null,
                    'user_id' => $shipment->customer_id,
                    'event_type' => 'shipment_cod_reconciled',
                    'provider' => 'cod',
                    'amount' => (float) $shipment->cod_amount,
                    'currency' => $shipment->currency,
                    'status' => $shipment->payment_status,
                    'meta' => [
                        'shipment_id' => $shipment->id,
                        'courier_id' => $courierId,
                        'admin_id' => $adminId,
                        'reconciliation_id' => $reconciliation->id,
                    ],
                ]);
            }

            return $reconciliation;
        });
    }

    protected function financialEvents(): FinancialEventService
    {
        return $this->financialEventService ?? app(FinancialEventService::class);
    }

    protected function normalizePaymentAmount($amount): int
    {
        if (!is_numeric($amount)) {
            return 0;
        }

        return max(0, (int) round((float) $amount));
    }
}
