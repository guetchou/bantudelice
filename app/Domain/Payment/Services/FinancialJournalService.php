<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Enums\JournalEntryStatus;
use App\Domain\Payment\Enums\JournalEntryType;
use App\Domain\Payment\Enums\LedgerDirection;
use App\Domain\Payment\Models\FinancialAccount;
use App\Domain\Payment\Models\FinancialJournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class FinancialJournalService
{
    public function post(
        string $idempotencyKey,
        JournalEntryType $type,
        array $lines,
        array $context = []
    ): FinancialJournalEntry {
        $normalized = $this->normalizeLines($lines);
        $currency = strtoupper((string) ($context['currency'] ?? 'XAF'));

        $this->assertBalanced($normalized);
        $this->assertAccountsCompatible($normalized, $currency);

        return DB::transaction(function () use (
            $idempotencyKey,
            $type,
            $normalized,
            $context,
            $currency
        ) {
            $existing = FinancialJournalEntry::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $this->assertSameIntent($existing, $type, $currency);

                return $existing->load('lines');
            }

            $source = $context['source'] ?? null;
            if ($source !== null && !$source instanceof Model) {
                throw new InvalidArgumentException('La source doit être un modèle Eloquent.');
            }

            $entry = FinancialJournalEntry::query()->create([
                'idempotency_key' => $idempotencyKey,
                'type' => $type->value,
                'status' => JournalEntryStatus::DRAFT->value,
                'reference' => $context['reference'] ?? null,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'description' => $context['description'] ?? null,
                'currency' => $currency,
                'effective_at' => $context['effective_at'] ?? now(),
                'metadata' => $context['metadata'] ?? null,
            ]);

            foreach ($normalized as $line) {
                $entry->lines()->create([
                    'financial_account_id' => $line['account']->getKey(),
                    'direction' => $line['direction']->value,
                    'amount' => $line['amount'],
                    'narrative' => $line['narrative'],
                    'metadata' => $line['metadata'],
                ]);
            }

            if (!$entry->fresh()->isBalanced()) {
                throw new LogicException('Une écriture non équilibrée ne peut pas être comptabilisée.');
            }

            $entry->forceFill([
                'status' => JournalEntryStatus::POSTED->value,
                'posted_at' => now(),
            ])->save();

            return $entry->fresh('lines');
        });
    }

    public function reverse(
        FinancialJournalEntry $entry,
        string $idempotencyKey,
        string $reason,
        array $metadata = []
    ): FinancialJournalEntry {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Le motif de contre-écriture est obligatoire.');
        }

        return DB::transaction(function () use ($entry, $idempotencyKey, $reason, $metadata) {
            $locked = FinancialJournalEntry::query()
                ->with('lines.account')
                ->lockForUpdate()
                ->findOrFail($entry->getKey());

            if ($locked->reversed_entry_id) {
                return FinancialJournalEntry::query()
                    ->with('lines')
                    ->findOrFail($locked->reversed_entry_id);
            }

            if ($locked->status !== JournalEntryStatus::POSTED) {
                throw new LogicException('Seule une écriture comptabilisée peut être contre-passée.');
            }

            $reversal = $this->post(
                $idempotencyKey,
                JournalEntryType::REVERSAL,
                $locked->lines->map(fn ($line) => [
                    'account' => $line->account,
                    'direction' => $line->direction->opposite(),
                    'amount' => $line->amount,
                    'narrative' => 'Contre-écriture : ' . $reason,
                    'metadata' => ['reversed_line_id' => $line->id],
                ])->all(),
                [
                    'currency' => $locked->currency,
                    'reference' => $locked->reference,
                    'source' => $locked,
                    'description' => $reason,
                    'effective_at' => now(),
                    'metadata' => array_merge($metadata, [
                        'reversed_entry_uuid' => $locked->uuid,
                    ]),
                ]
            );

            $locked->forceFill([
                'status' => JournalEntryStatus::REVERSED->value,
                'reversed_entry_id' => $reversal->id,
            ])->save();

            return $reversal;
        });
    }

    private function normalizeLines(array $lines): array
    {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('Une écriture doit contenir au moins deux lignes.');
        }

        return collect($lines)->map(function (array $line): array {
            $account = $line['account'] ?? null;
            if (!$account instanceof FinancialAccount) {
                throw new InvalidArgumentException('Chaque ligne doit référencer un compte financier.');
            }

            $direction = $line['direction'] ?? null;
            if (is_string($direction)) {
                $direction = LedgerDirection::tryFrom(strtolower($direction));
            }
            if (!$direction instanceof LedgerDirection) {
                throw new InvalidArgumentException('Direction de ligne invalide.');
            }

            $amount = (int) ($line['amount'] ?? 0);
            if ($amount <= 0) {
                throw new InvalidArgumentException('Le montant d’une ligne doit être strictement positif.');
            }

            return [
                'account' => $account,
                'direction' => $direction,
                'amount' => $amount,
                'narrative' => $line['narrative'] ?? null,
                'metadata' => $line['metadata'] ?? null,
            ];
        })->all();
    }

    private function assertBalanced(array $lines): void
    {
        $debits = collect($lines)
            ->where('direction', LedgerDirection::DEBIT)
            ->sum('amount');
        $credits = collect($lines)
            ->where('direction', LedgerDirection::CREDIT)
            ->sum('amount');

        if ((int) $debits !== (int) $credits) {
            throw new LogicException("Écriture déséquilibrée : débits {$debits}, crédits {$credits}.");
        }
    }

    private function assertAccountsCompatible(array $lines, string $currency): void
    {
        foreach ($lines as $line) {
            if ($line['account']->status !== 'active') {
                throw new LogicException('Une écriture ne peut pas utiliser un compte inactif.');
            }

            if (strtoupper($line['account']->currency) !== $currency) {
                throw new LogicException('Toutes les lignes doivent utiliser la devise de l’écriture.');
            }
        }
    }

    private function assertSameIntent(
        FinancialJournalEntry $entry,
        JournalEntryType $type,
        string $currency
    ): void {
        if ($entry->type !== $type || strtoupper($entry->currency) !== $currency) {
            throw new LogicException('La clé d’idempotence existe déjà pour une autre opération.');
        }
    }
}
