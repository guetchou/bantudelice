<?php

namespace Tests\Feature;

use App\Domain\Finance\Adapters\PartnerLedgerV2Gateway;
use App\Domain\Finance\Contracts\FinancialLedgerGateway;
use App\Domain\Finance\Data\CollectionDistribution;
use App\Domain\Finance\Data\WithdrawalInstruction;
use App\Domain\Finance\Services\FinancialLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinancialLedgerGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_records_collection_idempotently_and_releases_partner_earning(): void
    {
        $gateway = app(PartnerLedgerV2Gateway::class);
        $this->assertInstanceOf(FinancialLedgerGateway::class, $gateway);

        $distribution = new CollectionDistribution(
            paymentId: 1001,
            totalAmount: 10000,
            restaurantId: 51,
            restaurantNet: 8000,
            driverId: null,
            driverNet: 0,
            platformCommission: 1500,
            platformServiceFee: 300,
            taxPayable: 200,
        );

        $first = $gateway->recordCollection($distribution);
        $second = $gateway->recordCollection($distribution);

        $this->assertFalse($first->reused);
        $this->assertTrue($second->reused);
        $this->assertSame($first->batchUuid, $second->batchUuid);

        $pending = $gateway->partnerPosition('restaurant', 51);
        $this->assertSame(8000, $pending->pending);
        $this->assertSame(0, $pending->available);

        $gateway->releasePartnerEarning('restaurant', 51, 'order', 2001, 8000);

        $available = $gateway->partnerPosition('restaurant', 51);
        $this->assertSame(0, $available->pending);
        $this->assertSame(8000, $available->available);
        $this->assertSame(8000, $available->totalDue);
    }

    public function test_gateway_preserves_withdrawal_reservation_on_unknown_outcome(): void
    {
        $gateway = app(PartnerLedgerV2Gateway::class);

        $gateway->recordCollection(new CollectionDistribution(
            paymentId: 1002,
            totalAmount: 3000,
            restaurantId: null,
            restaurantNet: 0,
            driverId: 61,
            driverNet: 3000,
            platformCommission: 0,
        ));
        $gateway->releasePartnerEarning('driver', 61, 'delivery', 2002, 3000);

        $instruction = new WithdrawalInstruction('driver', 61, 3001, 1200);
        $gateway->reserveWithdrawal($instruction);

        $position = $gateway->partnerPosition('driver', 61);
        $this->assertSame(1800, $position->available);
        $this->assertSame(1200, $position->reserved);

        $samePosition = $gateway->partnerPosition('driver', 61);
        $this->assertSame(1200, $samePosition->reserved);
    }

    public function test_gateway_confirms_withdrawal_after_treasury_funding(): void
    {
        $gateway = app(PartnerLedgerV2Gateway::class);
        $ledger = app(FinancialLedgerService::class);

        $gateway->recordCollection(new CollectionDistribution(
            paymentId: 1003,
            totalAmount: 5000,
            restaurantId: 71,
            restaurantNet: 5000,
            driverId: null,
            driverNet: 0,
            platformCommission: 0,
        ));
        $gateway->releasePartnerEarning('restaurant', 71, 'order', 2003, 5000);
        $ledger->transferCollectionsToDisbursement(4001, 5000);

        $instruction = new WithdrawalInstruction('restaurant', 71, 3002, 2000);
        $gateway->reserveWithdrawal($instruction);
        $receipt = $gateway->confirmWithdrawal($instruction);

        $this->assertFalse($receipt->reused);
        $position = $gateway->partnerPosition('restaurant', 71);
        $this->assertSame(3000, $position->available);
        $this->assertSame(0, $position->reserved);
        $this->assertSame(3000, $position->totalDue);
    }

    public function test_distribution_contract_rejects_an_unbalanced_breakdown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must equal the collected total');

        new CollectionDistribution(
            paymentId: 1004,
            totalAmount: 10000,
            restaurantId: 81,
            restaurantNet: 9000,
            driverId: null,
            driverNet: 0,
            platformCommission: 500,
        );
    }
}
