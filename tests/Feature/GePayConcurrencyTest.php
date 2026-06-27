<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePayGateway;
use App\Domain\GePay\Enums\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private GePayClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Concurrency Test',
            'api_key' => 'gpk_conc',
            'api_secret' => Str::random(64),
            'capabilities' => ['collection'],
            'is_active' => true,
        ]);

        config([
            'gepay.providers.mtn_momo.enabled' => true,
            'gepay.providers.mtn_momo.environment' => 'sandbox',
            'gepay.providers.mtn_momo.target_environment' => 'sandbox',
            'gepay.providers.mtn_momo.base_url.sandbox' => 'https://sandbox.mtn.test',
            'gepay.providers.mtn_momo.collections' => [
                'subscription_key' => 'sub',
                'api_user' => 'user',
                'api_key' => 'key',
            ],
        ]);

        Http::fake([
            'https://sandbox.mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://sandbox.mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);
    }

    public function test_concurrent_same_idempotency_key_creates_only_one_transaction(): void
    {
        $gateway = app(GePayGateway::class);
        $payload = [
            'amount' => 5000,
            'phone' => '24206007777',
            'external_reference' => 'CONC-001',
            'currency' => 'XAF',
        ];
        $iKey = 'conc-ikey-001';

        $t1 = $gateway->initiate($this->client, TransactionType::COLLECTION, $payload, $iKey);
        $t2 = $gateway->initiate($this->client, TransactionType::COLLECTION, $payload, $iKey);

        $this->assertSame($t1->uuid, $t2->uuid);
        $this->assertSame(1, GePayTransaction::query()->where('idempotency_key', $iKey)->count());
    }

    public function test_concurrent_different_keys_create_separate_transactions(): void
    {
        $gateway = app(GePayGateway::class);

        $base = [
            'amount' => 5000,
            'phone' => '24206007777',
            'currency' => 'XAF',
        ];

        $t1 = $gateway->initiate($this->client, TransactionType::COLLECTION, array_merge($base, ['external_reference' => 'CONC-002']), 'conc-ikey-002');
        $t2 = $gateway->initiate($this->client, TransactionType::COLLECTION, array_merge($base, ['external_reference' => 'CONC-003']), 'conc-ikey-003');

        $this->assertNotSame($t1->uuid, $t2->uuid);
        $this->assertSame(2, GePayTransaction::query()->count());
    }

    public function test_same_external_reference_different_idempotency_key_throws(): void
    {
        $this->expectException(\RuntimeException::class);

        $gateway = app(GePayGateway::class);
        $payload = ['amount' => 5000, 'phone' => '24206007777', 'external_reference' => 'CONC-004', 'currency' => 'XAF'];

        $gateway->initiate($this->client, TransactionType::COLLECTION, $payload, 'conc-ikey-004a');
        $gateway->initiate($this->client, TransactionType::COLLECTION, $payload, 'conc-ikey-004b');
    }
}
