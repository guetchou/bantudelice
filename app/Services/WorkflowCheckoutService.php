<?php

namespace App\Services;

use App\Cart;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Exceptions\DeliveryCapacityException;
use App\Exceptions\RestaurantClosedException;
use App\Order;
use App\Restaurant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowCheckoutService extends CheckoutService
{
    public function startCheckout($user, string $paymentMethod, array $checkoutData = []): array
    {
        $cartItems = Cart::where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            throw new \RuntimeException('Le panier est vide.');
        }

        $totals = $this->calculateTotals($cartItems, $checkoutData);
        $loyaltyDiscount = 0.0;
        $loyaltyPointsUsed = 0;

        if (! empty($checkoutData['use_loyalty_points'])) {
            $points = LoyaltyService::getBalance($user->id);
            if ($points > 0) {
                $baseForCap = (float) ($totals['total'] ?? 0);
                $loyaltyDiscount = min(
                    LoyaltyService::calculateDiscount($points),
                    $baseForCap * 0.2
                );
                $loyaltyPointsUsed = (int) floor(($loyaltyDiscount / 1000) * 100);
            }
        }

        $amount = (int) max(0, ($totals['total'] ?? 0) - $loyaltyDiscount);
        $fulfillmentMode = strtolower((string) ($checkoutData['fulfillment_mode'] ?? 'delivery')) === 'pickup'
            ? 'pickup'
            : 'delivery';
        $scheduledAt = $this->resolveScheduledAt($checkoutData['scheduled_date'] ?? null);
        $restaurant = optional($cartItems->first())->restaurant_id
            ? Restaurant::find($cartItems->first()->restaurant_id)
            : null;

        if ($scheduledAt) {
            $this->guardRestaurantAvailableAt($restaurant, $scheduledAt);
            app(ScheduledRestaurantAvailabilityService::class)->guard($restaurant, $scheduledAt);
        } else {
            $this->guardRestaurantAvailableForOrdering($restaurant);
            $this->placeOrderService()->guardRestaurantOpenForOrdering($restaurant);
        }

        $deliveryServiceability = null;
        if ($fulfillmentMode !== 'pickup' && $restaurant) {
            if ($scheduledAt) {
                $deliveryServiceability = [
                    'serviceable' => true,
                    'assignment_deferred' => true,
                    'scheduled_at' => $scheduledAt->toIso8601String(),
                    'message' => 'La disponibilité du livreur sera vérifiée dans la fenêtre de préparation.',
                ];
            } else {
                $availableDrivers = $this->deliveryService->countOperationalDriversForRestaurant(
                    $restaurant,
                    isset($checkoutData['d_lat']) ? (float) $checkoutData['d_lat'] : null,
                    isset($checkoutData['d_lng']) ? (float) $checkoutData['d_lng'] : null
                );
                $suggestedDriver = $this->deliveryService->bestOperationalDriverForRestaurant(
                    $restaurant,
                    isset($checkoutData['d_lat']) ? (float) $checkoutData['d_lat'] : null,
                    isset($checkoutData['d_lng']) ? (float) $checkoutData['d_lng'] : null
                );

                $deliveryServiceability = [
                    'available_drivers_count' => $availableDrivers,
                    'serviceable' => $availableDrivers > 0,
                    'suggested_driver' => $suggestedDriver ? [
                        'id' => $suggestedDriver->id,
                        'name' => $suggestedDriver->name,
                        'phone' => $suggestedDriver->phone,
                    ] : null,
                ] + $this->deliveryService->estimateDeliveryWindowForRestaurant(
                    $restaurant,
                    isset($checkoutData['d_lat']) ? (float) $checkoutData['d_lat'] : null,
                    isset($checkoutData['d_lng']) ? (float) $checkoutData['d_lng'] : null
                );

                if ($availableDrivers < 1) {
                    throw new DeliveryCapacityException(
                        'Aucun livreur disponible autour du restaurant pour le moment.',
                        $deliveryServiceability
                    );
                }
            }
        }

        return DB::transaction(function () use (
            $user,
            $paymentMethod,
            $amount,
            $cartItems,
            $checkoutData,
            $totals,
            $fulfillmentMode,
            $deliveryServiceability,
            $loyaltyDiscount,
            $loyaltyPointsUsed,
            $scheduledAt
        ) {
            $checkoutSnapshot = [
                'checkout_data' => $checkoutData,
                'totals' => $totals,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'loyalty_points_used' => $loyaltyPointsUsed,
                'loyalty_discount' => $loyaltyDiscount,
                'scheduled_at' => $scheduledAt?->toIso8601String(),
            ];

            $orderNo = $this->createOrderFromCart(
                $user,
                $cartItems,
                $checkoutData,
                $totals,
                $paymentMethod,
                $checkoutSnapshot
            );
            $orders = Order::where('order_no', $orderNo)->get();
            $primaryOrder = $orders->firstOrFail();

            if ($scheduledAt) {
                Order::where('order_no', $orderNo)->update([
                    'payment_status' => OrderPaymentStatus::NOT_STARTED->value,
                ]);
                $primaryOrder->payment_status = OrderPaymentStatus::NOT_STARTED->value;
            }

            if ($loyaltyPointsUsed > 0) {
                $used = LoyaltyService::usePoints($user->id, $loyaltyPointsUsed, $primaryOrder->id);
                if (! $used) {
                    throw new \RuntimeException('Le solde de points fidélité a changé. Actualisez le panier.');
                }
            }

            try {
                $primaryOrder->loadMissing(['user', 'restaurant']);
                app(FoodOrderNotificationService::class)->notifyStatusChange(
                    $primaryOrder,
                    'pending_restaurant_acceptance',
                    ['scheduled_at' => $scheduledAt?->toIso8601String()]
                );
            } catch (\Throwable $e) {
                Log::warning('Erreur notification commande', ['error' => $e->getMessage()]);
            }

            Cart::where('user_id', $user->id)->delete();

            return [
                'payment' => null,
                'order' => $primaryOrder,
                'order_no' => $orderNo,
                'requires_external_payment' => false,
                'awaiting_restaurant_acceptance' => true,
                'scheduled' => $scheduledAt !== null,
                'scheduled_at' => $scheduledAt?->toIso8601String(),
                'delivery_serviceability' => $deliveryServiceability,
                'delivery_address_quality' => $checkoutData['delivery_address_quality'] ?? null,
            ];
        });
    }

    protected function guardRestaurantAvailableAt(?Restaurant $restaurant, Carbon $scheduledAt): void
    {
        if (! $restaurant) {
            throw new RestaurantClosedException('Restaurant introuvable ou indisponible.', null, null);
        }

        $approved = $restaurant->getAttribute('approved');
        if ($approved !== null && ! (bool) $approved) {
            throw new RestaurantClosedException(
                'Ce restaurant n’est pas encore autorisé à recevoir des commandes.',
                null,
                $restaurant->id
            );
        }

        if (! (bool) $restaurant->is_paused) {
            return;
        }

        if ($restaurant->paused_until && $scheduledAt->greaterThanOrEqualTo($restaurant->paused_until)) {
            return;
        }

        throw new RestaurantClosedException(
            'Ce restaurant sera encore en pause à l’heure programmée.',
            $restaurant->paused_until?->format('d/m/Y H:i'),
            $restaurant->id
        );
    }
}
