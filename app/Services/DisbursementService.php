<?php

namespace App\Services;

use App\Domain\Payment\MtnErrorCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service de décaissement Mobile Money (MTN MoMo) et utilitaires Mobile Money.
 *
 * Responsabilités :
 *  - Détection d'opérateur (06→MTN, 05→Airtel)
 *  - Catalogue d'erreurs MTN et enrichissement des métadonnées d'échec
 *  - Décaissements MTN (direct ou via proxy)
 *
 * Les paiements entrants (initiatePayment, checkStatus, handleCallback)
 * sont gérés par les adapters dans app/Domain/Payment/Adapters/.
 */
class DisbursementService
{
    const CURRENCY = 'XAF';
    const COUNTRY  = 'CG';

    // =========================================================================
    // API publique
    // =========================================================================

    /** @deprecated Utiliser MtnErrorCatalog::all() directement. */
    public static function getMtnReasonCatalog(): array
    {
        return MtnErrorCatalog::all();
    }

    public static function detectOperator(string $phone): string
    {
        $phone  = SmsService::normalizePhone($phone);
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '242')) {
            $digits = substr($digits, 3);
        }
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        if (str_starts_with($digits, '6')) {
            return 'mtn';
        }
        if (str_starts_with($digits, '5')) {
            return 'airtel';
        }

        return 'unknown';
    }

    public static function buildFailureMetadata(string $provider, array $providerStatus = [], array $payload = []): array
    {
        $normalizedProvider = $provider === 'momo' ? 'mtn_momo' : $provider;

        $failureReason = self::firstNonEmptyStatusText([
            data_get($providerStatus, 'reason'),
            data_get($providerStatus, 'data.reason'),
            $payload['reason'] ?? null,
            data_get($providerStatus, 'error'),
            data_get($providerStatus, 'data.error'),
        ]);

        if (in_array($failureReason, ['Impossible de vérifier le statut', 'Statut récupéré'], true)) {
            $failureReason = null;
        }

        if ($normalizedProvider === 'mtn_momo') {
            $catalogEntry = MtnErrorCatalog::lookup($failureReason ?? '');

            return self::filterFailureMetadata([
                'failure_reason'  => $failureReason ?: self::firstNonEmptyStatusText([
                    data_get($providerStatus, 'message'),
                    data_get($providerStatus, 'data.message'),
                    $payload['message'] ?? null,
                ]),
                'failure_message' => $catalogEntry['message'] ?? self::firstNonEmptyStatusText([
                    data_get($providerStatus, 'message'),
                    data_get($providerStatus, 'data.message'),
                    $payload['message'] ?? null,
                ]),
                'failure_action'  => $catalogEntry['action'] ?? null,
            ]);
        }

        return self::filterFailureMetadata([
            'failure_reason'  => $failureReason ?: self::firstNonEmptyStatusText([
                data_get($providerStatus, 'message'),
                data_get($providerStatus, 'data.message'),
                $payload['message'] ?? null,
            ]),
            'failure_message' => self::firstNonEmptyStatusText([
                data_get($providerStatus, 'message'),
                data_get($providerStatus, 'data.message'),
                $payload['message'] ?? null,
            ]),
        ]);
    }

    public static function initiateDisbursement(string $phone, $amount, array $context = []): array
    {
        $phone    = SmsService::normalizePhone($phone);
        $operator = $context['operator'] ?? self::detectOperator($phone);

        switch ($operator) {
            case 'mtn':
                return self::initiateMtnDisbursement($phone, $amount, $context);
            case 'airtel':
                return [
                    'success'  => false,
                    'provider' => 'airtel_money',
                    'error'    => 'Le décaissement automatique Airtel Money n\'est pas encore disponible.',
                ];
            default:
                return [
                    'success'  => false,
                    'provider' => 'unknown',
                    'error'    => 'Impossible de détecter un opérateur Mobile Money compatible pour ce numéro.',
                ];
        }
    }

    public static function checkDisbursementStatus(string $provider, string $reference): array
    {
        switch ($provider) {
            case 'momo':
            case 'mtn':
            case 'mtn_momo':
                return self::checkMtnDisbursementStatus($reference);
            default:
                return ['status' => 'UNKNOWN', 'error' => 'Provider de décaissement non supporté'];
        }
    }

    public static function waitForDisbursementFinalStatus(
        string $provider,
        string $reference,
        int $attempts = 3,
        int $sleepMilliseconds = 1500
    ): array {
        $attempts   = max(1, $attempts);
        $lastStatus = ['status' => 'UNKNOWN', 'error' => 'Aucun contrôle effectué'];

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            if ($attempt > 0 && $sleepMilliseconds > 0) {
                usleep($sleepMilliseconds * 1000);
            }

            $lastStatus       = self::checkDisbursementStatus($provider, $reference);
            $normalizedStatus = strtoupper((string) ($lastStatus['status'] ?? 'UNKNOWN'));

            if (in_array($normalizedStatus, ['SUCCESSFUL', 'SUCCESS', 'PAID', 'COMPLETED'], true)) {
                return $lastStatus;
            }
            if (in_array($normalizedStatus, ['FAILED', 'REJECTED', 'DECLINED', 'CANCELLED', 'EXPIRED', 'ERROR'], true)) {
                return $lastStatus;
            }
        }

        return $lastStatus;
    }

    // =========================================================================
    // Disbursements MTN internes
    // =========================================================================

    protected static function initiateMtnDisbursement(string $phone, $amount, array $context = []): array
    {
        $config = config('external-services.payments.mtn_momo');
        if (self::shouldUseMtnDisbursementProxy($config)) {
            return self::initiateMtnDisbursementViaProxy($phone, $amount, $context, $config);
        }

        $disbursements = $config['disbursements'] ?? [];
        $isConfigured  = ! empty($disbursements['subscription_key'])
            && ! empty($disbursements['api_user'])
            && ! empty($disbursements['api_key']);

        if (! $isConfigured) {
            Log::warning('MTN MoMo disbursement non configuré');
            return ['success' => false, 'provider' => 'mtn_momo', 'error' => 'Les identifiants MTN MoMo disbursements sont incomplets.'];
        }

        $environment       = $config['environment'];
        $targetEnvironment = $config['target_environment'] ?? ($environment === 'sandbox' ? 'sandbox' : 'mtncongo');
        $baseUrl           = $config['base_url'][$environment] ?? $config['base_url']['sandbox'];
        $referenceId       = null;
        $externalId        = null;
        $partyId           = null;

        try {
            $accessToken = self::getMtnAccessToken($disbursements, $baseUrl, 'disbursements');
            if (! $accessToken) {
                throw new \RuntimeException('Impossible d\'obtenir un token MTN disbursements');
            }

            $partyId           = self::formatMtnPartyId($phone);
            $accountValidation = self::validateMtnAccountHolder($baseUrl, $accessToken, $targetEnvironment, $disbursements, $partyId, 'disbursements');

            if (($accountValidation['available'] ?? false) && ! ($accountValidation['active'] ?? false)) {
                return ['success' => false, 'provider' => 'mtn_momo', 'error' => $accountValidation['message'] ?? 'Le bénéficiaire MTN MoMo est introuvable ou inactif.', 'details' => $accountValidation];
            }

            $externalId        = (string) ($context['external_reference'] ?? ('BD-PAYOUT-' . time()));
            $referenceId       = Str::uuid()->toString();
            $callbackUrl       = $config['callback_url'] ?? null;
            $useCallbackHeader = (bool) ($config['use_callback_header'] ?? true);

            $headers = [
                'Authorization'             => 'Bearer ' . $accessToken,
                'X-Reference-Id'            => $referenceId,
                'X-Target-Environment'      => $targetEnvironment,
                'Ocp-Apim-Subscription-Key' => $disbursements['subscription_key'],
                'Content-Type'              => 'application/json',
            ];
            if ($useCallbackHeader && $callbackUrl && $environment !== 'sandbox') {
                $headers['X-Callback-Url'] = $callbackUrl;
            }

            $payload = [
                'amount'       => self::normalizeMtnAmount($amount),
                'currency'     => self::CURRENCY,
                'externalId'   => $externalId,
                'payee'        => ['partyIdType' => 'MSISDN', 'partyId' => $partyId],
                'payerMessage' => self::sanitizeMtnText($context['payer_message'] ?? ('Versement ' . ConfigService::getCompanyName()), 'Versement Plateforme'),
                'payeeNote'    => self::sanitizeMtnText($context['payee_note'] ?? 'Decaissement Plateforme', 'Decaissement Plateforme'),
            ];

            $response     = self::sendMtnTransfer($baseUrl, $headers, $payload);
            $contentType  = (string) $response->header('Content-Type');
            $isHtml       = stripos($contentType, 'text/html') !== false;
            $responseJson = $response->json();

            if (self::isInvalidCallbackHostResponse($response)) {
                unset($headers['X-Callback-Url']);
                $referenceId               = Str::uuid()->toString();
                $headers['X-Reference-Id'] = $referenceId;
                $response                  = self::sendMtnTransfer($baseUrl, $headers, $payload);
                $contentType               = (string) $response->header('Content-Type');
                $isHtml                    = stripos($contentType, 'text/html') !== false;
                $responseJson              = $response->json();
            }

            if ($response->successful() && ! $isHtml) {
                return ['success' => true, 'provider' => 'mtn_momo', 'provider_reference' => $referenceId, 'external_id' => $externalId, 'status' => 'PENDING', 'message' => 'Décaissement MTN MoMo initié'];
            }

            $failureMetadata = self::buildFailureMetadata('mtn_momo', ['reason' => data_get($responseJson, 'reason') ?: data_get($responseJson, 'code'), 'message' => data_get($responseJson, 'message')]);
            if ($response->status() === 403) {
                $failureMetadata = array_merge(self::buildMtnDisbursementForbiddenDetails($targetEnvironment), $failureMetadata);
            }

            return ['success' => false, 'provider' => 'mtn_momo', 'error' => $failureMetadata['failure_message'] ?? data_get($responseJson, 'message') ?? 'Erreur lors de l\'initiation du décaissement MTN', 'details' => array_merge($responseJson ?? [], $failureMetadata, ['status' => $response->status()])];
        } catch (\Exception $e) {
            return ['success' => false, 'provider' => 'mtn_momo', 'error' => $e->getMessage()];
        }
    }

    protected static function checkMtnDisbursementStatus(string $referenceId): array
    {
        $config = config('external-services.payments.mtn_momo');
        if (self::shouldUseMtnDisbursementProxy($config)) {
            return self::checkMtnDisbursementStatusViaProxy($referenceId, $config);
        }

        $disbursements = $config['disbursements'] ?? [];
        $isConfigured  = ! empty($disbursements['subscription_key']) && ! empty($disbursements['api_user']) && ! empty($disbursements['api_key']);
        if (! $isConfigured) {
            return ['status' => 'ERROR', 'error' => 'MTN disbursements non configuré'];
        }

        $environment       = $config['environment'];
        $targetEnvironment = $config['target_environment'] ?? ($environment === 'sandbox' ? 'sandbox' : 'mtncongo');
        $baseUrl           = $config['base_url'][$environment] ?? $config['base_url']['sandbox'];

        try {
            $accessToken = self::getMtnAccessToken($disbursements, $baseUrl, 'disbursements');
            if (! $accessToken) {
                return ['status' => 'ERROR', 'error' => 'Impossible d\'obtenir un token MTN disbursements'];
            }

            $response = Http::withHeaders([
                'Authorization'             => 'Bearer ' . $accessToken,
                'X-Target-Environment'      => $targetEnvironment,
                'Ocp-Apim-Subscription-Key' => $disbursements['subscription_key'],
            ])->get($baseUrl . '/disbursement/v1_0/transfer/' . $referenceId);

            if ($response->successful()) {
                $data            = $response->json();
                $reason          = $data['reason'] ?? null;
                $failureMetadata = self::buildFailureMetadata('mtn_momo', ['reason' => $reason, 'message' => $data['message'] ?? $reason]);
                return ['status' => $data['status'] ?? 'UNKNOWN', 'provider' => 'mtn_momo', 'amount' => $data['amount'] ?? null, 'payee' => $data['payee']['partyId'] ?? null, 'reason' => $failureMetadata['failure_reason'] ?? $reason, 'message' => $failureMetadata['failure_message'] ?? ($data['message'] ?? $reason), 'action' => $failureMetadata['failure_action'] ?? null];
            }

            $pl              = $response->json();
            $failureMetadata = self::buildFailureMetadata('mtn_momo', ['reason' => data_get($pl, 'reason') ?: data_get($pl, 'code'), 'message' => data_get($pl, 'message')]);
            return ['status' => 'ERROR', 'error' => $failureMetadata['failure_message'] ?? data_get($pl, 'message') ?? 'Impossible de vérifier le statut du décaissement', 'reason' => $failureMetadata['failure_reason'] ?? null, 'action' => $failureMetadata['failure_action'] ?? null];
        } catch (\Exception $e) {
            return ['status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Helpers internes MTN
    // =========================================================================

    protected static function getMtnAccessToken(array $credentials, string $baseUrl, string $scope = 'collections'): ?string
    {
        $cacheKey = 'mtn_momo_' . $scope . '_access_token';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        if (empty($credentials['api_user']) || empty($credentials['api_key']) || empty($credentials['subscription_key'])) {
            return null;
        }
        try {
            $tokenPath = $scope === 'disbursements' ? '/disbursement/token/' : '/collection/token/';
            $response  = Http::withBasicAuth($credentials['api_user'], $credentials['api_key'])
                ->timeout(30)->connectTimeout(10)
                ->withHeaders(['Ocp-Apim-Subscription-Key' => $credentials['subscription_key'], 'Content-Length' => '0'])
                ->withBody('', 'application/json')
                ->post($baseUrl . $tokenPath);
            if ($response->successful()) {
                $data      = $response->json();
                $token     = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;
                Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));
                return $token;
            }
            return null;
        } catch (\Exception $e) {
            Log::error('MTN access token exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected static function validateMtnAccountHolder(string $baseUrl, string $accessToken, string $targetEnvironment, array $credentials, string $partyId, string $scope = 'collections'): array
    {
        try {
            $prefix   = $scope === 'disbursements' ? 'disbursement' : 'collection';
            $response = Http::timeout(30)->connectTimeout(10)
                ->withHeaders(['Authorization' => 'Bearer ' . $accessToken, 'X-Target-Environment' => $targetEnvironment, 'Ocp-Apim-Subscription-Key' => $credentials['subscription_key'] ?? ''])
                ->get($baseUrl . '/' . $prefix . '/v1_0/accountholder/msisdn/' . $partyId . '/active');

            $body = trim($response->body());
            if ($response->successful()) {
                $decoded    = json_decode($body, true);
                $isFalseStr = strtolower($body) === 'false';
                $active     = true;
                if (is_bool($decoded)) { $active = $decoded; }
                elseif (is_array($decoded) && array_key_exists('result', $decoded)) { $active = (bool) $decoded['result']; }
                elseif (is_array($decoded) && array_key_exists('active', $decoded)) { $active = (bool) $decoded['active']; }
                elseif ($isFalseStr) { $active = false; }
                return ['available' => true, 'active' => $active, 'message' => $active ? 'Compte Mobile Money actif' : 'Le numéro Mobile Money est introuvable ou inactif.'];
            }

            $pl     = $response->json();
            $reason = strtoupper((string) (data_get($pl, 'reason') ?? data_get($pl, 'code') ?? ''));
            if (in_array($reason, ['PAYER_NOT_FOUND', 'PAYEE_NOT_FOUND', 'RESOURCE_NOT_FOUND'], true) || $response->status() === 404) {
                return ['available' => true, 'active' => false, 'message' => 'Le numéro Mobile Money est introuvable ou inactif.'];
            }
            return ['available' => false, 'active' => null, 'message' => data_get($pl, 'message') ?: 'Validation du compte MTN indisponible.'];
        } catch (\Throwable $e) {
            return ['available' => false, 'active' => null, 'message' => 'Validation du compte MTN indisponible.'];
        }
    }

    protected static function sendMtnTransfer(string $baseUrl, array $headers, array $payload)
    {
        return Http::timeout(90)->connectTimeout(10)->withHeaders($headers)->post($baseUrl . '/disbursement/v1_0/transfer', $payload);
    }

    protected static function shouldUseMtnDisbursementProxy(array $config): bool
    {
        $proxy = $config['disbursement_proxy'] ?? [];
        return ! empty($proxy['enabled']) && ! empty($proxy['url']);
    }

    protected static function buildDisbursementProxyHeaders(array $proxy): array
    {
        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'X-BantuDelice-Source' => 'bantudelice'];
        if (! empty($proxy['token'])) { $headers['Authorization'] = 'Bearer ' . $proxy['token']; }
        if (! empty($proxy['source_ip'])) { $headers['X-Expected-Source-IP'] = (string) $proxy['source_ip']; }
        return $headers;
    }

    protected static function initiateMtnDisbursementViaProxy(string $phone, $amount, array $context, array $config): array
    {
        $proxy       = $config['disbursement_proxy'] ?? [];
        $proxyUrl    = rtrim((string) ($proxy['url'] ?? ''), '/');
        $referenceId = Str::uuid()->toString();
        $externalId  = (string) ($context['external_reference'] ?? ('BD-PAYOUT-' . time()));
        $partyId     = self::formatMtnPartyId($phone);

        $payload = [
            'operator'           => 'mtn',
            'phone'              => $phone,
            'party_id'           => $partyId,
            'amount'             => self::normalizeMtnAmount($amount),
            'currency'           => self::CURRENCY,
            'reference_id'       => $referenceId,
            'external_id'        => $externalId,
            'payer_message'      => self::sanitizeMtnText($context['payer_message'] ?? ('Versement ' . ConfigService::getCompanyName()), 'Versement Plateforme'),
            'payee_note'         => self::sanitizeMtnText($context['payee_note'] ?? 'Decaissement Plateforme', 'Decaissement Plateforme'),
            'target_environment' => $config['target_environment'] ?? null,
            'callback_url'       => $config['callback_url'] ?? null,
            'context'            => $context,
        ];

        try {
            $response = Http::timeout(max(5, (int) ($proxy['timeout'] ?? 90)))->connectTimeout(10)->withHeaders(self::buildDisbursementProxyHeaders($proxy))->post($proxyUrl, $payload);
            $body     = $response->json() ?? [];

            if ($response->successful() && data_get($body, 'success', true) !== false) {
                return ['success' => true, 'provider' => 'mtn_momo', 'provider_reference' => data_get($body, 'provider_reference') ?? data_get($body, 'reference_id') ?? $referenceId, 'external_id' => data_get($body, 'external_id') ?? $externalId, 'status' => strtoupper((string) (data_get($body, 'status') ?? 'PENDING')), 'message' => data_get($body, 'message') ?? 'Décaissement relayé vers la gateway proxy'];
            }
            return ['success' => false, 'provider' => 'mtn_momo', 'error' => data_get($body, 'error') ?? data_get($body, 'message') ?? 'La gateway proxy a refusé le décaissement.', 'details' => array_merge($body, ['status' => $response->status(), 'proxy_url' => $proxyUrl])];
        } catch (\Throwable $e) {
            return ['success' => false, 'provider' => 'mtn_momo', 'error' => $e->getMessage()];
        }
    }

    protected static function buildDisbursementProxyStatusUrl(array $proxy, string $referenceId): string
    {
        $statusUrl = (string) ($proxy['status_url'] ?? '');
        if ($statusUrl !== '') {
            return str_contains($statusUrl, '{reference}')
                ? str_replace('{reference}', rawurlencode($referenceId), $statusUrl)
                : rtrim($statusUrl, '/') . '/' . rawurlencode($referenceId);
        }
        return rtrim((string) ($proxy['url'] ?? ''), '/') . '/' . rawurlencode($referenceId);
    }

    protected static function checkMtnDisbursementStatusViaProxy(string $referenceId, array $config): array
    {
        $proxy     = $config['disbursement_proxy'] ?? [];
        $statusUrl = self::buildDisbursementProxyStatusUrl($proxy, $referenceId);
        try {
            $response = Http::timeout(max(5, (int) ($proxy['timeout'] ?? 90)))->connectTimeout(10)->withHeaders(self::buildDisbursementProxyHeaders($proxy))->get($statusUrl);
            $body     = $response->json() ?? [];
            if ($response->successful()) {
                return ['status' => strtoupper((string) (data_get($body, 'status') ?? 'UNKNOWN')), 'provider' => 'mtn_momo', 'amount' => data_get($body, 'amount'), 'payee' => data_get($body, 'payee') ?? data_get($body, 'party_id'), 'reason' => data_get($body, 'reason'), 'message' => data_get($body, 'message') ?? 'Statut récupéré via la gateway proxy', 'action' => data_get($body, 'action')];
            }
            return ['status' => 'ERROR', 'error' => data_get($body, 'error') ?? data_get($body, 'message') ?? 'Impossible de vérifier le statut via proxy'];
        } catch (\Throwable $e) {
            return ['status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }

    protected static function buildMtnDisbursementForbiddenDetails(string $targetEnvironment): array
    {
        $message = 'Le compte MTN disbursement répond mais refuse l\'opération de transfert.';
        $action  = 'Vérifiez avec MTN que votre ligne est autorisée aux transferts API.';
        if ($targetEnvironment === 'mtncongo') {
            $message = 'MTN Congo refuse l\'appel API de décaissement.';
            $action  = 'La ligne disbursement semble limitée au bulk payment via portail MTN / CSV.';
        }
        return ['failure_reason' => 'FORBIDDEN', 'failure_message' => $message, 'failure_action' => $action];
    }

    protected static function isInvalidCallbackHostResponse($response): bool
    {
        $pl      = $response->json();
        $code    = strtoupper((string) data_get($pl, 'code'));
        $message = strtoupper((string) data_get($pl, 'message'));
        return $code === 'INVALID_CALLBACK_URL_HOST' || str_contains($message, 'CALLBACK URL DOES NOT MATCH THE CONFIGURED VALUE');
    }

    protected static function formatMtnPartyId(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '2420')) { return $digits; }
        if (str_starts_with($digits, '242') && strlen($digits) === 11 && preg_match('/^242[456]/', $digits)) { return '2420' . substr($digits, 3); }
        if (preg_match('/^0[456]\d{7,8}$/', $digits)) { return '242' . $digits; }
        if (preg_match('/^[456]\d{7,8}$/', $digits)) { return '2420' . $digits; }
        return ltrim($digits, '+');
    }

    protected static function sanitizeMtnText(?string $value, string $fallback): string
    {
        $value = Str::ascii((string) $value);
        $value = preg_replace('/[^A-Za-z0-9 ._-]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        $value = trim($value, " ._-");
        return Str::limit($value !== '' ? $value : $fallback, 64, '');
    }

    protected static function normalizeMtnAmount($amount): string
    {
        return is_numeric($amount) ? (string) max(0, (int) round((float) $amount)) : '0';
    }

    protected static function firstNonEmptyStatusText(array $candidates, ?string $default = null): ?string
    {
        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') { return $value; }
        }
        return $default;
    }

    protected static function filterFailureMetadata(array $metadata): array
    {
        return array_filter($metadata, static fn($v) => $v !== null && $v !== '');
    }
}
