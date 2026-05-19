<?php

namespace App\Domain\Payment\Adapters;

use App\Domain\Payment\Contracts\PaymentGatewayAdapterInterface;
use App\Domain\Payment\ValueObjects\GatewayResult;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter PayPal — logique extraite de PaymentService (phase 1 = même code, nouveau seam).
 *
 * La capture (retour PayPal) reste gérée par PaymentService::finalizePayPalReturn()
 * car elle implique une redirection HTTP. Seul l'initiation passe par cet adapter.
 */
final class PayPalAdapter implements PaymentGatewayAdapterInterface
{
    public function provider(): string
    {
        return 'paypal';
    }

    public function initiate(Payment $payment, array $context): GatewayResult
    {
        $config       = config('external-services.payments.paypal', []);
        $clientId     = trim((string) ($config['client_id'] ?? env('PAYPAL_CLIENT_ID', '')));
        $clientSecret = trim((string) ($config['secret'] ?? env('PAYPAL_SECRET', env('PAYPAL_CLIENT_SECRET', ''))));
        $mode         = (string) ($config['mode'] ?? env('PAYPAL_MODE', 'sandbox'));
        $currency     = strtoupper((string) ($config['currency'] ?? $payment->currency ?? 'XAF'));
        $paymentId    = (int) $payment->id;

        $returnUrl = $this->resolveReturnUrl($paymentId, $context);
        $cancelUrl = $this->resolveCancelUrl($paymentId, $context);

        if ($clientId === '' || $clientSecret === '') {
            Log::warning('PayPalAdapter : configuration manquante — mode démo activé', [
                'payment_id' => $paymentId,
            ]);

            $reference = 'PAYPAL-DEMO-' . $paymentId . '-' . time();

            return GatewayResult::demo($reference, [
                'provider'   => 'paypal',
                'amount'     => $payment->amount,
                'currency'   => $currency,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ], $returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'demo=1');
        }

        try {
            $accessToken = $this->createAccessToken($clientId, $clientSecret, $mode);

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->post($this->apiBaseUrl($mode) . '/v2/checkout/orders', [
                    'intent'         => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => (string) $paymentId,
                        'custom_id'    => (string) $paymentId,
                        'invoice_id'   => 'payment-' . $paymentId,
                        'amount'       => [
                            'currency_code' => $currency,
                            'value'         => $this->normalizeAmount($payment->amount),
                        ],
                    ]],
                    'payment_source' => [
                        'paypal' => [
                            'experience_context' => [
                                'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                                'payment_method_selected'   => 'PAYPAL',
                                'brand_name'                => config('app.name', 'BantuDelice'),
                                'locale'                    => 'fr-FR',
                                'landing_page'              => 'LOGIN',
                                'shipping_preference'       => 'NO_SHIPPING',
                                'user_action'               => 'PAY_NOW',
                                'return_url'                => $returnUrl,
                                'cancel_url'                => $cancelUrl,
                            ],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('PayPalAdapter::initiate — erreur création order', [
                    'payment_id' => $paymentId,
                    'status'     => $response->status(),
                    'body'       => $response->json() ?: $response->body(),
                ]);

                return GatewayResult::failure('Impossible d\'initier le paiement PayPal pour le moment.');
            }

            $payload          = $response->json();
            $providerReference = (string) ($payload['id'] ?? '');
            $redirectUrl       = $this->extractApprovalUrl($payload);

            if ($providerReference === '' || $redirectUrl === null) {
                Log::error('PayPalAdapter::initiate — réponse incomplète', [
                    'payment_id' => $paymentId,
                    'payload'    => $payload,
                ]);

                return GatewayResult::failure('Réponse PayPal invalide lors de l\'initialisation.');
            }

            return GatewayResult::success($providerReference, [
                'provider'             => 'paypal',
                'amount'               => $payment->amount,
                'currency'             => $currency,
                'mode'                 => $mode,
                'return_url'           => $returnUrl,
                'cancel_url'           => $cancelUrl,
                'paypal_order_status'  => $payload['status'] ?? null,
                'paypal_links'         => $payload['links'] ?? [],
            ], $redirectUrl);
        } catch (\Throwable $e) {
            Log::error('PayPalAdapter::initiate exception', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);

            return GatewayResult::failure(
                $e->getMessage() !== '' ? $e->getMessage() : 'Erreur inattendue lors de l\'initiation PayPal.'
            );
        }
    }

    public function checkStatus(string $providerReference): GatewayStatus
    {
        // PayPal n'expose pas un endpoint de "check status" simple pour les CAPTURE orders.
        // Le statut est déterminé lors du retour de l'acheteur via capture().
        return GatewayStatus::pending('AWAITING_CAPTURE');
    }

    /**
     * Capturer un order PayPal approuvé par le payeur (retour depuis le site PayPal).
     *
     * @return GatewayStatus — PAID si COMPLETED/APPROVED, FAILED sinon.
     */
    public function capture(string $providerReference): GatewayStatus
    {
        $config       = config('external-services.payments.paypal', []);
        $clientId     = trim((string) ($config['client_id'] ?? env('PAYPAL_CLIENT_ID', '')));
        $clientSecret = trim((string) ($config['secret'] ?? env('PAYPAL_SECRET', env('PAYPAL_CLIENT_SECRET', ''))));
        $mode         = (string) ($config['mode'] ?? env('PAYPAL_MODE', 'sandbox'));

        if ($clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('Configuration PayPal manquante.');
        }

        try {
            $accessToken = $this->createAccessToken($clientId, $clientSecret, $mode);

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->post($this->apiBaseUrl($mode) . '/v2/checkout/orders/' . $providerReference . '/capture');

            if (! $response->successful()) {
                Log::error('PayPalAdapter::capture — erreur HTTP', [
                    'reference' => $providerReference,
                    'status'    => $response->status(),
                    'body'      => $response->json() ?: $response->body(),
                ]);
                return GatewayStatus::failed('CAPTURE_FAILED', 'Impossible de confirmer le paiement PayPal.', null, $response->json() ?? []);
            }

            $payload = $response->json();
            $status  = strtoupper((string) ($payload['status'] ?? ''));

            if (in_array($status, ['COMPLETED', 'APPROVED'], true)) {
                return GatewayStatus::paid(['paypal_capture' => $payload], $status);
            }

            return GatewayStatus::failed($status, 'Le paiement PayPal n\'a pas été validé.', null, ['paypal_capture' => $payload]);
        } catch (\Throwable $e) {
            Log::error('PayPalAdapter::capture exception', ['reference' => $providerReference, 'error' => $e->getMessage()]);
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function handleCallback(array $payload): GatewayStatus
    {
        if (!$this->verifySignature($payload)) {
            Log::critical('PayPalAdapter::handleCallback — signature webhook invalide, callback rejeté', [
                'headers' => array_keys($payload['_headers'] ?? []),
            ]);
            return GatewayStatus::failed('INVALID_SIGNATURE', 'Signature PayPal invalide.', null, []);
        }

        $status = strtoupper((string) ($payload['status'] ?? $payload['state'] ?? ''));

        if (in_array($status, ['COMPLETED', 'APPROVED'], true)) {
            return GatewayStatus::paid($payload, $status);
        }

        if (in_array($status, ['FAILED', 'CANCELLED', 'REJECTED', 'DECLINED'], true)) {
            return GatewayStatus::failed($status, $payload['message'] ?? null, null, $payload);
        }

        return GatewayStatus::pending($status, $payload);
    }

    public function verifySignature(array $payload): bool
    {
        // S4.3 — Vérification signature PayPal via l'API verify-webhook-signature.
        // Nécessite les headers HTTP originaux stockés dans $payload['_headers'].
        $webhookId = config('external-services.paypal.webhook_id', env('PAYPAL_WEBHOOK_ID'));

        // Si pas de webhook_id configuré → mode sandbox ou PayPal non actif : on accepte
        if (!$webhookId) {
            Log::info('PayPalAdapter: PAYPAL_WEBHOOK_ID non configuré, signature non vérifiée (mode sandbox/test)');
            return true;
        }

        $headers = $payload['_headers'] ?? [];
        $body    = $payload['_raw_body'] ?? json_encode(array_diff_key($payload, array_flip(['_headers', '_raw_body'])));

        $required = ['PAYPAL-TRANSMISSION-ID', 'PAYPAL-TRANSMISSION-TIME', 'PAYPAL-CERT-URL', 'PAYPAL-TRANSMISSION-SIG'];
        foreach ($required as $h) {
            if (empty($headers[$h]) && empty($headers[strtolower($h)])) {
                Log::warning('PayPalAdapter: header de signature manquant', ['header' => $h]);
                return false;
            }
        }

        try {
            $config = $this->paypalConfig();
            $token  = $this->createAccessToken($config['client_id'], $config['client_secret'], $config['mode']);
            $base   = $this->apiBaseUrl($config['mode']);

            $resp = Http::withToken($token)
                ->acceptJson()
                ->post("{$base}/v1/notifications/verify-webhook-signature", [
                    'auth_algo'         => $headers['PAYPAL-AUTH-ALGO']     ?? $headers['paypal-auth-algo']     ?? 'SHA256withRSA',
                    'cert_url'          => $headers['PAYPAL-CERT-URL']      ?? $headers['paypal-cert-url'],
                    'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID']   ?? $headers['paypal-transmission-id'],
                    'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG']  ?? $headers['paypal-transmission-sig'],
                    'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME']  ?? $headers['paypal-transmission-time'],
                    'webhook_id'        => $webhookId,
                    'webhook_event'     => json_decode($body, true) ?? [],
                ]);

            $verified = strtoupper((string) ($resp->json('verification_status') ?? '')) === 'SUCCESS';

            if (!$verified) {
                Log::warning('PayPalAdapter: signature webhook invalide', ['response' => $resp->json()]);
            }

            return $verified;
        } catch (\Throwable $e) {
            Log::error('PayPalAdapter: erreur vérification signature', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function paypalConfig(): array
    {
        $mode = config('external-services.paypal.mode', env('PAYPAL_MODE', 'sandbox'));
        return [
            'mode'          => $mode,
            'client_id'     => $mode === 'live'
                ? config('external-services.paypal.live_client_id', env('PAYPAL_LIVE_CLIENT_ID'))
                : config('external-services.paypal.sandbox_client_id', env('PAYPAL_SANDBOX_CLIENT_ID')),
            'client_secret' => $mode === 'live'
                ? config('external-services.paypal.live_client_secret', env('PAYPAL_LIVE_CLIENT_SECRET'))
                : config('external-services.paypal.sandbox_client_secret', env('PAYPAL_SANDBOX_CLIENT_SECRET')),
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers privés (même logique que PaymentService — zéro changement de comportement)
    // -------------------------------------------------------------------------

    private function apiBaseUrl(string $mode): string
    {
        return $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function createAccessToken(string $clientId, string $clientSecret, string $mode): string
    {
        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->acceptJson()
            ->post($this->apiBaseUrl($mode) . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Authentification PayPal impossible.');
        }

        $token = trim((string) ($response->json('access_token') ?? ''));
        if ($token === '') {
            throw new \RuntimeException('Token PayPal manquant.');
        }

        return $token;
    }

    private function extractApprovalUrl(array $payload): ?string
    {
        foreach (($payload['links'] ?? []) as $link) {
            $rel = strtolower((string) ($link['rel'] ?? ''));
            if (in_array($rel, ['approve', 'payer-action'], true)) {
                $href = trim((string) ($link['href'] ?? ''));

                return $href !== '' ? $href : null;
            }
        }

        return null;
    }

    private function resolveReturnUrl(int $paymentId, array $context): string
    {
        $custom = trim((string) ($context['paypal_return_url'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return route('checkout.paypal.return', ['payment_id' => $paymentId]);
    }

    private function resolveCancelUrl(int $paymentId, array $context): string
    {
        $custom = trim((string) ($context['paypal_cancel_url'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return route('checkout.paypal.cancel', ['payment_id' => $paymentId]);
    }

    private function normalizeAmount($amount): string
    {
        if (! is_numeric($amount)) {
            return '0.00';
        }

        return number_format(max(0, (float) $amount), 2, '.', '');
    }
}
