<?php

namespace App\Domain\Finance\Contracts;

use App\Domain\Finance\Data\CollectionDistribution;
use App\Domain\Finance\Data\PartnerPosition;
use App\Domain\Finance\Data\PostingReceipt;
use App\Domain\Finance\Data\WithdrawalInstruction;

interface FinancialLedgerGateway
{
    public function recordCollection(CollectionDistribution $distribution): PostingReceipt;

    public function releasePartnerEarning(
        string $partnerType,
        int $partnerId,
        string $sourceType,
        int $sourceId,
        int $amount
    ): PostingReceipt;

    public function recognizePlatformRevenue(
        string $sourceType,
        int $sourceId,
        int $commissionAmount,
        int $serviceFeeAmount = 0
    ): PostingReceipt;

    public function reserveWithdrawal(WithdrawalInstruction $instruction): PostingReceipt;

    public function confirmWithdrawal(WithdrawalInstruction $instruction): PostingReceipt;

    public function releaseWithdrawal(
        WithdrawalInstruction $instruction,
        string $reason
    ): PostingReceipt;

    public function partnerPosition(string $partnerType, int $partnerId): PartnerPosition;
}
