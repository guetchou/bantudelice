<?php

namespace App\Services;

use App\FinancialLedgerEntry;
use App\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialLedgerService
{
    public function record(array $payload): ?FinancialLedgerEntry
    {
        if (!Schema::hasTable('financial_ledger_entries')) {
            return null;
        }

        $amount = (float) ($payload['amount'] ?? 0);
        $direction = strtolower((string) ($payload['direction'] ?? 'credit'));

        if ($amount <= 0) {
            throw new \DomainException('Le montant d’une écriture doit être strictement positif.');
        }

        if (!in_array($direction, ['credit', 'debit'], true)) {
            throw new \DomainException('Le sens d’une écriture doit être credit ou debit.');
        }

        $attributes = [
            'module' => $payload['module'] ?? 'food',
            'account_type' => $payload['account_type'] ?? null,
            'account_id' => $payload['account_id'] ?? null,
            'entry_type' => $payload['entry_type'] ?? 'adjustment',
            'direction' => $direction,
            'status' => $payload['status'] ?? 'posted',
            'order_id' => $payload['order_id'] ?? null,
            'order_no' => $payload['order_no'] ?? null,
            'payment_id' => $payload['payment_id'] ?? null,
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'idempotency_key' => $payload['idempotency_key'] ?? null,
            'related_entry_id' => $payload['related_entry_id'] ?? null,
            'effective_at' => $payload['effective_at'] ?? now(),
            'currency' => $payload['currency'] ?? config('commerce.currency', 'XAF'),
            'amount' => $amount,
            'balance_before' => $payload['balance_before'] ?? null,
            'balance_after' => $payload['balance_after'] ?? null,
            'actor_type' => $payload['actor_type'] ?? null,
            'actor_id' => $payload['actor_id'] ?? null,
            'created_by' => $payload['created_by'] ?? $payload['actor_id'] ?? null,
            'payload' => $payload,
        ];

        $idempotencyKey = $attributes['idempotency_key'];

        if ($idempotencyKey) {
            return DB::transaction(function () use ($attributes, $idempotencyKey) {
                $existing = FinancialLedgerEntry::where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                return $existing ?: FinancialLedgerEntry::create($attributes);
            });
        }

        return FinancialLedgerEntry::create($attributes);
    }

    public function reverse(
        FinancialLedgerEntry $entry,
        string $reason,
        string $idempotencyKey,
        ?int $actorId = null,
    ): FinancialLedgerEntry {
        $reason = trim($reason);

        if ($reason === '') {
            throw new \DomainException('Le motif de contre-écriture est obligatoire.');
        }

        return $this->record([
            'module' => $entry->module,
            'account_type' => $entry->account_type,
            'account_id' => $entry->account_id,
            'entry_type' => 'reversal',
            'direction' => $entry->direction === 'credit' ? 'debit' : 'credit',
            'status' => 'posted',
            'order_id' => $entry->order_id,
            'order_no' => $entry->order_no,
            'payment_id' => $entry->payment_id,
            'source_type' => 'ledger_entry',
            'source_id' => $entry->id,
            'reference' => $entry->reference,
            'idempotency_key' => $idempotencyKey,
            'related_entry_id' => $entry->id,
            'currency' => $entry->currency,
            'amount' => (float) $entry->amount,
            'actor_type' => $actorId ? 'admin' : 'system',
            'actor_id' => $actorId,
            'created_by' => $actorId,
            'reason' => $reason,
        ]);
    }

    public function position(string $accountType, ?int $accountId): array
    {
        if (!Schema::hasTable('financial_ledger_entries')) {
            return $this->emptyPosition();
        }

        $entries = FinancialLedgerEntry::query()
            ->where('account_type', $accountType)
            ->when($accountId === null, fn ($query) => $query->whereNull('account_id'))
            ->when($accountId !== null, fn ($query) => $query->where('account_id', $accountId))
            ->get();

        $posted = $entries->where('status', 'posted');
        $held = $entries->whereIn('status', ['pending', 'reserved']);

        $credits = (float) $posted->where('direction', 'credit')->sum('amount');
        $debits = (float) $posted->where('direction', 'debit')->sum('amount');
        $reservedDebits = (float) $held->where('direction', 'debit')->sum('amount');
        $reservedCredits = (float) $held->where('direction', 'credit')->sum('amount');
        $balance = $credits - $debits;
        $reserved = max(0, $reservedDebits - $reservedCredits);

        return [
            'credits' => $credits,
            'debits' => $debits,
            'balance' => $balance,
            'reserved' => $reserved,
            'available' => max(0, $balance - $reserved),
            'entries_count' => $entries->count(),
        ];
    }

    public function reserveAccount(
        string $accountType,
        int $accountId,
        float $amount,
        string $sourceType,
        int $sourceId,
        string $idempotencyKey,
        array $payload = [],
    ): ?FinancialLedgerEntry {
        return $this->record(array_merge($payload, [
            'account_type' => $accountType,
            'account_id' => $accountId,
            'entry_type' => 'withdrawal_reservation',
            'direction' => 'debit',
            'status' => 'reserved',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'idempotency_key' => $idempotencyKey,
            'amount' => $amount,
        ]));
    }

    public function releaseReservation(
        FinancialLedgerEntry $reservation,
        string $idempotencyKey,
        string $reason,
        array $payload = [],
    ): ?FinancialLedgerEntry {
        return $this->record(array_merge($payload, [
            'module' => $reservation->module,
            'account_type' => $reservation->account_type,
            'account_id' => $reservation->account_id,
            'entry_type' => 'withdrawal_release',
            'direction' => 'credit',
            'status' => 'reserved',
            'source_type' => 'ledger_entry',
            'source_id' => $reservation->id,
            'related_entry_id' => $reservation->id,
            'idempotency_key' => $idempotencyKey,
            'amount' => (float) $reservation->amount,
            'currency' => $reservation->currency,
            'reason' => $reason,
        ]));
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
            'source_type' => 'order',
            'source_id' => $order->id,
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
            'source_type' => 'order',
            'source_id' => $order->id,
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
            'source_type' => 'order',
            'source_id' => $order->id,
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
            'source_type' => 'order',
            'source_id' => $order->id,
            'amount' => $amount,
            'reference' => $payload['reference'] ?? $order->order_no,
        ], $payload));
    }

    private function emptyPosition(): array
    {
        return [
            'credits' => 0.0,
            'debits' => 0.0,
            'balance' => 0.0,
            'reserved' => 0.0,
            'available' => 0.0,
            'entries_count' => 0,
        ];
    }
}
