<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Services\GePaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayDisbursementTest extends TestCase
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
            'name' => 'Disbursement Test',
            'api_key' => 'gpk_dis',
            'api_secret' => $this->secret,
            'capabilities' => ['disbursement'],
            'is_active' => true,
        ]);

        config([
            'gepay.providers.mtn_momo.enabled' => true,
            'gepay.providers.mtn_momo.environment' => 'production',
            'gepay.providers.mtn_momo.target_environment' => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production' => 'https://mtn.test',
            'gepay.providers.mtn_momo.callback_url' => null,
            'gepay.providers.mtn_momo.disbursements' => [
                'subscription_key' => 'dis-sub',
                'api_user' => 'dis-user',
                'api_key' => 'dis-key',
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

    public function test_disbursement_202_produces_pending(): void
    {
        Http::fake([
            'https://mtn.test/disbursement/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/disbursement/v1_0/transfer' => Http::response([], 202),
        ]);

        $payload = ['amount' => 10000, 'phone' => '24206005555', 'external_reference' => 'DIS-001'];
        $uri = '/api/gepay/v1/disbursements';
        $body = json_encode($payload) ?: '';

        $resp = $this->withHeaders($this->headers('POST', $uri, $body, 'ikey-d001'))
            ->postJson($uri, $payload)
            ->assertStatus(202);

        $this->assertSame('pending', $resp->json('transaction.status'));
    }

    public function test_disbursement_token_sends_x_target_environment_mtncongo(): void
    {
        Http::fake([
            'https://mtn.test/disbursement/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/disbursement/v1_0/transfer' => Http::response([], 202),
        ]);

        $payload = ['amount' => 10000, 'phone' => '24206005555', 'external_reference' => 'DIS-002'];
        $uri = '/api/gepay/v1/disbursements';
        $body = json_encode($payload) ?: '';

        $this->withHeaders($this->headers('POST', $uri, $body, 'ikey-d002'))
            ->postJson($uri, $payload);

        Http::assertSent(fn (Request $req) =>
            str_contains($req->url(), '/disbursement/token/')
            && $req->hasHeader('X-Target-Environment', 'mtncongo')
        );
    }

    public function test_disbursement_timeout_produces_unknown_not_failed(): void
    {
        Http::fake([
            'https://mtn.test/disbursement/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/disbursement/v1_0/transfer' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            },
        ]);

        $payload = ['amount' => 10000, 'phone' => '24206005555', 'external_reference' => 'DIS-003'];
        $uri = '/api/gepay/v1/disbursements';
        $body = json_encode($payload) ?: '';

        $resp = $this->withHeaders($this->headers('POST', $uri, $body, 'ikey-d003'))
            ->postJson($uri, $payload)
            ->assertStatus(202);

        $this->assertSame('unknown', $resp->json('transaction.status'));
    }

    public function test_disbursement_token_cache_uses_expires_in(): void
    {
        $tokenRequestCount = 0;
        Http::fake([
            'https://mtn.test/disbursement/token/' => function () use (&$tokenRequestCount) {
                $tokenRequestCount++;
                return Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200);
            },
            'https://mtn.test/disbursement/v1_0/transfer' => Http::response([], 202),
        ]);

        $uri = '/api/gepay/v1/disbursements';
        foreach (['DIS-004', 'DIS-005'] as $i => $ref) {
            $payload = ['amount' => 10000, 'phone' => '24206005555', 'external_reference' => $ref];
            $body = json_encode($payload) ?: '';
            $this->withHeaders($this->headers('POST', $uri, $body, "ikey-d00{$i}4"))
                ->postJson($uri, $payload);
        }

        $this->assertSame(1, $tokenRequestCount);
    }

    public function test_collection_client_cannot_call_disbursement(): void
    {
        $secret = Str::random(64);
        $colClient = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Col only',
            'api_key' => 'gpk_col_only',
            'api_secret' => $secret,
            'capabilities' => ['collection'],
            'is_active' => true,
        ]);

        $payload = ['amount' => 10000, 'phone' => '24206005555', 'external_reference' => 'DIS-006'];
        $uri = '/api/gepay/v1/disbursements';
        $body = json_encode($payload) ?: '';
        $ts = (string) now()->timestamp;

        $this->withHeaders([
            'X-GePay-Key' => $colClient->api_key,
            'X-GePay-Timestamp' => $ts,
            'X-GePay-Signature' => GePaySigner::sign($secret, $ts, 'POST', $uri, $body),
            'Idempotency-Key' => 'ikey-d006',
        ])->postJson($uri, $payload)->assertStatus(422);
    }
}
