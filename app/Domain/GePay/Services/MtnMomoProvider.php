<?php

namespace App\Domain\GePay\Services;

use App\Domain\GePay\Contracts\PaymentProviderInterface;
use App\Domain\GePay\Data\ProviderResult;
use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MtnMomoProvider implements PaymentProviderInterface
{
    public function code(): string
    {
        return 'mtn_momo';
    }

    public function supports(TransactionType $type): bool
    {
        return in_array($type, [TransactionType::COLLECTION, TransactionType::DISBURSEMENT], true);
    }

    public function initiate(GePayTransaction $transaction): ProviderResult
    {
        if (! config('gepay.providers.mtn_momo.enabled')) {
            return new ProviderResult(TransactionStatus::FAILED, failureCode: 'PROVIDER_DISABLED', failureMessage: "MTN MoMo n'est pas activé dans GePay.");
        }

        return match ($transaction->type) {
            TransactionType::COLLECTION => $this->initiateCollection($transaction),
            TransactionType::DISBURSEMENT => $this->initiateDisbursement($transaction),
            default => new ProviderResult(TransactionStatus::FAILED, failureCode: 'UNSUPPORTED_CAPABILITY', failureMessage: 'Produit MTN non supporté.'),
        };
    }

    public function checkStatus(GePayTransaction $transaction): ProviderResult
    {
        if (! $transaction->provider_reference) {
            return new ProviderResult(TransactionStatus::UNKNOWN, failureCode: 'MISSING_PROVIDER_REFERENCE', failureMessage: 'Référence fournisseur absente.');
        }

        $product = $transaction->type === TransactionType::DISBURSEMENT ? 'disbursements' : 'collections';
        $credentials = $this->credentials($product);
        $token = $this->getAccessToken($product, $credentials);
        $baseUrl = $this->baseUrl();
        $prefix = $product === 'disbursements' ? 'disbursement' : 'collection';
        $resource = $product === 'disbursements' ? 'transfer' : 'requesttopay';

        try {
            $response = Http::timeout(30)->connectTimeout(10)->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'X-Target-Environment' => $this->targetEnvironment(),
                'Ocp-Apim-Subscription-Key' => $credentials['subscription_key'],
            ])->get($baseUrl."/{$prefix}/v1_0/{$resource}/{$transaction->provider_reference}");

            if (! $response->successful()) {
                return new ProviderResult(TransactionStatus::UNKNOWN, failureCode: 'STATUS_HTTP_'.$response->status(), failureMessage: 'Impossible de vérifier le statut MTN.', metadata: ['http_status' => $response->status()]);
            }

            $body = $response->json() ?? [];
            return $this->normalizeStatus((string) ($body['status'] ?? 'UNKNOWN'), $body);
        } catch (\Throwable $exception) {
            return new ProviderResult(TransactionStatus::UNKNOWN, failureCode: 'STATUS_EXCEPTION', failureMessage: $exception->getMessage());
        }
    }

    private function initiateCollection(GePayTransaction $transaction): ProviderResult
    {
        return $this->initiateProduct($transaction, 'collections', '/collection/v1_0/requesttopay', 'payer');
    }

    private function initiateDisbursement(GePayTransaction $transaction): ProviderResult
    {
        return $this->initiateProduct($transaction, 'disbursements', '/disbursement/v1_0/transfer', 'payee');
    }

    private function initiateProduct(GePayTransaction $transaction, string $product, string $path, string $partyField): ProviderResult
    {
        $credentials = $this->credentials($product);
        $token = $this->getAccessToken($product, $credentials);
        $providerReference = Str::uuid()->toString();
        $metadata = $transaction->metadata ?? [];
        $externalId = $transaction->external_reference;

        $payload = [
            'amount' => (string) $transaction->amount,
            'currency' => $transaction->currency,
            'externalId' => $externalId,
            $partyField => [
                'partyIdType' => 'MSISDN',
                'partyId' => $this->formatPartyId((string) $transaction->phone),
            ],
            'payerMessage' => $this->sanitizeText((string) ($metadata['payer_message'] ?? 'Paiement GePay'), 'Paiement GePay'),
            'payeeNote' => $this->sanitizeText((string) ($metadata['payee_note'] ?? 'Transaction GePay'), 'Transaction GePay'),
        ];

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'X-Reference-Id' => $providerReference,
            'X-Target-Environment' => $this->targetEnvironment(),
            'Ocp-Apim-Subscription-Key' => $credentials['subscription_key'],
            'Content-Type' => 'application/json',
        ];

        $callbackUrl = config('gepay.providers.mtn_momo.callback_url');
        if ($callbackUrl && config('gepay.providers.mtn_momo.environment') !== 'sandbox') {
            $headers['X-Callback-Url'] = $callbackUrl;
        }

        try {
            $response = Http::timeout(90)->connectTimeout(10)->withHeaders($headers)->post($this->baseUrl().$path, $payload);
            $body = $response->json() ?? [];

            if ($response->successful()) {
                return new ProviderResult(TransactionStatus::PENDING, providerReference: $providerReference, metadata: [
                    'http_status' => $response->status(),
                    'external_id' => $externalId,
                    'target_environment' => $this->targetEnvironment(),
                ]);
            }

            return new ProviderResult(
                TransactionStatus::FAILED,
                providerReference: $providerReference,
                failureCode: (string) ($body['reason'] ?? $body['code'] ?? 'HTTP_'.$response->status()),
                failureMessage: (string) ($body['message'] ?? 'MTN a refusé la transaction.'),
                metadata: ['http_status' => $response->status()]
            );
        } catch (\Throwable $exception) {
            return new ProviderResult(
                TransactionStatus::UNKNOWN,
                providerReference: $providerReference,
                failureCode: 'INITIATION_EXCEPTION',
                failureMessage: $exception->getMessage()
            );
        }
    }

    private function getAccessToken(string $product, array $credentials): string
    {
        foreach (['subscription_key', 'api_user', 'api_key'] as $required) {
            if (empty($credentials[$required])) {
                throw new RuntimeException("Configuration MTN {$product} incomplète.");
            }
        }

        $targetEnvironment = $this->targetEnvironment();
        $cacheKey = 'gepay:mtn:token:'.$product.':'.$targetEnvironment.':'.hash('sha256', (string) $credentials['api_user']);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $tokenPath = match ($product) {
            'collections' => '/collection/token/',
            'disbursements' => '/disbursement/token/',
            default => throw new RuntimeException('Produit MTN non supporté pour le token.'),
        };

        $response = Http::withBasicAuth($credentials['api_user'], $credentials['api_key'])
            ->timeout(30)
            ->connectTimeout(10)
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => $credentials['subscription_key'],
                'X-Target-Environment' => $targetEnvironment,
                'Content-Type' => 'application/json',
                'Content-Length' => '0',
            ])
            ->withBody('', 'application/json')
            ->post($this->baseUrl().$tokenPath);

        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            throw new RuntimeException("Impossible d'obtenir le token MTN {$product}.");
        }

        $token = (string) $response->json('access_token');
        $expiresIn = max(30, (int) $response->json('expires_in', 3600));
        Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));

        return $token;
    }

    private function normalizeStatus(string $rawStatus, array $metadata): ProviderResult
    {
        $status = strtoupper(trim($rawStatus));

        return match ($status) {
            'SUCCESSFUL', 'SUCCESS', 'PAID', 'COMPLETED' => new ProviderResult(TransactionStatus::SUCCESSFUL, metadata: $metadata),
            'FAILED', 'REJECTED', 'DECLINED' => new ProviderResult(TransactionStatus::FAILED, failureCode: (string) ($metadata['reason'] ?? $status), failureMessage: (string) ($metadata['message'] ?? $metadata['reason'] ?? 'Transaction échouée.'), metadata: $metadata),
            'CANCELLED', 'CANCELED' => new ProviderResult(TransactionStatus::CANCELLED, metadata: $metadata),
            'EXPIRED' => new ProviderResult(TransactionStatus::EXPIRED, metadata: $metadata),
            'PENDING', 'ONGOING' => new ProviderResult(TransactionStatus::PENDING, metadata: $metadata),
            default => new ProviderResult(TransactionStatus::UNKNOWN, failureCode: $status, metadata: $metadata),
        };
    }

    private function credentials(string $product): array
    {
        return (array) config('gepay.providers.mtn_momo.'.$product, []);
    }

    private function baseUrl(): string
    {
        $environment = (string) config('gepay.providers.mtn_momo.environment', 'sandbox');
        return rtrim((string) config('gepay.providers.mtn_momo.base_url.'.$environment), '/');
    }

    private function targetEnvironment(): string
    {
        return (string) config('gepay.providers.mtn_momo.target_environment', 'sandbox');
    }

    private function formatPartyId(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '2420')) {
            return $digits;
        }
        if (str_starts_with($digits, '242')) {
            return '2420'.substr($digits, 3);
        }
        if (str_starts_with($digits, '0')) {
            return '242'.$digits;
        }
        return '2420'.$digits;
    }

    private function sanitizeText(string $value, string $fallback): string
    {
        $value = Str::ascii($value);
        $value = preg_replace('/[^A-Za-z0-9 ._-]+/', ' ', $value) ?? '';
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        return Str::limit($value !== '' ? $value : $fallback, 64, '');
    }
}
