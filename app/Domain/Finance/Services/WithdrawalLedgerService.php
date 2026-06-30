<?php

namespace App\Domain\Finance\Services;

use Illuminate\Support\Facades\DB;

final class WithdrawalLedgerService
{
    public function __construct(
        private readonly LedgerPostingService $postings,
        private readonly FinancialAccountService $accounts,
    ) {
    }

    public function reserve(string $partnerType, int $partnerId, int $withdrawalId, int $amount): array
    {
        $this->postings->assertPositive($amount, 'Le montant du retrait');
        $accounts = $this->accounts->provisionPartner($partnerType, $partnerId);
        $key = 'partner-withdrawal:' . $withdrawalId . ':reserve:v1';
        $lines = [
            ['account' => $accounts['available'], 'direction' => 'debit', 'amount' => $amount],
            ['account' => $accounts['reserved'], 'direction' => 'credit', 'amount' => $amount],
        ];
        $context = ['source_type' => 'partner_withdrawal', 'source_id' => $withdrawalId];

        $existing = $this->postings->findExisting('withdrawal_reserved', $key, $lines, $context);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($accounts, $amount, $key, $lines, $context) {
            $this->postings->lockAccounts([$accounts['available'], $accounts['reserved']]);
            if ($this->postings->balance($accounts['available']) < $amount) {
                throw new \DomainException('Solde financier disponible insuffisant.');
            }

            return $this->postings->postBatch('withdrawal_reserved', $key, $lines, $context);
        });
    }

    public function confirm(string $partnerType, int $partnerId, int $withdrawalId, int $amount): array
    {
        $this->postings->assertPositive($amount, 'Le montant du retrait');
        $accounts = $this->accounts->provisionPartner($partnerType, $partnerId);
        $disbursement = $this->accounts->platform('mtn_disbursement_cash');
        $key = 'partner-withdrawal:' . $withdrawalId . ':paid:v1';
        $lines = [
            ['account' => $accounts['reserved'], 'direction' => 'debit', 'amount' => $amount],
            ['account' => $disbursement, 'direction' => 'credit', 'amount' => $amount],
        ];
        $context = ['source_type' => 'partner_withdrawal', 'source_id' => $withdrawalId];

        $existing = $this->postings->findExisting('withdrawal_paid', $key, $lines, $context);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($accounts, $disbursement, $amount, $key, $lines, $context) {
            $this->postings->lockAccounts([$accounts['reserved'], $disbursement]);
            if ($this->postings->balance($accounts['reserved']) < $amount) {
                throw new \DomainException('Le montant réservé est insuffisant.');
            }
            if ($this->postings->balance($disbursement) < $amount) {
                throw new \DomainException('La trésorerie MTN Disbursements est insuffisante.');
            }

            return $this->postings->postBatch('withdrawal_paid', $key, $lines, $context);
        });
    }

    public function release(
        string $partnerType,
        int $partnerId,
        int $withdrawalId,
        int $amount,
        string $reason
    ): array {
        $this->postings->assertPositive($amount, 'Le montant du retrait');
        $accounts = $this->accounts->provisionPartner($partnerType, $partnerId);
        $key = 'partner-withdrawal:' . $withdrawalId . ':release:v1';
        $lines = [
            ['account' => $accounts['reserved'], 'direction' => 'debit', 'amount' => $amount],
            ['account' => $accounts['available'], 'direction' => 'credit', 'amount' => $amount],
        ];
        $context = [
            'source_type' => 'partner_withdrawal',
            'source_id' => $withdrawalId,
            'metadata' => ['reason' => $reason],
        ];

        $existing = $this->postings->findExisting('withdrawal_released', $key, $lines, $context);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($accounts, $amount, $key, $lines, $context) {
            $this->postings->lockAccounts([$accounts['reserved'], $accounts['available']]);
            if ($this->postings->balance($accounts['reserved']) < $amount) {
                throw new \DomainException('Le montant réservé est insuffisant pour être libéré.');
            }

            return $this->postings->postBatch('withdrawal_released', $key, $lines, $context);
        });
    }

    public function transferCollectionsToDisbursement(int $settlementId, int $amount): array
    {
        $this->postings->assertPositive($amount, 'Le montant du transfert');
        $collections = $this->accounts->platform('mtn_collections_cash');
        $disbursement = $this->accounts->platform('mtn_disbursement_cash');
        $key = 'treasury-settlement:' . $settlementId . ':v1';
        $lines = [
            ['account' => $disbursement, 'direction' => 'debit', 'amount' => $amount],
            ['account' => $collections, 'direction' => 'credit', 'amount' => $amount],
        ];
        $context = ['source_type' => 'treasury_settlement', 'source_id' => $settlementId];

        $existing = $this->postings->findExisting('treasury_settlement', $key, $lines, $context);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($collections, $disbursement, $amount, $key, $lines, $context) {
            $this->postings->lockAccounts([$collections, $disbursement]);
            if ($this->postings->balance($collections) < $amount) {
                throw new \DomainException('La trésorerie MTN Collections est insuffisante.');
            }

            return $this->postings->postBatch('treasury_settlement', $key, $lines, $context);
        });
    }

    public function recordOperatorFee(int $transactionId, int $amount, string $cashPurpose): array
    {
        $this->postings->assertPositive($amount, 'Le montant des frais opérateur');
        $cash = $this->accounts->platform($cashPurpose);
        $expense = $this->accounts->platform('operator_fee_expense');
        $key = 'operator-fee:' . $transactionId . ':' . $cashPurpose . ':v1';
        $lines = [
            ['account' => $expense, 'direction' => 'debit', 'amount' => $amount],
            ['account' => $cash, 'direction' => 'credit', 'amount' => $amount],
        ];
        $context = ['source_type' => 'operator_fee', 'source_id' => $transactionId];

        $existing = $this->postings->findExisting('operator_fee_recorded', $key, $lines, $context);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($cash, $expense, $amount, $key, $lines, $context) {
            $this->postings->lockAccounts([$cash, $expense]);
            if ($this->postings->balance($cash) < $amount) {
                throw new \DomainException('La trésorerie source est insuffisante.');
            }

            return $this->postings->postBatch('operator_fee_recorded', $key, $lines, $context);
        });
    }
}
