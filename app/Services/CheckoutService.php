<?php

namespace App\Services;

use App\Cart;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Domain\Food\Services\OrderPricingService;
use App\Domain\Food\Services\PlaceOrderService;
use App\Exceptions\DeliveryCapacityException;
use App\Order;
use App\Restaurant;
use App\Services\DispatchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service pour gérer le processus de checkout
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
     * Démarrer le processus de checkout
     * 
     * @param \App\User $user
     * @param string $paymentMethod
     * @param array $checkoutData (delivery_address, d_lat, d_lng, driver_tip, voucher_code, etc.)
     * @return array
     */
    public function startCheckout($user, string $paymentMethod, array $checkoutData = []): array
    {
        // 1. Récupérer le panier
        $cartItems = Cart::where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            throw new \RuntimeException("Le panier est vide.");
        }

        // 2. Calculer les totaux
        $totals = $this->calculateTotals($cartItems, $checkoutData);
        $loyaltyDiscount   = 0.0;
        $loyaltyPointsUsed = 0;
        if (!empty($checkoutData['use_loyalty_points'])) {
            $points = \App\Services\LoyaltyService::getBalance($user->id);
            if ($points > 0) {
                $baseForCap      = (float) ($totals['total'] ?? 0);
                $loyaltyDiscount = min(
                    \App\Services\LoyaltyService::calculateDiscount($points),
                    $baseForCap * 0.2
                );
                $loyaltyPointsUsed = (int) floor(($loyaltyDiscount / 1000) * 100);
            }
        }
        $amount = (int) max(0, ($totals['total'] ?? 0) - $loyaltyDiscount);

        $fulfillmentMode = strtolower((string) ($checkoutData['fulfillment_mode'] ?? 'delivery')) === 'pickup' ? 'pickup' : 'delivery';
        $scheduledAt = $this->resolveScheduledAt($checkoutData['scheduled_date'] ?? null);
        $restaurant = optional($cartItems->first())->restaurant_id
            ? Restaurant::find($cartItems->first()->restaurant_id)
            : null;
        $deliveryServiceability = null;

        // Vérification horaires avant disponibilité livreurs : si fermé, inutile d'aller plus loin
        app(\App\Domain\Food\Services\PlaceOrderService::class)->guardRestaurantOpenForOrdering($restaurant);

        if ($fulfillmentMode !== 'pickup' && $scheduledAt === null && $restaurant) {
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
                    "Aucun livreur disponible autour du restaurant pour le moment.",
                    $deliveryServiceability
                );
            }
        }

        return DB::transaction(function () use ($user, $paymentMethod, $amount, $cartItems, $checkoutData, $totals, $fulfillmentMode, $deliveryServiceability, $loyaltyDiscount, $loyaltyPointsUsed) {
            // 3. Créer la commande — TOUJOURS en pending_restaurant_acceptance, quel que soit le
            // mode de paiement. Plus de Payment créé ici, plus de Delivery créée ici : le paiement
            // (en ligne) et la livraison ne sont déclenchés qu'à l'acceptation restaurant, voir
            // App\Domain\Food\Services\OrderAcceptanceService::handleAccepted().
            $checkoutSnapshot = [
                'checkout_data' => $checkoutData,
                'totals' => $totals,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'loyalty_points_used' => $loyaltyPointsUsed,
                'loyalty_discount' => $loyaltyDiscount,
            ];

            $orderNo = $this->createOrderFromCart($user, $cartItems, $checkoutData, $totals, $paymentMethod, $checkoutSnapshot);
            $orders = Order::where('order_no', $orderNo)->get();
            $primaryOrder = $orders->first();

            if ($loyaltyPointsUsed > 0) {
                \App\Services\LoyaltyService::usePoints($user->id, $loyaltyPointsUsed, null);
            }

            // Notifier le restaurant qu'une nouvelle commande attend son acceptation
            try {
                $primaryOrder->loadMissing(['user', 'restaurant']);
                app(\App\Services\FoodOrderNotificationService::class)
                    ->notifyStatusChange($primaryOrder, 'pending_restaurant_acceptance', []);
            } catch (\Exception $e) {
                Log::warning('Erreur notification commande', ['error' => $e->getMessage()]);
            }

            // Vider le panier
            Cart::where('user_id', $user->id)->delete();

            return [
                'payment' => null,
                'order'   => $primaryOrder,
                'order_no' => $orderNo,
                'requires_external_payment' => false,
                'awaiting_restaurant_acceptance' => true,
                'delivery_serviceability' => $deliveryServiceability,
                'delivery_address_quality' => $checkoutData['delivery_address_quality'] ?? null,
            ];
        });
    }

    /**
     * Créer une commande depuis le panier
     *
     * @param \App\User $user
     * @param \Illuminate\Database\Eloquent\Collection $cartItems
     * @param array $checkoutData
     * @param array $totals
     * @param string $paymentMethod
     * @param array|null $checkoutSnapshot Capture checkoutData/totals/payment_method à rejouer
     *        plus tard (acceptation restaurant) — le panier sera vidé et aucun Payment n'existe
     *        encore à ce stade pour porter cette information.
     * @return string Numéro de commande
     */
    public function createOrderFromCart($user, $cartItems, array $checkoutData, array $totals, string $paymentMethod, ?array $checkoutSnapshot = null): string
    {
        $fulfillmentMode = strtolower((string) ($checkoutData['fulfillment_mode'] ?? 'delivery')) === 'pickup' ? 'pickup' : 'delivery';
        $scheduledAt = $this->resolveScheduledAt($checkoutData['scheduled_date'] ?? null);
        $restaurant = optional($cartItems->first())->restaurant_id ? Restaurant::find($cartItems->first()->restaurant_id) : null;

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
            // Une commande qui vient d'être créée n'a jamais encore été payée — la commande
            // n'existe désormais TOUJOURS qu'avant tout paiement, quel que soit le mode.
            'payment_status' => OrderPaymentStatus::PENDING->value,
            'status' => $scheduledAt ? 'scheduled' : 'pending',
            'business_status' => 'pending_restaurant_acceptance',
            'checkout_snapshot' => $checkoutSnapshot,
            'ordered_time' => now(),
            'delivered_time' => null,
        ]);
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

    /**
     * Calculer les totaux
     * 
     * @param \Illuminate\Database\Eloquent\Collection $cartItems
     * @param array $options
     * @return array
     */
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
