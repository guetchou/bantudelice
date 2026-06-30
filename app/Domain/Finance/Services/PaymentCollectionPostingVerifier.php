<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\FinancialMirrorEvent;
use App\Domain\Finance\Models\FinancialPostingBatch;
use App\Payment;

final class PaymentCollectionPostingVerifier
{
    public function verify(Payment $payment, FinancialMirrorEvent $event): array
    {
        if (strtolower((string) $event->status) !== 'posted') {
            return [];
        }

        $batchUuid = trim((string) $event->posting_batch_uuid);
        if ($batchUuid === '') {
            return ['posted_mirror_missing_batch'];
        }

        $batch = FinancialPostingBatch::query()
            ->with('postings.account')
            ->where('uuid', $batchUuid)
            ->first();

        if (! $batch) {
            return ['posted_mirror_missing_batch'];
        }

        $amount = $this->integerAmountOrNull($payment->amount);
        if ($amount === null) {
            return ['posted_mirror_invalid_batch'];
        }

        $expectedKey = 'payment:' . $payment->id . ':collection-received:v1';
        if ((string) $batch->event_type !== 'payment_collection_received'
            || (string) $batch->source_type !== 'payment'
            || (int) $batch->source_id !== (int) $payment->id
            || (string) $batch->idempotency_key !== $expectedKey
            || (string) $batch->status !== 'posted') {
            return ['posted_mirror_invalid_batch'];
        }

        $postings = $batch->postings;
        if ($postings->count() !== 2) {
            return ['posted_mirror_invalid_batch'];
        }

        $debits = $postings->where('direction', 'debit');
        $credits = $postings->where('direction', 'credit');
        if ($debits->count() !== 1 || $credits->count() !== 1) {
            return ['posted_mirror_invalid_batch'];
        }

        $debit = $debits->first();
        $credit = $credits->first();
        $debitCode = (string) optional($debit->account)->code;
        $creditCode = (string) optional($credit->account)->code;

        if ((int) $debit->amount !== $amount
            || (int) $credit->amount !== $amount
            || strtoupper((string) $debit->currency) !== 'XAF'
            || strtoupper((string) $credit->currency) !== 'XAF'
            || ! str_starts_with($debitCode, 'ASSET:PAYMENT_PROVIDER:')
            || ! str_ends_with($debitCode, ':COLLECTIONS')
            || $creditCode !== 'LIABILITY:PAYMENT:CLEARING') {
            return ['posted_mirror_invalid_batch'];
        }

        return [];
    }

    private function integerAmountOrNull(mixed $amount): ?int
    {
        if (! is_numeric($amount)) {
            return null;
        }

        $numeric = (float) $amount;
        $rounded = (int) round($numeric);

        if ($rounded <= 0 || abs($numeric - $rounded) > 0.0001) {
            return null;
        }

        return $rounded;
    }
}
