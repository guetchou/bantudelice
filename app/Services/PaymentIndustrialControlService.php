<?php

namespace App\Services;

use App\Domain\Payment\PaymentOperatingModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PaymentIndustrialControlService
{
    public function build(array $filters = []): array
    {
        $now = now();
        $payments = $this->loadTodayPayments($filters, $now);
        $confirmed = $payments->filter(fn ($payment) => PaymentOperatingModel::isConfirmedCollection($payment->status));
        $unresolved = $payments->filter(fn ($payment) => PaymentOperatingModel::isUnresolvedCollection($payment->status));
        $unallocated = $confirmed->filter(fn ($payment) => empty($payment->order_id))->values();
        $duplicates = $this->duplicateReferences($payments);
        $withdrawals = $this->withdrawalSnapshot($now);
        $obligations = $this->partnerObligations();

        $queue = $this->buildQueue($payments, $unallocated, $duplicates, $withdrawals['exception_items'], $now);

        return [
            'industrialHealth' => $this->health($queue, $withdrawals),
            'financialPosition' => [
                'confirmed_collections' => $this->position(
                    'Encaissements confirmés',
                    (int) $confirmed->sum('amount'),
                    $confirmed->count(),
                    'Fonds confirmés par le fournisseur, hors simple tentative.'
                ),
                'unresolved_collections' => $this->position(
                    'Encaissements non résolus',
                    (int) $unresolved->sum('amount'),
                    $unresolved->count(),
                    'Transactions initiées, en attente, en traitement ou inconnues.'
                ),
                'unallocated_collections' => $this->position(
                    'Confirmés non affectés',
                    (int) $unallocated->sum('amount'),
                    $unallocated->count(),
                    'Paiements confirmés sans commande rattachée.'
                ),
                'reserved_withdrawals' => $this->position(
                    'Fonds partenaires réservés',
                    $withdrawals['reserved_amount'],
                    $withdrawals['reserved_count'],
                    'Retraits engagés, y compris les statuts inconnus.'
                ),
                'paid_withdrawals' => $this->position(
                    'Retraits payés aujourd’hui',
                    $withdrawals['paid_today_amount'],
                    $withdrawals['paid_today_count'],
                    'Décaissements partenaires définitivement confirmés aujourd’hui.'
                ),
            ],
            'collectionsControl' => [
                'confirmed_count' => $confirmed->count(),
                'confirmed_amount' => (int) $confirmed->sum('amount'),
                'unresolved_count' => $unresolved->count(),
                'unresolved_amount' => (int) $unresolved->sum('amount'),
                'unallocated_count' => $unallocated->count(),
                'unallocated_amount' => (int) $unallocated->sum('amount'),
                'duplicate_reference_count' => $duplicates->count(),
                'duplicate_reference_amount' => (int) $duplicates->sum('amount'),
            ],
            'withdrawalControl' => $withdrawals,
            'partnerObligations' => $obligations,
            'industrialQueue' => $queue,
            'controlCoverage' => $this->controlCoverage(),
            'accountingRules' => $this->accountingRules(),
        ];
    }

    private function loadTodayPayments(array $filters, Carbon $now): Collection
    {
        if (! Schema::hasTable('payments')) {
            return collect();
        }

        $query = DB::table('payments')->where('created_at', '>=', $now->copy()->startOfDay());
        if (Schema::hasColumn('payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $provider = strtolower(trim((string) ($filters['provider'] ?? 'all')));
        if ($provider !== '' && $provider !== 'all') {
            $query->whereIn('provider', match ($provider) {
                'mtn' => ['momo', 'mtn_momo', 'mtn'],
                'airtel' => ['airtel', 'airtel_money'],
                'card' => ['card', 'stripe'],
                default => [$provider],
            });
        }

        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if ($status !== '' && $status !== 'all') {
            $rawStatuses = $this->rawStatuses($status);
            $query->where(function ($statusQuery) use ($status, $rawStatuses) {
                $statusQuery->whereIn('status', $rawStatuses);
                if ($status === 'unknown') {
                    $statusQuery->orWhereNull('status');
                }
            });
        }

        return $query->orderByDesc('updated_at')->get();
    }

    private function duplicateReferences(Collection $payments): Collection
    {
        return $payments
            ->filter(fn ($payment) => trim((string) ($payment->provider_reference ?? '')) !== '')
            ->groupBy(fn ($payment) => strtolower((string) $payment->provider) . '|' . trim((string) $payment->provider_reference))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->map(fn (Collection $group) => [
                'provider' => $this->providerLabel($group->first()->provider),
                'reference' => $group->first()->provider_reference,
                'count' => $group->count(),
                'amount' => (int) $group->sum('amount'),
                'payment_ids' => $group->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'latest_at' => $group->max('updated_at') ?? $group->max('created_at'),
            ])->values();
    }

    private function withdrawalSnapshot(Carbon $now): array
    {
        $empty = [
            'reserved_amount' => 0,
            'reserved_count' => 0,
            'paid_today_amount' => 0,
            'paid_today_count' => 0,
            'unknown_amount' => 0,
            'unknown_count' => 0,
            'reversed_amount' => 0,
            'reversed_count' => 0,
            'failed_today_amount' => 0,
            'failed_today_count' => 0,
            'restaurant_reserved_amount' => 0,
            'restaurant_reserved_count' => 0,
            'driver_reserved_amount' => 0,
            'driver_reserved_count' => 0,
            'exception_items' => collect(),
        ];

        if (! Schema::hasTable('partner_withdrawals')) {
            return $empty;
        }

        $amountColumn = Schema::hasColumn('partner_withdrawals', 'net_amount') ? 'net_amount' : 'requested_amount';
        $reservedStatuses = PaymentOperatingModel::WITHDRAWAL_RESERVED;
        $base = fn () => DB::table('partner_withdrawals');

        $reservedByPartner = $base()
            ->select('partner_type', DB::raw('COUNT(*) as count'), DB::raw("COALESCE(SUM({$amountColumn}), 0) as amount"))
            ->whereIn('status', $reservedStatuses)
            ->groupBy('partner_type')
            ->get()
            ->keyBy('partner_type');

        $exceptionRows = $base()
            ->where(function ($query) use ($now) {
                $query->whereIn('status', ['unknown', 'reversed'])
                    ->orWhere(function ($pending) use ($now) {
                        $pending->whereIn('status', ['created', 'reserved', 'submitted', 'pending'])
                            ->where('updated_at', '<=', $now->copy()->subMinutes(10));
                    });
            })
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $exceptionItems = $exceptionRows->map(function ($withdrawal) use ($amountColumn, $now) {
            $status = PaymentOperatingModel::canonicalWithdrawal($withdrawal->status);
            $activityAt = Carbon::parse($withdrawal->updated_at ?? $withdrawal->created_at);
            $critical = in_array($status, ['unknown', 'reversed'], true);

            return [
                'source' => 'withdrawal',
                'control_type' => 'withdrawal_exception',
                'priority' => $critical ? 'critical' : 'warning',
                'priority_score' => ($critical ? 400 : 180) + min((int) $activityAt->diffInMinutes($now), 99),
                'reference' => $withdrawal->external_reference ?? $withdrawal->provider_reference ?? ('WD-' . $withdrawal->id),
                'party' => ucfirst((string) ($withdrawal->partner_type ?? 'partenaire')),
                'amount' => (int) ($withdrawal->{$amountColumn} ?? 0),
                'status' => $status,
                'status_label' => $this->withdrawalStatusLabel($status),
                'age_label' => $activityAt->diffForHumans(),
                'reason' => $status === 'unknown'
                    ? 'Résultat fournisseur incertain : les fonds restent réservés.'
                    : ($status === 'reversed'
                        ? 'Décaissement inversé : contre-écriture et contrôle requis.'
                        : 'Retrait en attente depuis plus de dix minutes.'),
                'raw_id' => (int) $withdrawal->id,
                'can_reconcile' => false,
            ];
        });

        return [
            'reserved_amount' => (int) $base()->whereIn('status', $reservedStatuses)->sum($amountColumn),
            'reserved_count' => (int) $base()->whereIn('status', $reservedStatuses)->count(),
            'paid_today_amount' => (int) $base()->where('status', 'paid')->where('created_at', '>=', $now->copy()->startOfDay())->sum($amountColumn),
            'paid_today_count' => (int) $base()->where('status', 'paid')->where('created_at', '>=', $now->copy()->startOfDay())->count(),
            'unknown_amount' => (int) $base()->where('status', 'unknown')->sum($amountColumn),
            'unknown_count' => (int) $base()->where('status', 'unknown')->count(),
            'reversed_amount' => (int) $base()->where('status', 'reversed')->sum($amountColumn),
            'reversed_count' => (int) $base()->where('status', 'reversed')->count(),
            'failed_today_amount' => (int) $base()->whereIn('status', ['failed', 'cancelled'])->where('updated_at', '>=', $now->copy()->startOfDay())->sum($amountColumn),
            'failed_today_count' => (int) $base()->whereIn('status', ['failed', 'cancelled'])->where('updated_at', '>=', $now->copy()->startOfDay())->count(),
            'restaurant_reserved_amount' => (int) data_get($reservedByPartner, 'restaurant.amount', 0),
            'restaurant_reserved_count' => (int) data_get($reservedByPartner, 'restaurant.count', 0),
            'driver_reserved_amount' => (int) data_get($reservedByPartner, 'driver.amount', 0),
            'driver_reserved_count' => (int) data_get($reservedByPartner, 'driver.count', 0),
            'exception_items' => $exceptionItems,
        ];
    }

    private function partnerObligations(): array
    {
        $result = [
            'restaurant_scheduled_amount' => 0,
            'restaurant_scheduled_count' => 0,
            'driver_scheduled_amount' => 0,
            'driver_scheduled_count' => 0,
        ];

        foreach ([
            'restaurant' => 'restaurant_payments',
            'driver' => 'driver_payments',
        ] as $partner => $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $amountColumn = Schema::hasColumn($table, 'payout_amount') ? 'payout_amount' : 'amount';
            $query = DB::table($table)->where('status', 'pending');
            $result[$partner . '_scheduled_amount'] = (int) $query->sum($amountColumn);
            $result[$partner . '_scheduled_count'] = (int) $query->count();
        }

        $result['scheduled_total_amount'] = $result['restaurant_scheduled_amount'] + $result['driver_scheduled_amount'];
        $result['scheduled_total_count'] = $result['restaurant_scheduled_count'] + $result['driver_scheduled_count'];

        return $result;
    }

    private function buildQueue(
        Collection $payments,
        Collection $unallocated,
        Collection $duplicates,
        Collection $withdrawalItems,
        Carbon $now
    ): Collection {
        $items = collect($withdrawalItems);
        $duplicateKeys = $duplicates->pluck('reference')->filter()->all();
        $unallocatedIds = $unallocated->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($duplicates as $duplicate) {
            $items->push([
                'source' => 'collection',
                'control_type' => 'duplicate_reference',
                'priority' => 'critical',
                'priority_score' => 500,
                'reference' => $duplicate['reference'],
                'party' => $duplicate['provider'],
                'amount' => $duplicate['amount'],
                'status' => 'duplicate',
                'status_label' => 'Référence dupliquée',
                'age_label' => Carbon::parse($duplicate['latest_at'])->diffForHumans(),
                'reason' => $duplicate['count'] . ' paiements partagent la même référence fournisseur.',
                'raw_id' => $duplicate['payment_ids'][0] ?? null,
                'can_reconcile' => false,
            ]);
        }

        foreach ($unallocated as $payment) {
            $items->push($this->paymentQueueItem(
                $payment,
                $now,
                'unallocated_collection',
                'critical',
                450,
                'Confirmé non affecté',
                'Paiement confirmé sans commande rattachée.',
                false
            ));
        }

        foreach ($payments as $payment) {
            $status = PaymentOperatingModel::canonicalCollection($payment->status);
            $activityAt = Carbon::parse($payment->updated_at ?? $payment->created_at);
            $stale = PaymentOperatingModel::isUnresolvedCollection($payment->status)
                && $activityAt->lte($now->copy()->subMinutes(2));
            $exception = in_array($status, ['failed', 'unknown', 'reversed', 'disputed'], true) || $stale;

            if (! $exception || in_array((int) $payment->id, $unallocatedIds, true) || in_array($payment->provider_reference, $duplicateKeys, true)) {
                continue;
            }

            $critical = in_array($status, ['unknown', 'reversed', 'disputed'], true);
            $items->push($this->paymentQueueItem(
                $payment,
                $now,
                'collection_exception',
                $critical ? 'critical' : 'warning',
                ($critical ? 350 : 200) + min((int) $activityAt->diffInMinutes($now), 99),
                $this->collectionStatusLabel($status),
                $this->failureReason($payment) ?: 'État fournisseur à contrôler.',
                PaymentOperatingModel::canReconcileCollection($payment->status)
            ));
        }

        return $items
            ->sortByDesc('priority_score')
            ->take(12)
            ->values();
    }

    private function paymentQueueItem(
        object $payment,
        Carbon $now,
        string $type,
        string $priority,
        int $score,
        string $label,
        string $reason,
        bool $canReconcile
    ): array {
        $activityAt = Carbon::parse($payment->updated_at ?? $payment->created_at);

        return [
            'source' => 'collection',
            'control_type' => $type,
            'priority' => $priority,
            'priority_score' => $score,
            'reference' => $payment->provider_reference ?: ('TX-' . $payment->id),
            'party' => $this->providerLabel($payment->provider),
            'amount' => (int) $payment->amount,
            'status' => PaymentOperatingModel::canonicalCollection($payment->status),
            'status_label' => $label,
            'age_label' => $activityAt->diffForHumans(),
            'reason' => $reason,
            'raw_id' => (int) $payment->id,
            'can_reconcile' => $canReconcile,
        ];
    }

    private function health(Collection $queue, array $withdrawals): array
    {
        $critical = $queue->where('priority', 'critical')->count();

        if ($critical > 0) {
            return [
                'tone' => 'danger',
                'label' => 'Intervention financière requise',
                'message' => $critical . ' contrôle(s) critique(s) exigent une décision documentée.',
            ];
        }
        if ($queue->isNotEmpty()) {
            return [
                'tone' => 'warning',
                'label' => 'Flux sous surveillance',
                'message' => $queue->count() . ' dossier(s) non clôturé(s) dans la file de contrôle.',
            ];
        }

        return [
            'tone' => 'success',
            'label' => 'Flux opérationnels stables',
            'message' => 'Aucune exception financière prioritaire détectée.',
        ];
    }

    private function controlCoverage(): array
    {
        return [
            $this->coverage('Encaissements fournisseur', Schema::hasTable('payments'), 'Tentative, confirmation et statut fournisseur séparés.'),
            $this->coverage('Affectation paiement → commande', Schema::hasTable('payment_allocations'), 'Affectation et réaffectation sans modifier le paiement original.'),
            $this->coverage('Registre financier immuable', $this->anyTable(['ledger_entries', 'financial_ledger_entries', 'partner_ledger_entries']), 'Débits, crédits, réservations et contre-écritures traçables.'),
            $this->coverage('Remboursements structurés', $this->anyTable(['refunds', 'payment_refunds']), 'Remboursements partiels ou totaux avec workflow distinct.'),
            $this->coverage('Litiges structurés', $this->anyTable(['payment_disputes', 'disputes']), 'Preuves, décision, responsabilité et clôture.'),
            $this->coverage('Retraits partenaires', Schema::hasTable('partner_withdrawals'), 'Réservation, soumission, paiement, échec et inversion distincts.'),
        ];
    }

    private function accountingRules(): array
    {
        return [
            ['title' => 'Commande ≠ paiement', 'formula' => 'Une commande peut avoir plusieurs tentatives, mais une seule valeur encaissée affectée.'],
            ['title' => 'Confirmé ≠ disponible', 'formula' => 'Un paiement confirmé peut encore financer une dette partenaire, un remboursement ou une réserve.'],
            ['title' => 'Inconnu ≠ échoué', 'formula' => 'Les fonds restent réservés tant que le fournisseur n’a pas donné un résultat certain.'],
            ['title' => 'Correction ≠ suppression', 'formula' => 'Toute correction financière doit passer par une contre-écriture et un audit.'],
        ];
    }

    private function position(string $label, int $amount, int $count, string $definition): array
    {
        return compact('label', 'amount', 'count', 'definition');
    }

    private function coverage(string $label, bool $active, string $definition): array
    {
        return ['label' => $label, 'status' => $active ? 'active' : 'missing', 'definition' => $definition];
    }

    private function anyTable(array $tables): bool
    {
        return collect($tables)->contains(fn ($table) => Schema::hasTable($table));
    }

    private function rawStatuses(string $status): array
    {
        return match ($status) {
            'initiated' => ['INITIATED', 'CREATED'],
            'pending' => ['PENDING'],
            'processing' => ['AUTHORIZED', 'PROCESSING', 'SUBMITTED'],
            'success' => ['SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'CAPTURED', 'APPROVED'],
            'paid' => ['PAID'],
            'failed' => ['FAILED', 'REJECTED', 'DECLINED'],
            'cancelled' => ['CANCELLED', 'CANCELED'],
            'expired' => ['EXPIRED', 'TIMEOUT'],
            'refunded' => ['REFUNDED', 'PARTIALLY_REFUNDED'],
            'reversed' => ['REVERSED', 'REVERSAL', 'ROLLED_BACK'],
            'disputed' => ['DISPUTED', 'CHARGEBACK'],
            'unknown' => ['UNKNOWN', ''],
            default => [strtoupper($status)],
        };
    }

    private function collectionStatusLabel(string $status): string
    {
        return match ($status) {
            'initiated' => 'Initialisation',
            'pending' => 'En attente',
            'processing' => 'Traitement',
            'success', 'paid' => 'Confirmé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            'expired' => 'Expiré',
            'refunded' => 'Remboursé',
            'reversed' => 'Inversé',
            'disputed' => 'Contesté',
            default => 'Inconnu',
        };
    }

    private function withdrawalStatusLabel(string $status): string
    {
        return match ($status) {
            'created' => 'Demandé',
            'reserved' => 'Réservé',
            'submitted' => 'Soumis',
            'pending' => 'En attente',
            'paid' => 'Payé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            'reversed' => 'Inversé',
            default => 'Inconnu',
        };
    }

    private function providerLabel(?string $provider): string
    {
        return match (strtolower(trim((string) $provider))) {
            'mtn_momo', 'momo', 'mtn' => 'MTN MoMo',
            'airtel_money', 'airtel' => 'Airtel Money',
            'cash' => 'Espèces',
            'paypal' => 'PayPal',
            'stripe', 'card' => 'Carte',
            'gepay' => 'GePay',
            default => strtoupper(trim((string) $provider)) ?: 'Autre',
        };
    }

    private function failureReason(object $payment): ?string
    {
        $meta = is_array($payment->meta ?? null)
            ? $payment->meta
            : json_decode((string) ($payment->meta ?? ''), true);
        $meta = is_array($meta) ? $meta : [];

        foreach ([
            data_get($meta, 'failure_reason'),
            data_get($meta, 'provider_status.reason'),
            data_get($meta, 'provider_status.message'),
            data_get($meta, 'message'),
        ] as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
