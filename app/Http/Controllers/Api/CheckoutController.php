<?php

namespace App\Http\Controllers\Api;

use App\Address;
use App\Exceptions\DeliveryCapacityException;
use App\Http\Controllers\Controller;
use App\Order;
use App\Services\AddressQualityService;
use App\Services\CheckoutService;
use App\Services\PaymentExperienceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected PaymentExperienceService $paymentExperienceService,
        protected AddressQualityService $addressQualityService
    ) {}

    /**
     * Initier un checkout
     * 
     * POST /api/checkout
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $fulfillmentMode = strtolower((string) $request->input('fulfillment_mode', 'delivery')) === 'pickup'
            ? 'pickup'
            : 'delivery';

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:cash,momo,paypal',
            'fulfillment_mode' => 'nullable|string|in:delivery,pickup',
            'delivery_address' => 'nullable|string',
            'delivery_area' => 'nullable|string|max:120',
            'delivery_city' => 'nullable|string|max:120',
            'delivery_department' => 'nullable|string|max:120',
            'd_lat' => 'nullable|numeric',
            'd_lng' => 'nullable|numeric',
            'delivery_address_confirmed' => 'nullable|boolean',
            'driver_tip' => 'nullable|numeric|min:0',
            'voucher_code' => 'nullable|string',
            'scheduled_date' => 'nullable|date|after:now',
            'address_id' => 'nullable|integer|exists:user_address,id',
            'pickup_note' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'use_loyalty_points' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        if ($fulfillmentMode !== 'pickup' && !$request->filled('address_id') && !trim((string) $request->input('delivery_address', ''))) {
            return response()->json([
                'status' => false,
                'message' => 'Adresse de livraison requise',
                'errors' => [
                    'delivery_address' => ['Adresse de livraison requise'],
                ],
            ], 422);
        }

        $deliveryAddressQuality = null;

        try {
            $savedAddress = null;
            if ($request->filled('address_id')) {
                $savedAddress = Address::where('user_id', $user->id)
                    ->where('id', $request->input('address_id'))
                    ->first();

                if (!$savedAddress) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Adresse introuvable',
                        'errors' => [
                            'address_id' => ['Adresse introuvable'],
                        ],
                    ], 404);
                }
            }

            if ($fulfillmentMode !== 'pickup') {
                if ($savedAddress && ($savedAddress->latitude === null || $savedAddress->longitude === null)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Coordonnees de livraison requises',
                        'errors' => [
                            'address_id' => ['Cette adresse enregistree n’a pas de coordonnees de livraison.'],
                        ],
                    ], 422);
                }

                if (! $savedAddress && (! $request->filled('d_lat') || ! $request->filled('d_lng'))) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Coordonnees de livraison requises',
                        'errors' => [
                            'd_lat' => ['Latitude de livraison requise'],
                            'd_lng' => ['Longitude de livraison requise'],
                        ],
                    ], 422);
                }
            }

            $deliveryAddressQuality = $fulfillmentMode === 'pickup'
                ? null
                : $this->addressQualityService->forFood($savedAddress, $request->all());

            if (
                $fulfillmentMode !== 'pickup'
                && $deliveryAddressQuality !== null
                && $this->addressQualityService->needsExplicitConfirmation($deliveryAddressQuality)
                && ! $request->boolean('delivery_address_confirmed')
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Confirmez precisement l adresse de livraison avant de continuer.',
                    'delivery_address_quality' => $deliveryAddressQuality,
                    'errors' => [
                        'delivery_address_confirmed' => ['Confirmation precise de l adresse requise'],
                    ],
                ], 422);
            }

            $paymentPhone = trim((string) $request->input('phone', ''));
            if ($request->input('payment_method') === 'momo' && $paymentPhone === '') {
                return response()->json([
                    'status' => false,
                    'message' => 'Saisissez un numéro Mobile Money valide commençant par 06 (MTN) ou 05 (Airtel).',
                    'errors' => [
                        'phone' => ['Saisissez un numéro Mobile Money valide commençant par 06 (MTN) ou 05 (Airtel).'],
                    ],
                ], 422);
            }

            $checkoutData = [
                'fulfillment_mode' => $fulfillmentMode,
                'delivery_address' => $savedAddress
                    ? trim(implode(' | ', array_filter([
                        $savedAddress->title,
                        $savedAddress->complete_address,
                        $savedAddress->area,
                    ])))
                    : trim((string) $request->input('delivery_address')),
                'd_lat' => $savedAddress ? $savedAddress->latitude : ($request->filled('d_lat') ? $request->input('d_lat') : null),
                'd_lng' => $savedAddress ? $savedAddress->longitude : ($request->filled('d_lng') ? $request->input('d_lng') : null),
                'driver_tip' => $fulfillmentMode === 'pickup' ? 0 : $request->input('driver_tip', 0),
                'voucher_code' => $request->input('voucher_code'),
                'scheduled_date' => $request->input('scheduled_date'),
                'address_id' => $savedAddress?->id,
                'pickup_note' => $request->input('pickup_note'),
                'phone' => $paymentPhone,
                'delivery_address_quality' => $deliveryAddressQuality,
            ];

            $result = $this->checkoutService->startCheckout(
                $user,
                $request->input('payment_method'),
                $checkoutData
            );

            // Formater la réponse — la commande est toujours créée en attente d'acceptation
            // restaurant désormais ; aucun Payment n'existe encore à ce stade (déclenché plus
            // tard par OrderAcceptanceService, après acceptation), quel que soit le mode de paiement.
            $response = [
                'status' => true,
                'payment' => null,
                'requires_external_payment' => false,
                'awaiting_restaurant_acceptance' => true,
                'order' => [
                    'id' => $result['order']->id ?? null,
                    'order_no' => $result['order_no'] ?? null,
                ],
            ];

            if (isset($result['delivery_serviceability'])) {
                $response['delivery_serviceability'] = $result['delivery_serviceability'];
            }

            if (isset($result['delivery_assignment'])) {
                $response['delivery_assignment'] = $result['delivery_assignment'];
            }

            if (isset($result['delivery_address_quality'])) {
                $response['delivery_address_quality'] = $result['delivery_address_quality'];
            }

            return response()->json($response);

        } catch (DeliveryCapacityException $e) {
            $response = [
                'status' => false,
                'message' => $e->getMessage(),
                'delivery_serviceability' => $e->serviceability(),
            ];

            if ($deliveryAddressQuality !== null) {
                $response['delivery_address_quality'] = $deliveryAddressQuality;
            }

            return response()->json($response, 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout error', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erreur lors du checkout. Veuillez réessayer.'
            ], 500);
        }
    }

    /**
     * Relancer le paiement d'une commande en accepted_awaiting_payment.
     * Redirige vers la page de suivi avec un message contextuel ; l'implémentation
     * du formulaire de retry paiement (via PaymentService::prepareExternalPayment)
     * sera ajoutée dans une prochaine itération.
     */
    public function retryPayment(string $orderNo, Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $user->id)
            ->where('business_status', 'accepted_awaiting_payment')
            ->first();

        if (! $order) {
            return redirect()->route('home')->with('alert', [
                'type'    => 'danger',
                'message' => 'Commande introuvable ou déjà traitée.',
            ]);
        }

        // TODO: implémenter le formulaire de saisie/confirmation du paiement (MoMo, PayPal…)
        // Pour l'instant, on redirige vers la page de suivi avec un message d'information.
        return redirect()->route('track.order', ['id' => $orderNo])->with('alert', [
            'type'    => 'info',
            'message' => 'Contactez le support pour finaliser votre paiement sur la commande #' . $orderNo . '.',
        ]);
    }
}
