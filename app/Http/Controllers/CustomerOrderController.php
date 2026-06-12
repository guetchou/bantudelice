<?php

namespace App\Http\Controllers;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Http\Controllers\Concerns\RemembersFrontendBrand;
use App\Cart;
use App\Order;
use App\Address;
use App\Product;
use App\Restaurant;
use App\User;
use App\UserToken;
use App\DriverLocation;
use App\Domain\Food\Services\OrderPricingService;
use App\Domain\Food\Services\PlaceOrderService;
use App\Services\CartGroupService;
use App\Services\CommerceSignalService;
use App\Services\DeliveryService;
use App\Services\FinancialLedgerService;
use App\Services\LoyaltyService;
use App\Services\PostOrderService;
use App\Services\NotificationService;
use App\Services\OrderChatService;
use App\Services\PaymentExperienceService;
use App\Services\PromotionService;
use App\Services\RiskService;
use App\Services\SubstitutionService;
use App\Mail\OrderConfirmationMail;
use Carbon\Carbon;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Requests\Order\ConfirmReceiptRequest;
use App\Http\Requests\Order\ReportIncidentRequest;
use App\Http\Requests\Order\RequestRedeliveryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CustomerOrderController extends Controller
{
    use RemembersFrontendBrand;

    /**
     * Passer une commande (paiement cash ou mobile money)
     */
    public function getOrders(PlaceOrderRequest $request)
    {
        // Vérifier que l'utilisateur est connecté
        if(!auth()->check()){
            return redirect()->route('user.login')->with('message', 'Veuillez vous connecter');
        }

        $request->validate([
            'fulfillment_mode' => 'required|in:delivery,pickup',
            'payment_method' => 'required|in:cash,mobile_money,paypal',
            'pickup_note' => 'nullable|string|max:500',
            'scheduled_date' => 'nullable|date|after:now',
            'address_id' => 'nullable|integer|exists:user_address,id',
            'phone' => [
                $request->input('payment_method') === 'mobile_money' ? 'required' : 'nullable',
                'string',
                'max:30',
                'regex:/^(06|05)\d{7}$/',
            ],
        ], [
            'phone.required' => 'Le numéro Mobile Money est obligatoire pour ce mode de paiement.',
            'phone.regex'    => 'Le numéro doit commencer par 06 (MTN) ou 05 (Airtel) et contenir 9 chiffres.',
        ]);

        $isPickup = $request->input('fulfillment_mode') === 'pickup';
        $savedAddress = null;
        if (!$isPickup && $request->filled('address_id') && \Illuminate\Support\Facades\Schema::hasTable('user_address')) {
            // Vérification ownership : l'adresse doit appartenir à l'utilisateur connecté
            $savedAddress = Address::where('user_id', auth()->id())
                ->where('id', $request->input('address_id'))
                ->first();
            if (! $savedAddress) {
                return back()->withErrors(['address_id' => 'Adresse introuvable ou non autorisée.']);
            }
        }

        if (!$isPickup) {
            $request->validate([
                'delivery_address' => 'required_without:address_id|string|max:500',
            ]);
        }

        $userId = auth()->user()->id;
        $cartItems = Cart::where('user_id', $userId)->get();
        $cartItemsForValidation = app(CartGroupService::class)->cartItemsForUser($userId);
        $stockIssues = app(SubstitutionService::class)->suggestForCart($cartItemsForValidation, 4);

        if ($stockIssues->isNotEmpty()) {
            app(CommerceSignalService::class)->emit('catalog.order_blocked_out_of_stock', [
                'module' => 'food',
                'severity' => 'warning',
                'user_id' => $userId,
                'payload' => [
                    'items' => $stockIssues->toArray(),
                    'order_no' => null,
                    'restaurant_id' => optional($cartItems->first())->restaurant_id,
                ],
            ]);

            $primaryIssue = $stockIssues->first();
            if (!empty($primaryIssue['product_id'])) {
                app(\App\Services\SupportTicketService::class)->openUnique([
                    'module' => 'food',
                    'category' => 'stock',
                    'priority' => 'high',
                    'status' => 'open',
                    'title' => 'Rupture produit détectée',
                    'description' => 'Un produit du panier est indisponible avant validation de la commande.',
                    'subject_type' => Product::class,
                    'subject_id' => $primaryIssue['product_id'],
                    'opened_by_type' => 'system',
                    'opened_by_id' => $userId,
                    'meta' => [
                        'items' => $stockIssues->toArray(),
                        'restaurant_id' => optional($cartItems->first())->restaurant_id,
                    ],
                ]);
            }

            return back()
                ->with('message', 'Certains produits sont en rupture. Remplacez-les avant de valider votre commande.')
                ->with('stockIssues', $stockIssues->toArray());
        }

        if($cartItems->isEmpty()){
            return redirect()->route('cart.detail')->with('message', 'Votre panier est vide');
        }

        $restaurant = Restaurant::find($cartItems->first()->restaurant_id);
        $pricing = $this->orderPricingService()->calculate($cartItems, [
            'fulfillment_mode' => $isPickup ? 'pickup' : 'delivery',
            'driver_tip' => $request->driver_tip ?? 0,
        ]);
        $subTotal = (float) ($pricing['sub_total'] ?? 0);
        $deliveryFee = (float) ($pricing['delivery_fee'] ?? 0);
        $tax = (float) ($pricing['tax'] ?? 0);
        $serviceFee = (float) ($pricing['service_fee'] ?? 0);
        $driverTip = (float) ($pricing['driver_tip'] ?? 0);
        $total = (float) ($pricing['total'] ?? 0);
        $scheduledAt = $this->resolveScheduledAt($request->input('scheduled_date'));

        // Appliquer le voucher si présent
        $discount = 0;
        $voucher = null;
        if($request->voucher_code){
            $restaurantId = $cartItems->first()->restaurant_id;
            $restaurantModel = Restaurant::find($restaurantId);
            $promoPreview = app(PromotionService::class)->preview((string) $request->voucher_code, $restaurantModel, auth()->user(), $subTotal);
            if(!empty($promoPreview['valid'])){
                $voucher = $promoPreview['voucher'];
                $discount = (float) ($promoPreview['discount'] ?? 0);
                $total -= $discount;
            }
        }

        // Appliquer les points de fidélité si utilisés
        $loyaltyDiscount = 0;
        $loyaltyPointsUsed = 0;
        if($request->use_loyalty_points && auth()->check()){
            $loyaltyRedemption = $this->resolveLoyaltyRedemption($userId, $subTotal + $deliveryFee + $tax + $serviceFee, true);
            $loyaltyDiscount = $loyaltyRedemption['discount'];
            $loyaltyPointsUsed = $loyaltyRedemption['points_used'];
            $total -= $loyaltyDiscount;
        }

        $pickupCode = $isPickup ? (string) random_int(1000, 9999) : null;
        $orderAddress = $savedAddress
            ? trim(implode(' | ', array_filter([
                $savedAddress->title,
                $savedAddress->complete_address,
                $savedAddress->area,
            ])))
            : ($isPickup
            ? trim(implode(' | ', array_filter([
                'Retrait sur place',
                optional($restaurant)->name,
                optional($restaurant)->address,
                $request->pickup_note ? 'Note: ' . trim((string) $request->pickup_note) : null,
            ])))
            : $request->delivery_address);
        $deliveryLatitude = $savedAddress ? $savedAddress->latitude : ($request->d_lat ?? null);
        $deliveryLongitude = $savedAddress ? $savedAddress->longitude : ($request->d_lng ?? null);

        // Créer les commandes
        DB::beginTransaction();
        try {
            $orderNo = app(PlaceOrderService::class)->placeFromCart(auth()->user(), $cartItems, [
                'restaurant' => $restaurant,
                'order_no' => 'TD-' . date('Ymd') . '-' . rand(1000, 9999),
                'fulfillment_mode' => $isPickup ? 'pickup' : 'delivery',
                'scheduled_at' => $scheduledAt,
                'pickup_code' => $pickupCode,
                'pickup_note' => $request->pickup_note,
                'offer_discount' => $discount + $loyaltyDiscount,
                'tax' => $tax,
                'delivery_charges' => $deliveryFee,
                'sub_total' => $subTotal,
                'total' => $total,
                'driver_tip' => $driverTip,
                'delivery_address' => $orderAddress,
                'latitude' => $isPickup ? (optional($restaurant)->latitude ?? null) : $deliveryLatitude,
                'longitude' => $isPickup ? (optional($restaurant)->longitude ?? null) : $deliveryLongitude,
                'd_lat' => $isPickup ? (optional($restaurant)->latitude ?? '-4.2767') : ($deliveryLatitude ?? '-4.2767'),
                'd_lng' => $isPickup ? (optional($restaurant)->longitude ?? '15.2832') : ($deliveryLongitude ?? '15.2832'),
                'payment_method' => $request->payment_method,
                'payment_status' => OrderPaymentStatus::PENDING->value,
                'status' => $scheduledAt ? 'scheduled' : 'pending',
                'business_status' => 'pending_restaurant_acceptance',
                'ordered_time' => now(),
                'delivered_time' => null,
            ]);

            // Vider le panier
            Cart::where('user_id', $userId)->delete();

            // Récupérer les commandes créées pour créer les livraisons
            $orders = Order::where('order_no', $orderNo)->get();

            // Créer UNE SEULE livraison pour le groupe de commande (pas une par item)
            if (!$isPickup) {
                $deliveryService = new \App\Services\DeliveryService();
                $primaryOrder = $orders->first();
                try {
                    $delivery = $deliveryService->createForOrder($primaryOrder);

                    enqueue_job('food', 'auto_assign_delivery', [
                        'delivery' => $delivery,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Erreur création livraison', [
                        'order_id' => $primaryOrder->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($voucher && $discount > 0) {
                app(PromotionService::class)->redeem($voucher, $orders->first(), auth()->user(), (float) $subTotal, (float) $discount, [
                    'module' => 'food',
                    'source' => 'checkout',
                    'order_no' => $orderNo,
                ]);
            }

            DB::commit();

            // Opérations post-commit déléguées au PostOrderService
            $primaryOrder = $orders->first();
            app(PostOrderService::class)->run($primaryOrder, [
                'total'            => $total,
                'sub_total'        => $subTotal,
                'delivery_fee'     => $deliveryFee,
                'tax'              => $tax,
                'service_fee'      => $serviceFee,
                'driver_tip'       => $driverTip,
                'discount'         => $discount + $loyaltyDiscount,
                'payment_method'   => $request->payment_method,
                'fulfillment_mode' => $isPickup ? 'pickup' : 'delivery',
                'scheduled'        => !empty($scheduledAt),
            ]);

            // Stocker le numéro de commande en session
            session()->put('order_no', $orderNo);

            return redirect()->route('thanks', ['orderID' => $orderNo])
                             ->with('success', 'Commande passée avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('message', 'Erreur lors de la commande: ' . $e->getMessage());
        }
    }

    /**
     * Suivre une commande
     */
    public function trackOrder(Request $request, $orderNo = null)
    {
        if (!$orderNo && $request->has('order_no')) {
            $orderNo = $request->order_no;
        }

        if (!$orderNo) {
            return redirect()->route('user.profile')->with('message', 'Numéro de commande requis');
        }

        $order = Order::where('order_no', $orderNo)->first();

        if (!$order) {
            return redirect()->route('user.profile')->with('message', 'Commande non trouvée');
        }

        // Vérification stricte de propriété (=== évite la comparaison lâche PHP)
        if (auth()->check() && (int) $order->user_id !== (int) auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        // Charger la relation delivery avec driver
        $order->load(['delivery.driver', 'restaurant', 'payment']);
        if ($order->delivery && !$order->isPickup()) {
            app(\App\Services\DeliveryService::class)->ensureDeliveryOtp($order->delivery);
            $refreshedDelivery = $order->delivery->fresh(['driver']);
            $order->setRelation('delivery', $refreshedDelivery);
        }
        $order->tracking_status = $order->resolveTrackingStatus();
        $order->effective_business_status = $order->resolveEffectiveBusinessStatus();
        $order->tracking_progress = $order->resolveTrackingProgress();

        // Récupérer tous les produits de la commande
        $orderItems = Order::where('order_no', $orderNo)
                          ->with(['product', 'restaurant'])
                          ->get();

        // Calculer le temps estime de livraison
        $estimatedTime = 30; // minutes par defaut
        if ($order->restaurant && $order->restaurant->avg_delivery_time) {
            $rawEstimatedTime = trim((string) $order->restaurant->avg_delivery_time);
            if (is_numeric($rawEstimatedTime)) {
                $estimatedTime = (int) $rawEstimatedTime;
            } elseif (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $rawEstimatedTime, $matches)) {
                $estimatedTime = ((int) $matches[1] * 60) + (int) $matches[2];
                if ($estimatedTime === 0 && !empty($matches[3])) {
                    $estimatedTime = (int) $matches[3];
                }
            } elseif (preg_match('/(\d+)/', $rawEstimatedTime, $matches)) {
                $estimatedTime = (int) $matches[1];
            }
        }

        // Calculer le temps écoulé depuis la commande
        $elapsedMinutes = now()->diffInMinutes($order->created_at);
        $remainingMinutes = max(0, $estimatedTime - $elapsedMinutes);

        // Récupérer les informations de livraison
        $delivery = $order->delivery;
        $chatData = app(OrderChatService::class)->viewDataForOrder($order, auth()->user());
        $paymentExperience = app(\App\Services\PaymentExperienceService::class)->describe($order->payment);

        return response()
            ->view('frontend.track_order', compact('order', 'orderItems', 'estimatedTime', 'remainingMinutes', 'delivery', 'chatData', 'paymentExperience'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function confirmOrderReceipt(ConfirmReceiptRequest $request, $orderNo)
    {
        $request->validate([
            'delivery_otp' => 'nullable|string|max:12',
            'customer_confirmed' => 'nullable|boolean',
        ]);

        $order = Order::with('delivery')
            ->where('order_no', $orderNo)
            ->firstOrFail();

        if (!auth()->check() || $order->user_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        if ($order->isPickup()) {
            $pickupCode = trim((string) $request->input('delivery_otp'));
            if (!empty($order->pickup_code) && $pickupCode !== (string) $order->pickup_code) {
                return back()->with('message', 'Code de retrait invalide.');
            }

            $stateMachine = app(\App\Services\FoodOrderStateMachineService::class);
            try {
                if ($order->resolveEffectiveBusinessStatus() === 'ready_for_pickup') {
                    $stateMachine->transitionOrderGroup($orderNo, 'customer_arrived', [
                        'actor_type' => 'customer',
                        'actor_id' => auth()->id(),
                        'notes' => 'Confirmation client de présence au retrait.',
                    ]);
                }

                $stateMachine->transitionOrderGroup($orderNo, 'picked_up_by_customer', [
                    'actor_type' => 'customer',
                    'actor_id' => auth()->id(),
                    'notes' => 'Retrait confirmé côté client.',
                ]);
            } catch (\Throwable $e) {
                return back()->with('message', $e->getMessage());
            }

            return back()->with('success', 'Retrait confirmé avec succès.');
        }

        if (!$order->delivery) {
            return back()->with('message', 'Aucune livraison associée à cette commande.');
        }

        $delivery = $order->delivery;
        $service = app(\App\Services\DeliveryService::class);

        if (!$delivery->customer_confirmed_at) {
            if ($delivery->status === 'DELIVERED') {
                if ($delivery->requiresOtp() && !$service->verifyDeliveryOtp($delivery, $request->input('delivery_otp'))) {
                    return back()->with('message', 'Code OTP invalide.');
                }

                $delivery->update([
                    'customer_confirmed_at' => now(),
                    'otp_verified_at' => $request->filled('delivery_otp') ? now() : $delivery->otp_verified_at,
                    'delivery_confirmation_method' => $request->filled('delivery_otp') ? 'otp' : 'customer_button',
                ]);
            } else {
                try {
                    $service->updateStatus($delivery, 'DELIVERED', [
                        'customer_confirmed' => true,
                        'delivery_otp' => $request->input('delivery_otp'),
                    ]);
                } catch (\Throwable $e) {
                    return back()->with('message', $e->getMessage());
                }
            }
        }

        return back()->with('success', 'Réception confirmée avec succès.');
    }

    public function reopenPickupOrder(Request $request, $orderNo)
    {
        $request->validate([
            'order_no' => 'sometimes|string|max:100',
        ]);

        $order = Order::where('order_no', $orderNo)->firstOrFail();

        if (!auth()->check() || $order->user_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        if (!$order->isPickup()) {
            return back()->with('message', "Cette commande n'est pas en mode retrait.");
        }

        if ($order->resolveEffectiveBusinessStatus() !== 'no_show') {
            return back()->with('message', "La réactivation n'est disponible que pour un retrait marqué absent.");
        }

        try {
            app(\App\Services\FoodOrderStateMachineService::class)->transitionOrderGroup($orderNo, 'ready_for_pickup', [
                'actor_type' => 'customer',
                'actor_id' => auth()->id(),
                'notes' => 'Réactivation du retrait demandée par le client.',
            ]);
        } catch (\Throwable $e) {
            return back()->with('message', $e->getMessage());
        }

        return back()->with('success', 'Votre retrait a été réactivé. Présentez votre code au restaurant.');
    }

    public function reportOrderIncident(ReportIncidentRequest $request, $orderNo)
    {
        $request->validate([
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $order = Order::with('delivery')->where('order_no', $orderNo)->firstOrFail();

        if (!auth()->check() || $order->user_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        if (!$order->delivery) {
            return back()->with('message', 'Aucune livraison associée à cette commande.');
        }

        try {
            app(\App\Services\DeliveryService::class)->reportIncident($order->delivery, $request->input('reason'), [
                'actor_type' => 'customer',
                'actor_id' => auth()->id(),
                'notes' => $request->input('notes'),
                'support_status' => 'open',
            ]);
        } catch (\Throwable $e) {
            return back()->with('message', $e->getMessage());
        }

        return back()->with('success', 'Votre signalement a été transmis au support.');
    }

    public function requestOrderRedelivery(RequestRedeliveryRequest $request, $orderNo)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $order = Order::with('delivery')->where('order_no', $orderNo)->firstOrFail();

        if (!auth()->check() || $order->user_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        if (!$order->delivery) {
            return back()->with('message', 'Aucune livraison associée à cette commande.');
        }

        try {
            app(\App\Services\DeliveryService::class)->requestRedelivery($order->delivery, [
                'actor_type' => 'customer',
                'actor_id' => auth()->id(),
                'notes' => $request->input('notes'),
            ]);
        } catch (\Throwable $e) {
            return back()->with('message', $e->getMessage());
        }

        app(\App\Services\SupportTicketService::class)->openFromDelivery($order->delivery->fresh(), 'redelivery', 'Demande de re-livraison', 'Le client a demandé une nouvelle tentative de livraison.', [
            'opened_by_type' => 'customer',
            'opened_by_id' => auth()->id(),
            'priority' => 'normal',
            'status' => 'pending_redelivery',
            'notes' => $request->input('notes'),
        ]);

        return back()->with('success', 'Demande de re-livraison enregistrée.');
    }

    /**
     * API: Récupérer le statut d'une commande en temps réel
     * Route: GET /api/order/{orderNo}/status
     */
    public function getOrderStatus($orderNo)
    {
        try {
            $order = Order::where('order_no', $orderNo)
                ->with(['restaurant', 'driver', 'user'])
                ->first();

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            // Vérifier l'autorisation (optionnel - peut être public ou avec auth)
            if (auth()->check() && $order->user_id != auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Récupérer tous les items de la commande
            $orderItems = Order::where('order_no', $orderNo)
                ->with('product')
                ->get();

            // Calculer le temps estime
            $estimatedTime = 30;
            if ($order->restaurant && $order->restaurant->avg_delivery_time) {
                $rawEstimatedTime = trim((string) $order->restaurant->avg_delivery_time);
                if (is_numeric($rawEstimatedTime)) {
                    $estimatedTime = (int) $rawEstimatedTime;
                } elseif (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $rawEstimatedTime, $matches)) {
                    $estimatedTime = ((int) $matches[1] * 60) + (int) $matches[2];
                    if ($estimatedTime === 0 && !empty($matches[3])) {
                        $estimatedTime = (int) $matches[3];
                    }
                } elseif (preg_match('/(\d+)/', $rawEstimatedTime, $matches)) {
                    $estimatedTime = (int) $matches[1];
                }
            }

            $elapsedMinutes = now()->diffInMinutes($order->created_at);
            $remainingMinutes = max(0, $estimatedTime - $elapsedMinutes);

            // Charger la livraison avec le livreur
            $order->load(['delivery.driver']);
            $delivery = $order->delivery;

            $effectiveStatus = $order->resolveTrackingStatus();
            $businessStatus = $order->resolveEffectiveBusinessStatus();
            $progressPercentage = $order->resolveTrackingProgress();

            // Récupérer la position GPS du livreur (dernière position connue)
            $driverLocation = null;
            if ($delivery && $delivery->driver) {
                // D'abord essayer depuis driver_locations (historique)
                $latestLocation = \Illuminate\Support\Facades\Schema::hasTable('driver_locations')
                    ? \App\DriverLocation::where('driver_id', $delivery->driver->id)
                        ->orderBy('timestamp', 'desc')
                        ->first()
                    : null;

                if ($latestLocation) {
                    $driverLocation = [
                        'latitude' => (float) $latestLocation->latitude,
                        'longitude' => (float) $latestLocation->longitude,
                        'accuracy' => $latestLocation->accuracy ? (float) $latestLocation->accuracy : null,
                        'heading' => $latestLocation->heading ? (float) $latestLocation->heading : null,
                        'speed' => $latestLocation->speed ? (float) $latestLocation->speed : null,
                        'timestamp' => $latestLocation->timestamp->toIso8601String(),
                    ];
                } elseif ($delivery->driver->latitude && $delivery->driver->longitude) {
                    // Fallback sur la position dans la table drivers
                    $driverLocation = [
                        'latitude' => (float) $delivery->driver->latitude,
                        'longitude' => (float) $delivery->driver->longitude,
                        'accuracy' => null,
                        'heading' => null,
                        'speed' => null,
                        'timestamp' => $delivery->driver->updated_at->toIso8601String(),
                    ];
                }
            }

            // Coordonnées du restaurant
            $restaurantLocation = null;
            if ($order->restaurant && $order->restaurant->latitude && $order->restaurant->longitude) {
                $restaurantLocation = [
                    'latitude' => (float) $order->restaurant->latitude,
                    'longitude' => (float) $order->restaurant->longitude,
                ];
            }

            // Coordonnées de livraison (client)
            $deliveryLocation = null;
            if ($order->d_lat && $order->d_lng) {
                $deliveryLocation = [
                    'latitude' => (float) $order->d_lat,
                    'longitude' => (float) $order->d_lng,
                ];
            } elseif ($order->latitude && $order->longitude) {
                $deliveryLocation = [
                    'latitude' => (float) $order->latitude,
                    'longitude' => (float) $order->longitude,
                ];
            }

            return response()->json([
                'status' => true,
                'order' => [
                    'order_no' => $order->order_no,
                    'status' => $effectiveStatus,
                    'business_status' => $businessStatus,
                    'delivery_status' => $delivery->status ?? null,
                    'fulfillment_mode' => $order->fulfillment_mode ?? 'delivery',
                    'progress' => $progressPercentage,
                    'delivery_otp_required' => $delivery ? $delivery->requiresOtp() : false,
                    'customer_confirmed_at' => optional($delivery)->customer_confirmed_at?->toIso8601String(),
                    'delivery_confirmation_method' => optional($delivery)->delivery_confirmation_method,
                    'pickup_code' => auth()->check() && $order->user_id === auth()->id() ? ($order->pickup_code ?? null) : null,
                    'created_at' => $order->created_at->toDateTimeString(),
                    'estimated_time' => $estimatedTime,
                    'remaining_minutes' => $remainingMinutes,
                    'restaurant' => [
                        'id' => $order->restaurant->id ?? null,
                        'name' => $order->restaurant->name ?? null,
                        'address' => $order->restaurant->address ?? null,
                        'location' => $restaurantLocation, // Position GPS
                    ],
                    'driver' => ($delivery && $delivery->driver) ? [
                        'id' => $delivery->driver->id,
                        'name' => $delivery->driver->name,
                        'phone' => $delivery->driver->phone,
                        'vehicle' => $delivery->driver->vehicle ?? null,
                        'latitude' => $driverLocation['latitude'] ?? ($delivery->driver->latitude ? (float) $delivery->driver->latitude : null),
                        'longitude' => $driverLocation['longitude'] ?? ($delivery->driver->longitude ? (float) $delivery->driver->longitude : null),
                        'location' => $driverLocation, // Position GPS en temps réel avec métadonnées
                    ] : null,
                    'delivery_address' => $order->delivery_address,
                    'delivery_location' => $deliveryLocation, // Position GPS de livraison
                    'total' => $order->total,
                ],
                'items' => $orderItems->map(function($item) {
                    return [
                        'product_name' => $item->product->name ?? 'Produit supprimé',
                        'qty' => $item->qty,
                        'price' => $item->price,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function orderPricingService(): OrderPricingService
    {
        return app(OrderPricingService::class);
    }

    private function resolveScheduledAt(?string $scheduledDate): ?Carbon
    {
        if (blank($scheduledDate)) {
            return null;
        }

        try {
            $candidate = Carbon::parse($scheduledDate);

            return $candidate->isFuture() ? $candidate : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveLoyaltyRedemption(int $userId, float $baseAmount, bool $consume = false): array
    {
        $loyaltyPoints = LoyaltyService::getBalance($userId);
        if ($loyaltyPoints <= 0 || $baseAmount <= 0) {
            return [
                'discount' => 0.0,
                'points_used' => 0,
            ];
        }

        $discount = min(LoyaltyService::calculateDiscount($loyaltyPoints), $baseAmount * 0.2);
        $pointsUsed = (int) floor(($discount / 1000) * 100);

        if ($pointsUsed > $loyaltyPoints) {
            $pointsUsed = $loyaltyPoints;
            $discount = LoyaltyService::calculateDiscount($loyaltyPoints);
        }

        if ($consume && ($pointsUsed < 1 || ! LoyaltyService::usePoints($userId, $pointsUsed, null))) {
            return [
                'discount' => 0.0,
                'points_used' => 0,
            ];
        }

        return [
            'discount' => (float) $discount,
            'points_used' => $pointsUsed,
        ];
    }
}
