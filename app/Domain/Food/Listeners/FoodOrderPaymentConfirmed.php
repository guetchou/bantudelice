<?php

namespace App\Domain\Food\Listeners;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Domain\Payment\Events\PaymentConfirmed;
use App\Domain\Checkout\Contracts\CheckoutOrchestratorInterface;
use App\Services\FinancialEventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FoodOrderPaymentConfirmed
{
    public function handle(PaymentConfirmed $event): void
    {
        $payment = $event->payment;

        // Cet événement ne concerne que les paiements food.
        // Transport et Colis ont leurs propres listeners.
        if ($payment->transport_booking_id || $payment->shipment_id) {
            return;
        }

        if (! $payment->order_id) {
            $this->handleOrderCreation($payment);
        } else {
            $this->handleOrderFinalization($payment);
        }
    }

    private function handleOrderCreation($payment): void
    {
        $user = $payment->user;

        if (! $user) {
            Log::warning('FoodOrderPaymentConfirmed : utilisateur introuvable', ['payment_id' => $payment->id]);
            return;
        }

        $cartItems = \App\Cart::where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return;
        }

        $checkout        = app(CheckoutOrchestratorInterface::class);
        $checkoutData    = $payment->meta['checkout_data'] ?? [];
        $totals          = $checkout->calculateTotals($cartItems, $checkoutData);
        $fulfillmentMode = $this->resolveFulfillmentMode($checkoutData);

        $orderNo = $checkout->createOrderFromCart($user, $cartItems, $checkoutData, $totals, $payment);
        $orders  = \App\Order::where('order_no', $orderNo)->get();

        if ($fulfillmentMode !== 'pickup') {
            $deliveryService = app(\App\Services\DeliveryService::class);
            foreach ($orders as $order) {
                try {
                    $delivery = $deliveryService->createForOrder($order);
                    enqueue_job('food', 'auto_assign_delivery', ['delivery' => $delivery]);
                } catch (\Exception $e) {
                    Log::error('FoodOrderPaymentConfirmed : erreur création livraison', [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }
        }

        $payment->update(['order_id' => $orders->first()->id]);
        $this->financialEvents()->recordForPayment($payment->fresh(), 'payment_linked_to_order', [
            'order_no' => $orderNo,
        ]);

        $this->finalizeFoodOrder(
            $payment->fresh(),
            $orders,
            (array) ($checkoutData ?? []),
            (array) ($payment->meta['totals'] ?? []),
            $fulfillmentMode,
            $orderNo
        );

        \App\Cart::where('user_id', $user->id)->delete();
    }

    private function handleOrderFinalization($payment): void
    {
        $payment->order->update(['payment_status' => OrderPaymentStatus::PAID->value]);
        $freshOrder = $payment->order->fresh();

        $this->financialEvents()->recordForOrder($freshOrder, 'order_payment_marked_paid', [
            'payment_id' => $payment->id,
        ]);

        if (! $freshOrder || ! $this->needsFinalization($freshOrder, $payment)) {
            return;
        }

        $checkoutData = (array) (
            $payment->meta['checkout_data']
            ?? data_get($payment->meta ?? [], 'momo.checkout_data', [])
        );

        $totals = (array) ($payment->meta['totals'] ?? []);
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

        $this->finalizeFoodOrder(
            $payment->fresh(),
            \App\Order::where('order_no', $freshOrder->order_no)->get(),
            $checkoutData,
            $totals,
            $this->resolveFulfillmentMode($checkoutData, $freshOrder->fulfillment_mode ?? 'delivery'),
            $freshOrder->order_no
        );
    }

    private function finalizeFoodOrder($payment, $orders, array $checkoutData, array $totals, string $fulfillmentMode, string $orderNo): void
    {
        $order = $orders->first();
        if (! $order) {
            return;
        }

        $shouldRecordLedger     = $this->shouldRecordLedger($orderNo);
        $shouldEmitOrderCreated = $this->shouldEmitSignal($orderNo);
        $user                   = $payment->user ?? $order->user ?? null;
        $restaurant             = $order->restaurant ?? \App\Restaurant::find($order->restaurant_id);
        $isPickup               = $fulfillmentMode === 'pickup';

        if ($shouldRecordLedger) {
            try {
                app(\App\Services\FinancialLedgerService::class)->record([
                    'module'      => 'food',
                    'entry_type'  => 'order_created',
                    'direction'   => 'credit',
                    'status'      => 'posted',
                    'order_id'    => $order->id,
                    'order_no'    => $orderNo,
                    'payment_id'  => $payment->id,
                    'amount'      => (float) ($totals['total'] ?? $payment->amount ?? 0),
                    'reference'   => $orderNo,
                    'actor_type'  => 'user',
                    'actor_id'    => $user?->id,
                    'payload'     => [
                        'payment_method'   => $payment->provider,
                        'fulfillment_mode' => $fulfillmentMode,
                        'delivery_fee'     => (float) ($totals['delivery_fee'] ?? 0),
                        'service_fee'      => (float) ($totals['service_fee'] ?? 0),
                        'tax'              => (float) ($totals['tax'] ?? 0),
                        'discount'         => (float) ($totals['discount'] ?? 0),
                        'sub_total'        => (float) ($totals['sub_total'] ?? 0),
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning('FoodOrderPaymentConfirmed : erreur ledger', [
                    'payment_id' => $payment->id,
                    'order_no'   => $orderNo,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        if ($shouldEmitOrderCreated) {
            try {
                app(\App\Services\CommerceSignalService::class)->emitOrder($order, 'order.created', [
                    'module'           => 'food',
                    'severity'         => 'info',
                    'actor_type'       => 'customer',
                    'actor_id'         => $user?->id,
                    'payment_method'   => $payment->provider,
                    'fulfillment_mode' => $fulfillmentMode,
                    'discount'         => (float) ($totals['discount'] ?? 0),
                ]);

                app(\App\Services\RiskService::class)->assessOrder($order, [
                    'module'           => 'food',
                    'payment_method'   => $payment->provider,
                    'fulfillment_mode' => $fulfillmentMode,
                    'has_discount'     => ((float) ($totals['discount'] ?? 0)) > 0,
                    'scheduled'        => ! empty($checkoutData['scheduled_date'] ?? null),
                ], 'order_created');
            } catch (\Throwable $e) {
                Log::warning('FoodOrderPaymentConfirmed : erreur signaux métier', [
                    'payment_id' => $payment->id,
                    'order_no'   => $orderNo,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        if ($shouldEmitOrderCreated) {
            try {
                if ($user?->id) {
                    \App\Services\NotificationService::sendToUser(
                        $user->id,
                        'Commande confirmee',
                        $isPickup
                            ? 'Votre commande retrait #' . $orderNo . ' a ete confirmee et est en preparation.'
                            : 'Votre commande #' . $orderNo . ' a ete confirmee et est en preparation.',
                        [
                            'key'     => 'orderConfirmed',
                            'channel' => 'user',
                            'module'  => 'food',
                            'type'    => 'order_created',
                        ]
                    );
                }

                if ($restaurant && $restaurant->user_id) {
                    $restaurantUser = \App\User::where('id', $restaurant->user_id)
                        ->where('type', 'restaurant')
                        ->first();

                    if ($restaurantUser) {
                        \App\Services\NotificationService::sendToUser(
                            $restaurantUser->id,
                            'Nouvelle commande',
                            'Nouvelle commande #' . $orderNo . ' recue.',
                            [
                                'key'     => 'newOrder',
                                'channel' => 'restaurant',
                                'module'  => 'food',
                                'type'    => 'order_created',
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('FoodOrderPaymentConfirmed : erreur notifications', [
                    'payment_id' => $payment->id,
                    'order_no'   => $orderNo,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    private function resolveFulfillmentMode(array $checkoutData, string $fallback = 'delivery'): string
    {
        return strtolower((string) ($checkoutData['fulfillment_mode'] ?? $fallback)) === 'pickup'
            ? 'pickup'
            : 'delivery';
    }

    private function needsFinalization($order, $payment): bool
    {
        return $this->shouldRecordLedger($order->order_no)
            || $this->shouldEmitSignal($order->order_no);
    }

    private function shouldRecordLedger(string $orderNo): bool
    {
        if (! Schema::hasTable('financial_ledger_entries')) {
            return true;
        }

        return ! DB::table('financial_ledger_entries')
            ->where('order_no', $orderNo)
            ->where('entry_type', 'order_created')
            ->exists();
    }

    private function shouldEmitSignal(string $orderNo): bool
    {
        if (! Schema::hasTable('commerce_signals')) {
            return true;
        }

        return ! DB::table('commerce_signals')
            ->where('order_no', $orderNo)
            ->where('signal_type', 'order.created')
            ->exists();
    }

    private function financialEvents(): FinancialEventService
    {
        return app(FinancialEventService::class);
    }
}
