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

        try {
            // 1. Vérifier la signature (déjà fait dans PaymentService)
            // 2. Retrouver le paiement
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

            // 4. Traiter le callback
            $this->paymentService->handleCallback($provider, $request->all());

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
                    \App\Jobs\RetryPaymentCallbackJob::dispatch($payment, $request->all())
                        ->delay(now()->addMinutes(1));
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
                    \App\Jobs\RetryPaymentCallbackJob::dispatch($payment, $request->all())
                        ->delay(now()->addMinutes(5));
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


