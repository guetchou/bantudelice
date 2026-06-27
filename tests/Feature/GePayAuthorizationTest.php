<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Services\GePaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayAuthorizationTest extends TestCase
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
            'name' => 'Auth Test',
            'api_key' => 'gpk_auth',
            'api_secret' => $this->secret,
            'capabilities' => ['collection'],
            'is_active' => true,
        ]);
    }

    private function signedHeaders(string $method, string $uri, string $body, ?string $secret = null): array
    {
        $ts = (string) now()->timestamp;
        return [
            'X-GePay-Key' => $this->client->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => GePaySigner::sign($secret ?? $this->secret, $ts, $method, $uri, $body),
        ];
    }

    public function test_missing_headers_returns_401(): void
    {
        $this->getJson('/api/gepay/v1/client')->assertUnauthorized();
    }

    public function test_expired_timestamp_is_rejected(): void
    {
        $ts = (string) (now()->timestamp - 600);
        $this->withHeaders([
            'X-GePay-Key' => $this->client->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => GePaySigner::sign($this->secret, $ts, 'GET', '/api/gepay/v1/client', ''),
        ])->getJson('/api/gepay/v1/client')->assertUnauthorized();
    }

    public function test_future_timestamp_beyond_tolerance_is_rejected(): void
    {
        $ts = (string) (now()->timestamp + 600);
        $this->withHeaders([
            'X-GePay-Key' => $this->client->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => GePaySigner::sign($this->secret, $ts, 'GET', '/api/gepay/v1/client', ''),
        ])->getJson('/api/gepay/v1/client')->assertUnauthorized();
    }

    public function test_wrong_signature_returns_401(): void
    {
        $this->withHeaders([
            ...$this->signedHeaders('GET', '/api/gepay/v1/client', ''),
            'X-GePay-Signature' => str_repeat('0', 64),
        ])->getJson('/api/gepay/v1/client')->assertUnauthorized();
    }

    public function test_disabled_client_is_rejected(): void
    {
        $this->client->forceFill(['is_active' => false])->save();
        $this->withHeaders($this->signedHeaders('GET', '/api/gepay/v1/client', ''))
            ->getJson('/api/gepay/v1/client')
            ->assertUnauthorized();
    }

    public function test_ip_restriction_rejects_unauthorized_ip(): void
    {
        $this->client->forceFill(['allowed_ips' => ['10.0.0.1']])->save();
        $this->withHeaders($this->signedHeaders('GET', '/api/gepay/v1/client', ''))
            ->getJson('/api/gepay/v1/client')
            ->assertForbidden();
    }

    public function test_nonce_replay_is_rejected(): void
    {
        $uri = '/api/gepay/v1/client';
        $ts = (string) now()->timestamp;
        $sig = GePaySigner::sign($this->secret, $ts, 'GET', $uri, '');
        $nonce = Str::uuid()->toString();

        $this->withHeaders([
            'X-GePay-Key' => $this->client->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => $sig,
            'X-GePay-Nonce' => $nonce,
        ])->get($uri)->assertOk();

        $this->withHeaders([
            'X-GePay-Key' => $this->client->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => $sig,
            'X-GePay-Nonce' => $nonce,
        ])->get($uri)->assertUnauthorized();
    }

    public function test_body_modification_invalidates_signature(): void
    {
        $uri = '/api/gepay/v1/client';
        $ts = (string) now()->timestamp;
        $sig = GePaySigner::sign($this->secret, $ts, 'GET', $uri, '{"original":true}');

        $this->withHeaders([
            'X-GePay-Key' => $this->client->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => $sig,
        ])->getJson($uri)->assertUnauthorized();
    }

    public function test_client_isolation_prevents_cross_client_access(): void
    {
        $secret2 = Str::random(64);
        $client2 = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Client 2',
            'api_key' => 'gpk_client2',
            'api_secret' => $secret2,
            'capabilities' => ['collection'],
            'is_active' => true,
        ]);

        $uri = '/api/gepay/v1/client';
        $ts = (string) now()->timestamp;

        $resp = $this->withHeaders([
            'X-GePay-Key' => $client2->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => GePaySigner::sign($secret2, $ts, 'GET', $uri, ''),
        ])->get($uri)->assertOk();

        $this->assertSame($client2->uuid, $resp->json('client.uuid'));
        $this->assertNotSame($this->client->uuid, $resp->json('client.uuid'));
    }

    public function test_response_never_exposes_api_secret(): void
    {
        $uri = '/api/gepay/v1/client';
        $resp = $this->withHeaders($this->signedHeaders('GET', $uri, ''))
            ->get($uri)
            ->assertOk();

        $content = json_encode($resp->json()) ?: '';
        $this->assertStringNotContainsString($this->secret, $content);
    }
}
