<?php

namespace Tests\Feature;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\MtnMomoProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayMtnTokenHeaderTest extends TestCase
{
    use RefreshDatabase;

    private GePayClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->client = GePayClient::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'Token Header Test',
            'api_key'      => 'gpk_token_header_test',
            'api_secret'   => Str::random(64),
            'capabilities' => ['collection', 'disbursement'],
            'is_active'    => true,
        ]);
        config([
            'gepay.providers.mtn_momo.enabled' => true,
            'gepay.providers.mtn_momo.environment' => 'production',
            'gepay.providers.mtn_momo.target_environment' => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production' => 'https://mtn.example.test',
            'gepay.providers.mtn_momo.callback_url' => null,
            'gepay.providers.mtn_momo.collections' => [
                'subscription_key' => 'collection-subscription',
                'api_user' => 'collection-user',
                'api_key' => 'collection-key',
            ],
            'gepay.providers.mtn_momo.disbursements' => [
                'subscription_key' => 'disbursement-subscription',
                'api_user' => 'disbursement-user',
                'api_key' => 'disbursement-key',
            ],
        ]);
    }

    public function test_collection_token_includes_target_environment(): void
    {
        Http::fake([
            'https://mtn.example.test/collection/token/' => Http::response([
                'access_token' => 'collection-token',
                'expires_in' => 3600,
            ], 200),
            'https://mtn.example.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $result = app(MtnMomoProvider::class)->initiate($this->transaction(TransactionType::COLLECTION));

        $this->assertSame(TransactionStatus::PENDING, $result->status);
        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://mtn.example.test/collection/token/'
            && $request->hasHeader('X-Target-Environment', 'mtncongo')
            && $request->hasHeader('Ocp-Apim-Subscription-Key', 'collection-subscription')
        );
    }

    public function test_disbursement_token_includes_target_environment(): void
    {
        Http::fake([
            'https://mtn.example.test/disbursement/token/' => Http::response([
                'access_token' => 'disbursement-token',
                'expires_in' => 3600,
            ], 200),
            'https://mtn.example.test/disbursement/v1_0/transfer' => Http::response([], 202),
        ]);

        $result = app(MtnMomoProvider::class)->initiate($this->transaction(TransactionType::DISBURSEMENT));

        $this->assertSame(TransactionStatus::PENDING, $result->status);
        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://mtn.example.test/disbursement/token/'
            && $request->hasHeader('X-Target-Environment', 'mtncongo')
            && $request->hasHeader('Ocp-Apim-Subscription-Key', 'disbursement-subscription')
        );
    }

    private function transaction(TransactionType $type): GePayTransaction
    {
        return GePayTransaction::create([
            'uuid'               => (string) Str::uuid(),
            'client_id'          => $this->client->id,
            'type'               => $type,
            'provider'           => 'mtn_momo',
            'external_reference' => 'TEST-'.strtoupper($type->value),
            'idempotency_key'    => 'token-header-'.strtolower($type->value),
            'request_hash'       => hash('sha256', 'TEST-'.strtoupper($type->value)),
            'amount'             => 500,
            'currency'           => 'XAF',
            'phone'              => '242061234567',
            'phone_masked'       => '242••••567',
            'status'             => TransactionStatus::CREATED,
            'metadata'           => [],
        ]);
    }
}
