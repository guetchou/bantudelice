<?php

namespace App\Observers;

use App\PartnerWithdrawal;
use App\Services\FinancialLedgerService;

class PartnerWithdrawalObserver
{
    public function __construct(
        private readonly FinancialLedgerService $ledger,
    ) {}

    public function created(PartnerWithdrawal $withdrawal): void
    {
        if ($withdrawal->status === 'reserved') {
            $this->ledger->recordWithdrawalReservation($withdrawal);
        }
    }

    public function updated(PartnerWithdrawal $withdrawal): void
    {
        if (! $withdrawal->wasChanged('status')) {
            return;
        }

        $previous = (string) $withdrawal->getOriginal('status');
        $current = (string) $withdrawal->status;

        if ($current === 'paid') {
            $this->ledger->recordWithdrawalPaid($withdrawal);
            return;
        }

        if (
            in_array($current, ['failed', 'cancelled'], true)
            && in_array($previous, ['created', 'reserved', 'submitted', 'pending', 'unknown'], true)
        ) {
            $this->ledger->recordWithdrawalRelease(
                $withdrawal,
                'Retrait ' . $current . ' après statut ' . $previous
            );
            return;
        }

        if ($current === 'reversed') {
            $this->ledger->record([
                'entry_key' => 'withdrawal:' . $withdrawal->id . ':available:credit:reversal',
                'module' => 'payments',
                'entry_type' => 'withdrawal_reversed',
                'direction' => 'credit',
                'status' => 'posted',
                'owner_type' => $withdrawal->partner_type,
                'owner_id' => $withdrawal->partner_id,
                'account_code' => 'partner_available',
                'source_type' => 'partner_withdrawal_reversal',
                'source_id' => $withdrawal->id,
                'withdrawal_id' => $withdrawal->id,
                'reference' => $withdrawal->provider_reference ?: $withdrawal->reference(),
                'currency' => $withdrawal->currency ?: 'XAF',
                'amount' => (float) $withdrawal->net_amount,
                'effective_at' => now(),
                'metadata' => [
                    'previous_status' => $previous,
                    'current_status' => $current,
                ],
            ]);
        }
    }
}
