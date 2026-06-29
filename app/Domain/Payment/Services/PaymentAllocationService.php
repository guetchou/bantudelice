<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Enums\PaymentAllocationStatus;
use App\Domain\Payment\Models\PaymentAllocation;
use App\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class PaymentAllocationService
{
    public function allocate(
        Payment $payment,
        Model $allocatable,
        int $amount,
        string $idempotencyKey,
        array $metadata = []
    ): PaymentAllocation {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant affecté doit être strictement positif.');
        }

        return DB::transaction(function () use (
            $payment,
            $allocatable,
            $amount,
            $idempotencyKey,
            $metadata
        ) {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            if (!$lockedPayment->canonicalStatus()->isFinanciallyConfirmed()) {
                throw new LogicException('Seul un paiement confirmé peut être affecté.');
            }

            $existing = PaymentAllocation::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $this->assertSameIntent($existing, $lockedPayment, $allocatable, $amount);

                return $existing;
            }

            $alreadyAllocated = (int) PaymentAllocation::query()
                ->where('payment_id', $lockedPayment->id)
                ->where('status', PaymentAllocationStatus::ACTIVE->value)
                ->sum('amount');

            if (($alreadyAllocated + $amount) > (int) $lockedPayment->amount) {
                throw new LogicException('L’affectation dépasse le montant confirmé du paiement.');
            }

            return PaymentAllocation::query()->create([
                'payment_id' => $lockedPayment->id,
                'allocatable_type' => $allocatable->getMorphClass(),
                'allocatable_id' => $allocatable->getKey(),
                'amount' => $amount,
                'currency' => strtoupper((string) ($lockedPayment->currency ?: 'XAF')),
                'status' => PaymentAllocationStatus::ACTIVE->value,
                'idempotency_key' => $idempotencyKey,
                'allocated_at' => now(),
                'metadata' => $metadata,
            ]);
        });
    }

    public function reverse(
        PaymentAllocation $allocation,
        string $reason,
        array $metadata = []
    ): PaymentAllocation {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Le motif d’annulation de l’affectation est obligatoire.');
        }

        return DB::transaction(function () use ($allocation, $reason, $metadata) {
            $locked = PaymentAllocation::query()
                ->lockForUpdate()
                ->findOrFail($allocation->getKey());

            if ($locked->status === PaymentAllocationStatus::REVERSED) {
                return $locked;
            }

            $locked->forceFill([
                'status' => PaymentAllocationStatus::REVERSED->value,
                'reversed_at' => now(),
                'metadata' => array_merge($locked->metadata ?? [], $metadata, [
                    'reversal_reason' => $reason,
                ]),
            ])->save();

            return $locked->fresh();
        });
    }

    public function unallocatedAmount(Payment $payment): int
    {
        $allocated = (int) PaymentAllocation::query()
            ->where('payment_id', $payment->id)
            ->where('status', PaymentAllocationStatus::ACTIVE->value)
            ->sum('amount');

        return max((int) $payment->amount - $allocated, 0);
    }

    private function assertSameIntent(
        PaymentAllocation $allocation,
        Payment $payment,
        Model $allocatable,
        int $amount
    ): void {
        $sameTarget = $allocation->allocatable_type === $allocatable->getMorphClass()
            && (int) $allocation->allocatable_id === (int) $allocatable->getKey();

        if (
            (int) $allocation->payment_id !== (int) $payment->id
            || !$sameTarget
            || (int) $allocation->amount !== $amount
        ) {
            throw new LogicException('La clé d’idempotence existe déjà pour une autre affectation.');
        }
    }
}
