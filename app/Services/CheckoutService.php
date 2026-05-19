<?php

namespace App\Services;

use App\Cart;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Domain\Food\Services\OrderPricingService;
use App\Domain\Food\Services\PlaceOrderService;
use App\Exceptions\DeliveryCapacityException;
use App\Order;
use App\Payment;
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
        $amount = (int) $totals['total'];

        $fulfillmentMode = strtolower((string) ($checkoutData['fulfillment_mode'] ?? 'delivery')) === 'pickup' ? 'pickup' : 'delivery';
        $scheduledAt = $this->resolveScheduledAt($checkoutData['scheduled_date'] ?? null);
        $restaurant = optional($cartItems->first())->restaurant_id
            ? Restaurant::find($cartItems->first()->restaurant_id)
            : null;
        $deliveryServiceability = null;

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

        return DB::transaction(function () use ($user, $paymentMethod, $amount, $cartItems, $checkoutData, $totals, $fulfillmentMode, $scheduledAt, $deliveryServiceability) {
            // 3. Créer le paiement
            $payment = Payment::create([
                'user_id'  => $user->id,
                'amount'   => $amount,
                'currency' => 'XAF',
                'status'   => 'PENDING',
                'provider' => $paymentMethod, // ex: "momo", "cash", "paypal"
            ]);

            // 4. Traiter selon le mode de paiement
            if ($paymentMethod === 'cash') {
                // Cash à la livraison : créer immédiatement la commande
                $orderNo = $this->createOrderFromCart($user, $cartItems, $checkoutData, $totals, $payment);
                $orders = Order::where('order_no', $orderNo)->get();
                $deliveryAssignment = null;
                
                // Créer les livraisons
                if ($fulfillmentMode !== 'pickup') {
                    foreach ($orders as $order) {
                        try {
                            $delivery = $this->deliveryService->createForOrder($order);
                            $dispatchResult = app(DispatchService::class)->autoAssignResult($delivery);
                            $delivery = $delivery->fresh(['driver']);

                            if ($deliveryAssignment === null) {
                                $deliveryAssignment = [
                                    'status' => $dispatchResult['status'] ?? 'pending',
                                    'delivery_id' => $delivery->id,
                                    'driver_id' => $delivery->driver_id,
                                    'driver' => $delivery->driver ? [
                                        'id' => $delivery->driver->id,
                                        'name' => $delivery->driver->name,
                                        'phone' => $delivery->driver->phone,
                                    ] : null,
                                    'available_drivers_count' => $deliveryServiceability['available_drivers_count'] ?? 0,
                                    'serviceable' => $deliveryServiceability['serviceable'] ?? false,
                                    'capacity_state' => $deliveryServiceability['capacity_state'] ?? null,
                                    'next_capacity_check_minutes' => $deliveryServiceability['next_capacity_check_minutes'] ?? null,
                                ];
                            }

                            if (($dispatchResult['status'] ?? null) !== 'assigned') {
                                enqueue_job('food', 'auto_assign_delivery', [
                                    'delivery' => $delivery,
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur création livraison', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                // Paiement à la livraison : commande créée, encaissement en attente
                $payment->update([
                    'status' => 'PENDING',
                    'order_id' => $orders->first()->id,
                    'meta' => [
                        'cash_on_delivery' => $fulfillmentMode !== 'pickup',
                        'cash_on_pickup' => $fulfillmentMode === 'pickup',
                        'fulfillment_mode' => $fulfillmentMode,
                    ]
                ]);

                // Vider le panier
                Cart::where('user_id', $user->id)->delete();

            return [
                'payment' => $payment,
                'order'   => $orders->first(),
                'order_no' => $orderNo,
                'requires_external_payment' => false,
                'delivery_serviceability' => $deliveryServiceability,
                'delivery_assignment' => $deliveryAssignment,
                'delivery_address_quality' => $checkoutData['delivery_address_quality'] ?? null,
            ];
        }

            // 5. Mode paiement en ligne (MoMo, PayPal, etc.)
            $paymentFlow = $this->paymentService()->prepareExternalPayment(
                $payment,
                $cartItems,
                $checkoutData,
                ['totals' => $totals]
            );

            return [
                'payment' => $paymentFlow['payment'],
                'order'   => null,
                'requires_external_payment' => true,
                'payment_payload' => $paymentFlow['payment_payload'],
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
     * @param Payment $payment
     * @return string Numéro de commande
     */
    public function createOrderFromCart($user, $cartItems, array $checkoutData, array $totals, Payment $payment): string
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
            'payment_method' => $payment->provider,
            'payment_status' => $payment->status === 'PAID' ? OrderPaymentStatus::PAID->value : OrderPaymentStatus::PENDING->value,
            'status' => $scheduledAt ? 'scheduled' : 'pending',
            'business_status' => 'pending_restaurant_acceptance',
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
