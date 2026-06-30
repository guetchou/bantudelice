<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\FinancialAccount;
use App\Domain\Finance\Models\FinancialPosting;
use App\Domain\Finance\Models\FinancialPostingBatch;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LedgerPostingService
{
    public function postBatch(
        string $eventType,
        string $idempotencyKey,
        array $lines,
        array $context = []
    ): array {
        $idempotencyKey = trim($idempotencyKey);
        if ($idempotencyKey === '') {
            throw new \InvalidArgumentException('Une clé d’idempotence financière est obligatoire.');
        }
        if (count($lines) < 2) {
            throw new \DomainException('Une écriture financière doit contenir au moins deux lignes.');
        }

        $normalized = $this->normalizeLines($lines);
        $this->assertBalanced($normalized);
        $payloadHash = $this->payloadHash($eventType, $normalized, $context);

        $existing = $this->existingResult($idempotencyKey, $payloadHash);
        if ($existing) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($eventType, $idempotencyKey, $normalized, $context, $payloadHash) {
                $this->lockAccounts(collect($normalized)->pluck('account')->all());

                $existingAfterLock = $this->existingResult($idempotencyKey, $payloadHash);
                if ($existingAfterLock) {
                    return $existingAfterLock;
                }

                foreach ($normalized as $line) {
                    $account = FinancialAccount::findOrFail($line['account']->id);
                    if ($account->status !== 'active') {
                        throw new \DomainException('Le compte financier ' . $account->code . ' n’est pas actif.');
                    }
                    if ($account->currency !== $line['currency']) {
                        throw new \DomainException('La devise ne correspond pas au compte ' . $account->code . '.');
                    }
                }

                $now = now();
                $batch = FinancialPostingBatch::create([
                    'uuid' => (string) Str::uuid(),
                    'event_type' => $eventType,
                    'source_type' => $context['source_type'] ?? null,
                    'source_id' => $context['source_id'] ?? null,
                    'idempotency_key' => $idempotencyKey,
                    'payload_hash' => $payloadHash,
                    'status' => 'posted',
                    'reversal_of_batch_id' => $context['reversal_of_batch_id'] ?? null,
                    'effective_at' => $context['effective_at'] ?? $now,
                    'posted_at' => $now,
                    'metadata' => $context['metadata'] ?? null,
                ]);

                foreach ($normalized as $index => $line) {
                    FinancialPosting::create([
                        'batch_id' => $batch->id,
                        'account_id' => $line['account']->id,
                        'line_no' => $index + 1,
                        'direction' => $line['direction'],
                        'amount' => $line['amount'],
                        'currency' => $line['currency'],
                        'description' => $line['description'],
                        'metadata' => $line['metadata'],
                        'created_at' => $now,
                    ]);
                }

                return ['batch' => $batch->load('postings'), 'reused' => false];
            });
        } catch (QueryException $exception) {
            $existingAfterRace = $this->existingResult($idempotencyKey, $payloadHash);
            if ($existingAfterRace) {
                return $existingAfterRace;
            }
            throw $exception;
        }
    }

    public function balance(FinancialAccount $account): int
    {
        $totals = FinancialPosting::query()
            ->where('account_id', $account->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) AS debit_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) AS credit_total")
            ->first();

        $debits = (int) ($totals->debit_total ?? 0);
        $credits = (int) ($totals->credit_total ?? 0);

        return in_array($account->category, ['asset', 'expense'], true)
            ? $debits - $credits
            : $credits - $debits;
    }

    public function findExisting(
        string $eventType,
        string $idempotencyKey,
        array $lines,
        array $context = []
    ): ?array {
        $normalized = $this->normalizeLines($lines);
        $this->assertBalanced($normalized);

        return $this->existingResult(
            $idempotencyKey,
            $this->payloadHash($eventType, $normalized, $context)
        );
    }

    public function lockAccounts(array $accounts): void
    {
        $ids = collect($accounts)->pluck('id')->unique()->sort()->values();
        $locked = FinancialAccount::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($locked->count() !== $ids->count()) {
            throw new \DomainException('Un compte financier est introuvable.');
        }
    }

    public function assertPositive(int $amount, string $label): void
    {
        if ($amount <= 0) {
            throw new \DomainException($label . ' doit être strictement positif.');
        }
    }

    private function normalizeLines(array $lines): array
    {
        return collect($lines)->map(function (array $line) {
            $account = $line['account'] ?? null;
            $direction = strtolower((string) ($line['direction'] ?? ''));
            $amount = $line['amount'] ?? null;

            if (! $account instanceof FinancialAccount || ! $account->exists) {
                throw new \InvalidArgumentException('Chaque ligne doit référencer un compte financier persisté.');
            }
            if (! in_array($direction, ['debit', 'credit'], true)) {
                throw new \InvalidArgumentException('La direction doit être debit ou credit.');
            }
            if (! is_int($amount) || $amount <= 0) {
                throw new \InvalidArgumentException('Les montants doivent être des entiers FCFA strictement positifs.');
            }

            return [
                'account' => $account,
                'direction' => $direction,
                'amount' => $amount,
                'currency' => strtoupper((string) ($line['currency'] ?? $account->currency)),
                'description' => $line['description'] ?? null,
                'metadata' => $line['metadata'] ?? null,
            ];
        })->all();
    }

    private function assertBalanced(array $lines): void
    {
        $totals = [];
        foreach ($lines as $line) {
            $currency = $line['currency'];
            $totals[$currency] ??= ['debit' => 0, 'credit' => 0];
            $totals[$currency][$line['direction']] += $line['amount'];
        }

        foreach ($totals as $currency => $total) {
            if ($total['debit'] !== $total['credit']) {
                throw new \DomainException(sprintf(
                    'Écriture déséquilibrée en %s : débits %d, crédits %d.',
                    $currency,
                    $total['debit'],
                    $total['credit']
                ));
            }
        }
    }

    private function payloadHash(string $eventType, array $lines, array $context): string
    {
        $payload = [
            'event_type' => $eventType,
            'source_type' => $context['source_type'] ?? null,
            'source_id' => $context['source_id'] ?? null,
            'reversal_of_batch_id' => $context['reversal_of_batch_id'] ?? null,
            'lines' => collect($lines)->map(fn (array $line) => [
                'account_id' => (int) $line['account']->id,
                'direction' => $line['direction'],
                'amount' => $line['amount'],
                'currency' => $line['currency'],
            ])->values()->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function existingResult(string $idempotencyKey, string $payloadHash): ?array
    {
        $batch = FinancialPostingBatch::query()
            ->where('idempotency_key', $idempotencyKey)
            ->with('postings')
            ->first();

        if (! $batch) {
            return null;
        }
        if (! hash_equals((string) $batch->payload_hash, $payloadHash)) {
            throw new \DomainException('La clé d’idempotence existe avec un contenu financier différent.');
        }

        return ['batch' => $batch, 'reused' => true];
    }
}
