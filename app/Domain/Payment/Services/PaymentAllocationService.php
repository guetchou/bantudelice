<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Order;
use App\Payment;
use App\PaymentAllocation;
use App\Services\FinancialEventService;
use Illuminate\Support\Facades\DB;

final class PaymentAllocationService
{
    public const TARGET_FOOD_ORDER_GROUP = 'food_order_group';

    public function __construct(
        private readonly FinancialEventService $financialEvents,
    ) {
    }

    public function allocateConfirmedPayment(Payment $payment): array
    {
        if (PaymentStatus::fromRaw($payment->status) !== PaymentStatus::PAID) {
            throw new \DomainException('Seul un paiement confirmé peut être affecté.');
        }

        if (! $payment->order_id) {
            return $this->unhandledResult($payment, 'unsupported_target');
        }

        return DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);
            $order = Order::query()->find($lockedPayment->order_id);

            if (! $order) {
                return $this->unhandledResult($lockedPayment, 'order_not_found');
            }

            $orderNo = (string) $order->order_no;
            Order::query()
                ->where('order_no', $orderNo)
                ->lockForUpdate()
                ->get(['id']);

            $dueAmount = $this->foodOrderGroupDueAmount($order);
            $allocatedBefore = (int) PaymentAllocation::query()
                ->where('target_type', self::TARGET_FOOD_ORDER_GROUP)
                ->where('target_reference', $orderNo)
                ->where('status', 'allocated')
                ->lockForUpdate()
                ->sum('amount');

            $idempotencyKey = 'payment:'
                . $lockedPayment->id
                . ':food-order-group:'
                . $orderNo;
            $existing = PaymentAllocation::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $this->foodResult(
                    $lockedPayment,
                    $order,
                    $dueAmount,
                    $allocatedBefore,
                    (int) $existing->amount,
                    max(0, (int) round($lockedPayment->amount) - (int) $existing->amount),
                    true
                );
            }

            $paymentAmount = max(0, (int) round((float) $lockedPayment->amount));
            $remainingBefore = max(0, $dueAmount - $allocatedBefore);
            $allocatedNow = min($paymentAmount, $remainingBefore);
            $unallocatedAmount = max(0, $paymentAmount - $allocatedNow);

            if ($allocatedNow > 0) {
                PaymentAllocation::create([
                    'payment_id' => $lockedPayment->id,
                    'target_type' => self::TARGET_FOOD_ORDER_GROUP,
                    'target_id' => $order->id,
                    'target_reference' => $orderNo,
                    'amount' => $allocatedNow,
                    'currency' => $lockedPayment->currency ?: 'XAF',
                    'status' => 'allocated',
                    'idempotency_key' => $idempotencyKey,
                    'metadata' => [
                        'order_no' => $orderNo,
                        'due_amount' => $dueAmount,
                        'payment_amount' => $paymentAmount,
                    ],
                    'allocated_at' => now(),
                ]);
            }

            $allocatedTotal = $allocatedBefore + $allocatedNow;
            $result = $this->foodResult(
                $lockedPayment,
                $order,
                $dueAmount,
                $allocatedTotal,
                $allocatedNow,
                $unallocatedAmount,
                false
            );

            $lockedPayment->update([
                'meta' => array_replace_recursive($lockedPayment->meta ?? [], [
                    'allocation' => [
                        'target_type' => self::TARGET_FOOD_ORDER_GROUP,
                        'target_reference' => $orderNo,
                        'allocated_amount' => $allocatedNow,
                        'unallocated_amount' => $unallocatedAmount,
                        'fully_funded' => $result['fully_funded'],
                        'recorded_at' => now()->toIso8601String(),
                    ],
                ]),
            ]);

            $this->financialEvents->recordForPayment(
                $lockedPayment->fresh(),
                'payment_allocated',
                $result
            );

            return $result;
        });
    }

    public function fundingStatusForFoodOrderGroup(string $orderNo): array
    {
        $anchor = Order::query()->where('order_no', $orderNo)->first();

        if (! $anchor) {
            return [
                'target_type' => self::TARGET_FOOD_ORDER_GROUP,
                'target_reference' => $orderNo,
                'due_amount' => 0,
                'allocated_amount' => 0,
                'remaining_amount' => 0,
                'fully_funded' => false,
            ];
        }

        $dueAmount = $this->foodOrderGroupDueAmount($anchor);
        $allocatedAmount = (int) PaymentAllocation::query()
            ->where('target_type', self::TARGET_FOOD_ORDER_GROUP)
            ->where('target_reference', $orderNo)
            ->where('status', 'allocated')
            ->sum('amount');

        return [
            'target_type' => self::TARGET_FOOD_ORDER_GROUP,
            'target_reference' => $orderNo,
            'due_amount' => $dueAmount,
            'allocated_amount' => $allocatedAmount,
            'remaining_amount' => max(0, $dueAmount - $allocatedAmount),
            'fully_funded' => $dueAmount > 0 && $allocatedAmount >= $dueAmount,
        ];
    }

    public function reverseForPayment(Payment $payment, string $reason): int
    {
        return DB::transaction(function () use ($payment, $reason) {
            $allocations = PaymentAllocation::query()
                ->where('payment_id', $payment->id)
                ->where('status', 'allocated')
                ->lockForUpdate()
                ->get();

            foreach ($allocations as $allocation) {
                $allocation->update([
                    'status' => 'reversed',
                    'reversed_at' => now(),
                    'metadata' => array_replace_recursive($allocation->metadata ?? [], [
                        'reversal' => [
                            'reason' => $reason,
                            'at' => now()->toIso8601String(),
                        ],
                    ]),
                ]);
            }

            if ($allocations->isNotEmpty()) {
                $this->financialEvents->recordForPayment(
                    $payment->fresh(),
                    'payment_allocation_reversed',
                    [
                        'reason' => $reason,
                        'allocation_ids' => $allocations->pluck('id')->all(),
                        'amount' => (int) $allocations->sum('amount'),
                    ]
                );
            }

            return $allocations->count();
        });
    }

    private function foodOrderGroupDueAmount(Order $order): int
    {
        $snapshotTotal = (float) data_get($order->checkout_snapshot, 'totals.total', 0);

        if ($snapshotTotal > 0) {
            return (int) round($snapshotTotal);
        }

        return max(0, (int) round((float) Order::query()
            ->where('order_no', $order->order_no)
            ->max('total')));
    }

    private function foodResult(
        Payment $payment,
        Order $order,
        int $dueAmount,
        int $allocatedTotal,
        int $allocatedNow,
        int $unallocatedAmount,
        bool $reused
    ): array {
        return [
            'handled' => true,
            'reused' => $reused,
            'payment_id' => $payment->id,
            'target_type' => self::TARGET_FOOD_ORDER_GROUP,
            'target_id' => $order->id,
            'target_reference' => (string) $order->order_no,
            'due_amount' => $dueAmount,
            'allocated_amount' => $allocatedNow,
            'allocated_total' => $allocatedTotal,
            'unallocated_amount' => $unallocatedAmount,
            'remaining_amount' => max(0, $dueAmount - $allocatedTotal),
            'fully_funded' => $dueAmount > 0 && $allocatedTotal >= $dueAmount,
        ];
    }

    private function unhandledResult(Payment $payment, string $reason): array
    {
        return [
            'handled' => false,
            'reason' => $reason,
            'payment_id' => $payment->id,
            'target_type' => null,
            'target_reference' => null,
            'due_amount' => 0,
            'allocated_amount' => 0,
            'allocated_total' => 0,
            'unallocated_amount' => max(0, (int) round((float) $payment->amount)),
            'remaining_amount' => 0,
            'fully_funded' => false,
        ];
    }
}
