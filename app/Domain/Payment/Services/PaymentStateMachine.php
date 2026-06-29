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

        return DB::transaction(function () use ($payment, $targetStatus, $metaPatch, $reason) {
            $locked = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            $currentStatus = PaymentStatus::fromRaw($locked->status);

            if ($currentStatus === $targetStatus) {
                if ($metaPatch !== []) {
                    $locked->update([
                        'meta' => $this->mergeMeta($locked->meta ?? [], $metaPatch),
                    ]);
                }

                return $locked->fresh();
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

            $locked->update([
                'status' => $targetStatus->value,
                'meta' => $this->mergeMeta(
                    $locked->meta ?? [],
                    $metaPatch,
                    ['last_transition' => $transition]
                ),
            ]);

            $fresh = $locked->fresh();
            $this->financialEvents->recordForPayment(
                $fresh,
                'payment_status_changed',
                ['transition' => $transition]
            );

            return $fresh;
        });
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
