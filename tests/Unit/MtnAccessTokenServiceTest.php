<?php

namespace Tests\Unit;

use App\Services\MtnAccessTokenService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Vérifie que X-Target-Environment est bien présent sur tous les appels /token/
 * et que le cache distingue produit × target_environment × api_user.
 */
class MtnAccessTokenServiceTest extends TestCase
{
    private MtnAccessTokenService $service;

    private const BASE_URL     = 'https://sandbox.momodeveloper.mtn.com';
    private const CREDENTIALS  = [
        'api_user'         => 'test-api-user',
        'api_key'          => 'test-api-key',
        'subscription_key' => 'test-subscription-key',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MtnAccessTokenService();
        Cache::flush();
    }

    // =========================================================================
    // Collections — X-Target-Environment présent
    // =========================================================================

    /** @test */
    public function collections_token_request_includes_x_target_environment_mtncongo()
    {
        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok-coll', 'expires_in' => 3600], 200),
        ]);

        $token = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertSame('tok-coll', $token);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/collection/token/')
                && $request->hasHeader('X-Target-Environment', 'mtncongo')
                && $request->hasHeader('Ocp-Apim-Subscription-Key', 'test-subscription-key');
        });
    }

    /** @test */
    public function collections_token_request_includes_x_target_environment_sandbox()
    {
        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok-sandbox', 'expires_in' => 3600], 200),
        ]);

        $token = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'sandbox');

        $this->assertSame('tok-sandbox', $token);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/collection/token/')
                && $request->hasHeader('X-Target-Environment', 'sandbox');
        });
    }

    // =========================================================================
    // Disbursements — X-Target-Environment présent
    // =========================================================================

    /** @test */
    public function disbursements_token_request_includes_x_target_environment()
    {
        Http::fake([
            '*/disbursement/token/' => Http::response(['access_token' => 'tok-disb', 'expires_in' => 3600], 200),
        ]);

        $token = $this->service->getToken('disbursements', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertSame('tok-disb', $token);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/disbursement/token/')
                && $request->hasHeader('X-Target-Environment', 'mtncongo')
                && $request->hasHeader('Ocp-Apim-Subscription-Key', 'test-subscription-key');
        });
    }

    // =========================================================================
    // Remittances
    // =========================================================================

    /** @test */
    public function remittances_token_request_includes_x_target_environment()
    {
        Http::fake([
            '*/remittance/token/' => Http::response(['access_token' => 'tok-remi', 'expires_in' => 3600], 200),
        ]);

        $token = $this->service->getToken('remittances', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertSame('tok-remi', $token);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/remittance/token/')
                && $request->hasHeader('X-Target-Environment', 'mtncongo');
        });
    }

    // =========================================================================
    // Cache — isolation par (produit × target_env × api_user)
    // =========================================================================

    /** @test */
    public function collections_and_disbursements_use_different_cache_keys()
    {
        Http::fake([
            '*/collection/token/'    => Http::response(['access_token' => 'tok-coll2', 'expires_in' => 3600], 200),
            '*/disbursement/token/'  => Http::response(['access_token' => 'tok-disb2', 'expires_in' => 3600], 200),
        ]);

        $collToken  = $this->service->getToken('collections',   self::CREDENTIALS, self::BASE_URL, 'mtncongo');
        $disbToken  = $this->service->getToken('disbursements', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertSame('tok-coll2', $collToken);
        $this->assertSame('tok-disb2', $disbToken);
        $this->assertNotSame($collToken, $disbToken);

        Http::assertSentCount(2);
    }

    /** @test */
    public function sandbox_and_production_targets_use_different_cache_keys()
    {
        Http::fake([
            '*/collection/token/' => Http::sequence()
                ->push(['access_token' => 'tok-mtncongo', 'expires_in' => 3600], 200)
                ->push(['access_token' => 'tok-sandbox',  'expires_in' => 3600], 200),
        ]);

        $prodToken    = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'mtncongo');
        $sandboxToken = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'sandbox');

        $this->assertSame('tok-mtncongo', $prodToken);
        $this->assertSame('tok-sandbox',  $sandboxToken);
        Http::assertSentCount(2);
    }

    /** @test */
    public function cached_token_avoids_second_http_call()
    {
        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok-cached', 'expires_in' => 3600], 200),
        ]);

        $first  = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'mtncongo');
        $second = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    // =========================================================================
    // Erreurs
    // =========================================================================

    /** @test */
    public function returns_null_when_credentials_are_incomplete()
    {
        Http::fake();

        $token = $this->service->getToken('collections', ['api_user' => '', 'api_key' => '', 'subscription_key' => ''], self::BASE_URL, 'mtncongo');

        $this->assertNull($token);
        Http::assertNothingSent();
    }

    /** @test */
    public function returns_null_on_mtn_401_response()
    {
        Http::fake([
            '*/collection/token/' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $token = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertNull($token);
    }

    /** @test */
    public function returns_null_on_mtn_403_response()
    {
        Http::fake([
            '*/collection/token/' => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $token = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertNull($token);
    }

    /** @test */
    public function returns_null_on_mtn_500_response()
    {
        Http::fake([
            '*/collection/token/' => Http::response(['message' => 'Internal Server Error'], 500),
        ]);

        $token = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertNull($token);
    }

    /** @test */
    public function returns_null_when_response_has_no_access_token_field()
    {
        Http::fake([
            '*/collection/token/' => Http::response(['token_type' => 'Bearer'], 200),
        ]);

        $token = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertNull($token);
    }

    /** @test */
    public function returns_null_for_unknown_product()
    {
        Http::fake();

        $token = $this->service->getToken('unknown_product', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertNull($token);
        Http::assertNothingSent();
    }

    /** @test */
    public function returns_null_on_connection_timeout()
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $token = $this->service->getToken('collections', self::CREDENTIALS, self::BASE_URL, 'mtncongo');

        $this->assertNull($token);
    }
}
