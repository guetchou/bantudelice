<?php

namespace App\Services;

use App\Order;
use App\User;
use App\UserToken;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Opérations post-commit après validation d'une commande food.
 * Toutes les opérations ici sont fire-and-forget (try/catch individuels).
 */
class PostOrderService
{
    public function __construct(
        private CommerceSignalService $signals,
        private RiskService $risk,
        private FinancialLedgerService $ledger,
    ) {}

    /**
     * Lancer toutes les actions post-commit pour une commande food.
     */
    public function run(Order $order, array $context = []): void
    {
        $orderNo          = $order->order_no;
        $userId           = $order->user_id;
        $total            = (float) ($context['total']            ?? $order->total ?? 0);
        $subTotal         = (float) ($context['sub_total']        ?? $order->sub_total ?? 0);
        $deliveryFee      = (float) ($context['delivery_fee']     ?? $order->delivery_charges ?? 0);
        $tax              = (float) ($context['tax']              ?? $order->tax ?? 0);
        $serviceFee       = (float) ($context['service_fee']      ?? 0);
        $driverTip        = (float) ($context['driver_tip']       ?? $order->driver_tip ?? 0);
        $discount         = (float) ($context['discount']         ?? 0);
        $paymentMethod    = (string) ($context['payment_method']  ?? $order->payment_method ?? '');
        $fulfillmentMode  = (string) ($context['fulfillment_mode'] ?? $order->fulfillment_mode ?? 'delivery');
        $isPickup         = $fulfillmentMode === 'pickup';

        $this->recordLedger($order, $orderNo, $userId, $total, $subTotal, $discount, $deliveryFee, $tax, $serviceFee, $driverTip, $paymentMethod, $fulfillmentMode);
        $this->emitSignals($order, $userId, $paymentMethod, $fulfillmentMode, $discount);
        $this->assessRisk($order, $paymentMethod, $fulfillmentMode, $discount, $context['scheduled'] ?? false);
        $this->sendPushNotifications($order, $orderNo, $userId, $isPickup);
        $this->sendConfirmationEmail($order, $orderNo);
    }

    private function recordLedger(Order $order, string $orderNo, int $userId, float $total, float $subTotal, float $discount, float $deliveryFee, float $tax, float $serviceFee, float $driverTip, string $paymentMethod, string $fulfillmentMode): void
    {
        try {
            $this->ledger->record([
                'module'           => 'food',
                'entry_type'       => 'order_created',
                'direction'        => 'credit',
                'status'           => 'posted',
                'order_id'         => $order->id,
                'order_no'         => $orderNo,
                'amount'           => $total,
                'reference'        => $orderNo,
                'actor_type'       => 'user',
                'actor_id'         => $userId,
                'payload'          => compact('sub_total', 'discount', 'delivery_fee', 'tax', 'service_fee', 'driver_tip', 'payment_method', 'fulfillment_mode'),
            ]);
        } catch (\Throwable $e) {
            Log::error('[PostOrderService] ledger failed', ['order_no' => $orderNo, 'error' => $e->getMessage()]);
        }
    }

    private function emitSignals(Order $order, int $userId, string $paymentMethod, string $fulfillmentMode, float $discount): void
    {
        try {
            $this->signals->emitOrder($order, 'order.created', [
                'module'           => 'food',
                'severity'         => 'info',
                'actor_type'       => 'customer',
                'actor_id'         => $userId,
                'payment_method'   => $paymentMethod,
                'fulfillment_mode' => $fulfillmentMode,
                'discount'         => $discount,
            ]);
        } catch (\Throwable $e) {
            Log::error('[PostOrderService] signals failed', ['order_no' => $order->order_no, 'error' => $e->getMessage()]);
        }
    }

    private function assessRisk(Order $order, string $paymentMethod, string $fulfillmentMode, float $discount, bool $scheduled): void
    {
        try {
            $this->risk->assessOrder($order, [
                'module'           => 'food',
                'payment_method'   => $paymentMethod,
                'fulfillment_mode' => $fulfillmentMode,
                'has_discount'     => $discount > 0,
                'scheduled'        => $scheduled,
            ], 'order_created');
        } catch (\Throwable $e) {
            Log::error('[PostOrderService] risk assessment failed', ['order_no' => $order->order_no, 'error' => $e->getMessage()]);
        }
    }

    private function sendPushNotifications(Order $order, string $orderNo, int $userId, bool $isPickup): void
    {
        try {
            // Client
            $userToken = UserToken::where('user_id', $userId)->first();
            if ($userToken?->device_tokens) {
                NotificationService::sendToDevice(
                    $userToken->device_tokens,
                    'Commande confirmée',
                    $isPickup
                        ? "Votre commande retrait #{$orderNo} a été confirmée et est en préparation."
                        : "Votre commande #{$orderNo} a été confirmée et est en préparation.",
                    'orderConfirmed',
                    $userId,
                    'user'
                );
            }

            // Restaurant
            $restaurant = $order->restaurant;
            if ($restaurant?->user_id) {
                $restaurantUser = \App\User::where('id', $restaurant->user_id)->where('type', 'restaurant')->first();
                if ($restaurantUser) {
                    $restaurantToken = UserToken::where('user_id', $restaurantUser->id)->first();
                    if ($restaurantToken?->device_tokens) {
                        NotificationService::sendToDevice(
                            $restaurantToken->device_tokens,
                            'Nouvelle commande',
                            "Nouvelle commande #{$orderNo} reçue.",
                            'newOrder',
                            $restaurantUser->id,
                            'restaurant'
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('[PostOrderService] push notifications failed', ['order_no' => $orderNo, 'error' => $e->getMessage()]);
        }
    }

    private function sendConfirmationEmail(Order $order, string $orderNo): void
    {
        try {
            $user = User::find($order->user_id);
            $skipDomains = ['@bantudelice.cg', '@example.com', '@test.com'];
            $shouldSend = $user?->email
                && !collect($skipDomains)->contains(fn($d) => str_ends_with($user->email, $d));

            if ($shouldSend) {
                $freshOrder = Order::with('cartDetails')->where('order_no', $orderNo)->first();
                if ($freshOrder) {
                    Mail::to($user->email)->queue(new OrderConfirmationMail($freshOrder));
                }
            }
        } catch (\Throwable $e) {
            Log::error('[PostOrderService] confirmation email failed', ['order_no' => $orderNo, 'error' => $e->getMessage()]);
        }
    }
}
