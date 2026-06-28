<?php

namespace Tests\Feature;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\PartnerWithdrawal;
use App\Services\PartnerWithdrawalService;
use App\Services\ResilientPartnerWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayWithdrawalSafetyTest extends TestCase
{
    use RefreshDatabase;

    private GePayClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Withdrawal safety',
            'api_key' => 'gpk_withdrawal_safety',
            'api_secret' => Str::random(64),
            'capabilities' => ['disbursement'],
            'is_active' => true,
        ]);

        config(['gepay.internal_client_uuid' => $this->client->uuid]);
    }

    public function test_container_resolves_resilient_withdrawal_service(): void
    {
        $this->assertInstanceOf(
            ResilientPartnerWithdrawalService::class,
            app(PartnerWithdrawalService::class)
        );
    }

    public function test_missing_provider_reference_is_repaired_from_external_reference(): void
    {
        $withdrawal = $this->withdrawal('WITHDRAWAL-LINK-001');
        $transaction = $this->transaction(
            'WITHDRAWAL-LINK-001',
            TransactionStatus::SUCCESSFUL
        );

        app(PartnerWithdrawalService::class)->reconcile($withdrawal->id);

        $fresh = $withdrawal->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertSame($transaction->uuid, $fresh->provider_reference);
        $this->assertNotNull($fresh->paid_at);
    }

    public function test_missing_gepay_transaction_keeps_withdrawal_unknown_and_reserved(): void
    {
        $withdrawal = $this->withdrawal('WITHDRAWAL-MISSING-001');

        app(PartnerWithdrawalService::class)->reconcile($withdrawal->id);

        $fresh = $withdrawal->fresh();
        $this->assertSame('unknown', $fresh->status);
        $this->assertFalse($fresh->isFailed());
        $this->assertSame('GEPAY_TRANSACTION_NOT_FOUND', $fresh->failure_code);
        $this->assertTrue((bool) data_get($fresh->metadata, 'manual_review_required'));
    }

    public function test_reversed_transaction_marks_withdrawal_reversed(): void
    {
        $withdrawal = $this->withdrawal('WITHDRAWAL-REVERSED-001');
        $transaction = $this->transaction(
            'WITHDRAWAL-REVERSED-001',
            TransactionStatus::REVERSED
        );
        $withdrawal->forceFill(['provider_reference' => $transaction->uuid])->save();

        app(PartnerWithdrawalService::class)->reconcile($withdrawal->id);

        $fresh = $withdrawal->fresh();
        $this->assertSame('reversed', $fresh->status);
        $this->assertTrue((bool) data_get($fresh->metadata, 'financial_reversal'));
        $this->assertNull($fresh->paid_at);
    }

    private function withdrawal(string $externalReference): PartnerWithdrawal
    {
        return PartnerWithdrawal::create([
            'uuid' => (string) Str::uuid(),
            'partner_type' => 'restaurant',
            'partner_id' => 1,
            'operator' => 'mtn',
            'provider' => 'gepay',
            'phone' => '0600000000',
            'requested_amount' => 5000,
            'fee_amount' => 0,
            'net_amount' => 5000,
            'currency' => 'XAF',
            'status' => 'unknown',
            'external_reference' => $externalReference,
            'idempotency_key' => 'withdrawal-test-'.Str::lower(Str::random(12)),
            'source' => 'self_service',
            'initiated_at' => now(),
        ]);
    }

    private function transaction(
        string $externalReference,
        TransactionStatus $status
    ): GePayTransaction {
        return GePayTransaction::create([
            'uuid' => (string) Str::uuid(),
            'client_id' => $this->client->id,
            'type' => TransactionType::DISBURSEMENT,
            'provider' => 'mtn_momo',
            'external_reference' => $externalReference,
            'provider_reference' => (string) Str::uuid(),
            'idempotency_key' => 'transaction-test-'.Str::lower(Str::random(12)),
            'request_hash' => hash('sha256', $externalReference),
            'amount' => 5000,
            'currency' => 'XAF',
            'phone' => '242060000000',
            'phone_masked' => '24••••00',
            'status' => $status,
            'metadata' => [],
            'completed_at' => $status->isTerminal() ? now() : null,
        ]);
    }
}
