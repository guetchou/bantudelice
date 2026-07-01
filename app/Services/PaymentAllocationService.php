<?php

namespace App\Services;

use App\Payment;
use App\PaymentAllocation;
use Illuminate\Support\Facades\DB;

class PaymentAllocationService
{
    private const ALLOWED_TARGETS = [
        'order',
        'shipment',
        'transport_booking',
        'rental_due',
        'obligation',
    ];

    public function allocate(
        Payment $payment,
        string $targetType,
        int $targetId,
        int $amount,
        string $idempotencyKey,
        ?string $targetReference = null,
        array $metadata = [],
        ?int $createdBy = null,
    ): PaymentAllocation {
        $targetType = strtolower(trim($targetType));

        if (!in_array($targetType, self::ALLOWED_TARGETS, true)) {
            throw new \DomainException('Type de cible d’affectation non autorisé.');
        }

        if ($targetId <= 0 || $amount <= 0) {
            throw new \DomainException('La cible et le montant doivent être strictement positifs.');
        }

        if (trim($idempotencyKey) === '') {
            throw new \DomainException('Une clé d’idempotence est obligatoire.');
        }

        return DB::transaction(function () use (
            $payment,
            $targetType,
            $targetId,
            $amount,
            $idempotencyKey,
            $targetReference,
            $metadata,
            $createdBy,
        ) {
            $existing = PaymentAllocation::where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $lockedPayment = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if (!$lockedPayment->isConfirmed()) {
                throw new \DomainException('Seul un paiement confirmé peut être affecté.');
            }

            $allocations = PaymentAllocation::where('payment_id', $lockedPayment->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();
            $alreadyAllocated = (int) $allocations->sum('amount');

            if ($alreadyAllocated + $amount > (int) $lockedPayment->amount) {
                throw new \DomainException('Le montant affecté dépasse le montant confirmé du paiement.');
            }

            return PaymentAllocation::create([
                'payment_id' => $lockedPayment->id,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'target_reference' => $targetReference,
                'amount' => $amount,
                'currency' => $lockedPayment->currency ?: 'XAF',
                'status' => 'active',
                'idempotency_key' => $idempotencyKey,
                'allocated_at' => now(),
                'created_by' => $createdBy,
                'metadata' => $metadata,
            ]);
        });
    }

    public function reverse(PaymentAllocation $allocation, string $reason, ?int $actorId = null): PaymentAllocation
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new \DomainException('Le motif de contre-affectation est obligatoire.');
        }

        return DB::transaction(function () use ($allocation, $reason, $actorId) {
            $locked = PaymentAllocation::whereKey($allocation->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'reversed') {
                return $locked;
            }

            $locked->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'metadata' => array_merge($locked->metadata ?? [], [
                    'reversed_by' => $actorId,
                ]),
            ]);

            return $locked->fresh();
        });
    }
}
