<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Obtention centralisée des tokens MTN MoMo.
 *
 * Corrige l'absence de X-Target-Environment sur les endpoints /token/,
 * requis depuis la migration MTN MoMo (juin 2026).
 */
final class MtnAccessTokenService
{
    private const TOKEN_PATHS = [
        'collections'   => '/collection/token/',
        'disbursements' => '/disbursement/token/',
        'remittances'   => '/remittance/token/',
    ];

    /**
     * Retourne un token valide, mis en cache par (produit, target_env, api_user).
     *
     * @param  string  $product           collections | disbursements | remittances
     * @param  array   $credentials       ['api_user', 'api_key', 'subscription_key']
     * @param  string  $baseUrl
     * @param  string  $targetEnvironment sandbox | mtncongo | …
     * @return string|null                null si échec ou config incomplète
     */
    public function getToken(
        string $product,
        array $credentials,
        string $baseUrl,
        string $targetEnvironment
    ): ?string {
        if (! isset(self::TOKEN_PATHS[$product])) {
            Log::warning('MtnAccessTokenService: produit inconnu', ['product' => $product]);
            return null;
        }

        if (
            empty($credentials['api_user'])
            || empty($credentials['api_key'])
            || empty($credentials['subscription_key'])
        ) {
            Log::warning('MtnAccessTokenService: configuration incomplète', [
                'product'            => $product,
                'target_environment' => $targetEnvironment,
                'missing'            => array_keys(array_filter([
                    'api_user'         => empty($credentials['api_user']),
                    'api_key'          => empty($credentials['api_key']),
                    'subscription_key' => empty($credentials['subscription_key']),
                ])),
            ]);
            return null;
        }

        $cacheKey = $this->buildCacheKey($product, $targetEnvironment, $credentials['api_user']);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        return $this->fetchAndCache($product, $credentials, $baseUrl, $targetEnvironment, $cacheKey);
    }

    private function buildCacheKey(string $product, string $targetEnvironment, string $apiUser): string
    {
        return sprintf(
            'mtn_momo:%s:%s:%s:access_token',
            $product,
            $targetEnvironment,
            hash('sha256', $apiUser)
        );
    }

    private function fetchAndCache(
        string $product,
        array $credentials,
        string $baseUrl,
        string $targetEnvironment,
        string $cacheKey
    ): ?string {
        $tokenPath = self::TOKEN_PATHS[$product];

        try {
            $response = Http::withBasicAuth($credentials['api_user'], $credentials['api_key'])
                ->timeout(30)
                ->connectTimeout(10)
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $credentials['subscription_key'],
                    'X-Target-Environment'      => $targetEnvironment,
                    'Content-Type'              => 'application/json',
                    'Content-Length'            => '0',
                ])
                ->withBody('', 'application/json')
                ->post($baseUrl . $tokenPath);

            if ($response->successful()) {
                $data      = $response->json();
                $token     = $data['access_token'] ?? null;
                $expiresIn = (int) ($data['expires_in'] ?? 3600);

                if (! $token) {
                    Log::error('MtnAccessTokenService: réponse sans access_token', [
                        'product'            => $product,
                        'target_environment' => $targetEnvironment,
                        'url'                => $baseUrl . $tokenPath,
                        'http_status'        => $response->status(),
                    ]);
                    return null;
                }

                Cache::put($cacheKey, $token, now()->addSeconds(max(60, $expiresIn - 60)));
                return $token;
            }

            Log::error('MtnAccessTokenService: échec token', [
                'product'            => $product,
                'target_environment' => $targetEnvironment,
                'url'                => $baseUrl . $tokenPath,
                'http_status'        => $response->status(),
                'mtn_reason'         => $response->json('reason') ?? $response->json('message'),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('MtnAccessTokenService: exception', [
                'product'            => $product,
                'target_environment' => $targetEnvironment,
                'url'                => $baseUrl . $tokenPath,
                'error'              => $e->getMessage(),
            ]);
            return null;
        }
    }
}
