<?php

namespace App\Services;

use App\FinancialLedgerEntry;
use App\Order;
use App\PartnerWithdrawal;
use App\Payment;
use Illuminate\Support\Facades\Schema;

class FinancialLedgerService
{
    public function record(array $payload): ?FinancialLedgerEntry
    {
        if (! Schema::hasTable('financial_ledger_entries')) {
            return null;
        }

        $attributes = [
            'module' => $payload['module'] ?? 'food',
            'entry_type' => $payload['entry_type'] ?? 'adjustment',
            'direction' => $payload['direction'] ?? 'credit',
            'status' => $payload['status'] ?? 'posted',
            'order_id' => $payload['order_id'] ?? null,
            'order_no' => $payload['order_no'] ?? null,
            'payment_id' => $payload['payment_id'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'currency' => $payload['currency'] ?? config('commerce.currency', 'XAF'),
            'amount' => $payload['amount'] ?? 0,
            'balance_before' => $payload['balance_before'] ?? null,
            'balance_after' => $payload['balance_after'] ?? null,
            'actor_type' => $payload['actor_type'] ?? null,
            'actor_id' => $payload['actor_id'] ?? null,
            'payload' => $payload,
        ];

        if (Schema::hasColumn('financial_ledger_entries', 'entry_key')) {
            $attributes = array_merge($attributes, [
                'entry_key' => $payload['entry_key'] ?? null,
                'owner_type' => $payload['owner_type'] ?? null,
                'owner_id' => $payload['owner_id'] ?? null,
                'account_code' => $payload['account_code'] ?? null,
                'source_type' => $payload['source_type'] ?? ($payload['module'] ?? 'food'),
                'source_id' => $payload['source_id'] ?? null,
                'withdrawal_id' => $payload['withdrawal_id'] ?? null,
                'reversal_of_id' => $payload['reversal_of_id'] ?? null,
                'effective_at' => $payload['effective_at'] ?? now(),
                'metadata' => $payload['metadata'] ?? $payload,
                'created_by' => $payload['created_by'] ?? null,
            ]);
        }

        $entryKey = trim((string) ($attributes['entry_key'] ?? ''));

        if ($entryKey !== '') {
            return FinancialLedgerEntry::firstOrCreate(
                ['entry_key' => $entryKey],
                $attributes
            );
        }

        return FinancialLedgerEntry::create($attributes);
    }

    /**
     * Enregistre la preuve comptable minimale d'un encaissement confirmé.
     * Deux écritures opposées sont créées une seule fois : actif fournisseur
     * et fonds client à affecter.
     *
     * @return array<int, FinancialLedgerEntry|null>
     */
    public function recordConfirmedCollection(Payment $payment, ?Order $order = null, array $context = []): array
    {
        $base = [
            'module' => 'payments',
            'source_type' => 'payment',
            'source_id' => $payment->id,
            'payment_id' => $payment->id,
            'order_id' => $order?->id,
            'order_no' => $order?->order_no,
            'reference' => $payment->provider_reference ?: ('PAY-' . $payment->id),
            'currency' => $payment->currency ?: 'XAF',
            'amount' => (float) $payment->amount,
            'status' => 'posted',
            'effective_at' => now(),
            'metadata' => $context,
        ];

        return [
            $this->record(array_merge($base, [
                'entry_key' => 'payment:' . $payment->id . ':provider-clearing:debit',
                'entry_type' => 'collection_confirmed',
                'direction' => 'debit',
                'owner_type' => 'platform',
                'owner_id' => null,
                'account_code' => 'provider_clearing',
            ])),
            $this->record(array_merge($base, [
                'entry_key' => 'payment:' . $payment->id . ':customer-funds:credit',
                'entry_type' => 'customer_funds_received',
                'direction' => 'credit',
                'owner_type' => 'user',
                'owner_id' => $payment->user_id,
                'account_code' => 'customer_funds',
            ])),
        ];
    }

    /**
     * Contre-écrit un encaissement confirmé sans supprimer l'historique.
     *
     * @return array<int, FinancialLedgerEntry|null>
     */
    public function reverseConfirmedCollection(Payment $payment, string $reason, array $context = []): array
    {
        $originalDebit = FinancialLedgerEntry::where('entry_key', 'payment:' . $payment->id . ':provider-clearing:debit')->first();
        $originalCredit = FinancialLedgerEntry::where('entry_key', 'payment:' . $payment->id . ':customer-funds:credit')->first();
        $base = [
            'module' => 'payments',
            'source_type' => 'payment_reversal',
            'source_id' => $payment->id,
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'reference' => $payment->provider_reference ?: ('PAY-' . $payment->id),
            'currency' => $payment->currency ?: 'XAF',
            'amount' => (float) $payment->amount,
            'status' => 'posted',
            'effective_at' => now(),
            'metadata' => array_merge($context, ['reason' => $reason]),
        ];

        return [
            $this->record(array_merge($base, [
                'entry_key' => 'payment:' . $payment->id . ':provider-clearing:reversal:credit',
                'entry_type' => 'collection_reversed',
                'direction' => 'credit',
                'owner_type' => 'platform',
                'account_code' => 'provider_clearing',
                'reversal_of_id' => $originalDebit?->id,
            ])),
            $this->record(array_merge($base, [
                'entry_key' => 'payment:' . $payment->id . ':customer-funds:reversal:debit',
                'entry_type' => 'customer_funds_reversed',
                'direction' => 'debit',
                'owner_type' => 'user',
                'owner_id' => $payment->user_id,
                'account_code' => 'customer_funds',
                'reversal_of_id' => $originalCredit?->id,
            ])),
        ];
    }

    public function recordWithdrawalReservation(PartnerWithdrawal $withdrawal): array
    {
        $base = [
            'module' => 'payments',
            'source_type' => 'partner_withdrawal',
            'source_id' => $withdrawal->id,
            'withdrawal_id' => $withdrawal->id,
            'owner_type' => $withdrawal->partner_type,
            'owner_id' => $withdrawal->partner_id,
            'reference' => $withdrawal->external_reference ?: $withdrawal->reference(),
            'currency' => $withdrawal->currency ?: 'XAF',
            'amount' => (float) $withdrawal->net_amount,
            'status' => 'posted',
            'effective_at' => now(),
        ];

        return [
            $this->record(array_merge($base, [
                'entry_key' => 'withdrawal:' . $withdrawal->id . ':available:debit',
                'entry_type' => 'withdrawal_reserved',
                'direction' => 'debit',
                'account_code' => 'partner_available',
            ])),
            $this->record(array_merge($base, [
                'entry_key' => 'withdrawal:' . $withdrawal->id . ':reserved:credit',
                'entry_type' => 'withdrawal_reserved',
                'direction' => 'credit',
                'account_code' => 'partner_reserved',
            ])),
        ];
    }

    public function recordWithdrawalPaid(PartnerWithdrawal $withdrawal): ?FinancialLedgerEntry
    {
        return $this->record([
            'entry_key' => 'withdrawal:' . $withdrawal->id . ':reserved:debit:paid',
            'module' => 'payments',
            'entry_type' => 'withdrawal_paid',
            'direction' => 'debit',
            'status' => 'posted',
            'owner_type' => $withdrawal->partner_type,
            'owner_id' => $withdrawal->partner_id,
            'account_code' => 'partner_reserved',
            'source_type' => 'partner_withdrawal',
            'source_id' => $withdrawal->id,
            'withdrawal_id' => $withdrawal->id,
            'reference' => $withdrawal->provider_reference ?: $withdrawal->reference(),
            'currency' => $withdrawal->currency ?: 'XAF',
            'amount' => (float) $withdrawal->net_amount,
            'effective_at' => now(),
        ]);
    }

    public function recordWithdrawalRelease(PartnerWithdrawal $withdrawal, string $reason): array
    {
        return [
            $this->record([
                'entry_key' => 'withdrawal:' . $withdrawal->id . ':reserved:debit:release',
                'module' => 'payments',
                'entry_type' => 'withdrawal_released',
                'direction' => 'debit',
                'status' => 'posted',
                'owner_type' => $withdrawal->partner_type,
                'owner_id' => $withdrawal->partner_id,
                'account_code' => 'partner_reserved',
                'source_type' => 'partner_withdrawal',
                'source_id' => $withdrawal->id,
                'withdrawal_id' => $withdrawal->id,
                'reference' => $withdrawal->reference(),
                'currency' => $withdrawal->currency ?: 'XAF',
                'amount' => (float) $withdrawal->net_amount,
                'effective_at' => now(),
                'metadata' => ['reason' => $reason],
            ]),
            $this->record([
                'entry_key' => 'withdrawal:' . $withdrawal->id . ':available:credit:release',
                'module' => 'payments',
                'entry_type' => 'withdrawal_released',
                'direction' => 'credit',
                'status' => 'posted',
                'owner_type' => $withdrawal->partner_type,
                'owner_id' => $withdrawal->partner_id,
                'account_code' => 'partner_available',
                'source_type' => 'partner_withdrawal',
                'source_id' => $withdrawal->id,
                'withdrawal_id' => $withdrawal->id,
                'reference' => $withdrawal->reference(),
                'currency' => $withdrawal->currency ?: 'XAF',
                'amount' => (float) $withdrawal->net_amount,
                'effective_at' => now(),
                'metadata' => ['reason' => $reason],
            ]),
        ];
    }

    public function capture(Order $order, float $amount, array $payload = []): ?FinancialLedgerEntry
    {
        return $this->record(array_merge([
            'module' => 'food',
            'entry_type' => 'capture',
            'direction' => 'credit',
            'status' => 'posted',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'amount' => $amount,
            'reference' => $payload['reference'] ?? $order->order_no,
        ], $payload));
    }

    public function refund(Order $order, float $amount, array $payload = []): ?FinancialLedgerEntry
    {
        return $this->record(array_merge([
            'module' => 'food',
            'entry_type' => 'refund',
            'direction' => 'debit',
            'status' => 'posted',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'amount' => $amount,
            'reference' => $payload['reference'] ?? $order->order_no,
        ], $payload));
    }

    public function reserve(Order $order, float $amount, array $payload = []): ?FinancialLedgerEntry
    {
        return $this->record(array_merge([
            'module' => 'food',
            'entry_type' => 'reserve',
            'direction' => 'debit',
            'status' => 'pending',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'amount' => $amount,
            'reference' => $payload['reference'] ?? $order->order_no,
        ], $payload));
    }

    public function release(Order $order, float $amount, array $payload = []): ?FinancialLedgerEntry
    {
        return $this->record(array_merge([
            'module' => 'food',
            'entry_type' => 'release',
            'direction' => 'credit',
            'status' => 'posted',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'amount' => $amount,
            'reference' => $payload['reference'] ?? $order->order_no,
        ], $payload));
    }
}
