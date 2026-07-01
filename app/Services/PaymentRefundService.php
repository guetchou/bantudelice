<?php

namespace App\Services;

use App\FinancialLedgerEntry;
use App\Payment;
use App\PaymentRefund;
use Illuminate\Support\Facades\DB;

class PaymentRefundService
{
    public function __construct(
        private readonly PaymentBusinessStateService $paymentStates,
        private readonly FinancialLedgerService $ledger,
        private readonly FinancialStateTransitionService $transitionJournal,
    ) {}

    public function request(
        Payment $payment,
        int $amount,
        string $reason,
        string $idempotencyKey,
        ?int $requestedBy = null,
        array $metadata = [],
    ): PaymentRefund {
        $reason = trim($reason);
        $idempotencyKey = trim($idempotencyKey);

        if ($amount <= 0) {
            throw new \DomainException('Le montant du remboursement doit être strictement positif.');
        }

        if ($reason === '') {
            throw new \DomainException('Le motif du remboursement est obligatoire.');
        }

        if ($idempotencyKey === '') {
            throw new \DomainException('Une clé d’idempotence est obligatoire.');
        }

        return DB::transaction(function () use (
            $payment,
            $amount,
            $reason,
            $idempotencyKey,
            $requestedBy,
            $metadata,
        ) {
            $existing = PaymentRefund::where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $lockedPayment = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if (!$lockedPayment->isFinanciallyConfirmed()) {
                throw new \DomainException('Seul un paiement financièrement confirmé peut être remboursé.');
            }

            $committed = (int) PaymentRefund::where('payment_id', $lockedPayment->id)
                ->whereIn('status', ['requested', 'approved', 'submitted', 'pending', 'unknown', 'refunded'])
                ->lockForUpdate()
                ->get()
                ->sum('amount');

            if ($committed + $amount > (int) $lockedPayment->amount) {
                throw new \DomainException('Le total des remboursements dépasse le montant confirmé du paiement.');
            }

            $refund = PaymentRefund::create([
                'payment_id' => $lockedPayment->id,
                'amount' => $amount,
                'currency' => $lockedPayment->currency ?: 'XAF',
                'status' => 'requested',
                'reason' => $reason,
                'idempotency_key' => $idempotencyKey,
                'requested_by' => $requestedBy,
                'requested_at' => now(),
                'metadata' => $metadata,
            ]);

            $this->recordRefundTransition($refund, null, 'requested', [
                'source' => 'refund_request',
                'reason' => $reason,
                'actor_id' => $requestedBy,
                'idempotency_key' => $idempotencyKey . ':transition:requested',
                'context' => ['amount' => $amount, 'payment_id' => $lockedPayment->id],
            ]);

            return $refund;
        });
    }

    public function approve(PaymentRefund $refund, int $approvedBy): PaymentRefund
    {
        return DB::transaction(function () use ($refund, $approvedBy) {
            $locked = PaymentRefund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'approved') {
                return $locked;
            }

            $from = $locked->status;
            $locked->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            $this->recordRefundTransition($locked, $from, 'approved', [
                'source' => 'refund_approval',
                'actor_id' => $approvedBy,
                'idempotency_key' => 'refund:' . $locked->uuid . ':transition:approved',
            ]);

            return $locked->fresh();
        });
    }

    public function submit(PaymentRefund $refund, string $providerReference): PaymentRefund
    {
        $providerReference = trim($providerReference);

        if ($providerReference === '') {
            throw new \DomainException('La référence fournisseur est obligatoire pour soumettre le remboursement.');
        }

        return DB::transaction(function () use ($refund, $providerReference) {
            $locked = PaymentRefund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'submitted') {
                if ($locked->provider_reference !== $providerReference) {
                    throw new \DomainException('La référence fournisseur d’un remboursement soumis est immuable.');
                }

                return $locked;
            }

            $from = $locked->status;
            $locked->update([
                'status' => 'submitted',
                'provider_reference' => $providerReference,
                'submitted_at' => now(),
            ]);

            $this->recordRefundTransition($locked, $from, 'submitted', [
                'source' => 'refund_provider_submission',
                'idempotency_key' => 'refund:' . $locked->uuid . ':transition:submitted',
                'context' => ['provider_reference' => $providerReference],
            ]);

            return $locked->fresh();
        });
    }

    public function markPending(PaymentRefund $refund, array $metadata = []): PaymentRefund
    {
        return $this->transition($refund, 'pending', [
            'source' => 'provider_status',
            'metadata' => $metadata,
        ]);
    }

    public function markUnknown(PaymentRefund $refund, string $reason, array $metadata = []): PaymentRefund
    {
        return $this->transition($refund, 'unknown', [
            'source' => 'provider_status',
            'reason' => $reason,
            'metadata' => array_merge($metadata, ['uncertainty_reason' => $reason]),
        ]);
    }

    public function markFailed(PaymentRefund $refund, string $reason, array $metadata = []): PaymentRefund
    {
        return $this->transition($refund, 'failed', [
            'source' => 'provider_status',
            'reason' => $reason,
            'failed_at' => now(),
            'metadata' => array_merge($metadata, ['failure_reason' => $reason]),
        ]);
    }

    public function markRefunded(PaymentRefund $refund, array $metadata = []): PaymentRefund
    {
        return DB::transaction(function () use ($refund, $metadata) {
            $lockedRefund = PaymentRefund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedRefund->status !== 'refunded') {
                $from = $lockedRefund->status;
                $lockedRefund->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'metadata' => array_merge($lockedRefund->metadata ?? [], $metadata),
                ]);

                $this->recordRefundTransition($lockedRefund, $from, 'refunded', [
                    'source' => 'provider_status',
                    'idempotency_key' => 'refund:' . $lockedRefund->uuid . ':transition:refunded',
                    'context' => $metadata,
                ]);
            }

            $payment = Payment::whereKey($lockedRefund->payment_id)->lockForUpdate()->firstOrFail();
            $refundedAmount = (int) PaymentRefund::where('payment_id', $payment->id)
                ->where('status', 'refunded')
                ->lockForUpdate()
                ->get()
                ->sum('amount');

            $targetStatus = $refundedAmount >= (int) $payment->amount
                ? 'refunded'
                : 'partially_refunded';

            if ($payment->canonicalBusinessStatus() !== $targetStatus) {
                $payment = $this->paymentStates->transition($payment, $targetStatus, [
                    'source' => 'refund_service',
                    'reason' => 'refund_confirmed',
                    'idempotency_key' => 'payment:' . $payment->id . ':refund:' . $lockedRefund->uuid . ':confirmed',
                    'context' => [
                        'refund_id' => $lockedRefund->id,
                        'refunded_total' => $refundedAmount,
                    ],
                ]);
            }

            $this->ledger->record([
                'module' => 'payments',
                'account_type' => 'customer',
                'account_id' => $payment->user_id,
                'entry_type' => 'refund',
                'direction' => 'debit',
                'status' => 'posted',
                'payment_id' => $payment->id,
                'source_type' => 'payment_refund',
                'source_id' => $lockedRefund->id,
                'reference' => $lockedRefund->provider_reference,
                'idempotency_key' => 'refund:' . $lockedRefund->uuid . ':ledger',
                'amount' => (int) $lockedRefund->amount,
                'currency' => $lockedRefund->currency,
                'refund_reason' => $lockedRefund->reason,
            ]);

            return $lockedRefund->fresh();
        });
    }

    public function reverse(PaymentRefund $refund, string $reason, ?int $actorId = null): PaymentRefund
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new \DomainException('Le motif d’inversion du remboursement est obligatoire.');
        }

        return DB::transaction(function () use ($refund, $reason, $actorId) {
            $lockedRefund = PaymentRefund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedRefund->status === 'reversed') {
                return $lockedRefund;
            }

            $from = $lockedRefund->status;
            $lockedRefund->update([
                'status' => 'reversed',
                'metadata' => array_merge($lockedRefund->metadata ?? [], [
                    'reversal_reason' => $reason,
                    'reversed_by' => $actorId,
                    'reversed_at' => now()->toIso8601String(),
                ]),
            ]);

            $this->recordRefundTransition($lockedRefund, $from, 'reversed', [
                'source' => 'refund_reversal',
                'reason' => $reason,
                'actor_id' => $actorId,
                'idempotency_key' => 'refund:' . $lockedRefund->uuid . ':transition:reversed',
            ]);

            $payment = Payment::whereKey($lockedRefund->payment_id)->lockForUpdate()->firstOrFail();
            $remainingRefunded = (int) PaymentRefund::where('payment_id', $payment->id)
                ->where('status', 'refunded')
                ->lockForUpdate()
                ->get()
                ->sum('amount');

            $targetStatus = $remainingRefunded <= 0
                ? 'confirmed'
                : ($remainingRefunded >= (int) $payment->amount ? 'refunded' : 'partially_refunded');

            if ($payment->canonicalBusinessStatus() !== $targetStatus) {
                $this->paymentStates->transition($payment, $targetStatus, [
                    'source' => 'refund_service',
                    'reason' => 'refund_reversed',
                    'actor_id' => $actorId,
                    'idempotency_key' => 'payment:' . $payment->id . ':refund:' . $lockedRefund->uuid . ':reversed',
                    'context' => ['refund_id' => $lockedRefund->id],
                ]);
            }

            $ledgerEntry = FinancialLedgerEntry::where(
                'idempotency_key',
                'refund:' . $lockedRefund->uuid . ':ledger'
            )->first();

            if ($ledgerEntry) {
                $this->ledger->reverse(
                    $ledgerEntry,
                    $reason,
                    'refund:' . $lockedRefund->uuid . ':ledger:reversal',
                    $actorId,
                );
            }

            return $lockedRefund->fresh();
        });
    }

    private function transition(PaymentRefund $refund, string $status, array $attributes = []): PaymentRefund
    {
        return DB::transaction(function () use ($refund, $status, $attributes) {
            $locked = PaymentRefund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === $status) {
                return $locked;
            }

            $from = $locked->status;
            $metadata = array_merge($locked->metadata ?? [], $attributes['metadata'] ?? []);
            $source = $attributes['source'] ?? 'refund_service';
            $reason = $attributes['reason'] ?? null;
            unset($attributes['metadata'], $attributes['source'], $attributes['reason']);

            $locked->update(array_merge($attributes, [
                'status' => $status,
                'metadata' => $metadata,
            ]));

            $this->recordRefundTransition($locked, $from, $status, [
                'source' => $source,
                'reason' => $reason,
                'idempotency_key' => 'refund:' . $locked->uuid . ':transition:' . $status,
                'context' => $metadata,
            ]);

            return $locked->fresh();
        });
    }

    private function recordRefundTransition(
        PaymentRefund $refund,
        ?string $fromStatus,
        string $toStatus,
        array $context,
    ): void {
        $this->transitionJournal->record(
            'payment_refund',
            $refund->id,
            $fromStatus,
            $toStatus,
            $context,
        );
    }
}
