<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Gérer le callback d'un PSP (MoMo, PayPal, etc.)
     * 
     * POST /api/payments/callback/{provider}
     * 
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, string $provider)
    {
        // Logger le callback pour debug
        Log::info('Payment callback received', [
            'provider' => $provider,
            'payload' => $request->all(),
            'ip' => $request->ip()
        ]);

        $payment = null;

        try {
            // 1. Retrouver le paiement (la signature est vérifiée plus bas,
            // une fois le paiement et son provider identifiés)
            $providerRef = $request->get('reference') ?? $request->get('transaction_id') ?? $request->get('id') ?? null;
            
            if (!$providerRef) {
                throw new \RuntimeException('Référence de paiement manquante');
            }

            $payment = \App\Payment::where('provider_reference', $providerRef)
                ->where('provider', $provider)
                ->first();

            if (!$payment) {
                throw new \RuntimeException('Paiement non trouvé pour la référence: ' . $providerRef);
            }

            // Injecter les headers HTTP pour la vérif de signature PayPal — construit
            // ici (avant le routage transport/colis) pour que la vérification de
            // signature ci-dessous dispose des mêmes données que handleCallback().
            $paypalHeaders = [];
            if (strtolower($provider) === 'paypal') {
                foreach (['PAYPAL-TRANSMISSION-ID','PAYPAL-TRANSMISSION-TIME','PAYPAL-CERT-URL','PAYPAL-TRANSMISSION-SIG','PAYPAL-AUTH-ALGO'] as $h) {
                    $val = $request->header($h);
                    if ($val !== null) {
                        $paypalHeaders[$h] = $val;
                    }
                }
            }
            $callbackPayload = array_merge($request->all(), [
                '_headers'  => $paypalHeaders,
                '_raw_body' => $request->getContent(),
            ]);

            // Vérifier la signature AVANT tout traitement, y compris pour les
            // branches transport/colis ci-dessous qui retournent avant
            // d'atteindre paymentService->handleCallback() (seul endroit où
            // la signature était vérifiée jusqu'ici) — sans ce contrôle,
            // un callback forgé pouvait confirmer un paiement transport/colis
            // sans vérification du PSP.
            if (!$this->paymentService->verifyCallbackSignature($provider, $callbackPayload)) {
                Log::warning('Signature callback invalide — rejeté avant traitement', [
                    'provider' => $provider,
                    'payment_id' => $payment->id,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Signature de callback invalide',
                ], 401);
            }

            if ($payment->transport_booking_id) {
                app(\App\Services\ModuleQueueService::class)->enqueueJob('transport', 'handle_transport_payment_callback', [
                    'payment_id' => $payment->id,
                    'provider' => $provider,
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'status' => 'accepted',
                    'message' => 'Callback transport mis en file pour traitement',
                ], 202);
            }

            if ($payment->shipment_id) {
                app(\App\Services\ModuleQueueService::class)->enqueueJob('colis', 'handle_shipment_payment_callback', [
                    'payment_id' => $payment->id,
                    'provider' => $provider,
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'status' => 'accepted',
                    'message' => 'Callback colis mis en file pour traitement',
                ], 202);
            }

            // 3. Vérifier anti-fraude (si paiement en attente)
            if ($payment->status === 'PENDING') {
                $fraudService = new \App\Services\FraudDetectionService();
                $fraudCheck = $fraudService->checkFraud($payment, [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                if ($fraudCheck['is_fraud'] && $fraudCheck['recommendation'] === 'BLOCK') {
                    Log::warning('Paiement bloqué par anti-fraude', [
                        'payment_id' => $payment->id,
                        'risk_score' => $fraudCheck['risk_score'],
                        'reasons' => $fraudCheck['reasons']
                    ]);

                    $payment->update([
                        'status' => 'FAILED',
                        'meta' => array_merge($payment->meta ?? [], [
                            'fraud_detected' => true,
                            'fraud_reasons' => $fraudCheck['reasons'],
                            'risk_score' => $fraudCheck['risk_score'],
                            'blocked_at' => now()->toIso8601String()
                        ])
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Paiement bloqué pour raison de sécurité'
                    ], 403);
                }

                // Si REVIEW requis, logger mais continuer
                if ($fraudCheck['recommendation'] === 'REVIEW') {
                    Log::warning('Paiement nécessite révision manuelle', [
                        'payment_id' => $payment->id,
                        'risk_score' => $fraudCheck['risk_score'],
                        'reasons' => $fraudCheck['reasons']
                    ]);
                }
            }

            // 4. Traiter le callback ($callbackPayload déjà construit ci-dessus,
            // avant la vérification de signature)
            $this->paymentService->handleCallback($provider, $callbackPayload);

            // La réponse exacte dépend du PSP
            // MoMo attend généralement un JSON avec status
            return response()->json([
                'status' => 'success',
                'message' => 'Callback traité avec succès'
            ], 200);

        } catch (\RuntimeException $e) {
            Log::error('Payment callback error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            // Si le paiement existe, planifier un retry
            if (isset($payment) && $payment) {
                try {
                    app(\App\Services\ModuleQueueService::class)->enqueueJob('food', 'retry_payment_callback', [
                        'payment' => $payment,
                        'callback_data' => $request->all(),
                        '_delay' => now()->addMinutes(1),
                    ]);
                } catch (\Exception $retryException) {
                    Log::error('Erreur lors de la planification du retry', [
                        'payment_id' => $payment->id,
                        'error' => $retryException->getMessage()
                    ]);
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Payment callback exception', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Si le paiement existe, planifier un retry
            if (isset($payment) && $payment) {
                try {
                    app(\App\Services\ModuleQueueService::class)->enqueueJob('food', 'retry_payment_callback', [
                        'payment' => $payment,
                        'callback_data' => $request->all(),
                        '_delay' => now()->addMinutes(5),
                    ]);
                } catch (\Exception $retryException) {
                    Log::error('Erreur lors de la planification du retry', [
                        'payment_id' => $payment->id,
                        'error' => $retryException->getMessage()
                    ]);
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du traitement du callback'
            ], 500);
        }
    }
}
