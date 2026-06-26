<?php

namespace App\Services;

use App\Cart;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Domain\Food\Services\OrderPricingService;
use App\Domain\Food\Services\PlaceOrderService;
use App\Exceptions\DeliveryCapacityException;
use App\Exceptions\RestaurantClosedException;
use App\Order;
use App\Restaurant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service pour gérer le processus de checkout.
 */
class CheckoutService implements \App\Domain\Checkout\Contracts\CheckoutOrchestratorInterface
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected ?PaymentService $paymentService = null,
        protected ?PlaceOrderService $placeOrderService = null,
        protected ?OrderPricingService $orderPricingService = null
    ) {}

    /**
     * Démarrer le processus de checkout.
     *
     * @param \App\User $user
     * @param string $paymentMethod
     * @param array $checkoutData
     */
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

        // La planification existante démarrait immédiatement paiement, cuisine et livraison.
        // Elle reste bloquée tant qu'un véritable état accepted_scheduled + scheduler n'existe pas.
        if ($scheduledAt !== null) {
            throw new \RuntimeException(
                'Les commandes programmées sont temporairement indisponibles. Passez une commande immédiate.'
            );
        }

        $this->guardRestaurantAvailableForOrdering($restaurant);
        $this->placeOrderService()->guardRestaurantOpenForOrdering($restaurant);

        $deliveryServiceability = null;
        if ($fulfillmentMode !== 'pickup' && $restaurant) {
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
            $loyaltyPointsUsed
        ) {
            $checkoutSnapshot = [
                'checkout_data' => $checkoutData,
                'totals' => $totals,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'loyalty_points_used' => $loyaltyPointsUsed,
                'loyalty_discount' => $loyaltyDiscount,
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
            $primaryOrder = $orders->first();

            if ($loyaltyPointsUsed > 0) {
                LoyaltyService::usePoints($user->id, $loyaltyPointsUsed, null);
            }

            try {
                $primaryOrder->loadMissing(['user', 'restaurant']);
                app(FoodOrderNotificationService::class)
                    ->notifyStatusChange($primaryOrder, 'pending_restaurant_acceptance', []);
            } catch (\Exception $e) {
                Log::warning('Erreur notification commande', ['error' => $e->getMessage()]);
            }

            Cart::where('user_id', $user->id)->delete();

            return [
                'payment' => null,
                'order' => $primaryOrder,
                'order_no' => $orderNo,
                'requires_external_payment' => false,
                'awaiting_restaurant_acceptance' => true,
                'delivery_serviceability' => $deliveryServiceability,
                'delivery_address_quality' => $checkoutData['delivery_address_quality'] ?? null,
            ];
        });
    }

    public function createOrderFromCart(
        $user,
        $cartItems,
        array $checkoutData,
        array $totals,
        string $paymentMethod,
        ?array $checkoutSnapshot = null
    ): string {
        $fulfillmentMode = strtolower((string) ($checkoutData['fulfillment_mode'] ?? 'delivery')) === 'pickup'
            ? 'pickup'
            : 'delivery';
        $scheduledAt = $this->resolveScheduledAt($checkoutData['scheduled_date'] ?? null);
        $restaurant = optional($cartItems->first())->restaurant_id
            ? Restaurant::find($cartItems->first()->restaurant_id)
            : null;

        return $this->placeOrderService()->placeFromCart($user, $cartItems, [
            'restaurant' => $restaurant,
            'fulfillment_mode' => $fulfillmentMode,
            'scheduled_at' => $scheduledAt,
            'pickup_note' => $checkoutData['pickup_note'] ?? null,
            'offer_discount' => (float) ($totals['discount'] ?? 0),
            'tax' => (float) ($totals['tax'] ?? 0),
            'delivery_charges' => (float) ($totals['delivery_fee'] ?? 0),
            'sub_total' => (float) ($totals['sub_total'] ?? 0),
            'total' => (float) ($totals['total'] ?? 0),
            'driver_tip' => $totals['driver_tip'] ?? 0,
            'delivery_address' => $checkoutData['delivery_address'] ?? '',
            'latitude' => $checkoutData['d_lat'] ?? null,
            'longitude' => $checkoutData['d_lng'] ?? null,
            'd_lat' => $checkoutData['d_lat'] ?? null,
            'd_lng' => $checkoutData['d_lng'] ?? null,
            'payment_method' => $paymentMethod,
            'payment_status' => OrderPaymentStatus::PENDING->value,
            'status' => $scheduledAt ? 'scheduled' : 'pending',
            'business_status' => 'pending_restaurant_acceptance',
            'checkout_snapshot' => $checkoutSnapshot,
            'ordered_time' => now(),
            'delivered_time' => null,
        ]);
    }

    protected function guardRestaurantAvailableForOrdering(?Restaurant $restaurant): void
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

        if ((bool) $restaurant->is_paused) {
            if ($restaurant->paused_until && now()->greaterThanOrEqualTo($restaurant->paused_until)) {
                $restaurant->forceFill([
                    'is_paused' => false,
                    'paused_until' => null,
                    'pause_reason' => null,
                ])->save();
                $restaurant->refresh();
                return;
            }

            $nextOpening = $restaurant->paused_until?->format('d/m/Y H:i');
            throw new RestaurantClosedException(
                'Ce restaurant est temporairement en pause.',
                $nextOpening,
                $restaurant->id
            );
        }
    }

    protected function placeOrderService(): PlaceOrderService
    {
        return $this->placeOrderService ??= app(PlaceOrderService::class);
    }

    protected function resolveScheduledAt($scheduledDate): ?Carbon
    {
        if (empty($scheduledDate)) {
            return null;
        }

        try {
            $candidate = Carbon::parse($scheduledDate);
            return $candidate->isFuture() ? $candidate : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function calculateTotals($cartItems, array $options = []): array
    {
        return $this->orderPricingService()->calculate($cartItems, $options);
    }

    protected function orderPricingService(): OrderPricingService
    {
        return $this->orderPricingService ??= app(OrderPricingService::class);
    }

    protected function paymentService(): PaymentService
    {
        return $this->paymentService ??= app(PaymentService::class);
    }
}
