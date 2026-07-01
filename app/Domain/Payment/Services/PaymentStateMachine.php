<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Payment;
use App\Services\FinancialEventService;
use Illuminate\Support\Facades\DB;

final class PaymentStateMachine
{
    public function __construct(
        private readonly FinancialEventService $financialEvents,
    ) {
    }

    public function transition(
        Payment $payment,
        PaymentStatus|string $target,
        array $metaPatch = [],
        string $reason = 'system'
    ): Payment {
        $targetStatus = $target instanceof PaymentStatus
            ? $target
            : PaymentStatus::fromRaw($target);

        if (! $payment->exists) {
            return $this->transitionDetachedSnapshot(
                $payment,
                $targetStatus,
                $metaPatch,
                $reason
            );
        }

        return DB::transaction(function () use ($payment, $targetStatus, $metaPatch, $reason) {
            $locked = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            return $this->applyTransition(
                $locked,
                $targetStatus,
                $metaPatch,
                $reason,
                true
            );
        });
    }

    private function transitionDetachedSnapshot(
        Payment $payment,
        PaymentStatus $targetStatus,
        array $metaPatch,
        string $reason
    ): Payment {
        return $this->applyTransition(
            $payment,
            $targetStatus,
            $metaPatch,
            $reason,
            false
        );
    }

    private function applyTransition(
        Payment $payment,
        PaymentStatus $targetStatus,
        array $metaPatch,
        string $reason,
        bool $recordFinancialEvent
    ): Payment {
        $currentStatus = PaymentStatus::fromRaw($payment->status);

        if ($currentStatus === $targetStatus) {
            if ($metaPatch !== []) {
                $payment->update([
                    'meta' => $this->mergeMeta($payment->meta ?? [], $metaPatch),
                ]);
            }

            return $payment->fresh() ?? $payment;
        }

        if (! $currentStatus->canTransitionTo($targetStatus)) {
            throw new \DomainException(
                'Transition de paiement interdite : '
                . $currentStatus->value
                . ' vers '
                . $targetStatus->value
            );
        }

        $transition = [
            'from' => $currentStatus->value,
            'to' => $targetStatus->value,
            'reason' => $reason,
            'at' => now()->toIso8601String(),
        ];

        $payment->update([
            'status' => $targetStatus->value,
            'meta' => $this->mergeMeta(
                $payment->meta ?? [],
                $metaPatch,
                ['last_transition' => $transition]
            ),
        ]);

        $fresh = $payment->fresh() ?? $payment;

        if ($recordFinancialEvent) {
            $this->financialEvents->recordForPayment(
                $fresh,
                'payment_status_changed',
                ['transition' => $transition]
            );
        }

        return $fresh;
    }

    private function mergeMeta(array ...$segments): array
    {
        $merged = [];

        foreach ($segments as $segment) {
            if ($segment !== []) {
                $merged = array_replace_recursive($merged, $segment);
            }
        }

        return $merged;
    }
}
