<?php

namespace App\Domain\Payment\Adapters;

use App\Domain\Payment\Contracts\PaymentGatewayAdapterInterface;
use App\Domain\Payment\ValueObjects\GatewayResult;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use App\Services\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Logique Airtel Money — paiements entrants uniquement.
 */
final class AirtelMoneyAdapter implements PaymentGatewayAdapterInterface
{
    private const STATUS_MAP = [
        'TS'  => GatewayStatus::PAID,
        'TF'  => GatewayStatus::FAILED,
        'TA'  => GatewayStatus::PENDING,
        'TIP' => GatewayStatus::PENDING,
    ];

    // =========================================================================
    // Interface
    // =========================================================================

    public function provider(): string
    {
        return 'airtel';
    }

    public function initiate(Payment $payment, array $context): GatewayResult
    {
        $phone  = trim((string) SmsService::normalizePhone(data_get($context, 'phone', '')));

        if ($phone === '') {
            return GatewayResult::failure('Numéro de téléphone manquant pour le paiement Airtel Money');
        }

        $config = $this->config();

        if (empty($config['enabled'])) {
            return $this->demoResult($payment, $phone);
        }

        $environment = $config['environment'];
        $baseUrl     = $config['base_url'][$environment];

        try {
            $accessToken = $this->getAccessToken($config, $baseUrl);
            if (! $accessToken) {
                return GatewayResult::failure('Impossible d\'obtenir un token Airtel Money');
            }

            $reference = 'BD-' . $payment->id . '-' . time();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
                'X-Country'     => $config['country'],
                'X-Currency'    => $config['currency'],
            ])->post($baseUrl . '/merchant/v1/payments/', [
                'reference'   => $reference,
                'subscriber'  => [
                    'country'  => $config['country'],
                    'currency' => $config['currency'],
                    'msisdn'   => ltrim($phone, '+'),
                ],
                'transaction' => [
                    'amount'   => $payment->amount,
                    'country'  => $config['country'],
                    'currency' => $config['currency'],
                    'id'       => $reference,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status']['success'] ?? false) {
                    $providerRef = $data['data']['transaction']['id'] ?? $reference;

                    Log::info('AirtelMoneyAdapter: paiement initié', [
                        'payment_id' => $payment->id,
                        'reference'  => $providerRef,
                    ]);

                    return GatewayResult::success($providerRef, [
                        'provider'           => 'airtel',
                        'demo'               => false,
                        'amount'             => $payment->amount,
                        'currency'           => $payment->currency,
                        'phone'              => $phone,
                        'operator'           => 'airtel',
                        'provider_reference' => $providerRef,
                        'instructions'       => [
                            'Composez *143# sur votre téléphone Airtel',
                            'Sélectionnez "Approuver un paiement"',
                            'Entrez votre code PIN pour confirmer',
                        ],
                        'message'       => 'Veuillez confirmer le paiement sur votre téléphone Airtel Money',
                        'checkout_data' => $context,
                    ]);
                }
            }

            Log::error('AirtelMoneyAdapter: échec initiation', [
                'payment_id' => $payment->id,
                'response'   => $response->json(),
            ]);

            return GatewayResult::failure('Erreur lors de l\'initiation du paiement Airtel Money');
        } catch (\Throwable $e) {
            Log::error('AirtelMoneyAdapter::initiate exception', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);

            return GatewayResult::failure(
                $e->getMessage() !== '' ? $e->getMessage() : 'Impossible d\'initier le paiement Airtel Money.'
            );
        }
    }

    public function checkStatus(string $providerReference): GatewayStatus
    {
        $config = $this->config();

        if (empty($config['enabled'])) {
            return GatewayStatus::paid([], 'DEMO');
        }

        $environment = $config['environment'];
        $baseUrl     = $config['base_url'][$environment];

        try {
            $accessToken = $this->getAccessToken($config, $baseUrl);
            if (! $accessToken) {
                return GatewayStatus::unknown('ERROR', ['error' => 'Impossible d\'obtenir un token Airtel']);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Country'     => $config['country'],
                'X-Currency'    => $config['currency'],
            ])->get($baseUrl . '/standard/v1/payments/' . $providerReference);

            if ($response->successful()) {
                $data      = $response->json();
                $rawStatus = $data['data']['transaction']['status'] ?? null;
                $message   = $data['data']['transaction']['message'] ?? null;

                return $this->normalizeAirtelStatus($rawStatus, $message);
            }

            return GatewayStatus::unknown('ERROR', ['error' => 'Impossible de vérifier le statut Airtel']);
        } catch (\Throwable $e) {
            Log::error('AirtelMoneyAdapter::checkStatus exception', [
                'reference' => $providerReference,
                'error'     => $e->getMessage(),
            ]);

            return GatewayStatus::unknown('ERROR', ['error' => $e->getMessage()]);
        }
    }

    public function handleCallback(array $payload): GatewayStatus
    {
        $rawStatus = $payload['transaction']['status'] ?? null;
        $message   = $payload['transaction']['message'] ?? null;

        if ($rawStatus === null) {
            return GatewayStatus::unknown('MISSING_STATUS', ['error' => 'Statut Airtel absent du callback']);
        }

        return $this->normalizeAirtelStatus($rawStatus, $message, $payload);
    }

    public function verifySignature(array $payload): bool
    {
        // Airtel Money ne signe pas ses callbacks.
        return true;
    }

    // =========================================================================
    // Helpers privés
    // =========================================================================

    private function config(): array
    {
        return config('external-services.payments.airtel_money', []);
    }

    private function getAccessToken(array $config, string $baseUrl): ?string
    {
        $cacheKey = 'airtel_money_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::asForm()->post($baseUrl . '/auth/oauth2/token', [
                'client_id'     => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'grant_type'    => 'client_credentials',
            ]);

            if ($response->successful()) {
                $data      = $response->json();
                $token     = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;
                Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));
                return $token;
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('AirtelMoneyAdapter: exception token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function normalizeAirtelStatus(?string $rawStatus, ?string $message = null, array $meta = []): GatewayStatus
    {
        $normalized = self::STATUS_MAP[$rawStatus] ?? GatewayStatus::UNKNOWN;

        return match ($normalized) {
            GatewayStatus::PAID    => GatewayStatus::paid(array_merge($meta, ['message' => $message]), $rawStatus),
            GatewayStatus::FAILED  => GatewayStatus::failed($rawStatus, $message, null, $meta),
            GatewayStatus::PENDING => GatewayStatus::pending($rawStatus, $meta),
            default                => GatewayStatus::unknown($rawStatus, $meta),
        };
    }

    private function demoResult(Payment $payment, string $phone): GatewayResult
    {
        $reference = 'DEMO-airtel-' . $payment->id . '-' . time();

        Log::info('AirtelMoneyAdapter: mode démo', ['payment_id' => $payment->id, 'phone' => $phone]);

        return GatewayResult::demo($reference, [
            'provider'           => 'airtel',
            'amount'             => $payment->amount,
            'currency'           => $payment->currency,
            'phone'              => $phone,
            'operator'           => 'airtel',
            'provider_reference' => $reference,
            'instructions'       => ['Le paiement est simulé en mode démo', 'Configurez AIRTEL_MONEY_* dans .env pour les paiements réels'],
            'message'            => 'Mode démo : aucun paiement réel effectué',
            'checkout_data'      => [],
        ]);
    }
}
