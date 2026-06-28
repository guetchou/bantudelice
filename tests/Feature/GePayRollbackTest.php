<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\Payment\Adapters\GePayAdapter;
use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\PaymentGatewayFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayRollbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_disabling_collections_flag_immediately_returns_mtn_adapter(): void
    {
        config(['gepay.bantudelice.collections_enabled' => true]);
        $this->assertInstanceOf(GePayAdapter::class, app(PaymentGatewayFactory::class)->for('momo'));

        config(['gepay.bantudelice.collections_enabled' => false]);
        $this->assertInstanceOf(MtnMomoAdapter::class, app(PaymentGatewayFactory::class)->for('momo'));
    }

    public function test_gepay_transactions_created_before_rollback_remain_queryable(): void
    {
        $client = GePayClient::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'Rollback Test',
            'api_key'      => 'gpk_rollback',
            'api_secret'   => Str::random(64),
            'capabilities' => ['collection'],
            'is_active'    => true,
        ]);

        config([
            'gepay.bantudelice.collections_enabled'           => true,
            'gepay.internal_client_uuid'                      => $client->uuid,
            'gepay.providers.mtn_momo.enabled'                => true,
            'gepay.providers.mtn_momo.environment'            => 'production',
            'gepay.providers.mtn_momo.target_environment'     => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production'    => 'https://mtn.test',
            'gepay.providers.mtn_momo.callback_url'           => null,
            'gepay.providers.mtn_momo.collections'            => [
                'subscription_key' => 'col-sub',
                'api_user'         => 'col-user',
                'api_key'          => 'col-key',
            ],
        ]);

        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $adapter = app(GePayAdapter::class);
        $payment = $this->makePayment(5000, 20);
        $result  = $adapter->initiate($payment, ['phone' => '068001234']);

        $gePayUuid = $result->providerReference;

        // Simuler un rollback : désactiver le flag
        config(['gepay.bantudelice.collections_enabled' => false]);

        // La transaction GePay existante reste réconciliable via checkStatus direct
        $status = $adapter->checkStatus($gePayUuid);
        $this->assertArrayHasKey('gepay', $status->meta);
        $this->assertSame($gePayUuid, $status->meta['gepay']['reference']);
    }

    public function test_old_payments_with_mtn_ref_not_broken_by_gepay_activation(): void
    {
        // Un ancien Payment avec provider_reference = UUID MTN (pas GePay)
        // doit toujours être reconcilié via MtnMomoAdapter sans planter
        config(['gepay.bantudelice.collections_enabled' => false]);

        $mtnAdapter = app(MtnMomoAdapter::class);
        $status     = $mtnAdapter->checkStatus('DEMO-mtn-old-ref-12345');

        $this->assertNotNull($status->status);
    }

    public function test_client_isolation_between_gepay_clients(): void
    {
        $client1 = GePayClient::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'Client 1',
            'api_key'      => 'gpk_c1',
            'api_secret'   => Str::random(64),
            'capabilities' => ['collection'],
            'is_active'    => true,
        ]);

        $client2 = GePayClient::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'Client 2',
            'api_key'      => 'gpk_c2',
            'api_secret'   => Str::random(64),
            'capabilities' => ['collection'],
            'is_active'    => true,
        ]);

        // L'internal resolver retourne toujours le client configuré
        config(['gepay.internal_client_uuid' => $client1->uuid]);
        $resolved = app(\App\Domain\GePay\Services\GePayInternalClientResolver::class)->resolve();
        $this->assertSame($client1->uuid, $resolved->uuid);

        config(['gepay.internal_client_uuid' => $client2->uuid]);
        $resolved = app(\App\Domain\GePay\Services\GePayInternalClientResolver::class)->resolve();
        $this->assertSame($client2->uuid, $resolved->uuid);
    }

    public function test_resolver_fails_clearly_without_uuid_config(): void
    {
        config(['gepay.internal_client_uuid' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/GEPAY_INTERNAL_CLIENT_UUID/');

        app(\App\Domain\GePay\Services\GePayInternalClientResolver::class)->resolve();
    }

    public function test_resolver_fails_clearly_with_inactive_client(): void
    {
        $client = GePayClient::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'Inactive',
            'api_key'      => 'gpk_inactive',
            'api_secret'   => Str::random(64),
            'capabilities' => ['collection'],
            'is_active'    => false,
        ]);

        config(['gepay.internal_client_uuid' => $client->uuid]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found or inactive/');

        app(\App\Domain\GePay\Services\GePayInternalClientResolver::class)->resolve();
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
