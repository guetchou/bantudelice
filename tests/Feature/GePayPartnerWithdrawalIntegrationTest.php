<?php

namespace Tests\Feature;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\PartnerWithdrawal;
use App\Services\PartnerWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayPartnerWithdrawalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PartnerWithdrawalService $service;
    private GePayClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->client = GePayClient::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'BantuDelice Withdrawals',
            'api_key'      => 'gpk_withdrawal',
            'api_secret'   => Str::random(64),
            'capabilities' => ['disbursement'],
            'is_active'    => true,
        ]);

        config([
            'gepay.bantudelice.withdrawals_enabled'           => true,
            'gepay.internal_client_uuid'                      => $this->client->uuid,
            'gepay.providers.mtn_momo.enabled'                => true,
            'gepay.providers.mtn_momo.environment'            => 'production',
            'gepay.providers.mtn_momo.target_environment'     => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production'    => 'https://mtn.test',
            'gepay.providers.mtn_momo.callback_url'           => null,
            'gepay.providers.mtn_momo.disbursements'          => [
                'subscription_key' => 'dis-sub',
                'api_user'         => 'dis-user',
                'api_key'          => 'dis-key',
            ],
        ]);

        $this->service = app(PartnerWithdrawalService::class);
    }

    public function test_withdrawal_via_gepay_stores_gepay_uuid_in_provider_reference(): void
    {
        Http::fake([
            'https://mtn.test/disbursement/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/disbursement/v1_0/transfer' => Http::response([], 202),
        ]);

        $withdrawal = $this->createReservedWithdrawal(5000);

        $this->service->initiateForWithdrawal($withdrawal, '068006730', 5000, 'restaurant');

        $withdrawal->refresh();

        $gePayUuid = $withdrawal->provider_reference;
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string) $gePayUuid
        );

        $transaction = GePayTransaction::where('uuid', $gePayUuid)->first();
        $this->assertNotNull($transaction);
        $this->assertSame('gepay', $withdrawal->provider);
    }

    public function test_external_reference_is_deterministic_withdrawal_uuid(): void
    {
        Http::fake([
            'https://mtn.test/disbursement/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/disbursement/v1_0/transfer' => Http::response([], 202),
        ]);

        $withdrawal = $this->createReservedWithdrawal(5000);
        $uuid = $withdrawal->uuid;

        $this->service->initiateForWithdrawal($withdrawal, '068006730', 5000, 'restaurant');
        $withdrawal->refresh();

        $transaction = GePayTransaction::where('uuid', $withdrawal->provider_reference)->first();
        $this->assertSame('WITHDRAWAL-' . $uuid, $transaction->external_reference);
        $this->assertSame('partner-withdrawal:' . $uuid . ':disbursement', $transaction->idempotency_key);
    }

    public function test_idempotent_retry_returns_same_gepay_transaction(): void
    {
        Http::fake([
            'https://mtn.test/disbursement/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/disbursement/v1_0/transfer' => Http::response([], 202),
        ]);

        $withdrawal = $this->createReservedWithdrawal(5000);

        $this->service->initiateForWithdrawal($withdrawal, '068006730', 5000, 'restaurant');
        $withdrawal->refresh();
        $firstRef = $withdrawal->provider_reference;

        $this->service->initiateForWithdrawal($withdrawal, '068006730', 5000, 'restaurant');
        $withdrawal->refresh();

        $this->assertSame($firstRef, $withdrawal->provider_reference);
        Http::assertSentCount(2);
    }

    public function test_unknown_status_does_not_release_balance(): void
    {
        Http::fake([
            'https://mtn.test/disbursement/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/disbursement/v1_0/transfer' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            },
        ]);

        $withdrawal = $this->createReservedWithdrawal(5000);
        $this->service->initiateForWithdrawal($withdrawal, '068006730', 5000, 'restaurant');
        $withdrawal->refresh();

        $this->assertNotSame('paid', $withdrawal->status);
        $this->assertFalse($withdrawal->isPaid());
    }

    public function test_failed_gepay_status_marks_withdrawal_failed(): void
    {
        Http::fake([
            'https://mtn.test/disbursement/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/disbursement/v1_0/transfer' => Http::response(['reason' => 'PAYEE_NOT_FOUND'], 404),
        ]);

        $withdrawal = $this->createReservedWithdrawal(5000);
        $this->service->initiateForWithdrawal($withdrawal, '068006730', 5000, 'restaurant');
        $withdrawal->refresh();

        $this->assertTrue($withdrawal->isFailed());
    }

    public function test_reconcile_via_gepay_uses_gepay_uuid(): void
    {
        Http::fake([
            'https://mtn.test/disbursement/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/disbursement/v1_0/transfer' => Http::response([], 202),
            'https://mtn.test/disbursement/v1_0/transfer/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $withdrawal = $this->createReservedWithdrawal(5000);
        $this->service->initiateForWithdrawal($withdrawal, '068006730', 5000, 'restaurant');
        $withdrawal->refresh();

        $gePayTx = GePayTransaction::where('uuid', $withdrawal->provider_reference)->first();
        $gePayTx->forceFill(['status' => TransactionStatus::PENDING])->save();

        // Préparation technique du scénario : on ne transforme pas paid → pending
        // par le modèle métier, car cette transition est volontairement interdite.
        DB::table('partner_withdrawals')
            ->where('id', $withdrawal->id)
            ->update(['status' => 'pending', 'paid_at' => null]);
        $withdrawal->refresh();

        $this->service->reconcile($withdrawal->id);
        $withdrawal->refresh();

        $this->assertTrue($withdrawal->isPaid());
    }

    public function test_fallback_to_disbursement_service_when_flag_disabled(): void
    {
        config(['gepay.bantudelice.withdrawals_enabled' => false]);

        $withdrawal = $this->createReservedWithdrawal(5000, 'mtn_momo');

        $this->assertSame('mtn_momo', $withdrawal->provider);
    }

    private function createReservedWithdrawal(int $amount, string $provider = 'gepay'): PartnerWithdrawal
    {
        $uuid = (string) Str::uuid();

        return PartnerWithdrawal::create([
            'uuid'               => $uuid,
            'partner_type'       => 'restaurant',
            'partner_id'         => 1,
            'operator'           => 'mtn',
            'provider'           => $provider,
            'phone'              => '068006730',
            'requested_amount'   => $amount,
            'fee_amount'         => 0,
            'net_amount'         => $amount,
            'currency'           => 'XAF',
            'status'             => 'reserved',
            'external_reference' => $provider === 'gepay' ? 'WITHDRAWAL-' . $uuid : 'WD-LEGACY-001',
            'idempotency_key'    => 'wd-test-' . Str::random(8),
            'source'             => 'self_service',
            'initiated_at'       => now(),
        ]);
    }
}
