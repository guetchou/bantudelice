<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Data\CollectedPayment;
use App\Domain\Finance\Models\FinancialAccount;

final class FinancialLedgerService
{
    public function __construct(
        private readonly LedgerPostingService $postings,
        private readonly FinancialAccountService $accounts,
        private readonly CollectionLedgerService $collections,
        private readonly PaymentCollectionLedgerService $paymentCollections,
        private readonly WithdrawalLedgerService $withdrawals,
    ) {
    }

    public function postBatch(string $eventType, string $idempotencyKey, array $lines, array $context = []): array
    {
        return $this->postings->postBatch($eventType, $idempotencyKey, $lines, $context);
    }

    public function balance(FinancialAccount $account): int
    {
        return $this->postings->balance($account);
    }

    public function partnerPosition(string $partnerType, int $partnerId): array
    {
        $accounts = $this->accounts->provisionPartner($partnerType, $partnerId);
        $pending = $this->balance($accounts['pending']);
        $available = $this->balance($accounts['available']);
        $reserved = $this->balance($accounts['reserved']);

        return [
            'partner_type' => $partnerType,
            'partner_id' => $partnerId,
            'currency' => 'XAF',
            'pending' => $pending,
            'available' => $available,
            'reserved' => $reserved,
            'total_due' => $pending + $available + $reserved,
            'accounts' => [
                'pending' => $accounts['pending']->code,
                'available' => $accounts['available']->code,
                'reserved' => $accounts['reserved']->code,
            ],
        ];
    }

    public function recordCollectedPayment(CollectedPayment $payment): array
    {
        return $this->paymentCollections->record($payment);
    }

    public function recordCollectionDistribution(
        int $paymentId,
        int $totalAmount,
        ?int $restaurantId,
        int $restaurantNet,
        ?int $driverId,
        int $driverNet,
        int $platformCommission,
        int $platformServiceFee = 0,
        int $taxPayable = 0,
        array $metadata = []
    ): array {
        return $this->collections->recordDistribution(
            $paymentId,
            $totalAmount,
            $restaurantId,
            $restaurantNet,
            $driverId,
            $driverNet,
            $platformCommission,
            $platformServiceFee,
            $taxPayable,
            $metadata
        );
    }

    public function releasePartnerEarning(
        string $partnerType,
        int $partnerId,
        string $sourceType,
        int $sourceId,
        int $amount
    ): array {
        return $this->collections->releasePartnerEarning(
            $partnerType,
            $partnerId,
            $sourceType,
            $sourceId,
            $amount
        );
    }

    public function recognizePlatformRevenue(
        string $sourceType,
        int $sourceId,
        int $commissionAmount,
        int $serviceFeeAmount = 0
    ): array {
        return $this->collections->recognizePlatformRevenue(
            $sourceType,
            $sourceId,
            $commissionAmount,
            $serviceFeeAmount
        );
    }

    public function reserveWithdrawal(string $partnerType, int $partnerId, int $withdrawalId, int $amount): array
    {
        return $this->withdrawals->reserve($partnerType, $partnerId, $withdrawalId, $amount);
    }

    public function confirmWithdrawal(string $partnerType, int $partnerId, int $withdrawalId, int $amount): array
    {
        return $this->withdrawals->confirm($partnerType, $partnerId, $withdrawalId, $amount);
    }

    public function releaseWithdrawal(
        string $partnerType,
        int $partnerId,
        int $withdrawalId,
        int $amount,
        string $reason
    ): array {
        return $this->withdrawals->release($partnerType, $partnerId, $withdrawalId, $amount, $reason);
    }

    public function transferCollectionsToDisbursement(int $settlementId, int $amount): array
    {
        return $this->withdrawals->transferCollectionsToDisbursement($settlementId, $amount);
    }

    public function recordOperatorFee(int $transactionId, int $amount, string $cashPurpose): array
    {
        return $this->withdrawals->recordOperatorFee($transactionId, $amount, $cashPurpose);
    }
}
