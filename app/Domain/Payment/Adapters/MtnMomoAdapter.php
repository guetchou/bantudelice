<?php

namespace App\Domain\Payment\Adapters;

use App\Domain\Payment\Contracts\PaymentGatewayAdapterInterface;
use App\Domain\Payment\MtnErrorCatalog;
use App\Domain\Payment\ValueObjects\GatewayResult;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use App\Services\ConfigService;
use App\Services\MtnAccessTokenService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Logique MTN MoMo — paiements entrants uniquement.
 * Les décaissements sont dans DisbursementService.
 */
final class MtnMomoAdapter implements PaymentGatewayAdapterInterface
{
    private const CURRENCY = 'XAF';


    // =========================================================================
    // Interface
    // =========================================================================

    public function provider(): string
    {
        return 'momo';
    }

    public function initiate(Payment $payment, array $context): GatewayResult
    {
        $phone = trim((string) SmsService::normalizePhone(data_get($context, 'phone', '')));

        if ($phone === '') {
            return GatewayResult::failure('Numéro de téléphone manquant pour le paiement MTN MoMo');
        }

        $config      = $this->config();
        $collections = $config['collections'] ?? [];

        if (empty($config['enabled'])) {
            return $this->demoResult($payment, $phone, $config);
        }

        $environment       = $config['environment'];
        $targetEnvironment = $config['target_environment'] ?? ($environment === 'sandbox' ? 'sandbox' : 'mtncongo');
        $baseUrl           = $config['base_url'][$environment] ?? $config['base_url']['sandbox'];
        $referenceId       = null;
        $externalId        = null;
        $partyId           = null;

        try {
            $accessToken = $this->getAccessToken($collections, $baseUrl, 'collections');
            if (! $accessToken) {
                return GatewayResult::failure('Impossible d\'obtenir un token MTN MoMo');
            }

            $partyId   = $this->formatPartyId($phone);
            $validation = $this->validateAccountHolder($baseUrl, $accessToken, $targetEnvironment, $collections, $partyId);

            if (($validation['available'] ?? false) && ! ($validation['active'] ?? false)) {
                return GatewayResult::failure(
                    $validation['message'] ?? 'Le numéro Mobile Money est introuvable ou inactif.'
                );
            }

            $externalId  = 'BD-' . $payment->id . '-' . time();
            $referenceId = Str::uuid()->toString();

            $callbackUrl       = $config['callback_url'] ?? null;
            $useCallbackHeader = (bool) ($config['use_callback_header'] ?? true);

            $headers = [
                'Authorization'            => 'Bearer ' . $accessToken,
                'X-Reference-Id'           => $referenceId,
                'X-Target-Environment'     => $targetEnvironment,
                'Ocp-Apim-Subscription-Key' => $collections['subscription_key'],
                'Content-Type'             => 'application/json',
            ];

            if ($useCallbackHeader && $callbackUrl && $environment !== 'sandbox') {
                $headers['X-Callback-Url'] = $callbackUrl;
            }

            $payload = [
                'amount'       => $this->normalizeAmount($payment->amount),
                'currency'     => self::CURRENCY,
                'externalId'   => $externalId,
                'payer'        => ['partyIdType' => 'MSISDN', 'partyId' => $partyId],
                'payerMessage' => $this->sanitizeText(
                    'Commande ' . ConfigService::getCompanyName() . ' ' . ($payment->order->order_no ?? $payment->id),
                    'Commande Plateforme'
                ),
                'payeeNote'    => $this->sanitizeText('Paiement commande', 'Paiement'),
            ];

            $response = $this->sendRequestToPay($baseUrl, $headers, $payload);

            // Retry sans callback si MTN rejette l'hôte
            if ($this->isInvalidCallbackHostResponse($response)) {
                Log::warning('MtnMomoAdapter: callback host rejeté, retry sans header', [
                    'payment_id'   => $payment->id,
                    'reference_id' => $referenceId,
                ]);
                unset($headers['X-Callback-Url']);
                $referenceId             = Str::uuid()->toString();
                $externalId              = 'BD-' . $payment->id . '-' . time();
                $headers['X-Reference-Id'] = $referenceId;
                $payload['externalId']   = $externalId;
                $response = $this->sendRequestToPay($baseUrl, $headers, $payload);
            }

            $isHtml = stripos((string) $response->header('Content-Type'), 'text/html') !== false;

            if ($response->successful() && ! $isHtml) {
                $meta = [
                    'provider'           => 'momo',
                    'demo'               => false,
                    'amount'             => $this->normalizeAmountInt($payment->amount),
                    'currency'           => $payment->currency,
                    'phone'              => $phone,
                    'operator'           => 'mtn',
                    'environment'        => $environment,
                    'target_environment' => $targetEnvironment,
                    'provider_reference' => $referenceId,
                    'external_id'        => $externalId,
                    'instructions'       => [
                        'Vous allez recevoir une notification sur votre téléphone',
                        'Entrez votre code PIN MTN MoMo pour confirmer',
                        'Le paiement sera validé automatiquement',
                    ],
                    'message'       => 'Veuillez confirmer le paiement sur votre téléphone MTN MoMo',
                    'checkout_data' => $context,
                ];

                Log::info('MtnMomoAdapter: paiement initié', [
                    'payment_id'   => $payment->id,
                    'reference_id' => $referenceId,
                    'external_id'  => $externalId,
                ]);

                return GatewayResult::success($referenceId, $meta);
            }

            $responseJson = $response->json();
            Log::error('MtnMomoAdapter: échec initiation', [
                'payment_id'   => $payment->id,
                'reference_id' => $referenceId,
                'status'       => $response->status(),
                'response'     => $responseJson,
            ]);

            return GatewayResult::failure(
                data_get($responseJson, 'message') ?: 'Erreur lors de l\'initiation du paiement MTN MoMo'
            );
        } catch (\Throwable $e) {
            Log::error('MtnMomoAdapter::initiate exception', [
                'payment_id'   => $payment->id,
                'reference_id' => $referenceId,
                'error'        => $e->getMessage(),
            ]);

            return GatewayResult::failure(
                $e->getMessage() !== '' ? $e->getMessage() : 'Impossible d\'initier le paiement MTN MoMo.'
            );
        }
    }

    public function checkStatus(string $providerReference): GatewayStatus
    {
        $config      = $this->config();
        $collections = $config['collections'] ?? [];

        if (empty($config['enabled'])) {
            return GatewayStatus::paid([], 'DEMO');
        }

        $environment       = $config['environment'];
        $targetEnvironment = $config['target_environment'] ?? ($environment === 'sandbox' ? 'sandbox' : 'mtncongo');
        $baseUrl           = $config['base_url'][$environment] ?? $config['base_url']['sandbox'];

        try {
            $accessToken = $this->getAccessToken($collections, $baseUrl, 'collections');
            if (! $accessToken) {
                return GatewayStatus::unknown('ERROR', ['error' => 'Impossible d\'obtenir un token MTN']);
            }

            $response = Http::withHeaders([
                'Authorization'            => 'Bearer ' . $accessToken,
                'X-Target-Environment'     => $targetEnvironment,
                'Ocp-Apim-Subscription-Key' => $collections['subscription_key'],
            ])->get($baseUrl . '/collection/v1_0/requesttopay/' . $providerReference);

            if ($response->successful()) {
                $data   = $response->json();
                $reason = $data['reason'] ?? null;
                $fm     = $this->buildFailureMeta(['reason' => $reason, 'message' => $reason]);

                return $this->normalizeStatusFromRaw([
                    'status'  => $data['status'],
                    'reason'  => $fm['failure_reason'] ?? $reason,
                    'message' => $fm['failure_message'] ?? $reason,
                    'action'  => $fm['failure_action'] ?? null,
                ]);
            }

            return GatewayStatus::unknown('ERROR', ['error' => 'Impossible de vérifier le statut MTN']);
        } catch (\Throwable $e) {
            Log::error('MtnMomoAdapter::checkStatus exception', [
                'reference' => $providerReference,
                'error'     => $e->getMessage(),
            ]);

            return GatewayStatus::unknown('ERROR', ['error' => $e->getMessage()]);
        }
    }

    public function handleCallback(array $payload): GatewayStatus
    {
        $ref = $payload['referenceId'] ?? $payload['reference'] ?? $payload['externalId'] ?? $payload['external_id'] ?? '';
        if ($ref === '') {
            return GatewayStatus::unknown('MISSING_REF', ['error' => 'Référence absente dans le callback MTN']);
        }

        // Re-vérification auprès du PSP (pattern officiel MTN)
        $status = $this->checkStatus($ref);

        if ($status->isPaid() || $status->isFailed()) {
            return $status;
        }

        // Fallback : utiliser le statut brut du payload si le checkStatus retourne PENDING/UNKNOWN
        $rawStatus = strtoupper((string) ($payload['status'] ?? 'UNKNOWN'));
        return $this->normalizeStatusFromRaw(['status' => $rawStatus], $payload);
    }

    public function verifySignature(array $payload): bool
    {
        // Flux officiel MTN ne signe pas les callbacks.
        return true;
    }

    // =========================================================================
    // Helpers privés
    // =========================================================================

    private function config(): array
    {
        return config('external-services.payments.mtn_momo', []);
    }

    private function getAccessToken(array $credentials, string $baseUrl, string $scope): ?string
    {
        $config            = $this->config();
        $environment       = $config['environment'];
        $targetEnvironment = $config['target_environment'] ?? ($environment === 'sandbox' ? 'sandbox' : 'mtncongo');

        return app(MtnAccessTokenService::class)->getToken($scope, $credentials, $baseUrl, $targetEnvironment);
    }

    private function validateAccountHolder(
        string $baseUrl,
        string $accessToken,
        string $targetEnvironment,
        array $credentials,
        string $partyId,
        string $scope = 'collections'
    ): array {
        try {
            $prefix   = $scope === 'disbursements' ? 'disbursement' : 'collection';
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization'            => 'Bearer ' . $accessToken,
                    'X-Target-Environment'     => $targetEnvironment,
                    'Ocp-Apim-Subscription-Key' => $credentials['subscription_key'] ?? '',
                ])
                ->get($baseUrl . '/' . $prefix . '/v1_0/accountholder/msisdn/' . $partyId . '/active');

            $body = trim($response->body());

            if ($response->successful()) {
                $decoded      = json_decode($body, true);
                $isFalseStr   = strtolower($body) === 'false';
                $active       = true;

                if (is_bool($decoded)) {
                    $active = $decoded;
                } elseif (is_array($decoded) && array_key_exists('result', $decoded)) {
                    $active = (bool) $decoded['result'];
                } elseif (is_array($decoded) && array_key_exists('active', $decoded)) {
                    $active = (bool) $decoded['active'];
                } elseif ($isFalseStr) {
                    $active = false;
                }

                return [
                    'available' => true,
                    'active'    => $active,
                    'message'   => $active ? 'Compte Mobile Money actif' : 'Le numéro Mobile Money est introuvable ou inactif.',
                ];
            }

            $pl     = $response->json();
            $reason = strtoupper((string) (data_get($pl, 'reason') ?? data_get($pl, 'code') ?? ''));

            if (in_array($reason, ['PAYER_NOT_FOUND', 'PAYEE_NOT_FOUND', 'RESOURCE_NOT_FOUND'], true) || $response->status() === 404) {
                return ['available' => true, 'active' => false, 'message' => 'Le numéro Mobile Money est introuvable ou inactif.'];
            }

            return ['available' => false, 'active' => null, 'message' => data_get($pl, 'message') ?: 'Validation du compte MTN indisponible.'];
        } catch (\Throwable $e) {
            Log::warning('MtnMomoAdapter: validateAccountHolder exception', ['party_id' => $partyId, 'error' => $e->getMessage()]);
            return ['available' => false, 'active' => null, 'message' => 'Validation du compte MTN indisponible.'];
        }
    }

    private function sendRequestToPay(string $baseUrl, array $headers, array $payload)
    {
        return Http::timeout(90)->connectTimeout(10)->withHeaders($headers)->post($baseUrl . '/collection/v1_0/requesttopay', $payload);
    }

    private function isInvalidCallbackHostResponse($response): bool
    {
        $pl      = $response->json();
        $code    = strtoupper((string) data_get($pl, 'code'));
        $message = strtoupper((string) data_get($pl, 'message'));

        return $code === 'INVALID_CALLBACK_URL_HOST'
            || str_contains($message, 'CALLBACK URL DOES NOT MATCH THE CONFIGURED VALUE');
    }

    private function formatPartyId(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '2420')) {
            return $digits;
        }
        if (str_starts_with($digits, '242') && strlen($digits) === 11 && preg_match('/^242[456]/', $digits)) {
            return '2420' . substr($digits, 3);
        }
        if (preg_match('/^0[456]\d{7,8}$/', $digits)) {
            return '242' . $digits;
        }
        if (preg_match('/^[456]\d{7,8}$/', $digits)) {
            return '2420' . $digits;
        }

        return ltrim($digits, '+');
    }

    private function sanitizeText(?string $value, string $fallback): string
    {
        $value = Str::ascii((string) $value);
        $value = preg_replace('/[^A-Za-z0-9 ._-]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        $value = trim($value, " ._-");

        return Str::limit($value !== '' ? $value : $fallback, 64, '');
    }

    private function normalizeAmount($amount): string
    {
        return is_numeric($amount) ? (string) max(0, (int) round((float) $amount)) : '0';
    }

    private function normalizeAmountInt($amount): int
    {
        return is_numeric($amount) ? max(0, (int) round((float) $amount)) : 0;
    }

    private function buildFailureMeta(array $providerStatus, array $payload = []): array
    {
        $reason = $this->firstNonEmpty([
            data_get($providerStatus, 'reason'),
            data_get($providerStatus, 'data.reason'),
            $payload['reason'] ?? null,
            data_get($providerStatus, 'error'),
        ]);

        if (in_array($reason, ['Impossible de vérifier le statut', 'Statut récupéré'], true)) {
            $reason = null;
        }

        $catalog = MtnErrorCatalog::lookup($reason ?? '');

        return array_filter([
            'failure_reason'  => $reason ?: $this->firstNonEmpty([
                data_get($providerStatus, 'message'),
                $payload['message'] ?? null,
            ]),
            'failure_message' => $catalog['message'] ?? $this->firstNonEmpty([
                data_get($providerStatus, 'message'),
                $payload['message'] ?? null,
            ]),
            'failure_action'  => $catalog['action'] ?? null,
        ]);
    }

    private function normalizeStatusFromRaw(array $raw, array $callbackPayload = []): GatewayStatus
    {
        $status = strtoupper((string) ($raw['status'] ?? 'UNKNOWN'));

        if (in_array($status, ['SUCCESSFUL', 'SUCCESS', 'PAID', 'COMPLETED', 'CAPTURED', 'APPROVED'], true)) {
            return GatewayStatus::paid($raw, $status);
        }

        if (in_array($status, ['FAILED', 'CANCELLED', 'REJECTED', 'DECLINED', 'EXPIRED'], true)) {
            $fm = $this->buildFailureMeta($raw, $callbackPayload);
            return GatewayStatus::failed($status, $fm['failure_reason'] ?? null, $fm['failure_action'] ?? null, $fm);
        }

        if ($status === 'DEMO') {
            return GatewayStatus::paid($raw, 'DEMO');
        }

        return GatewayStatus::pending($status, $raw);
    }

    private function demoResult(Payment $payment, string $phone, array $config): GatewayResult
    {
        $environment       = $config['environment'] ?? 'sandbox';
        $targetEnvironment = $config['target_environment'] ?? 'sandbox';
        $reference         = 'DEMO-mtn-' . $payment->id . '-' . time();

        Log::info('MtnMomoAdapter: mode démo', ['payment_id' => $payment->id, 'phone' => $phone]);

        return GatewayResult::demo($reference, [
            'provider'           => 'momo',
            'amount'             => $this->normalizeAmountInt($payment->amount),
            'currency'           => $payment->currency,
            'phone'              => $phone,
            'operator'           => 'mtn',
            'environment'        => $environment,
            'target_environment' => $targetEnvironment,
            'provider_reference' => $reference,
            'instructions'       => [
                'Le paiement est simulé en mode démo',
                'Configurez MOMO_COLLECTIONS_* dans .env pour les paiements réels',
            ],
            'message'       => 'Mode démo : aucun paiement réel effectué',
            'checkout_data' => [],
        ]);
    }

    private function firstNonEmpty(array $candidates): ?string
    {
        foreach ($candidates as $c) {
            $v = trim((string) $c);
            if ($v !== '') return $v;
        }
        return null;
    }
}
