<?php

namespace App\Services;

use App\FinancialLedgerEntry;
use App\Order;
use Illuminate\Support\Facades\Schema;

class FinancialLedgerService
{
    public function record(array $payload): ?FinancialLedgerEntry
    {
        if (!Schema::hasTable('financial_ledger_entries')) {
            return null;
        }

        return FinancialLedgerEntry::create([
            'module' => $payload['module'] ?? 'food',
            'entry_type' => $payload['entry_type'] ?? 'adjustment',
            'direction' => $payload['direction'] ?? 'credit',
            'status' => $payload['status'] ?? 'posted',
            'order_id' => $payload['order_id'] ?? null,
            'order_no' => $payload['order_no'] ?? null,
            'payment_id' => $payload['payment_id'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'currency' => $payload['currency'] ?? config('commerce.currency', 'FCFA'),
            'amount' => $payload['amount'] ?? 0,
            'balance_before' => $payload['balance_before'] ?? null,
            'balance_after' => $payload['balance_after'] ?? null,
            'actor_type' => $payload['actor_type'] ?? null,
            'actor_id' => $payload['actor_id'] ?? null,
            'payload' => $payload,
        ]);
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
