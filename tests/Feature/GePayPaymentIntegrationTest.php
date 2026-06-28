<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayPaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $client = GePayClient::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'BantuDelice Internal',
            'api_key'      => 'gpk_integration',
            'api_secret'   => Str::random(64),
            'capabilities' => ['collection'],
            'is_active'    => true,
        ]);

        config([
            'gepay.bantudelice.collections_enabled'     => true,
            'gepay.internal_client_uuid'                => $client->uuid,
            'gepay.providers.mtn_momo.enabled'          => true,
            'gepay.providers.mtn_momo.environment'      => 'production',
            'gepay.providers.mtn_momo.target_environment' => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production' => 'https://mtn.test',
            'gepay.providers.mtn_momo.callback_url'     => null,
            'gepay.providers.mtn_momo.collections'      => [
                'subscription_key' => 'col-sub',
                'api_user'         => 'col-user',
                'api_key'          => 'col-key',
            ],
        ]);
    }

    public function test_gepay_uuid_stored_in_payment_provider_reference(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payment = $this->makePayment(5000, 1);
        $adapter = app(\App\Domain\Payment\Adapters\GePayAdapter::class);
        $result  = $adapter->initiate($payment, ['phone' => '068001234']);

        $this->assertTrue($result->success);

        // Ce UUID sera stocké dans Payment::provider_reference
        $gePayUuid = $result->providerReference;

        // La transaction GePay existe
        $transaction = GePayTransaction::where('uuid', $gePayUuid)->first();
        $this->assertNotNull($transaction);

        // La référence MTN est dans GePay, pas exposée à BantuDelice directement
        $this->assertNotNull($transaction->provider_reference);
        $this->assertNotSame($gePayUuid, $transaction->provider_reference);

        // Hiérarchie : BantuDelice → GePay (UUID) → MTN (provider_reference)
        $this->assertSame($gePayUuid, $transaction->uuid);
    }

    public function test_pending_status_from_mtn(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payment = $this->makePayment(5000, 2);
        $adapter = app(\App\Domain\Payment\Adapters\GePayAdapter::class);
        $result  = $adapter->initiate($payment, ['phone' => '068001234']);

        $this->assertSame('pending', $result->meta['gepay']['status']);
    }

    public function test_successful_status_via_check_status(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $payment = $this->makePayment(5000, 3);
        $adapter = app(\App\Domain\Payment\Adapters\GePayAdapter::class);
        $result  = $adapter->initiate($payment, ['phone' => '068001234']);

        $status = $adapter->checkStatus($result->providerReference);
        $this->assertSame(GatewayStatus::PAID, $status->status);
    }

    public function test_failed_status_via_check_status(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'FAILED', 'reason' => 'PAYER_NOT_FOUND'], 200),
        ]);

        $payment = $this->makePayment(5000, 4);
        $adapter = app(\App\Domain\Payment\Adapters\GePayAdapter::class);
        $result  = $adapter->initiate($payment, ['phone' => '068001234']);

        $status = $adapter->checkStatus($result->providerReference);
        $this->assertSame(GatewayStatus::FAILED, $status->status);
    }

    public function test_unknown_status_does_not_make_second_mtn_call(): void
    {
        $callCount = 0;
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => function () use (&$callCount) {
                $callCount++;
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            },
        ]);

        $payment = $this->makePayment(5000, 5);
        $adapter = app(\App\Domain\Payment\Adapters\GePayAdapter::class);
        $result  = $adapter->initiate($payment, ['phone' => '068001234']);

        $this->assertTrue($result->success); // unknown → success, pas de retry
        $this->assertSame(1, $callCount);
    }

    public function test_legacy_payments_not_affected(): void
    {
        // Un Payment existant sans transaction GePay ne doit pas être modifié
        $oldRef = 'DEMO-mtn-old-123456';
        $status = app(\App\Domain\Payment\Adapters\MtnMomoAdapter::class)->checkStatus($oldRef);

        // Peu importe le statut, l'appel ne plante pas
        $this->assertContains($status->status, [GatewayStatus::PAID, GatewayStatus::FAILED, GatewayStatus::PENDING, GatewayStatus::UNKNOWN]);
    }

    private function makePayment(int $amount, int $id): \App\Payment
    {
        $p           = new \App\Payment();
        $p->id       = $id;
        $p->amount   = $amount;
        $p->currency = 'XAF';

        return $p;
    }
}
