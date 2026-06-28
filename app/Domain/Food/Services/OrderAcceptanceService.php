<?php

namespace App\Domain\Food\Services;

use App\Delivery;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Order;
use App\Payment;
use App\Services\DeliveryService;
use App\Services\FoodOrderStateMachineService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Point de déclenchement unique du paiement et de la livraison food — appelé uniquement
 * au moment où le restaurant accepte une commande (jamais avant).
 *
 * Cash  : pending_restaurant_acceptance -> confirmed (payment_status=pending) -> in_kitchen,
 *         livraison créée immédiatement. L'encaissement réel reste à collecter par le livreur
 *         et ne doit jamais être marqué paid avant preuve de collecte.
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

    /**
     * Accepte une commande une seule fois.
     *
     * Le verrou pessimiste sur la première ligne du groupe empêche deux clics ou deux
     * requêtes concurrentes de créer plusieurs paiements/livraisons pour le même order_no.
     */
    public function handleAccepted(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->where('order_no', $order->order_no)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                throw new RuntimeException('Commande introuvable.');
            }

            $currentStatus = $this->stateMachine->resolveCurrentBusinessStatus($lockedOrder);

            // Réponse idempotente : la première requête a déjà effectué l'acceptation.
            if (in_array($currentStatus, [
                'accepted_awaiting_payment',
                'confirmed',
                'in_kitchen',
                'ready_for_pickup',
                'dispatching',
                'driver_assigned',
                'driver_arrived_at_restaurant',
                'picked_up',
                'out_for_delivery',
                'delivered',
                'customer_arrived',
                'picked_up_by_customer',
                'closed',
            ], true)) {
                return;
            }

            if ($currentStatus !== 'pending_restaurant_acceptance') {
                throw new RuntimeException(
                    "Cette commande ne peut plus être acceptée depuis l'état {$currentStatus}."
                );
            }

            $snapshot = $lockedOrder->checkout_snapshot ?? [];
            $paymentMethod = $snapshot['payment_method'] ?? $lockedOrder->payment_method ?? 'cash';

            if ($paymentMethod === 'cash') {
                $this->triggerCashFulfillment($lockedOrder, $snapshot);
                return;
            }

            $this->triggerOnlinePayment($lockedOrder, $snapshot, $paymentMethod);
        }, 3);
    }

    protected function triggerCashFulfillment(Order $order, array $snapshot): void
    {
        $orderNo = $order->order_no;

        // Pour le cash, l'acceptation restaurant autorise la cuisine mais ne solde pas le paiement.
        // L'encaissement réel sera validé uniquement à la livraison/retrait.
        Order::where('order_no', $orderNo)->update([
            'payment_status' => OrderPaymentStatus::PENDING->value,
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

        $freshOrder = Order::where('order_no', $orderNo)->firstOrFail();

        $payment = Payment::firstOrCreate(
            [
                'order_id' => $freshOrder->id,
                'provider' => 'cash',
            ],
            [
                'user_id'  => $freshOrder->user_id,
                'amount'   => (int) $freshOrder->total,
                'currency' => 'XAF',
                'status'   => 'PENDING',
                'meta'     => [
                    'cash_on_delivery' => $freshOrder->fulfillment_mode !== 'pickup',
                    'cash_on_pickup' => $freshOrder->fulfillment_mode === 'pickup',
                    'fulfillment_mode' => $freshOrder->fulfillment_mode,
                ],
            ]
        );

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

        $freshOrder = Order::where('order_no', $orderNo)->firstOrFail();
        $amount = (int) ($snapshot['amount'] ?? $freshOrder->total ?? 0);

        $payment = Payment::firstOrCreate(
            [
                'order_id' => $freshOrder->id,
                'provider' => $paymentMethod,
            ],
            [
                'user_id'  => $freshOrder->user_id,
                'amount'   => $amount,
                'currency' => 'XAF',
                'status'   => 'PENDING',
            ]
        );

        // Un paiement déjà confirmé ne doit jamais être remis en attente.
        if (strtoupper((string) $payment->status) !== 'PAID') {
            Order::where('order_no', $orderNo)->update([
                'payment_status' => OrderPaymentStatus::PENDING->value,
            ]);
        }

        $orderLines = Order::where('order_no', $orderNo)->get();

        try {
            // Ne relancer le provider que lors de la création effective du paiement.
            if ($payment->wasRecentlyCreated) {
                $this->paymentService->prepareExternalPayment(
                    $payment,
                    $orderLines,
                    (array) ($snapshot['checkout_data'] ?? []),
                    ['totals' => (array) ($snapshot['totals'] ?? [])]
                );
            }
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
     * Idempotent : ne crée jamais une 2e Delivery pour le même order_id.
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
