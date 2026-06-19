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
            // Chemin legacy — dans le nouveau flux, le Payment est créé avec order_id renseigné
            // dès l'acceptation restaurant (OrderAcceptanceService). Si on arrive ici sans order_id,
            // c'est un paiement orphelin ou un bug d'un autre flux.
            Log::warning('FoodOrderPaymentConfirmed: paiement sans order_id reçu — chemin legacy ignoré', [
                'payment_id' => $payment->id,
                'provider'   => $payment->provider,
            ]);
            return;
        }

        $this->handleOrderFinalization($payment);
    }

    private function handleOrderFinalization($payment): void
    {
        $order = $payment->order;
        if (! $order) {
            Log::warning('FoodOrderPaymentConfirmed: order introuvable pour payment', ['payment_id' => $payment->id]);
            return;
        }

        // 1. Marquer le paiement comme réglé sur la commande
        \App\Order::where('order_no', $order->order_no)->update([
            'payment_status' => OrderPaymentStatus::PAID->value,
        ]);
        $freshOrder = $order->fresh();

        $this->financialEvents->recordForOrder($freshOrder, 'order_payment_marked_paid', [
            'payment_id' => $payment->id,
        ]);

        // 2. Transition accepted_awaiting_payment → confirmed (ou pending_restaurant_acceptance → confirmed
        //    si jamais le business_status n'a pas encore bougé, tolérance).
        $currentStatus = $freshOrder->business_status ?? 'pending_restaurant_acceptance';
        $transitionContext = [
            'actor_type'  => 'system',
            'actor_id'    => null,
            'reason_code' => 'payment_confirmed',
        ];

        if (in_array($currentStatus, ['accepted_awaiting_payment', 'pending_restaurant_acceptance'], true)) {
            $this->stateMachine->transitionOrderGroup($freshOrder->order_no, 'confirmed', $transitionContext);
        }

        // 3. Auto-avance confirmed → in_kitchen (dans le même appel — confirmed est transitoire)
        $this->stateMachine->transitionOrderGroup($freshOrder->order_no, 'in_kitchen', $transitionContext);

        // 4. Créer la livraison si nécessaire (idempotent — ne crée jamais une 2e Delivery)
        $fulfillmentMode = $this->resolveFulfillmentMode(
            (array) ($freshOrder->checkout_snapshot['checkout_data'] ?? []),
            $freshOrder->fulfillment_mode ?? 'delivery'
        );
        if ($fulfillmentMode !== 'pickup') {
            $this->createDeliveryAndDispatch($freshOrder);
        }

        // 5. Notifications + ledger + signaux métier
        $checkoutSnapshot = (array) ($freshOrder->checkout_snapshot ?? []);
        $checkoutData = (array) ($checkoutSnapshot['checkout_data'] ?? $payment->meta['checkout_data'] ?? []);
        $totals = (array) ($checkoutSnapshot['totals'] ?? $payment->meta['totals'] ?? []);
        if (empty($totals)) {
            $totals = [
                'sub_total'    => (float) ($freshOrder->sub_total ?? 0),
                'tax'          => (float) ($freshOrder->tax ?? 0),
                'delivery_fee' => (float) ($freshOrder->delivery_charges ?? 0),
                'service_fee'  => 0,
                'discount'     => (float) ($freshOrder->offer_discount ?? 0),
                'total'        => (float) ($freshOrder->total ?? $payment->amount ?? 0),
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
     * Idempotent : ne crée jamais une 2e Delivery pour le même order_id.
     */
    private function createDeliveryAndDispatch(\App\Order $order): void
    {
        if (Delivery::where('order_id', $order->id)->exists()) {
            return;
        }

        try {
            $delivery = $this->deliveryService->createForOrder($order);
            enqueue_job('food', 'auto_assign_delivery', ['delivery' => $delivery]);
        } catch (\Exception $e) {
            Log::error('FoodOrderPaymentConfirmed: erreur création livraison', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
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
