<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayCollectionTest extends TestCase
{
    use RefreshDatabase;

    private GePayClient $client;
    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->secret = Str::random(64);
        $this->client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Collection Test',
            'api_key' => 'gpk_col',
            'api_secret' => $this->secret,
            'capabilities' => ['collection'],
            'is_active' => true,
        ]);

        config([
            'gepay.providers.mtn_momo.enabled' => true,
            'gepay.providers.mtn_momo.environment' => 'production',
            'gepay.providers.mtn_momo.target_environment' => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production' => 'https://mtn.test',
            'gepay.providers.mtn_momo.callback_url' => null,
            'gepay.providers.mtn_momo.collections' => [
                'subscription_key' => 'col-sub',
                'api_user' => 'col-user',
                'api_key' => 'col-key',
            ],
        ]);
    }

    private function headers(string $method, string $uri, string $body, string $iKey = ''): array
    {
        $ts = (string) now()->timestamp;
        $headers = [
            'X-GePay-Key' => $this->client->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => GePaySigner::sign($this->secret, $ts, $method, $uri, $body),
        ];
        if ($iKey !== '') {
            $headers['Idempotency-Key'] = $iKey;
        }
        return $headers;
    }

    public function test_202_accepted_produces_pending_transaction(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payload = ['amount' => 5000, 'phone' => '24206001234', 'external_reference' => 'COL-001'];
        $uri = '/api/gepay/v1/collections';
        $body = json_encode($payload) ?: '';

        $resp = $this->withHeaders($this->headers('POST', $uri, $body, 'ikey-c001'))
            ->postJson($uri, $payload)
            ->assertStatus(202);

        $this->assertSame('pending', $resp->json('transaction.status'));
        $this->assertNotNull($resp->json('transaction.reference'));
    }

    public function test_token_request_sends_x_target_environment_mtncongo(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payload = ['amount' => 5000, 'phone' => '24206001234', 'external_reference' => 'COL-002'];
        $uri = '/api/gepay/v1/collections';
        $body = json_encode($payload) ?: '';

        $this->withHeaders($this->headers('POST', $uri, $body, 'ikey-c002'))
            ->postJson($uri, $payload);

        Http::assertSent(fn (Request $req) =>
            str_contains($req->url(), '/collection/token/')
            && $req->hasHeader('X-Target-Environment', 'mtncongo')
        );
    }

    public function test_mtn_rejection_produces_failed_transaction(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response(['reason' => 'PAYER_NOT_FOUND', 'message' => 'Payer not found.'], 404),
        ]);

        $payload = ['amount' => 5000, 'phone' => '24206001234', 'external_reference' => 'COL-003'];
        $uri = '/api/gepay/v1/collections';
        $body = json_encode($payload) ?: '';

        $this->withHeaders($this->headers('POST', $uri, $body, 'ikey-c003'))
            ->postJson($uri, $payload)
            ->assertStatus(422)
            ->assertJsonPath('transaction.status', 'failed');
    }

    public function test_mtn_timeout_produces_unknown_not_failed(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::throw(new \Illuminate\Http\Client\ConnectionException('cURL error 28: Timeout')),
        ]);

        $payload = ['amount' => 5000, 'phone' => '24206001234', 'external_reference' => 'COL-004'];
        $uri = '/api/gepay/v1/collections';
        $body = json_encode($payload) ?: '';

        $resp = $this->withHeaders($this->headers('POST', $uri, $body, 'ikey-c004'))
            ->postJson($uri, $payload)
            ->assertStatus(202);

        $this->assertSame('unknown', $resp->json('transaction.status'));
    }

    public function test_cached_token_avoids_second_token_request(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok1', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $uri = '/api/gepay/v1/collections';
        foreach (['COL-005', 'COL-006'] as $i => $ref) {
            $payload = ['amount' => 5000, 'phone' => '24206001234', 'external_reference' => $ref];
            $body = json_encode($payload) ?: '';
            $this->withHeaders($this->headers('POST', $uri, $body, "ikey-c00{$i}5"))
                ->postJson($uri, $payload);
        }

        Http::assertSentCount(3); // 1 token + 2 requesttopay
    }

    public function test_phone_is_never_exposed_in_response(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payload = ['amount' => 5000, 'phone' => '24206009999', 'external_reference' => 'COL-007'];
        $uri = '/api/gepay/v1/collections';
        $body = json_encode($payload) ?: '';

        $resp = $this->withHeaders($this->headers('POST', $uri, $body, 'ikey-c007'))
            ->postJson($uri, $payload)
            ->assertStatus(202);

        $content = json_encode($resp->json());
        $this->assertStringNotContainsString('24206009999', (string) $content);
    }

    public function test_provider_disabled_returns_error(): void
    {
        config(['gepay.providers.mtn_momo.enabled' => false]);

        $payload = ['amount' => 5000, 'phone' => '24206001234', 'external_reference' => 'COL-008'];
        $uri = '/api/gepay/v1/collections';
        $body = json_encode($payload) ?: '';

        $this->withHeaders($this->headers('POST', $uri, $body, 'ikey-c008'))
            ->postJson($uri, $payload)
            ->assertStatus(422);
    }
}
