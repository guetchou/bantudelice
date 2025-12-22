<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
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
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:cash,momo,paypal',
            'delivery_address' => 'required|string',
            'd_lat' => 'nullable|numeric',
            'd_lng' => 'nullable|numeric',
            'driver_tip' => 'nullable|numeric|min:0',
            'voucher_code' => 'nullable|string',
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

        try {
            $checkoutData = [
                'delivery_address' => $request->input('delivery_address'),
                'd_lat' => $request->input('d_lat'),
                'd_lng' => $request->input('d_lng'),
                'driver_tip' => $request->input('driver_tip', 0),
                'voucher_code' => $request->input('voucher_code'),
            ];

            $result = $this->checkoutService->startCheckout(
                $user,
                $request->input('payment_method'),
                $checkoutData
            );

            // Formater la réponse
            $response = [
                'status' => true,
                'payment' => [
                    'id' => $result['payment']->id,
                    'status' => $result['payment']->status,
                    'amount' => $result['payment']->amount,
                    'currency' => $result['payment']->currency,
                    'provider' => $result['payment']->provider,
                ],
                'requires_external_payment' => $result['requires_external_payment'],
            ];

            if ($result['requires_external_payment']) {
                $response['payment_payload'] = $result['payment_payload'];
            } else {
                $response['order'] = [
                    'id' => $result['order']->id ?? null,
                    'order_no' => $result['order_no'] ?? null,
                ];
            }

            return response()->json($response);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
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
}


