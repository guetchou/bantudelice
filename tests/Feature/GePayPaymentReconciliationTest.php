<?php

namespace Tests\Feature;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\Payment\Adapters\GePayAdapter;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayPaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private GePayAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $client = GePayClient::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'BantuDelice Reconcile',
            'api_key'      => 'gpk_reconcile',
            'api_secret'   => Str::random(64),
            'capabilities' => ['collection'],
            'is_active'    => true,
        ]);

        config([
            'gepay.internal_client_uuid'                    => $client->uuid,
            'gepay.providers.mtn_momo.enabled'              => true,
            'gepay.providers.mtn_momo.environment'          => 'production',
            'gepay.providers.mtn_momo.target_environment'   => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production'  => 'https://mtn.test',
            'gepay.providers.mtn_momo.callback_url'         => null,
            'gepay.providers.mtn_momo.collections'          => [
                'subscription_key' => 'col-sub',
                'api_user'         => 'col-user',
                'api_key'          => 'col-key',
            ],
        ]);

        $this->adapter = app(GePayAdapter::class);
    }

    public function test_check_status_via_gepay_uuid_not_mtn_ref(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $payment = $this->makePayment(5000, 10);
        $result  = $this->adapter->initiate($payment, ['phone' => '068001234']);

        $gePayUuid   = $result->providerReference;
        $transaction = GePayTransaction::where('uuid', $gePayUuid)->first();
        $mtnRef      = $transaction->provider_reference;

        // La réconciliation utilise l'UUID GePay, pas la référence MTN
        $this->assertNotSame($gePayUuid, $mtnRef);

        $status = $this->adapter->checkStatus($gePayUuid);
        $this->assertContains($status->status, [GatewayStatus::PAID, GatewayStatus::PENDING]);
    }

    public function test_terminal_status_not_refreshed_from_mtn(): void
    {
        $client = GePayClient::where('is_active', true)->first();
        $transaction = GePayTransaction::create([
            'uuid'               => (string) Str::uuid(),
            'client_id'          => $client->id,
            'type'               => 'collection',
            'provider'           => 'mtn_momo',
            'external_reference' => 'PAYMENT-terminal-1',
            'idempotency_key'    => 'payment:terminal-1:collection',
            'request_hash'       => hash('sha256', 'terminal-test'),
            'amount'             => 1000,
            'currency'           => 'XAF',
            'phone'              => '068001234',
            'phone_masked'       => '06•••••34',
            'status'             => TransactionStatus::SUCCESSFUL,
            'provider_reference' => 'mtn-ref-terminal',
            'completed_at'       => now(),
        ]);

        // Aucun appel HTTP ne doit être fait pour un statut terminal
        Http::fake(['*' => Http::response([], 500)]);

        $status = $this->adapter->checkStatus($transaction->uuid);

        $this->assertSame(GatewayStatus::PAID, $status->status);
        Http::assertNothingSent();
    }

    public function test_check_status_preserves_gepay_meta(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payment = $this->makePayment(5000, 11);
        $result  = $this->adapter->initiate($payment, ['phone' => '068001234']);

        $status = $this->adapter->checkStatus($result->providerReference);

        $this->assertArrayHasKey('gepay', $status->meta);
        $this->assertArrayHasKey('reference', $status->meta['gepay']);
        $this->assertArrayHasKey('provider_reference', $status->meta['gepay']);
        $this->assertSame($result->providerReference, $status->meta['gepay']['reference']);
    }

    public function test_flag_disabled_after_initiation_reconciliation_still_works(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $payment = $this->makePayment(5000, 12);
        $result  = $this->adapter->initiate($payment, ['phone' => '068001234']);
        $gePayUuid = $result->providerReference;

        // Désactiver le flag après initiation
        config(['gepay.bantudelice.collections_enabled' => false]);

        // La réconciliation via GePayAdapter::checkStatus doit toujours fonctionner
        // car elle utilise l'UUID GePay directement (pas le flag)
        $status = $this->adapter->checkStatus($gePayUuid);
        $this->assertArrayHasKey('gepay', $status->meta);
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
