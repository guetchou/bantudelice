<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\FinancialPostingBatch;

final class PartnerLedgerWorkflowService
{
    public function __construct(
        private readonly FinancialLedgerService $ledger,
        private readonly FinancialAccountService $accounts,
    ) {
    }

    public function reserve(string $partnerType, int $partnerId, int $withdrawalId, int $amount): array
    {
        $key = $this->key($withdrawalId, 'reserve');
        $existing = $this->existing($key);

        if ($existing) {
            return ['batch' => $existing, 'reused' => true];
        }

        return $this->ledger->reserveWithdrawal($partnerType, $partnerId, $withdrawalId, $amount);
    }

    public function confirmPaid(string $partnerType, int $partnerId, int $withdrawalId, int $amount): array
    {
        $key = $this->key($withdrawalId, 'paid');
        $existing = $this->existing($key);

        if ($existing) {
            return ['batch' => $existing, 'reused' => true];
        }

        $this->assertEventExists($withdrawalId, 'reserve');
        $this->assertEventAbsent($withdrawalId, 'release', 'Un retrait libéré ne peut pas être marqué payé.');

        $position = $this->ledger->partnerPosition($partnerType, $partnerId);
        if ($position['reserved'] < $amount) {
            throw new \DomainException('Le montant réservé est insuffisant pour confirmer ce retrait.');
        }

        return $this->ledger->confirmWithdrawal($partnerType, $partnerId, $withdrawalId, $amount);
    }

    public function releaseFailed(
        string $partnerType,
        int $partnerId,
        int $withdrawalId,
        int $amount,
        string $reason
    ): array {
        $key = $this->key($withdrawalId, 'release');
        $existing = $this->existing($key);

        if ($existing) {
            return ['batch' => $existing, 'reused' => true];
        }

        $this->assertEventExists($withdrawalId, 'reserve');
        $this->assertEventAbsent($withdrawalId, 'paid', 'Un retrait déjà payé ne peut pas être libéré comme un échec.');

        return $this->ledger->releaseWithdrawal(
            $partnerType,
            $partnerId,
            $withdrawalId,
            $amount,
            $reason
        );
    }

    public function reversePaid(
        string $partnerType,
        int $partnerId,
        int $withdrawalId,
        int $amount,
        string $reason
    ): array {
        $key = $this->key($withdrawalId, 'reverse-paid');
        $existing = $this->existing($key);

        if ($existing) {
            return ['batch' => $existing, 'reused' => true];
        }

        $paid = $this->assertEventExists($withdrawalId, 'paid');
        $available = $this->accounts->partner(
            $partnerType,
            $partnerId,
            FinancialAccountService::PARTNER_AVAILABLE
        );

        return $this->ledger->postBatch(
            'withdrawal_reversed',
            $key,
            [
                [
                    'account' => $this->accounts->platform('mtn_disbursement_cash'),
                    'direction' => 'debit',
                    'amount' => $amount,
                    'description' => 'Retour de trésorerie après inversion opérateur',
                ],
                [
                    'account' => $available,
                    'direction' => 'credit',
                    'amount' => $amount,
                    'description' => 'Rétablissement de la dette envers le partenaire',
                ],
            ],
            [
                'source_type' => 'partner_withdrawal',
                'source_id' => $withdrawalId,
                'reversal_of_batch_id' => $paid->id,
                'metadata' => ['reason' => $reason],
            ]
        );
    }

    private function key(int $withdrawalId, string $event): string
    {
        return 'partner-withdrawal:' . $withdrawalId . ':' . $event . ':v1';
    }

    private function existing(string $key): ?FinancialPostingBatch
    {
        return FinancialPostingBatch::query()
            ->where('idempotency_key', $key)
            ->with('postings')
            ->first();
    }

    private function assertEventExists(int $withdrawalId, string $event): FinancialPostingBatch
    {
        $batch = $this->existing($this->key($withdrawalId, $event));

        if (! $batch) {
            throw new \DomainException('Événement financier préalable manquant pour le retrait.');
        }

        return $batch;
    }

    private function assertEventAbsent(int $withdrawalId, string $event, string $message): void
    {
        if ($this->existing($this->key($withdrawalId, $event))) {
            throw new \DomainException($message);
        }
    }
}
