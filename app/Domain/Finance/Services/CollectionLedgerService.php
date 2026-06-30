<?php

namespace App\Domain\Finance\Services;

use Illuminate\Support\Facades\DB;

final class CollectionLedgerService
{
    public function __construct(
        private readonly LedgerPostingService $postings,
        private readonly FinancialAccountService $accounts,
    ) {
    }

    public function recordDistribution(
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
        $this->postings->assertPositive($totalAmount, 'Le montant encaissé');

        $components = [
            'restaurant' => $restaurantNet,
            'driver' => $driverNet,
            'commission_deferred' => $platformCommission,
            'service_fee_deferred' => $platformServiceFee,
            'tax' => $taxPayable,
        ];

        foreach ($components as $name => $amount) {
            if ($amount < 0) {
                throw new \DomainException('La composante ' . $name . ' ne peut pas être négative.');
            }
        }
        if (array_sum($components) !== $totalAmount) {
            throw new \DomainException('La ventilation ne correspond pas au montant encaissé.');
        }
        if ($restaurantNet > 0 && ! $restaurantId) {
            throw new \DomainException('Le restaurant bénéficiaire est obligatoire.');
        }
        if ($driverNet > 0 && ! $driverId) {
            throw new \DomainException('Le livreur bénéficiaire est obligatoire.');
        }

        $lines = [[
            'account' => $this->accounts->platform('mtn_collections_cash'),
            'direction' => 'debit',
            'amount' => $totalAmount,
            'description' => 'Encaissement client confirmé',
        ]];

        if ($restaurantNet > 0) {
            $lines[] = [
                'account' => $this->accounts->partner('restaurant', $restaurantId, FinancialAccountService::PARTNER_PENDING),
                'direction' => 'credit',
                'amount' => $restaurantNet,
                'description' => 'Dette restaurant en attente de libération',
            ];
        }
        if ($driverNet > 0) {
            $lines[] = [
                'account' => $this->accounts->partner('driver', $driverId, FinancialAccountService::PARTNER_PENDING),
                'direction' => 'credit',
                'amount' => $driverNet,
                'description' => 'Dette livreur en attente de libération',
            ];
        }
        if ($platformCommission > 0) {
            $lines[] = [
                'account' => $this->accounts->platform('platform_commission_deferred'),
                'direction' => 'credit',
                'amount' => $platformCommission,
                'description' => 'Commission BantuDelice non encore acquise',
            ];
        }
        if ($platformServiceFee > 0) {
            $lines[] = [
                'account' => $this->accounts->platform('platform_service_fee_deferred'),
                'direction' => 'credit',
                'amount' => $platformServiceFee,
                'description' => 'Frais de service BantuDelice non encore acquis',
            ];
        }
        if ($taxPayable > 0) {
            $lines[] = [
                'account' => $this->accounts->platform('tax_payable'),
                'direction' => 'credit',
                'amount' => $taxPayable,
                'description' => 'Taxes collectées à reverser',
            ];
        }

        return $this->postings->postBatch(
            'collection_distributed',
            'payment:' . $paymentId . ':collection-distribution:v1',
            $lines,
            [
                'source_type' => 'payment',
                'source_id' => $paymentId,
                'metadata' => array_replace($metadata, ['components' => $components]),
            ]
        );
    }

    public function releasePartnerEarning(
        string $partnerType,
        int $partnerId,
        string $sourceType,
        int $sourceId,
        int $amount
    ): array {
        $this->postings->assertPositive($amount, 'Le montant à libérer');
        $accounts = $this->accounts->provisionPartner($partnerType, $partnerId);
        $key = sprintf('partner-earning:%s:%d:%s:%d:release:v1', $partnerType, $partnerId, $sourceType, $sourceId);
        $lines = [
            ['account' => $accounts['pending'], 'direction' => 'debit', 'amount' => $amount],
            ['account' => $accounts['available'], 'direction' => 'credit', 'amount' => $amount],
        ];
        $context = ['source_type' => $sourceType, 'source_id' => $sourceId];

        $existing = $this->postings->findExisting('partner_earning_released', $key, $lines, $context);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($accounts, $amount, $key, $lines, $context) {
            $this->postings->lockAccounts([$accounts['pending'], $accounts['available']]);
            if ($this->postings->balance($accounts['pending']) < $amount) {
                throw new \DomainException('Le solde partenaire en attente est insuffisant.');
            }

            return $this->postings->postBatch('partner_earning_released', $key, $lines, $context);
        });
    }

    public function recognizePlatformRevenue(
        string $sourceType,
        int $sourceId,
        int $commissionAmount,
        int $serviceFeeAmount = 0
    ): array {
        if ($commissionAmount < 0 || $serviceFeeAmount < 0 || ($commissionAmount + $serviceFeeAmount) <= 0) {
            throw new \DomainException('Le revenu BantuDelice à reconnaître doit être positif.');
        }

        $commissionDeferred = $this->accounts->platform('platform_commission_deferred');
        $commissionRevenue = $this->accounts->platform('platform_commission_revenue');
        $serviceDeferred = $this->accounts->platform('platform_service_fee_deferred');
        $serviceRevenue = $this->accounts->platform('platform_service_fee_revenue');
        $lines = [];

        if ($commissionAmount > 0) {
            $lines[] = ['account' => $commissionDeferred, 'direction' => 'debit', 'amount' => $commissionAmount];
            $lines[] = ['account' => $commissionRevenue, 'direction' => 'credit', 'amount' => $commissionAmount];
        }
        if ($serviceFeeAmount > 0) {
            $lines[] = ['account' => $serviceDeferred, 'direction' => 'debit', 'amount' => $serviceFeeAmount];
            $lines[] = ['account' => $serviceRevenue, 'direction' => 'credit', 'amount' => $serviceFeeAmount];
        }

        $key = sprintf('platform-revenue:%s:%d:recognize:v1', $sourceType, $sourceId);
        $context = ['source_type' => $sourceType, 'source_id' => $sourceId];
        $existing = $this->postings->findExisting('platform_revenue_recognized', $key, $lines, $context);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use (
            $commissionDeferred,
            $commissionRevenue,
            $serviceDeferred,
            $serviceRevenue,
            $commissionAmount,
            $serviceFeeAmount,
            $key,
            $lines,
            $context
        ) {
            $this->postings->lockAccounts([$commissionDeferred, $commissionRevenue, $serviceDeferred, $serviceRevenue]);
            if ($commissionAmount > 0 && $this->postings->balance($commissionDeferred) < $commissionAmount) {
                throw new \DomainException('La commission différée est insuffisante.');
            }
            if ($serviceFeeAmount > 0 && $this->postings->balance($serviceDeferred) < $serviceFeeAmount) {
                throw new \DomainException('Les frais de service différés sont insuffisants.');
            }

            return $this->postings->postBatch('platform_revenue_recognized', $key, $lines, $context);
        });
    }
}
