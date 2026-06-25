<?php

namespace App\Domain\Food\Services;

use App\Delivery;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Order;
use App\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WorkflowOrderAcceptanceService extends OrderAcceptanceService
{
    public function handleAccepted(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = $this->lockOrder($order->order_no);
            $currentStatus = $this->stateMachine->resolveCurrentBusinessStatus($lockedOrder);

            if (in_array($currentStatus, [
                'accepted_scheduled', 'preparation_due', 'accepted_awaiting_payment',
                'confirmed', 'in_kitchen', 'ready_for_pickup', 'dispatching',
                'driver_assigned', 'driver_arrived_at_restaurant', 'picked_up',
                'out_for_delivery', 'delivered', 'customer_arrived',
                'picked_up_by_customer', 'closed',
            ], true)) {
                return;
            }

            if ($currentStatus !== 'pending_restaurant_acceptance') {
                throw new RuntimeException(
                    "Cette commande ne peut plus être acceptée depuis l'état {$currentStatus}."
                );
            }

            $scheduledAt = $this->scheduledAt($lockedOrder);
            if ($scheduledAt && $scheduledAt->isFuture()) {
                $this->stateMachine->transitionOrderGroup($lockedOrder->order_no, 'accepted_scheduled', [
                    'actor_type' => 'restaurant',
                    'actor_id' => $lockedOrder->restaurant?->user_id,
                    'reason_code' => 'scheduled_order_accepted',
                ]);
                return;
            }

            $this->startWorkflow($lockedOrder, 'restaurant', $lockedOrder->restaurant?->user_id);
        }, 3);
    }

    public function releaseScheduled(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = $this->lockOrder($order->order_no);
            $currentStatus = $this->stateMachine->resolveCurrentBusinessStatus($lockedOrder);

            if (in_array($currentStatus, [
                'accepted_awaiting_payment', 'confirmed', 'in_kitchen',
                'ready_for_pickup', 'dispatching', 'driver_assigned',
                'picked_up', 'out_for_delivery', 'delivered',
                'picked_up_by_customer', 'closed',
            ], true)) {
                return;
            }

            if ($currentStatus === 'accepted_scheduled') {
                $this->stateMachine->transitionOrderGroup($lockedOrder->order_no, 'preparation_due', [
                    'actor_type' => 'system',
                    'actor_id' => null,
                    'reason_code' => 'scheduled_preparation_window_opened',
                ]);
            } elseif ($currentStatus !== 'preparation_due') {
                throw new RuntimeException(
                    "La commande programmée ne peut pas être libérée depuis l'état {$currentStatus}."
                );
            }

            $freshOrder = Order::where('order_no', $lockedOrder->order_no)->orderBy('id')->firstOrFail();
            $this->startWorkflow($freshOrder, 'system', null);
        }, 3);
    }

    protected function startWorkflow(Order $order, string $actorType, ?int $actorId): void
    {
        $snapshot = $order->checkout_snapshot ?? [];
        $paymentMethod = strtolower((string) ($snapshot['payment_method'] ?? $order->payment_method ?? 'cash'));

        if ($paymentMethod === 'cash') {
            $this->startCashWorkflow($order, $snapshot, $actorType, $actorId);
            return;
        }

        $this->startOnlineWorkflow($order, $snapshot, $paymentMethod, $actorType, $actorId);
    }

    protected function startCashWorkflow(
        Order $order,
        array $snapshot,
        string $actorType,
        ?int $actorId
    ): void {
        $orderNo = $order->order_no;

        Order::where('order_no', $orderNo)->update([
            'payment_status' => OrderPaymentStatus::CASH_DUE->value,
            'cash_collection_status' => 'pending_collection',
        ]);

        $this->stateMachine->transitionOrderGroup($orderNo, 'confirmed', [
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'reason_code' => 'cash_due_on_fulfillment',
        ]);
        $this->stateMachine->transitionOrderGroup($orderNo, 'in_kitchen', [
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'reason_code' => 'kitchen_started',
        ]);

        $freshOrder = Order::where('order_no', $orderNo)->firstOrFail();
        $payment = Payment::firstOrCreate(
            ['order_id' => $freshOrder->id, 'provider' => 'cash'],
            [
                'user_id' => $freshOrder->user_id,
                'amount' => (int) $freshOrder->total,
                'currency' => 'XAF',
                'status' => 'PENDING',
                'meta' => [
                    'cash_on_delivery' => $freshOrder->fulfillment_mode !== 'pickup',
                    'cash_on_pickup' => $freshOrder->fulfillment_mode === 'pickup',
                    'fulfillment_mode' => $freshOrder->fulfillment_mode,
                    'collection_status' => 'pending_collection',
                ],
            ]
        );

        if ($freshOrder->fulfillment_mode !== 'pickup' && ! Delivery::where('order_id', $freshOrder->id)->exists()) {
            try {
                $this->deliveryService->createForOrder($freshOrder);
            } catch (\Throwable $e) {
                Log::error('Erreur création livraison cash', [
                    'order_no' => $orderNo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->confirmationNotifier->confirmOrder(
            $payment,
            Order::where('order_no', $orderNo)->get(),
            (array) ($snapshot['checkout_data'] ?? []),
            (array) ($snapshot['totals'] ?? []),
            $freshOrder->fulfillment_mode ?? 'delivery',
            $orderNo
        );
    }

    protected function startOnlineWorkflow(
        Order $order,
        array $snapshot,
        string $paymentMethod,
        string $actorType,
        ?int $actorId
    ): void {
        $orderNo = $order->order_no;

        Order::where('order_no', $orderNo)->update([
            'payment_status' => OrderPaymentStatus::NOT_STARTED->value,
            'accepted_at' => now(),
        ]);
        $this->stateMachine->transitionOrderGroup($orderNo, 'accepted_awaiting_payment', [
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'reason_code' => 'accepted_awaiting_payment',
        ]);

        $freshOrder = Order::where('order_no', $orderNo)->firstOrFail();
        $payment = Payment::firstOrCreate(
            ['order_id' => $freshOrder->id, 'provider' => $paymentMethod],
            [
                'user_id' => $freshOrder->user_id,
                'amount' => (int) ($snapshot['amount'] ?? $freshOrder->total ?? 0),
                'currency' => 'XAF',
                'status' => 'PENDING',
            ]
        );

        if (strtoupper((string) $payment->status) !== 'PAID') {
            Order::where('order_no', $orderNo)->update([
                'payment_status' => OrderPaymentStatus::PENDING->value,
            ]);
        }

        try {
            if ($payment->wasRecentlyCreated) {
                $this->paymentService->prepareExternalPayment(
                    $payment,
                    Order::where('order_no', $orderNo)->get(),
                    (array) ($snapshot['checkout_data'] ?? []),
                    ['totals' => (array) ($snapshot['totals'] ?? [])]
                );
            }
        } catch (\Throwable $e) {
            Log::error('Erreur déclenchement paiement commande programmée', [
                'order_no' => $orderNo,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            $payment->update(['status' => 'FAILED']);
            Order::where('order_no', $orderNo)->update([
                'payment_status' => OrderPaymentStatus::FAILED->value,
            ]);
        }
    }

    protected function lockOrder(string $orderNo): Order
    {
        $order = Order::query()
            ->where('order_no', $orderNo)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if (! $order) {
            throw new RuntimeException('Commande introuvable.');
        }

        return $order;
    }

    protected function scheduledAt(Order $order): ?Carbon
    {
        if (empty($order->scheduled_date)) {
            return null;
        }

        try {
            return Carbon::parse($order->scheduled_date);
        } catch (\Throwable) {
            return null;
        }
    }
}
