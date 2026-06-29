<?php

namespace Tests\Feature;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Services\GePayInternalClientResolver;
use App\Domain\Payment\Adapters\GePayAdapter;
use App\Domain\Payment\ValueObjects\GatewayResult;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private GePayClient $client;
    private GePayAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::preventStrayRequests();

        $this->client = GePayClient::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'BantuDelice Internal',
            'api_key'      => 'gpk_internal_test',
            'api_secret'   => Str::random(64),
            'capabilities' => ['collection', 'disbursement'],
            'is_active'    => true,
        ]);

        config([
            'gepay.internal_client_uuid' => $this->client->uuid,
            'gepay.providers.mtn_momo.enabled' => true,
            'gepay.providers.mtn_momo.environment' => 'production',
            'gepay.providers.mtn_momo.target_environment' => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production' => 'https://mtn.test',
            'gepay.providers.mtn_momo.callback_url' => null,
            'gepay.providers.mtn_momo.collections' => [
                'subscription_key' => 'col-sub',
                'api_user'         => 'col-user',
                'api_key'          => 'col-key',
            ],
        ]);

        $this->adapter = app(GePayAdapter::class);
    }

    public function test_initiate_collection_returns_gepay_uuid_in_provider_reference(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payment = $this->makePayment(5000);

        $result = $this->adapter->initiate($payment, ['phone' => '068001234']);

        $this->assertTrue($result->success);
        // La référence doit être un UUID GePay, pas une référence MTN
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $result->providerReference
        );
        $this->assertArrayHasKey('gepay', $result->meta);
        $this->assertArrayHasKey('reference', $result->meta['gepay']);
        $this->assertSame($result->providerReference, $result->meta['gepay']['reference']);
    }

    public function test_mtn_reference_stays_in_gepay_transaction(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payment = $this->makePayment(5000);
        $result  = $this->adapter->initiate($payment, ['phone' => '068001234']);

        $this->assertTrue($result->success);

        $transaction = \App\Domain\GePay\Models\GePayTransaction::where('uuid', $result->providerReference)->first();
        $this->assertNotNull($transaction);
        $this->assertNotNull($transaction->provider_reference);
        // La référence MTN est dans GePay, pas dans BantuDelice Payment
        $this->assertNotSame($result->providerReference, $transaction->provider_reference);
    }

    public function test_initiate_returns_success_on_unknown_status(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            },
        ]);

        $payment = $this->makePayment(3000);
        $result  = $this->adapter->initiate($payment, ['phone' => '068001234']);

        // unknown → success car l'appel a peut-être atteint MTN (éviter double paiement)
        $this->assertTrue($result->success);
        $this->assertSame('unknown', $result->meta['gepay']['status']);
    }

    public function test_initiate_returns_failure_on_failed_status(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response(['reason' => 'PAYER_NOT_FOUND'], 404),
        ]);

        $payment = $this->makePayment(2000);
        $result  = $this->adapter->initiate($payment, ['phone' => '068001234']);

        $this->assertFalse($result->success);
    }

    public function test_check_status_uses_gepay_uuid_not_mtn_ref(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $payment = $this->makePayment(5000);
        $result  = $this->adapter->initiate($payment, ['phone' => '068001234']);
        $this->assertTrue($result->success);

        $gePayUuid = $result->providerReference;

        // checkStatus reçoit l'UUID GePay stocké dans Payment::provider_reference
        $status = $this->adapter->checkStatus($gePayUuid);

        $this->assertContains($status->status, [GatewayStatus::PENDING, GatewayStatus::PAID, GatewayStatus::UNKNOWN]);
        $this->assertArrayHasKey('gepay', $status->meta);
        $this->assertSame($gePayUuid, $status->meta['gepay']['reference']);
    }

    public function test_check_status_returns_unknown_for_missing_gepay_transaction(): void
    {
        // Sans cette config, MtnMomoAdapter retourne GatewayStatus::paid('DEMO')
        // inconditionnellement (mode démo quand le provider est désactivé).
        config([
            'external-services.payments.mtn_momo.enabled'            => true,
            'external-services.payments.mtn_momo.environment'         => 'production',
            'external-services.payments.mtn_momo.target_environment'  => 'mtncongo',
            'external-services.payments.mtn_momo.base_url.production' => 'https://mtn.test',
            'external-services.payments.mtn_momo.collections'         => [
                'subscription_key' => 'col-sub',
                'api_user'         => 'col-user',
                'api_key'          => 'col-key',
            ],
        ]);

        // Échec volontaire du token (401) : MtnMomoAdapter retourne UNKNOWN
        // sans atteindre la requête requesttopay.
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response([], 401),
        ]);

        $status = $this->adapter->checkStatus('00000000-0000-0000-0000-000000000000');

        $this->assertSame(GatewayStatus::UNKNOWN, $status->status);
    }

    public function test_reversed_status_includes_financial_reversal_flag(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payment = $this->makePayment(5000);
        $result  = $this->adapter->initiate($payment, ['phone' => '068001234']);

        $transaction = \App\Domain\GePay\Models\GePayTransaction::where('uuid', $result->providerReference)->first();
        $transaction->forceFill(['status' => TransactionStatus::REVERSED])->save();

        $status = $this->adapter->checkStatus($result->providerReference);

        $this->assertSame(GatewayStatus::FAILED, $status->status);
        $this->assertTrue($status->meta['financial_reversal'] ?? false);
        $this->assertSame('reversed', $status->meta['gepay_status'] ?? '');
    }

    public function test_missing_phone_returns_failure(): void
    {
        $result = $this->adapter->initiate($this->makePayment(1000), ['phone' => '']);
        $this->assertFalse($result->success);
    }

    public function test_idempotent_retry_returns_same_gepay_uuid(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payment = $this->makePayment(5000, 42);

        $r1 = $this->adapter->initiate($payment, ['phone' => '068001234']);
        $r2 = $this->adapter->initiate($payment, ['phone' => '068001234']);

        $this->assertTrue($r1->success);
        $this->assertTrue($r2->success);
        $this->assertSame($r1->providerReference, $r2->providerReference);
        Http::assertSentCount(2); // token + 1 seul requesttopay
    }

    private function makePayment(int $amount, int $id = 99): Payment
    {
        $p           = new Payment();
        $p->id       = $id;
        $p->amount   = $amount;
        $p->currency = 'XAF';

        return $p;
    }
}
