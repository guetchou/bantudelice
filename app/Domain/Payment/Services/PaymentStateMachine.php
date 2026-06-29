<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\PaymentStatusTransition;
use App\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class PaymentStateMachine
{
    private const ALLOWED_SOURCES = [
        'provider_callback',
        'provider_poll',
        'reconciliation',
        'admin',
        'system',
    ];

    public function transition(
        Payment $payment,
        PaymentStatus $target,
        string $source,
        string $idempotencyKey,
        ?string $reason = null,
        array $evidence = [],
        ?Model $actor = null
    ): PaymentStatusTransition {
        $source = strtolower(trim($source));
        if (!in_array($source, self::ALLOWED_SOURCES, true)) {
            throw new InvalidArgumentException('Source de transition non autorisée.');
        }

        return DB::transaction(function () use (
            $payment,
            $target,
            $source,
            $idempotencyKey,
            $reason,
            $evidence,
            $actor
        ) {
            $existing = PaymentStatusTransition::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if (
                    (int) $existing->payment_id !== (int) $payment->id
                    || $existing->to_status !== $target
                ) {
                    throw new LogicException('La clé d’idempotence correspond à une autre transition.');
                }

                return $existing;
            }

            $locked = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());
            $current = PaymentStatus::fromCanonicalOrStorage(
                $locked->canonical_status,
                $locked->status
            );

            if (!$current->canTransitionTo($target)) {
                throw new LogicException(
                    "Transition de paiement interdite : {$current->value} vers {$target->value}."
                );
            }

            $reason = $reason !== null ? trim($reason) : null;
            if ($this->requiresReason($source, $target) && $reason === '') {
                throw new InvalidArgumentException('Un motif est obligatoire pour cette transition.');
            }

            $occurredAt = now();
            $transition = PaymentStatusTransition::query()->create([
                'payment_id' => $locked->id,
                'from_status' => $current->value,
                'to_status' => $target->value,
                'source' => $source,
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->getKey(),
                'reason' => $reason,
                'evidence' => $evidence,
                'occurred_at' => $occurredAt,
                'idempotency_key' => $idempotencyKey,
            ]);

            $locked->forceFill([
                'status' => $target->legacyStorageValue(),
                'canonical_status' => $target->value,
                'status_version' => ((int) $locked->status_version) + 1,
                'status_updated_at' => $occurredAt,
                'meta' => array_merge($locked->meta ?? [], [
                    'last_status_transition' => [
                        'uuid' => $transition->uuid,
                        'from' => $current->value,
                        'to' => $target->value,
                        'source' => $source,
                        'occurred_at' => $occurredAt->toIso8601String(),
                    ],
                ]),
            ])->save();

            return $transition->fresh();
        });
    }

    private function requiresReason(string $source, PaymentStatus $target): bool
    {
        return $source === 'admin'
            || in_array($target, [
                PaymentStatus::REVERSED,
                PaymentStatus::REFUNDED,
                PaymentStatus::DISPUTED,
            ], true);
    }
}
