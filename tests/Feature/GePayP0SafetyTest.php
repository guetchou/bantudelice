<?php

namespace Tests\Feature;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayP0SafetyTest extends TestCase
{
    use RefreshDatabase;

    private GePayClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'GePay P0 Safety',
            'api_key' => 'gpk_p0_safety',
            'api_secret' => Str::random(64),
            'capabilities' => ['collection', 'disbursement'],
            'is_active' => true,
        ]);

        config([
            'gepay.submission_claim_timeout_seconds' => 120,
            'gepay.providers.mtn_momo.enabled' => true,
            'gepay.providers.mtn_momo.environment' => 'production',
            'gepay.providers.mtn_momo.target_environment' => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production' => 'https://mtn-p0.test',
            'gepay.providers.mtn_momo.callback_url' => null,
            'gepay.providers.mtn_momo.collections' => [
                'subscription_key' => 'collection-sub',
                'api_user' => 'collection-user',
                'api_key' => 'collection-key',
            ],
            'gepay.providers.mtn_momo.disbursements' => [
                'subscription_key' => 'disbursement-sub',
                'api_user' => 'disbursement-user',
                'api_key' => 'disbursement-key',
            ],
        ]);
    }

    public function test_same_idempotency_key_submits_to_mtn_only_once(): void
    {
        Http::fake([
            'https://mtn-p0.test/collection/token/' => Http::response([
                'access_token' => 'token',
                'expires_in' => 3600,
            ], 200),
            'https://mtn-p0.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $gateway = app(GePayGateway::class);
        $payload = $this->payload('P0-DUPLICATE');

        $first = $gateway->initiate($this->client, TransactionType::COLLECTION, $payload, 'p0-duplicate-key');
        $second = $gateway->initiate($this->client, TransactionType::COLLECTION, $payload, 'p0-duplicate-key');

        $this->assertSame($first->uuid, $second->uuid);
        $this->assertNotNull($first->provider_reference);

        $requestToPayCalls = collect(Http::recorded())
            ->filter(fn (array $record) => str_contains($record[0]->url(), '/collection/v1_0/requesttopay'));

        $this->assertCount(1, $requestToPayCalls);
    }

    public function test_provider_reference_is_persisted_before_network_call(): void
    {
        Http::fake([
            'https://mtn-p0.test/collection/token/' => Http::response([
                'access_token' => 'token',
                'expires_in' => 3600,
            ], 200),
            'https://mtn-p0.test/collection/v1_0/requesttopay' => function (Request $request) {
                $transaction = GePayTransaction::query()
                    ->where('external_reference', 'P0-PERSIST-FIRST')
                    ->firstOrFail();

                $headerReference = $request->header('X-Reference-Id')[0] ?? null;

                $this->assertNotNull($transaction->provider_reference);
                $this->assertSame($transaction->provider_reference, $headerReference);

                return Http::response([], 202);
            },
        ]);

        $transaction = app(GePayGateway::class)->initiate(
            $this->client,
            TransactionType::COLLECTION,
            $this->payload('P0-PERSIST-FIRST'),
            'p0-persist-first-key'
        );

        $this->assertSame(TransactionStatus::PENDING, $transaction->status);
        $this->assertNotNull($transaction->provider_reference);
    }

    public function test_http_500_after_submission_is_unknown_not_failed(): void
    {
        Http::fake([
            'https://mtn-p0.test/disbursement/token/' => Http::response([
                'access_token' => 'token',
                'expires_in' => 3600,
            ], 200),
            'https://mtn-p0.test/disbursement/v1_0/transfer' => Http::response([
                'message' => 'Temporary upstream failure',
            ], 500),
        ]);

        $transaction = app(GePayGateway::class)->initiate(
            $this->client,
            TransactionType::DISBURSEMENT,
            $this->payload('P0-HTTP-500'),
            'p0-http-500-key'
        );

        $this->assertSame(TransactionStatus::UNKNOWN, $transaction->status);
        $this->assertNotNull($transaction->provider_reference);
        $this->assertSame('INITIATION_HTTP_500', $transaction->failure_code);
    }

    public function test_token_failure_is_pre_submission_and_keeps_transaction_retryable(): void
    {
        Http::fake([
            'https://mtn-p0.test/collection/token/' => Http::response([
                'message' => 'Unavailable',
            ], 503),
        ]);

        $transaction = app(GePayGateway::class)->initiate(
            $this->client,
            TransactionType::COLLECTION,
            $this->payload('P0-TOKEN-FAIL'),
            'p0-token-fail-key'
        );

        $this->assertSame(TransactionStatus::CREATED, $transaction->status);
        $this->assertNull($transaction->provider_reference);
        $this->assertSame('PRE_SUBMISSION_ERROR', $transaction->failure_code);

        $requestToPayCalls = collect(Http::recorded())
            ->filter(fn (array $record) => str_contains($record[0]->url(), '/requesttopay'));

        $this->assertCount(0, $requestToPayCalls);
    }

    public function test_fresh_submission_claim_without_reference_is_not_reclaimed(): void
    {
        $transaction = $this->makeTransaction(
            externalReference: 'P0-FRESH-CLAIM',
            status: TransactionStatus::SUBMITTED,
            submittedAt: now()
        );

        Http::fake();

        $fresh = app(GePayGateway::class)->refresh($transaction);

        $this->assertSame(TransactionStatus::SUBMITTED, $fresh->status);
        $this->assertNull($fresh->provider_reference);
        Http::assertNothingSent();
    }

    public function test_stale_submission_claim_without_reference_is_reclaimed_once(): void
    {
        $transaction = $this->makeTransaction(
            externalReference: 'P0-STALE-CLAIM',
            status: TransactionStatus::SUBMITTED,
            submittedAt: now()->subMinutes(5)
        );

        Http::fake([
            'https://mtn-p0.test/collection/token/' => Http::response([
                'access_token' => 'token',
                'expires_in' => 3600,
            ], 200),
            'https://mtn-p0.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $fresh = app(GePayGateway::class)->refresh($transaction);

        $this->assertSame(TransactionStatus::PENDING, $fresh->status);
        $this->assertNotNull($fresh->provider_reference);

        $requestToPayCalls = collect(Http::recorded())
            ->filter(fn (array $record) => str_contains($record[0]->url(), '/collection/v1_0/requesttopay'));

        $this->assertCount(1, $requestToPayCalls);
    }

    private function payload(string $externalReference): array
    {
        return [
            'amount' => 5000,
            'currency' => 'XAF',
            'phone' => '24206007777',
            'external_reference' => $externalReference,
        ];
    }

    private function makeTransaction(
        string $externalReference,
        TransactionStatus $status,
        $submittedAt
    ): GePayTransaction {
        return GePayTransaction::create([
            'uuid' => (string) Str::uuid(),
            'client_id' => $this->client->id,
            'type' => TransactionType::COLLECTION,
            'provider' => 'mtn_momo',
            'external_reference' => $externalReference,
            'idempotency_key' => 'key-'.Str::lower(Str::random(16)),
            'request_hash' => hash('sha256', $externalReference),
            'amount' => 5000,
            'currency' => 'XAF',
            'phone' => '24206007777',
            'phone_masked' => '24••••77',
            'status' => $status,
            'metadata' => [],
            'submitted_at' => $submittedAt,
        ]);
    }
}
