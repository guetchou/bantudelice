<?php

namespace App\Domain\Food\Services;

use App\Delivery;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Order;
use App\Payment;
use App\Services\DeliveryService;
use App\Services\FoodOrderStateMachineService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

/**
 * Point de déclenchement unique du paiement et de la livraison food — appelé uniquement
 * au moment où le restaurant accepte une commande (jamais avant).
 *
 * Cash  : pending_restaurant_acceptance -> confirmed (payment_status=paid) -> in_kitchen,
 *         livraison créée immédiatement (l'acceptation suffit, rien à attendre côté paiement).
 * Online: pending_restaurant_acceptance -> accepted_awaiting_payment (payment_status=not_started),
 *         Payment créé + prepareExternalPayment() déclenché ici. La livraison n'est créée
 *         qu'au paiement confirmé, voir FoodOrderPaymentConfirmed::handleOrderFinalization().
 */
class OrderAcceptanceService
{
    public function __construct(
        protected FoodOrderStateMachineService $stateMachine,
        protected DeliveryService $deliveryService,
        protected PaymentService $paymentService,
        protected FoodOrderConfirmationNotifier $confirmationNotifier
    ) {}

    public function handleAccepted(Order $order): void
    {
        $snapshot = $order->checkout_snapshot ?? [];
        $paymentMethod = $snapshot['payment_method'] ?? $order->payment_method ?? 'cash';

        if ($paymentMethod === 'cash') {
            $this->triggerCashFulfillment($order, $snapshot);
            return;
        }

        $this->triggerOnlinePayment($order, $snapshot, $paymentMethod);
    }

    protected function triggerCashFulfillment(Order $order, array $snapshot): void
    {
        $orderNo = $order->order_no;

        // 1. Marquer le paiement cash comme "payé" au sens workflow (promesse de paiement à la
        // livraison) — aucune exception dans la garde in_kitchen, voir FoodOrderStateMachineService.
        // L'encaissement réel est suivi séparément via cash_collection_status.
        Order::where('order_no', $orderNo)->update([
            'payment_status' => OrderPaymentStatus::PAID->value,
            'cash_collection_status' => 'pending_collection',
        ]);

        $this->stateMachine->transitionOrderGroup($orderNo, 'confirmed', [
            'actor_type' => 'restaurant',
            'actor_id' => $order->restaurant?->user_id,
            'reason_code' => 'cash_accepted',
        ]);

        $this->stateMachine->transitionOrderGroup($orderNo, 'in_kitchen', [
            'actor_type' => 'restaurant',
            'actor_id' => $order->restaurant?->user_id,
            'reason_code' => 'kitchen_started',
        ]);

        $freshOrder = Order::where('order_no', $orderNo)->first();

        $payment = Payment::create([
            'user_id'  => $freshOrder->user_id,
            'order_id' => $freshOrder->id,
            'amount'   => (int) $freshOrder->total,
            'currency' => 'XAF',
            'status'   => 'PENDING', // encaissement réel restant à faire — trace comptable uniquement
            'provider' => 'cash',
            'meta'     => [
                'cash_on_delivery' => $freshOrder->fulfillment_mode !== 'pickup',
                'cash_on_pickup' => $freshOrder->fulfillment_mode === 'pickup',
                'fulfillment_mode' => $freshOrder->fulfillment_mode,
            ],
        ]);

        if ($freshOrder->fulfillment_mode !== 'pickup') {
            $this->createDeliveryAndDispatch($freshOrder);
        }

        $orders = Order::where('order_no', $orderNo)->get();
        $this->confirmationNotifier->confirmOrder(
            $payment,
            $orders,
            (array) ($snapshot['checkout_data'] ?? []),
            (array) ($snapshot['totals'] ?? []),
            $freshOrder->fulfillment_mode ?? 'delivery',
            $orderNo
        );
    }

    protected function triggerOnlinePayment(Order $order, array $snapshot, string $paymentMethod): void
    {
        $orderNo = $order->order_no;

        Order::where('order_no', $orderNo)->update([
            'payment_status' => OrderPaymentStatus::NOT_STARTED->value,
        ]);

        $this->stateMachine->transitionOrderGroup($orderNo, 'accepted_awaiting_payment', [
            'actor_type' => 'restaurant',
            'actor_id' => $order->restaurant?->user_id,
            'reason_code' => 'accepted_awaiting_payment',
        ]);

        $freshOrder = Order::where('order_no', $orderNo)->first();
        $amount = (int) ($snapshot['amount'] ?? $freshOrder->total ?? 0);

        $payment = Payment::create([
            'user_id'  => $freshOrder->user_id,
            'order_id' => $freshOrder->id, // renseigné dès la création, contrairement à l'ancien flux
            'amount'   => $amount,
            'currency' => 'XAF',
            'status'   => 'PENDING',
            'provider' => $paymentMethod,
        ]);

        Order::where('order_no', $orderNo)->update([
            'payment_status' => OrderPaymentStatus::PENDING->value,
        ]);

        $orderLines = Order::where('order_no', $orderNo)->get();

        try {
            $this->paymentService->prepareExternalPayment(
                $payment,
                $orderLines,
                (array) ($snapshot['checkout_data'] ?? []),
                ['totals' => (array) ($snapshot['totals'] ?? [])]
            );
        } catch (\Throwable $e) {
            Log::error('OrderAcceptanceService: erreur déclenchement paiement en ligne après acceptation', [
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

    /**
     * Idempotent : ne crée jamais une 2e Delivery pour le même order_id
     * (contrainte unique en DB de toute façon, ce check évite l'exception en pratique).
     */
    protected function createDeliveryAndDispatch(Order $order): void
    {
        if (Delivery::where('order_id', $order->id)->exists()) {
            return;
        }

        try {
            $delivery = $this->deliveryService->createForOrder($order);
            enqueue_job('food', 'auto_assign_delivery', ['delivery' => $delivery]);
        } catch (\Exception $e) {
            Log::error('OrderAcceptanceService: erreur création livraison', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
