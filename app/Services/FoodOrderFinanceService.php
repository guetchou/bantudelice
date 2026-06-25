<?php

namespace App\Services;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Order;
use App\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FoodOrderFinanceService
{
    public function __construct(
        protected FoodOrderStateMachineService $foodOrderStateMachine,
        protected ?FinancialEventService $financialEventService = null
    ) {}

    public function cancelOrderGroup(string $orderNo, array $context = []): array
    {
        $orders = Order::with(['delivery.driver'])->where('order_no', $orderNo)->orderBy('id')->get();
        if ($orders->isEmpty()) {
            throw new \RuntimeException('Commande introuvable.');
        }

        $firstOrder = $orders->first();
        $payment = Payment::where('order_id', $firstOrder->id)->latest('id')->first();
        $paymentStatus = $this->resolveCancelledPaymentStatus($payment, $firstOrder);
        $loyaltyRefunded = 0;

        DB::transaction(function () use ($orders, $firstOrder, $payment, $paymentStatus, $context, &$loyaltyRefunded) {
            $loyaltyRefunded = $this->refundSpentLoyaltyPoints($firstOrder->user_id, $firstOrder->id, $context);

            if ($payment) {
                $meta = array_merge($payment->meta ?? [], [
                    'cancelled_at' => now()->toIso8601String(),
                    'cancelled_reason' => $context['reason_code'] ?? 'order_cancelled',
                    'cancelled_by' => $context['actor_type'] ?? 'system',
                    'cancelled_by_id' => $context['actor_id'] ?? null,
                    'refund_mode' => $paymentStatus === OrderPaymentStatus::REFUNDED->value ? 'manual_refund_required' : 'not_applicable',
                ]);

                $payment->update([
                    'status' => strtoupper($paymentStatus === OrderPaymentStatus::REFUNDED->value ? 'REFUNDED' : 'FAILED'),
                    'meta' => $meta,
                ]);
                $this->financialEvents()->recordForPayment($payment->fresh(), $paymentStatus === OrderPaymentStatus::REFUNDED->value ? 'payment_refund_marked' : 'payment_failed_on_cancel', [
                    'order_no' => $firstOrder->order_no,
                    'cancelled_reason' => $context['reason_code'] ?? 'order_cancelled',
                ]);
            }

            foreach ($orders as $order) {
                if ($order->delivery && ! in_array($order->delivery->status, ['DELIVERED', 'CANCELLED'], true)) {
                    $order->delivery->update([
                        'status' => 'CANCELLED',
                        'support_status' => $order->delivery->support_status ?: 'resolved',
                        'support_notes' => $context['notes'] ?? $order->delivery->support_notes,
                        'support_resolved_at' => now(),
                        'support_resolved_by' => $context['actor_id'] ?? null,
                    ]);

                    if ($order->delivery->driver) {
                        if (Schema::hasColumn('drivers', 'is_available')) {
                            $order->delivery->driver->update(['is_available' => true]);
                        } elseif (Schema::hasColumn('drivers', 'status')) {
                            $order->delivery->driver->update(['status' => 'online']);
                        }
                    }
                }
            }

            $this->foodOrderStateMachine->transitionOrderGroup($firstOrder->order_no, 'cancelled', [
                'actor_type' => $context['actor_type'] ?? 'system',
                'actor_id' => $context['actor_id'] ?? null,
                'reason_code' => $context['reason_code'] ?? 'order_cancelled',
                'notes' => $context['notes'] ?? null,
                'payment_status' => $paymentStatus,
                'force' => true,
            ]);

            $this->financialEvents()->recordForOrder($firstOrder->fresh(), 'order_cancelled', [
                'payment_status' => $paymentStatus,
                'reason_code' => $context['reason_code'] ?? 'order_cancelled',
                'loyalty_refunded_points' => $loyaltyRefunded,
                'cancelled_by' => $context['actor_type'] ?? 'system',
                'cancelled_by_id' => $context['actor_id'] ?? null,
            ]);
        });

        return [
            'payment_status' => $paymentStatus,
            'loyalty_refunded_points' => $loyaltyRefunded,
        ];
    }

    protected function financialEvents(): FinancialEventService
    {
        return $this->financialEventService ?? app(FinancialEventService::class);
    }

    protected function resolveCancelledPaymentStatus(?Payment $payment, Order $order): string
    {
        if ($payment) {
            return strtoupper((string) $payment->status) === 'PAID'
                ? OrderPaymentStatus::REFUNDED->value
                : OrderPaymentStatus::FAILED->value;
        }

        return strtolower((string) $order->payment_status) === OrderPaymentStatus::PAID->value
            ? OrderPaymentStatus::REFUNDED->value
            : OrderPaymentStatus::FAILED->value;
    }

    protected function refundSpentLoyaltyPoints(int $userId, int $orderId, array $context = []): int
    {
        if (! Schema::hasTable('loyalty_transactions') || ! Schema::hasTable('loyalty_points')) {
            return 0;
        }

        $alreadyRefunded = DB::table('loyalty_transactions')
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->where('type', 'refund')
            ->sum('points');

        $spent = abs((int) DB::table('loyalty_transactions')
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->where('type', 'spent')
            ->sum('points'));

        // Ancien checkout : la transaction de dépense pouvait être créée avec order_id=null.
        // On la rattache de façon déterministe grâce au snapshot et à la fenêtre temporelle.
        if ($spent === 0) {
            $order = Order::find($orderId);
            $expectedPoints = (int) data_get($order?->checkout_snapshot, 'loyalty_points_used', 0);

            if ($order && $expectedPoints > 0) {
                $createdAt = $order->created_at ?? now();
                $unlinkedTransaction = DB::table('loyalty_transactions')
                    ->where('user_id', $userId)
                    ->whereNull('order_id')
                    ->where('type', 'spent')
                    ->where('points', -$expectedPoints)
                    ->whereBetween('created_at', [
                        $createdAt->copy()->subMinutes(5),
                        $createdAt->copy()->addMinutes(5),
                    ])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($unlinkedTransaction) {
                    DB::table('loyalty_transactions')
                        ->where('id', $unlinkedTransaction->id)
                        ->update([
                            'order_id' => $orderId,
                            'updated_at' => now(),
                        ]);
                    $spent = $expectedPoints;
                }
            }
        }

        $pointsToRefund = max(0, $spent - (int) $alreadyRefunded);
        if ($pointsToRefund <= 0) {
            return 0;
        }

        $pointRow = DB::table('loyalty_points')->where('user_id', $userId)->lockForUpdate()->first();
        if ($pointRow) {
            DB::table('loyalty_points')
                ->where('user_id', $userId)
                ->update([
                    'points' => (int) $pointRow->points + $pointsToRefund,
                    'total_spent' => max(0, (int) $pointRow->total_spent - $pointsToRefund),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('loyalty_points')->insert([
                'user_id' => $userId,
                'points' => $pointsToRefund,
                'total_earned' => 0,
                'total_spent' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('loyalty_transactions')->insert([
            'user_id' => $userId,
            'order_id' => $orderId,
            'points' => $pointsToRefund,
            'type' => 'refund',
            'description' => 'Points recrédités après annulation de commande',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $pointsToRefund;
    }
}
