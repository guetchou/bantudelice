<?php

namespace Tests\Feature;

use App\Domain\Finance\Models\FinancialPosting;
use App\Domain\Finance\Models\FinancialPostingBatch;
use App\Domain\Finance\Services\FinancialAccountService;
use App\Domain\Finance\Services\FinancialLedgerService;
use App\Domain\Finance\Services\PartnerLedgerWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinancialLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_is_pending_until_business_execution_then_revenue_is_recognized(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $accounts = app(FinancialAccountService::class);

        $result = $ledger->recordCollectionDistribution(
            paymentId: 501,
            totalAmount: 10000,
            restaurantId: 11,
            restaurantNet: 7000,
            driverId: 22,
            driverNet: 1000,
            platformCommission: 1500,
            platformServiceFee: 300,
            taxPayable: 200,
        );

        $this->assertFalse($result['reused']);
        $this->assertSame(7000, $ledger->partnerPosition('restaurant', 11)['pending']);
        $this->assertSame(0, $ledger->partnerPosition('restaurant', 11)['available']);
        $this->assertSame(1000, $ledger->partnerPosition('driver', 22)['pending']);
        $this->assertSame(1500, $ledger->balance($accounts->platform('platform_commission_deferred')));
        $this->assertSame(0, $ledger->balance($accounts->platform('platform_commission_revenue')));

        $ledger->releasePartnerEarning('restaurant', 11, 'order', 601, 7000);
        $ledger->releasePartnerEarning('driver', 22, 'delivery', 602, 1000);
        $ledger->recognizePlatformRevenue('order', 601, 1500, 300);

        $restaurant = $ledger->partnerPosition('restaurant', 11);
        $driver = $ledger->partnerPosition('driver', 22);

        $this->assertSame(0, $restaurant['pending']);
        $this->assertSame(7000, $restaurant['available']);
        $this->assertSame(0, $driver['pending']);
        $this->assertSame(1000, $driver['available']);
        $this->assertSame(0, $ledger->balance($accounts->platform('platform_commission_deferred')));
        $this->assertSame(1500, $ledger->balance($accounts->platform('platform_commission_revenue')));
        $this->assertSame(300, $ledger->balance($accounts->platform('platform_service_fee_revenue')));
        $this->assertSame(200, $ledger->balance($accounts->platform('tax_payable')));
        $this->assertSame(10000, $ledger->balance($accounts->platform('mtn_collections_cash')));
    }

    public function test_unbalanced_batch_is_rejected_before_any_write(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $accounts = app(FinancialAccountService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Écriture déséquilibrée');

        try {
            $ledger->postBatch('invalid', 'invalid:1', [
                ['account' => $accounts->platform('mtn_collections_cash'), 'direction' => 'debit', 'amount' => 1000],
                ['account' => $accounts->platform('platform_commission_revenue'), 'direction' => 'credit', 'amount' => 900],
            ]);
        } finally {
            $this->assertDatabaseCount('financial_posting_batches', 0);
            $this->assertDatabaseCount('financial_postings', 0);
        }
    }

    public function test_same_idempotency_key_with_different_financial_payload_is_rejected(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $accounts = app(FinancialAccountService::class);
        $cash = $accounts->platform('mtn_collections_cash');
        $revenue = $accounts->platform('platform_commission_revenue');

        $ledger->postBatch('idempotency_test', 'same-key', [
            ['account' => $cash, 'direction' => 'debit', 'amount' => 100],
            ['account' => $revenue, 'direction' => 'credit', 'amount' => 100],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('contenu financier différent');

        $ledger->postBatch('idempotency_test', 'same-key', [
            ['account' => $cash, 'direction' => 'debit', 'amount' => 200],
            ['account' => $revenue, 'direction' => 'credit', 'amount' => 200],
        ]);
    }

    public function test_withdrawal_moves_available_to_reserved_then_reduces_cash_once(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $workflow = app(PartnerLedgerWorkflowService::class);
        $accounts = app(FinancialAccountService::class);

        $ledger->recordCollectionDistribution(701, 5000, 33, 5000, null, 0, 0);
        $ledger->releasePartnerEarning('restaurant', 33, 'order', 702, 5000);
        $ledger->transferCollectionsToDisbursement(703, 5000);

        $firstReserve = $workflow->reserve('restaurant', 33, 801, 2000);
        $secondReserve = $workflow->reserve('restaurant', 33, 801, 2000);
        $this->assertFalse($firstReserve['reused']);
        $this->assertTrue($secondReserve['reused']);

        $reserved = $ledger->partnerPosition('restaurant', 33);
        $this->assertSame(3000, $reserved['available']);
        $this->assertSame(2000, $reserved['reserved']);

        $workflow->confirmPaid('restaurant', 33, 801, 2000);

        $paid = $ledger->partnerPosition('restaurant', 33);
        $this->assertSame(3000, $paid['available']);
        $this->assertSame(0, $paid['reserved']);
        $this->assertSame(3000, $ledger->balance($accounts->platform('mtn_disbursement_cash')));
    }

    public function test_failed_withdrawal_restores_available_balance_once(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $workflow = app(PartnerLedgerWorkflowService::class);

        $ledger->recordCollectionDistribution(901, 2500, null, 0, 44, 2500, 0);
        $ledger->releasePartnerEarning('driver', 44, 'delivery', 902, 2500);
        $workflow->reserve('driver', 44, 903, 1000);

        $first = $workflow->releaseFailed('driver', 44, 903, 1000, 'provider_failed');
        $second = $workflow->releaseFailed('driver', 44, 903, 1000, 'provider_failed');

        $this->assertFalse($first['reused']);
        $this->assertTrue($second['reused']);
        $this->assertSame(2500, $ledger->partnerPosition('driver', 44)['available']);
        $this->assertSame(0, $ledger->partnerPosition('driver', 44)['reserved']);
    }

    public function test_posted_lines_and_batches_cannot_be_modified_or_deleted(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $accounts = app(FinancialAccountService::class);

        $result = $ledger->postBatch('immutability_test', 'immutability:1', [
            ['account' => $accounts->platform('mtn_collections_cash'), 'direction' => 'debit', 'amount' => 100],
            ['account' => $accounts->platform('platform_commission_revenue'), 'direction' => 'credit', 'amount' => 100],
        ]);

        $posting = FinancialPosting::query()->firstOrFail();
        $batch = FinancialPostingBatch::query()->findOrFail($result['batch']->id);

        try {
            $posting->update(['amount' => 99]);
            $this->fail('Posting mutation should have failed.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('ne peut pas être modifiée', $exception->getMessage());
        }

        $this->expectException(\LogicException::class);
        $batch->delete();
    }
}
