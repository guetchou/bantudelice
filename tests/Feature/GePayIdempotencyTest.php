<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private GePayClient $client;
    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secret = Str::random(64);
        $this->client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Idempotency Test',
            'api_key' => 'gpk_idempotency',
            'api_secret' => $this->secret,
            'capabilities' => ['collection', 'disbursement'],
            'is_active' => true,
        ]);

        config([
            'gepay.providers.mtn_momo.enabled' => true,
            'gepay.providers.mtn_momo.environment' => 'sandbox',
            'gepay.providers.mtn_momo.target_environment' => 'sandbox',
            'gepay.providers.mtn_momo.base_url.sandbox' => 'https://sandbox.mtn.test',
            'gepay.providers.mtn_momo.collections' => [
                'subscription_key' => 'sub-key',
                'api_user' => 'api-user',
                'api_key' => 'api-key',
            ],
        ]);

        Http::fake([
            'https://sandbox.mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://sandbox.mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);
    }

    private function headers(string $method, string $uri, string $body, string $idempotencyKey): array
    {
        $ts = (string) now()->timestamp;
        return [
            'X-GePay-Key' => $this->client->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => GePaySigner::sign($this->secret, $ts, $method, $uri, $body),
            'Idempotency-Key' => $idempotencyKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function test_same_key_same_payload_returns_existing_transaction(): void
    {
        $payload = [
            'amount' => 5000,
            'phone' => '24206000001',
            'external_reference' => 'ORDER-1',
        ];
        $body = json_encode($payload) ?: '';
        $uri = '/api/gepay/v1/collections';
        $iKey = 'idem-key-001';

        $first = $this->withHeaders($this->headers('POST', $uri, $body, $iKey))
            ->postJson($uri, $payload)
            ->assertStatus(202)
            ->json('transaction.reference');

        $second = $this->withHeaders($this->headers('POST', $uri, $body, $iKey))
            ->postJson($uri, $payload)
            ->assertStatus(202)
            ->json('transaction.reference');

        $this->assertSame($first, $second);
        $this->assertSame(1, GePayTransaction::query()->where('idempotency_key', $iKey)->count());
    }

    public function test_same_key_different_payload_returns_422(): void
    {
        $payload1 = [
            'amount' => 5000,
            'phone' => '24206000001',
            'external_reference' => 'ORDER-2',
        ];
        $payload2 = [
            'amount' => 9999,
            'phone' => '24206000001',
            'external_reference' => 'ORDER-2',
        ];

        $uri = '/api/gepay/v1/collections';
        $iKey = 'idem-key-002';

        $this->withHeaders($this->headers('POST', $uri, json_encode($payload1) ?: '', $iKey))
            ->postJson($uri, $payload1)
            ->assertStatus(202);

        $this->withHeaders($this->headers('POST', $uri, json_encode($payload2) ?: '', $iKey))
            ->postJson($uri, $payload2)
            ->assertStatus(422);
    }

    public function test_duplicate_external_reference_is_rejected(): void
    {
        $payload = [
            'amount' => 5000,
            'phone' => '24206000001',
            'external_reference' => 'ORDER-3',
        ];
        $uri = '/api/gepay/v1/collections';

        $this->withHeaders($this->headers('POST', $uri, json_encode($payload) ?: '', 'key-a'))
            ->postJson($uri, $payload)
            ->assertStatus(202);

        $this->withHeaders($this->headers('POST', $uri, json_encode($payload) ?: '', 'key-b'))
            ->postJson($uri, $payload)
            ->assertStatus(422);
    }

    public function test_missing_idempotency_key_is_rejected(): void
    {
        $payload = ['amount' => 5000, 'phone' => '24206000001', 'external_reference' => 'ORDER-4'];
        $uri = '/api/gepay/v1/collections';
        $ts = (string) now()->timestamp;
        $body = json_encode($payload) ?: '';

        $this->withHeaders([
            'X-GePay-Key' => $this->client->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => GePaySigner::sign($this->secret, $ts, 'POST', $uri, $body),
            'Content-Type' => 'application/json',
        ])->postJson($uri, $payload)->assertStatus(422);
    }
}
