<?php

namespace App\Domain\Finance\Adapters;

use App\Domain\Finance\Contracts\FinancialLedgerGateway;
use App\Domain\Finance\Data\CollectedPayment;
use App\Domain\Finance\Data\CollectionDistribution;
use App\Domain\Finance\Data\PartnerPosition;
use App\Domain\Finance\Data\PostingReceipt;
use App\Domain\Finance\Data\WithdrawalInstruction;
use App\Domain\Finance\Services\FinancialLedgerService;

final class PartnerLedgerV2Gateway implements FinancialLedgerGateway
{
    public function __construct(
        private readonly FinancialLedgerService $ledger,
    ) {
    }

    public function recordCollectedPayment(CollectedPayment $payment): PostingReceipt
    {
        return $this->receipt($this->ledger->recordCollectedPayment($payment));
    }

    public function recordCollection(CollectionDistribution $distribution): PostingReceipt
    {
        return $this->receipt($this->ledger->recordCollectionDistribution(
            paymentId: $distribution->paymentId,
            totalAmount: $distribution->totalAmount,
            restaurantId: $distribution->restaurantId,
            restaurantNet: $distribution->restaurantNet,
            driverId: $distribution->driverId,
            driverNet: $distribution->driverNet,
            platformCommission: $distribution->platformCommission,
            platformServiceFee: $distribution->platformServiceFee,
            taxPayable: $distribution->taxPayable,
            metadata: $distribution->metadata,
        ));
    }

    public function releasePartnerEarning(
        string $partnerType,
        int $partnerId,
        string $sourceType,
        int $sourceId,
        int $amount
    ): PostingReceipt {
        return $this->receipt($this->ledger->releasePartnerEarning(
            $partnerType,
            $partnerId,
            $sourceType,
            $sourceId,
            $amount
        ));
    }

    public function recognizePlatformRevenue(
        string $sourceType,
        int $sourceId,
        int $commissionAmount,
        int $serviceFeeAmount = 0
    ): PostingReceipt {
        return $this->receipt($this->ledger->recognizePlatformRevenue(
            $sourceType,
            $sourceId,
            $commissionAmount,
            $serviceFeeAmount
        ));
    }

    public function reserveWithdrawal(WithdrawalInstruction $instruction): PostingReceipt
    {
        return $this->receipt($this->ledger->reserveWithdrawal(
            $instruction->partnerType,
            $instruction->partnerId,
            $instruction->withdrawalId,
            $instruction->amount
        ));
    }

    public function confirmWithdrawal(WithdrawalInstruction $instruction): PostingReceipt
    {
        return $this->receipt($this->ledger->confirmWithdrawal(
            $instruction->partnerType,
            $instruction->partnerId,
            $instruction->withdrawalId,
            $instruction->amount
        ));
    }

    public function releaseWithdrawal(
        WithdrawalInstruction $instruction,
        string $reason
    ): PostingReceipt {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('A withdrawal release reason is required.');
        }

        return $this->receipt($this->ledger->releaseWithdrawal(
            $instruction->partnerType,
            $instruction->partnerId,
            $instruction->withdrawalId,
            $instruction->amount,
            $reason
        ));
    }

    public function partnerPosition(string $partnerType, int $partnerId): PartnerPosition
    {
        $position = $this->ledger->partnerPosition($partnerType, $partnerId);

        return new PartnerPosition(
            partnerType: (string) $position['partner_type'],
            partnerId: (int) $position['partner_id'],
            currency: (string) $position['currency'],
            pending: (int) $position['pending'],
            available: (int) $position['available'],
            reserved: (int) $position['reserved'],
            totalDue: (int) $position['total_due'],
        );
    }

    private function receipt(array $result): PostingReceipt
    {
        return new PostingReceipt(
            batchUuid: (string) $result['batch']->uuid,
            reused: (bool) $result['reused'],
        );
    }
}
