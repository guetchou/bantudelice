<?php

namespace App\Domain\Food\Services;

use App\Payment;
use App\Restaurant;
use App\User;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Notification + traçabilité « commande confirmée » partagée entre les deux seuls points
 * du code qui peuvent légitimement confirmer une commande food :
 *  - OrderAcceptanceService (cash : confirmation immédiate à l'acceptation restaurant)
 *  - FoodOrderPaymentConfirmed (en ligne : confirmation au paiement confirmé, après acceptation)
 */
class FoodOrderConfirmationNotifier
{
    public function confirmOrder(Payment $payment, Collection $orders, array $checkoutData, array $totals, string $fulfillmentMode, string $orderNo): void
    {
        $order = $orders->first();
        if (! $order) {
            return;
        }

        $shouldRecordLedger     = $this->shouldRecordLedger($orderNo);
        $shouldEmitOrderCreated = $this->shouldEmitSignal($orderNo);
        $user                   = $payment->user ?? $order->user ?? null;
        $restaurant             = $order->restaurant ?? Restaurant::find($order->restaurant_id);
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
                Log::warning('FoodOrderConfirmationNotifier : erreur ledger', [
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
                Log::warning('FoodOrderConfirmationNotifier : erreur signaux métier', [
                    'payment_id' => $payment->id,
                    'order_no'   => $orderNo,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        if ($shouldEmitOrderCreated) {
            try {
                if ($user?->id) {
                    $customerPath = NotificationService::routePath('track.order', ['orderNo' => $orderNo]);
                    NotificationService::sendToUser(
                        $user->id,
                        'Commande confirmée',
                        $isPickup
                            ? 'Votre commande retrait #' . $orderNo . ' a été confirmée et est en préparation.'
                            : 'Votre commande #' . $orderNo . ' a été confirmée et est en préparation.',
                        [
                            'key'        => 'orderConfirmed',
                            'channel'    => 'user',
                            'module'     => 'food',
                            'type'       => 'order_created',
                            'order_no'   => $orderNo,
                            'dedup_key'  => 'food:user:confirmed:' . $orderNo,
                            'route_path' => $customerPath,
                            'deep_link'  => 'bantudelice://food/orders/' . $orderNo,
                            'actions'    => [
                                ['id' => 'open_order', 'label' => 'Suivre', 'path' => $customerPath],
                            ],
                        ]
                    );
                }

                if ($restaurant && $restaurant->user_id) {
                    $restaurantUser = User::where('id', $restaurant->user_id)
                        ->where('type', 'restaurant')
                        ->first();

                    if ($restaurantUser) {
                        $restaurantPath = NotificationService::routePath('restaurant.all_orders', ['focus' => $orderNo]);
                        NotificationService::sendToUser(
                            $restaurantUser->id,
                            'Nouvelle commande',
                            'Nouvelle commande #' . $orderNo . ' reçue.',
                            [
                                'key'        => 'newOrder',
                                'channel'    => 'restaurant',
                                'module'     => 'food',
                                'type'       => 'order_created',
                                'order_no'   => $orderNo,
                                'dedup_key'  => 'food:restaurant:confirmed:' . $orderNo,
                                'route_path' => $restaurantPath,
                                'actions'    => [
                                    ['id' => 'open_order', 'label' => 'Voir', 'path' => $restaurantPath],
                                ],
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('FoodOrderConfirmationNotifier : erreur notifications', [
                    'payment_id' => $payment->id,
                    'order_no'   => $orderNo,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
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
}
