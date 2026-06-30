<?php

namespace Tests\Feature;

use App\Domain\Finance\Models\FinancialPosting;
use App\Domain\Finance\Models\FinancialPostingBatch;
use App\Domain\Finance\Services\FinancialAccountService;
use App\Domain\Finance\Services\FinancialLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinancialLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_distribution_separates_partner_debts_and_bantudelice_revenue(): void
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
        $this->assertDatabaseCount('financial_posting_batches', 1);
        $this->assertDatabaseCount('financial_postings', 6);

        $restaurant = $accounts->partner('restaurant', 11, FinancialAccountService::PARTNER_AVAILABLE);
        $driver = $accounts->partner('driver', 22, FinancialAccountService::PARTNER_AVAILABLE);
        $commission = $accounts->platform('platform_commission_revenue');
        $serviceFee = $accounts->platform('platform_service_fee_revenue');
        $tax = $accounts->platform('tax_payable');
        $collections = $accounts->platform('mtn_collections_cash');

        $this->assertSame(7000, $ledger->balance($restaurant));
        $this->assertSame(1000, $ledger->balance($driver));
        $this->assertSame(1500, $ledger->balance($commission));
        $this->assertSame(300, $ledger->balance($serviceFee));
        $this->assertSame(200, $ledger->balance($tax));
        $this->assertSame(10000, $ledger->balance($collections));

        $reused = $ledger->recordCollectionDistribution(
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

        $this->assertTrue($reused['reused']);
        $this->assertDatabaseCount('financial_posting_batches', 1);
        $this->assertDatabaseCount('financial_postings', 6);
    }

    public function test_unbalanced_batch_is_rejected_before_any_write(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $accounts = app(FinancialAccountService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Écriture déséquilibrée');

        try {
            $ledger->postBatch(
                'invalid_test',
                'invalid-test:1',
                [
                    [
                        'account' => $accounts->platform('mtn_collections_cash'),
                        'direction' => 'debit',
                        'amount' => 1000,
                    ],
                    [
                        'account' => $accounts->platform('platform_commission_revenue'),
                        'direction' => 'credit',
                        'amount' => 900,
                    ],
                ]
            );
        } finally {
            $this->assertDatabaseCount('financial_posting_batches', 0);
            $this->assertDatabaseCount('financial_postings', 0);
        }
    }

    public function test_withdrawal_moves_money_from_available_to_reserved_then_paid(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $accounts = app(FinancialAccountService::class);

        $available = $accounts->partner('restaurant', 33, FinancialAccountService::PARTNER_AVAILABLE);
        $collections = $accounts->platform('mtn_collections_cash');

        $ledger->postBatch(
            'opening_controlled_position',
            'opening:restaurant:33:v1',
            [
                ['account' => $collections, 'direction' => 'debit', 'amount' => 5000],
                ['account' => $available, 'direction' => 'credit', 'amount' => 5000],
            ]
        );

        $ledger->transferCollectionsToDisbursement(701, 5000);
        $ledger->reserveWithdrawal('restaurant', 33, 801, 2000);

        $reservedPosition = $ledger->partnerPosition('restaurant', 33);
        $this->assertSame(3000, $reservedPosition['available']);
        $this->assertSame(2000, $reservedPosition['reserved']);
        $this->assertSame(5000, $reservedPosition['total_due']);

        $ledger->confirmWithdrawal('restaurant', 33, 801, 2000);

        $paidPosition = $ledger->partnerPosition('restaurant', 33);
        $this->assertSame(3000, $paidPosition['available']);
        $this->assertSame(0, $paidPosition['reserved']);
        $this->assertSame(3000, $paidPosition['total_due']);
        $this->assertSame(3000, $ledger->balance($accounts->platform('mtn_disbursement_cash')));
    }

    public function test_failed_withdrawal_releases_reserved_money_once(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $accounts = app(FinancialAccountService::class);
        $available = $accounts->partner('driver', 44, FinancialAccountService::PARTNER_AVAILABLE);
        $collections = $accounts->platform('mtn_collections_cash');

        $ledger->postBatch(
            'opening_controlled_position',
            'opening:driver:44:v1',
            [
                ['account' => $collections, 'direction' => 'debit', 'amount' => 2500],
                ['account' => $available, 'direction' => 'credit', 'amount' => 2500],
            ]
        );

        $ledger->reserveWithdrawal('driver', 44, 802, 1000);
        $first = $ledger->releaseWithdrawal('driver', 44, 802, 1000, 'provider_failed');
        $second = $ledger->releaseWithdrawal('driver', 44, 802, 1000, 'provider_failed');

        $this->assertFalse($first['reused']);
        $this->assertTrue($second['reused']);

        $position = $ledger->partnerPosition('driver', 44);
        $this->assertSame(2500, $position['available']);
        $this->assertSame(0, $position['reserved']);
    }

    public function test_posted_lines_and_batches_cannot_be_modified_or_deleted(): void
    {
        $ledger = app(FinancialLedgerService::class);
        $accounts = app(FinancialAccountService::class);

        $result = $ledger->postBatch(
            'immutability_test',
            'immutability-test:1',
            [
                ['account' => $accounts->platform('mtn_collections_cash'), 'direction' => 'debit', 'amount' => 100],
                ['account' => $accounts->platform('platform_commission_revenue'), 'direction' => 'credit', 'amount' => 100],
            ]
        );

        $posting = FinancialPosting::query()->firstOrFail();
        $batch = FinancialPostingBatch::query()->findOrFail($result['batch']->id);

        try {
            $posting->update(['amount' => 99]);
            $this->fail('La modification d’une écriture aurait dû être refusée.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('ne peut pas être modifiée', $exception->getMessage());
        }

        $this->expectException(\LogicException::class);
        $batch->delete();
    }
}
