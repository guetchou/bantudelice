<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\FinancialAccount;
use App\Domain\Finance\Models\FinancialPosting;
use App\Domain\Finance\Models\FinancialPostingBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FinancialLedgerService
{
    public function __construct(
        private readonly FinancialAccountService $accounts,
    ) {
    }

    /**
     * @param array<int, array{account: FinancialAccount, direction: string, amount: int, description?: string, metadata?: array}> $lines
     */
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

        return DB::transaction(function () use ($eventType, $idempotencyKey, $normalized, $context) {
            $existing = FinancialPostingBatch::query()
                ->where('idempotency_key', $idempotencyKey)
                ->with('postings')
                ->first();

            if ($existing) {
                return ['batch' => $existing, 'reused' => true];
            }

            $accountIds = collect($normalized)
                ->pluck('account.id')
                ->unique()
                ->sort()
                ->values();

            $lockedAccounts = FinancialAccount::query()
                ->whereIn('id', $accountIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lockedAccounts->count() !== $accountIds->count()) {
                throw new \DomainException('Un compte financier de l’écriture est introuvable.');
            }

            foreach ($normalized as $line) {
                $locked = $lockedAccounts->get($line['account']->id);

                if ($locked->status !== 'active') {
                    throw new \DomainException('Le compte financier ' . $locked->code . ' n’est pas actif.');
                }

                if ($locked->currency !== $line['currency']) {
                    throw new \DomainException('La devise de l’écriture ne correspond pas au compte ' . $locked->code . '.');
                }
            }

            $now = now();
            $batch = FinancialPostingBatch::create([
                'uuid' => (string) Str::uuid(),
                'event_type' => $eventType,
                'source_type' => $context['source_type'] ?? null,
                'source_id' => $context['source_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
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
                    'description' => $line['description'] ?? null,
                    'metadata' => $line['metadata'] ?? null,
                    'created_at' => $now,
                ]);
            }

            return ['batch' => $batch->load('postings'), 'reused' => false];
        });
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

    public function partnerPosition(string $partnerType, int $partnerId): array
    {
        $partnerAccounts = $this->accounts->provisionPartner($partnerType, $partnerId);
        $available = $this->balance($partnerAccounts['available']);
        $reserved = $this->balance($partnerAccounts['reserved']);

        return [
            'partner_type' => $partnerType,
            'partner_id' => $partnerId,
            'currency' => 'XAF',
            'available' => $available,
            'reserved' => $reserved,
            'total_due' => $available + $reserved,
            'accounts' => [
                'available' => $partnerAccounts['available']->code,
                'reserved' => $partnerAccounts['reserved']->code,
            ],
        ];
    }

    public function recordCollectionDistribution(
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
        $components = [
            'restaurant' => $restaurantNet,
            'driver' => $driverNet,
            'commission' => $platformCommission,
            'service_fee' => $platformServiceFee,
            'tax' => $taxPayable,
        ];

        foreach ($components as $name => $amount) {
            if ($amount < 0) {
                throw new \DomainException('La composante ' . $name . ' ne peut pas être négative.');
            }
        }

        if (array_sum($components) !== $totalAmount) {
            throw new \DomainException(
                'La distribution du paiement est déséquilibrée : total encaissé '
                . $totalAmount
                . ', total affecté '
                . array_sum($components)
                . '.'
            );
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
                'account' => $this->accounts->partner('restaurant', $restaurantId, FinancialAccountService::PARTNER_AVAILABLE),
                'direction' => 'credit',
                'amount' => $restaurantNet,
                'description' => 'Dette envers le restaurant',
            ];
        }

        if ($driverNet > 0) {
            $lines[] = [
                'account' => $this->accounts->partner('driver', $driverId, FinancialAccountService::PARTNER_AVAILABLE),
                'direction' => 'credit',
                'amount' => $driverNet,
                'description' => 'Dette envers le livreur',
            ];
        }

        if ($platformCommission > 0) {
            $lines[] = [
                'account' => $this->accounts->platform('platform_commission_revenue'),
                'direction' => 'credit',
                'amount' => $platformCommission,
                'description' => 'Commission acquise par BantuDelice',
            ];
        }

        if ($platformServiceFee > 0) {
            $lines[] = [
                'account' => $this->accounts->platform('platform_service_fee_revenue'),
                'direction' => 'credit',
                'amount' => $platformServiceFee,
                'description' => 'Frais de service acquis par BantuDelice',
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

        return $this->postBatch(
            'collection_distributed',
            'payment:' . $paymentId . ':collection-distribution:v1',
            $lines,
            [
                'source_type' => 'payment',
                'source_id' => $paymentId,
                'metadata' => $metadata + ['components' => $components],
            ]
        );
    }

    public function reserveWithdrawal(
        string $partnerType,
        int $partnerId,
        int $withdrawalId,
        int $amount
    ): array {
        if ($amount <= 0) {
            throw new \DomainException('Le montant du retrait doit être strictement positif.');
        }

        return DB::transaction(function () use ($partnerType, $partnerId, $withdrawalId, $amount) {
            $partnerAccounts = $this->accounts->provisionPartner($partnerType, $partnerId);

            FinancialAccount::query()
                ->whereIn('id', [$partnerAccounts['available']->id, $partnerAccounts['reserved']->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($this->balance($partnerAccounts['available']) < $amount) {
                throw new \DomainException('Solde financier disponible insuffisant.');
            }

            return $this->postBatch(
                'withdrawal_reserved',
                'partner-withdrawal:' . $withdrawalId . ':reserve:v1',
                [
                    [
                        'account' => $partnerAccounts['available'],
                        'direction' => 'debit',
                        'amount' => $amount,
                        'description' => 'Diminution du solde disponible',
                    ],
                    [
                        'account' => $partnerAccounts['reserved'],
                        'direction' => 'credit',
                        'amount' => $amount,
                        'description' => 'Réservation du retrait partenaire',
                    ],
                ],
                ['source_type' => 'partner_withdrawal', 'source_id' => $withdrawalId]
            );
        });
    }

    public function confirmWithdrawal(
        string $partnerType,
        int $partnerId,
        int $withdrawalId,
        int $amount
    ): array {
        $partnerAccounts = $this->accounts->provisionPartner($partnerType, $partnerId);

        return $this->postBatch(
            'withdrawal_paid',
            'partner-withdrawal:' . $withdrawalId . ':paid:v1',
            [
                [
                    'account' => $partnerAccounts['reserved'],
                    'direction' => 'debit',
                    'amount' => $amount,
                    'description' => 'Extinction de la dette partenaire réservée',
                ],
                [
                    'account' => $this->accounts->platform('mtn_disbursement_cash'),
                    'direction' => 'credit',
                    'amount' => $amount,
                    'description' => 'Sortie de trésorerie MTN Disbursements',
                ],
            ],
            ['source_type' => 'partner_withdrawal', 'source_id' => $withdrawalId]
        );
    }

    public function releaseWithdrawal(
        string $partnerType,
        int $partnerId,
        int $withdrawalId,
        int $amount,
        string $reason
    ): array {
        $partnerAccounts = $this->accounts->provisionPartner($partnerType, $partnerId);

        return $this->postBatch(
            'withdrawal_released',
            'partner-withdrawal:' . $withdrawalId . ':release:v1',
            [
                [
                    'account' => $partnerAccounts['reserved'],
                    'direction' => 'debit',
                    'amount' => $amount,
                    'description' => 'Annulation de la réservation du retrait',
                ],
                [
                    'account' => $partnerAccounts['available'],
                    'direction' => 'credit',
                    'amount' => $amount,
                    'description' => 'Restitution au solde disponible',
                ],
            ],
            [
                'source_type' => 'partner_withdrawal',
                'source_id' => $withdrawalId,
                'metadata' => ['reason' => $reason],
            ]
        );
    }

    public function transferCollectionsToDisbursement(int $settlementId, int $amount): array
    {
        return $this->postBatch(
            'treasury_settlement',
            'treasury-settlement:' . $settlementId . ':v1',
            [
                [
                    'account' => $this->accounts->platform('mtn_disbursement_cash'),
                    'direction' => 'debit',
                    'amount' => $amount,
                    'description' => 'Alimentation du compte de décaissement',
                ],
                [
                    'account' => $this->accounts->platform('mtn_collections_cash'),
                    'direction' => 'credit',
                    'amount' => $amount,
                    'description' => 'Transfert depuis les encaissements',
                ],
            ],
            ['source_type' => 'treasury_settlement', 'source_id' => $settlementId]
        );
    }

    /**
     * @param array<int, array> $lines
     * @return array<int, array>
     */
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
                throw new \InvalidArgumentException('Les montants financiers doivent être des entiers FCFA strictement positifs.');
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
                throw new \DomainException(
                    sprintf(
                        'Écriture déséquilibrée en %s : débits %d, crédits %d.',
                        $currency,
                        $total['debit'],
                        $total['credit']
                    )
                );
            }
        }
    }
}
