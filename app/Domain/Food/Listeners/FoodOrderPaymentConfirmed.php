<?php

namespace App\Domain\Food\Listeners;

use App\Delivery;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Domain\Food\Services\FoodOrderConfirmationNotifier;
use App\Domain\Payment\Events\PaymentConfirmed;
use App\Services\DeliveryService;
use App\Services\FinancialEventService;
use App\Services\FoodOrderStateMachineService;
use Illuminate\Support\Facades\Log;

class FoodOrderPaymentConfirmed
{
    public function __construct(
        protected FoodOrderStateMachineService $stateMachine,
        protected DeliveryService $deliveryService,
        protected FoodOrderConfirmationNotifier $confirmationNotifier,
        protected FinancialEventService $financialEvents
    ) {}

    public function handle(PaymentConfirmed $event): void
    {
        $payment = $event->payment;

        if ($payment->transport_booking_id || $payment->shipment_id) {
            return;
        }

        if (! $payment->order_id) {
            Log::warning('FoodOrderPaymentConfirmed: paiement sans order_id reçu — chemin legacy ignoré', [
                'payment_id' => $payment->id,
                'provider' => $payment->provider,
            ]);
            return;
        }

        $this->handleOrderFinalization($payment);
    }

    private function handleOrderFinalization($payment): void
    {
        $order = $payment->order;
        if (! $order) {
            Log::warning('FoodOrderPaymentConfirmed: order introuvable pour payment', [
                'payment_id' => $payment->id,
            ]);
            return;
        }

        \App\Order::where('order_no', $order->order_no)->update([
            'payment_status' => OrderPaymentStatus::PAID->value,
        ]);
        $freshOrder = $order->fresh();

        $this->financialEvents->recordForOrder($freshOrder, 'order_payment_marked_paid', [
            'payment_id' => $payment->id,
        ]);

        $currentStatus = $freshOrder->business_status ?? 'pending_restaurant_acceptance';
        $transitionContext = [
            'actor_type' => 'system',
            'actor_id' => null,
            'reason_code' => 'payment_confirmed',
        ];

        if (in_array($currentStatus, ['accepted_awaiting_payment', 'pending_restaurant_acceptance'], true)) {
            $this->stateMachine->transitionOrderGroup($freshOrder->order_no, 'confirmed', $transitionContext);
        }

        $this->stateMachine->transitionOrderGroup($freshOrder->order_no, 'in_kitchen', $transitionContext);

        $fulfillmentMode = $this->resolveFulfillmentMode(
            (array) ($freshOrder->checkout_snapshot['checkout_data'] ?? []),
            $freshOrder->fulfillment_mode ?? 'delivery'
        );

        if ($fulfillmentMode !== 'pickup') {
            $this->createDelivery($freshOrder);
        }

        $checkoutSnapshot = (array) ($freshOrder->checkout_snapshot ?? []);
        $checkoutData = (array) ($checkoutSnapshot['checkout_data'] ?? $payment->meta['checkout_data'] ?? []);
        $totals = (array) ($checkoutSnapshot['totals'] ?? $payment->meta['totals'] ?? []);
        if (empty($totals)) {
            $totals = [
                'sub_total' => (float) ($freshOrder->sub_total ?? 0),
                'tax' => (float) ($freshOrder->tax ?? 0),
                'delivery_fee' => (float) ($freshOrder->delivery_charges ?? 0),
                'service_fee' => 0,
                'discount' => (float) ($freshOrder->offer_discount ?? 0),
                'total' => (float) ($freshOrder->total ?? $payment->amount ?? 0),
            ];
        }

        $orders = \App\Order::where('order_no', $freshOrder->order_no)->get();
        $this->confirmationNotifier->confirmOrder(
            $payment->fresh(),
            $orders,
            $checkoutData,
            $totals,
            $fulfillmentMode,
            $freshOrder->order_no
        );
    }

    /**
     * La livraison est préparée ici, mais les offres ne démarrent qu'au passage ready_for_pickup.
     */
    private function createDelivery(\App\Order $order): void
    {
        if (Delivery::where('order_id', $order->id)->exists()) {
            return;
        }

        try {
            $this->deliveryService->createForOrder($order);
        } catch (\Throwable $e) {
            Log::error('FoodOrderPaymentConfirmed: erreur création livraison', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveFulfillmentMode(array $checkoutData, string $fallback = 'delivery'): string
    {
        return strtolower((string) ($checkoutData['fulfillment_mode'] ?? $fallback)) === 'pickup'
            ? 'pickup'
            : 'delivery';
    }
}
