<?php

namespace App\Services;

use App\Payment;
use Illuminate\Support\Facades\DB;

class PaymentBusinessStateService
{
    private const TRANSITIONS = [
        'initiated' => ['pending', 'authorized', 'confirmed', 'failed', 'cancelled', 'expired', 'unknown'],
        'pending' => ['authorized', 'confirmed', 'failed', 'cancelled', 'expired', 'unknown'],
        'authorized' => ['confirmed', 'failed', 'cancelled', 'expired', 'unknown'],
        'unknown' => ['pending', 'confirmed', 'failed', 'reversed'],
        'confirmed' => ['refunded', 'reversed', 'disputed'],
        'disputed' => ['confirmed', 'refunded', 'reversed'],
        'failed' => [],
        'cancelled' => [],
        'expired' => [],
        'refunded' => [],
        'reversed' => [],
    ];

    public function transition(Payment $payment, string $targetStatus, array $context = []): Payment
    {
        $targetStatus = strtolower(trim($targetStatus));

        return DB::transaction(function () use ($payment, $targetStatus, $context) {
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $current = $locked->canonicalBusinessStatus();

            if ($current === $targetStatus) {
                return $locked;
            }

            if (!in_array($targetStatus, self::TRANSITIONS[$current] ?? [], true)) {
                throw new \DomainException("Transition paiement interdite : {$current} → {$targetStatus}.");
            }

            if ($targetStatus === 'confirmed' && (int) $locked->amount <= 0) {
                throw new \DomainException('Un paiement sans montant positif ne peut pas être confirmé.');
            }

            $meta = array_merge($locked->meta ?? [], [
                'business_transition' => [
                    'from' => $current,
                    'to' => $targetStatus,
                    'at' => now()->toIso8601String(),
                    'reason' => $context['reason'] ?? null,
                    'actor_id' => $context['actor_id'] ?? null,
                ],
            ]);

            $updates = [
                'business_status' => $targetStatus,
                'status' => $this->legacyStatus($targetStatus),
                'meta' => $meta,
            ];

            $timestampColumn = match ($targetStatus) {
                'confirmed' => 'confirmed_at',
                'failed' => 'failed_at',
                'reversed' => 'reversed_at',
                'refunded' => 'refunded_at',
                default => null,
            };

            if ($timestampColumn) {
                $updates[$timestampColumn] = now();
            }

            $locked->update($updates);

            return $locked->fresh();
        });
    }

    public function canTransition(Payment $payment, string $targetStatus): bool
    {
        $current = $payment->canonicalBusinessStatus();

        return $current === strtolower($targetStatus)
            || in_array(strtolower($targetStatus), self::TRANSITIONS[$current] ?? [], true);
    }

    private function legacyStatus(string $businessStatus): string
    {
        return match ($businessStatus) {
            'authorized' => 'AUTHORIZED',
            'confirmed', 'refunded', 'reversed', 'disputed' => 'PAID',
            'failed', 'expired' => 'FAILED',
            'cancelled' => 'CANCELLED',
            default => 'PENDING',
        };
    }
}
