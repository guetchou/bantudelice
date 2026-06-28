<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Payment;
use App\Services\DisbursementReconciliationService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Gérer le callback d'un PSP (MoMo, Airtel, PayPal, etc.).
     */
    public function handle(Request $request, string $provider)
    {
        $normalizedProvider = $this->normalizeProvider($provider);
        $payment = null;

        try {
            $references = $this->extractReferenceCandidates($request);

            if ($references === []) {
                throw new \RuntimeException('Référence de paiement manquante');
            }

            $payment = $this->resolvePayment($normalizedProvider, $references);

            // MTN utilise la même URL de callback configurée pour Collection et
            // Disbursement dans l'intégration historique. Si aucune entrée Payment
            // ne correspond, tenter une réconciliation des reversements avant de
            // conclure que la référence est inconnue.
            if (!$payment && $normalizedProvider === 'momo') {
                $disbursement = app(DisbursementReconciliationService::class)
                    ->handleCallback($references);

                if ($disbursement !== null) {
                    return response()->json([
                        'status' => ($disbursement['handled'] ?? false) ? 'success' : 'accepted',
                        'message' => 'Callback de décaissement réconcilié',
                        'disbursement_status' => $disbursement['status'] ?? 'UNKNOWN',
                    ], ($disbursement['handled'] ?? false) ? 200 : 202);
                }
            }

            if (!$payment) {
                throw new \RuntimeException(
                    'Paiement non trouvé pour les références reçues: ' . implode(', ', $references)
                );
            }

            $callbackPayload = $this->buildCallbackPayload($request, $normalizedProvider);

            // La référence technique MTN est le X-Reference-Id stocké dans
            // provider_reference. Si le callback ne la répète pas sous le même nom,
            // on la réinjecte pour que le contrôle de statut interroge la bonne ressource.
            $callbackPayload['referenceId'] = $callbackPayload['referenceId']
                ?? $callbackPayload['reference']
                ?? $payment->provider_reference;
            $callbackPayload['reference'] = $callbackPayload['reference']
                ?? $payment->provider_reference;

            Log::info('Payment callback received', [
                'provider' => $normalizedProvider,
                'payment_id' => $payment->id,
                'reference' => $payment->provider_reference,
                'ip' => $request->ip(),
            ]);

            if (!$this->paymentService->verifyCallbackSignature($normalizedProvider, $callbackPayload)) {
                Log::warning('Signature callback invalide — rejeté avant traitement', [
                    'provider' => $normalizedProvider,
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
                    'provider' => $normalizedProvider,
                    'payload' => $callbackPayload,
                ]);

                return response()->json([
                    'status' => 'accepted',
                    'message' => 'Callback transport mis en file pour traitement',
                ], 202);
            }

            if ($payment->shipment_id) {
                app(\App\Services\ModuleQueueService::class)->enqueueJob('colis', 'handle_shipment_payment_callback', [
                    'payment_id' => $payment->id,
                    'provider' => $normalizedProvider,
                    'payload' => $callbackPayload,
                ]);

                return response()->json([
                    'status' => 'accepted',
                    'message' => 'Callback colis mis en file pour traitement',
                ], 202);
            }

            if ($payment->status === 'PENDING') {
                $fraudService = new \App\Services\FraudDetectionService();
                $fraudCheck = $fraudService->checkFraud($payment, [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                if ($fraudCheck['is_fraud'] && $fraudCheck['recommendation'] === 'BLOCK') {
                    Log::warning('Paiement bloqué par anti-fraude', [
                        'payment_id' => $payment->id,
                        'risk_score' => $fraudCheck['risk_score'],
                        'reasons' => $fraudCheck['reasons'],
                    ]);

                    $payment->update([
                        'status' => 'FAILED',
                        'meta' => array_merge($payment->meta ?? [], [
                            'fraud_detected' => true,
                            'fraud_reasons' => $fraudCheck['reasons'],
                            'risk_score' => $fraudCheck['risk_score'],
                            'blocked_at' => now()->toIso8601String(),
                        ]),
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Paiement bloqué pour raison de sécurité',
                    ], 403);
                }

                if ($fraudCheck['recommendation'] === 'REVIEW') {
                    Log::warning('Paiement nécessite révision manuelle', [
                        'payment_id' => $payment->id,
                        'risk_score' => $fraudCheck['risk_score'],
                        'reasons' => $fraudCheck['reasons'],
                    ]);
                }
            }

            $this->paymentService->handleCallback($normalizedProvider, $callbackPayload);

            return response()->json([
                'status' => 'success',
                'message' => 'Callback traité avec succès',
            ]);
        } catch (\RuntimeException $e) {
            Log::error('Payment callback error', [
                'provider' => $normalizedProvider,
                'error' => $e->getMessage(),
                'references' => $this->extractReferenceCandidates($request),
            ]);

            $this->scheduleRetry($payment, $request, 1);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Payment callback exception', [
                'provider' => $normalizedProvider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->scheduleRetry($payment, $request, 5);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du traitement du callback',
            ], 500);
        }
    }

    private function normalizeProvider(string $provider): string
    {
        return match (strtolower(trim($provider))) {
            'mtn', 'mtn_momo', 'momo' => 'momo',
            'airtel', 'airtel_money' => 'airtel',
            'paypal' => 'paypal',
            default => strtolower(trim($provider)),
        };
    }

    private function providerAliases(string $provider): array
    {
        return match ($provider) {
            'momo' => ['momo', 'mtn_momo', 'mtn'],
            'airtel' => ['airtel', 'airtel_money'],
            default => [$provider],
        };
    }

    private function extractReferenceCandidates(Request $request): array
    {
        $payload = $request->all();
        $candidates = [
            $request->header('X-Reference-Id'),
            data_get($payload, 'referenceId'),
            data_get($payload, 'reference'),
            data_get($payload, 'transaction_id'),
            data_get($payload, 'transaction.id'),
            data_get($payload, 'data.transaction.id'),
            data_get($payload, 'externalId'),
            data_get($payload, 'external_id'),
            data_get($payload, 'id'),
        ];

        return array_values(array_unique(array_filter(array_map(
            static fn($value) => trim((string) $value),
            $candidates
        ), static fn($value) => $value !== '')));
    }

    private function resolvePayment(string $provider, array $references): ?Payment
    {
        $aliases = $this->providerAliases($provider);

        $payment = Payment::query()
            ->whereIn('provider', $aliases)
            ->whereIn('provider_reference', $references)
            ->latest('id')
            ->first();

        if ($payment) {
            return $payment;
        }

        // Certains PSP renvoient l'externalId métier au lieu du X-Reference-Id.
        // Le fallback reste borné aux paiements récents du provider concerné.
        return Payment::query()
            ->whereIn('provider', $aliases)
            ->latest('id')
            ->limit(500)
            ->get()
            ->first(function (Payment $candidate) use ($references) {
                $metaReferences = array_filter([
                    data_get($candidate->meta, 'external_id'),
                    data_get($candidate->meta, 'externalId'),
                    data_get($candidate->meta, 'bridge.external_reference'),
                ]);

                return array_intersect($references, array_map('strval', $metaReferences)) !== [];
            });
    }

    private function buildCallbackPayload(Request $request, string $provider): array
    {
        $paypalHeaders = [];

        if ($provider === 'paypal') {
            foreach ([
                'PAYPAL-TRANSMISSION-ID',
                'PAYPAL-TRANSMISSION-TIME',
                'PAYPAL-CERT-URL',
                'PAYPAL-TRANSMISSION-SIG',
                'PAYPAL-AUTH-ALGO',
            ] as $header) {
                $value = $request->header($header);
                if ($value !== null) {
                    $paypalHeaders[$header] = $value;
                }
            }
        }

        return array_merge($request->all(), [
            '_headers' => $paypalHeaders,
            '_raw_body' => $request->getContent(),
        ]);
    }

    private function scheduleRetry(?Payment $payment, Request $request, int $delayMinutes): void
    {
        if (!$payment) {
            return;
        }

        try {
            app(\App\Services\ModuleQueueService::class)->enqueueJob('food', 'retry_payment_callback', [
                'payment' => $payment,
                'callback_data' => $request->all(),
                '_delay' => now()->addMinutes($delayMinutes),
            ]);
        } catch (\Throwable $retryException) {
            Log::error('Erreur lors de la planification du retry callback', [
                'payment_id' => $payment->id,
                'error' => $retryException->getMessage(),
            ]);
        }
    }
}
