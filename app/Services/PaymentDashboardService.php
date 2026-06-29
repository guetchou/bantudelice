<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentDashboardService
{
    private const RESOLVED_STATUSES = ['success', 'paid', 'cancelled', 'expired', 'refunded'];
    private const UNRESOLVED_STATUSES = ['initiated', 'pending', 'processing', 'unknown'];

    public function build(int $hours = 12, array $filters = []): array
    {
        $hours = in_array($hours, [6, 12, 24], true) ? $hours : 12;
        $filters = $this->normalizeFilters($filters);

        if (!Schema::hasTable('payments')) {
            return $this->emptyDashboard($hours, $filters);
        }

        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $windowStart = $now->copy()->subHours($hours)->startOfHour();
        $bucketSize = $hours >= 12 ? 2 : 1;

        $todayPayments = $this->applyFilters(
            DB::table('payments')
                ->whereNull('deleted_at')
                ->where('created_at', '>=', $todayStart)
                ->orderByDesc('updated_at'),
            $filters
        )->get();

        $windowPayments = $this->applyFilters(
            DB::table('payments')
                ->whereNull('deleted_at')
                ->where('created_at', '>=', $windowStart)
                ->orderBy('created_at'),
            $filters
        )->get();

        $recentPayments = $this->applyFilters(
            DB::table('payments')
                ->whereNull('deleted_at')
                ->orderByDesc('updated_at')
                ->limit(40),
            $filters
        )->get();

        $statusBreakdown = $this->statusBreakdown($todayPayments);
        $successCount = $statusBreakdown['success'] + $statusBreakdown['paid'];
        $transactionCount = $todayPayments->count();
        $successfulAmount = (int) $todayPayments
            ->filter(fn ($payment) => in_array($this->canonicalStatus($payment->status), ['success', 'paid'], true))
            ->sum('amount');

        $stalePayments = $todayPayments->filter(fn ($payment) => $this->isStaleUnresolved($payment, $now));
        $failedLastHour = $todayPayments->filter(function ($payment) use ($now) {
            return $this->canonicalStatus($payment->status) === 'failed'
                && Carbon::parse($payment->updated_at ?? $payment->created_at)->gte($now->copy()->subHour());
        });
        $unknownToday = $todayPayments->filter(fn ($payment) => $this->canonicalStatus($payment->status) === 'unknown');
        $reversedToday = $todayPayments->filter(fn ($payment) => $this->canonicalStatus($payment->status) === 'reversed');

        $exceptions = $todayPayments
            ->filter(fn ($payment) => $this->needsAttention($payment, $now))
            ->unique('id')
            ->values();

        return [
            'generatedAt' => $now,
            'hours' => $hours,
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
            'health' => $this->healthSummary($exceptions->count(), $unknownToday->count(), $reversedToday->count()),
            'kpis' => [
                'turnover' => $successfulAmount,
                'transactions' => (int) $transactionCount,
                'success_rate' => $transactionCount > 0 ? round(($successCount / $transactionCount) * 100, 1) : 0.0,
                'pending' => (int) collect(self::UNRESOLVED_STATUSES)->sum(fn ($status) => $statusBreakdown[$status] ?? 0),
                'exceptions' => (int) $exceptions->count(),
            ],
            'statusBreakdown' => $statusBreakdown,
            'providerBreakdown' => $this->providerBreakdown($todayPayments),
            'hourlySeries' => $this->hourlySeries($windowPayments, $windowStart, $hours, $bucketSize),
            'workQueue' => $this->workQueue($recentPayments, $now),
            'tablePayments' => $this->transformPayments($recentPayments->take(30), $now),
            'alerts' => $this->alerts(
                $stalePayments->count(),
                $failedLastHour->count(),
                $unknownToday->count(),
                $reversedToday->count()
            ),
        ];
    }

    protected function emptyDashboard(int $hours, array $filters): array
    {
        return [
            'generatedAt' => now(),
            'hours' => $hours,
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
            'health' => ['tone' => 'neutral', 'label' => 'Aucune donnée', 'message' => 'Aucun paiement disponible.'],
            'kpis' => [
                'turnover' => 0,
                'transactions' => 0,
                'success_rate' => 0.0,
                'pending' => 0,
                'exceptions' => 0,
            ],
            'statusBreakdown' => $this->statusSeed(),
            'providerBreakdown' => collect(),
            'hourlySeries' => ['labels' => [], 'amounts' => [], 'counts' => []],
            'workQueue' => collect(),
            'tablePayments' => collect(),
            'alerts' => [],
        ];
    }

    protected function statusSeed(): array
    {
        return [
            'initiated' => 0,
            'pending' => 0,
            'processing' => 0,
            'success' => 0,
            'paid' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'expired' => 0,
            'refunded' => 0,
            'unknown' => 0,
            'reversed' => 0,
            'disputed' => 0,
        ];
    }

    protected function statusBreakdown(Collection $payments): array
    {
        $seed = $this->statusSeed();

        foreach ($payments as $payment) {
            $status = $this->canonicalStatus($payment->status);
            $seed[$status] = ($seed[$status] ?? 0) + 1;
        }

        return $seed;
    }

    protected function providerBreakdown(Collection $payments): Collection
    {
        $items = $payments
            ->groupBy(fn ($payment) => $this->providerLabel($payment->provider))
            ->map(function (Collection $group, string $provider) {
                $successCount = $group->filter(fn ($payment) => in_array($this->canonicalStatus($payment->status), ['success', 'paid'], true))->count();

                return [
                    'provider' => $provider,
                    'count' => $group->count(),
                    'amount' => (int) $group
                        ->filter(fn ($payment) => in_array($this->canonicalStatus($payment->status), ['success', 'paid'], true))
                        ->sum('amount'),
                    'success_rate' => round(($successCount / max(1, $group->count())) * 100, 1),
                    'exceptions' => $group->filter(fn ($payment) => $this->needsAttention($payment, now()))->count(),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $totalAmount = max(1, (int) $items->sum('amount'));

        return $items->map(function (array $item) use ($totalAmount) {
            $item['share_percent'] = round(($item['amount'] / $totalAmount) * 100, 1);

            return $item;
        });
    }

    protected function hourlySeries(Collection $payments, Carbon $windowStart, int $hours, int $bucketSize): array
    {
        $labels = [];
        $amounts = [];
        $counts = [];
        $buckets = [];

        for ($cursor = $windowStart->copy(); $cursor->lte(now()); $cursor->addHours($bucketSize)) {
            $key = $cursor->format('Y-m-d H:00:00');
            $labels[] = $cursor->format('H\h');
            $buckets[$key] = ['amount' => 0, 'count' => 0];
        }

        foreach ($payments as $payment) {
            $createdAt = Carbon::parse($payment->created_at)->minute(0)->second(0);
            $bucketHour = (int) floor($createdAt->hour / $bucketSize) * $bucketSize;
            $bucketKey = $createdAt->copy()->setTime($bucketHour, 0)->format('Y-m-d H:00:00');

            if (!array_key_exists($bucketKey, $buckets)) {
                continue;
            }

            $buckets[$bucketKey]['count']++;

            if (in_array($this->canonicalStatus($payment->status), ['success', 'paid'], true)) {
                $buckets[$bucketKey]['amount'] += (int) $payment->amount;
            }
        }

        foreach ($buckets as $bucket) {
            $amounts[] = $bucket['amount'];
            $counts[] = $bucket['count'];
        }

        return compact('labels', 'amounts', 'counts');
    }

    protected function workQueue(Collection $payments, Carbon $now): Collection
    {
        return $payments
            ->filter(fn ($payment) => $this->needsAttention($payment, $now))
            ->map(function ($payment) use ($now) {
                $item = $this->transformPayment($payment, $now);
                $item['priority_score'] = match ($item['status']) {
                    'unknown', 'reversed', 'disputed' => 300,
                    'failed' => 200,
                    default => 100,
                } + min($item['age_minutes'], 99);

                return $item;
            })
            ->sortByDesc('priority_score')
            ->take(8)
            ->values();
    }

    protected function transformPayments(Collection $payments, ?Carbon $now = null): Collection
    {
        $now ??= now();

        return $payments->map(fn ($payment) => $this->transformPayment($payment, $now));
    }

    protected function transformPayment($payment, Carbon $now): array
    {
        $meta = $this->decodeMeta($payment->meta ?? null);
        $status = $this->canonicalStatus($payment->status);
        $activityAt = Carbon::parse($payment->updated_at ?? $payment->created_at);
        $ageMinutes = max(0, $activityAt->diffInMinutes($now));

        return [
            'id' => 'TX' . str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT),
            'raw_id' => (int) $payment->id,
            'order_reference' => $payment->order_id ? '#' . $payment->order_id : 'Sans commande',
            'phone' => $this->extractPhone($meta),
            'amount' => (int) $payment->amount,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'provider' => $this->providerLabel($payment->provider),
            'reference' => $payment->provider_reference ?: 'Non attribuée',
            'updated_at_human' => $activityAt->diffForHumans(),
            'updated_at_iso' => $activityAt->toIso8601String(),
            'age_minutes' => $ageMinutes,
            'age_label' => $this->ageLabel($ageMinutes),
            'reason' => $this->extractReason($meta),
            'severity' => $this->severity($status, $ageMinutes),
            'can_reconcile' => in_array($status, ['initiated', 'pending', 'processing', 'failed', 'unknown', 'reversed'], true),
        ];
    }

    protected function alerts(int $stale, int $failedLastHour, int $unknown, int $reversed): array
    {
        $alerts = [];

        if ($unknown > 0) {
            $alerts[] = ['tone' => 'danger', 'label' => 'Statuts inconnus', 'value' => $unknown, 'message' => 'Confirmation opérateur manquante : revue manuelle prioritaire.'];
        }
        if ($reversed > 0) {
            $alerts[] = ['tone' => 'danger', 'label' => 'Inversions financières', 'value' => $reversed, 'message' => 'Des paiements ont été inversés et doivent être rapprochés.'];
        }
        if ($stale > 0) {
            $alerts[] = ['tone' => 'warning', 'label' => 'Attentes prolongées', 'value' => $stale, 'message' => 'Paiements non résolus depuis plus de deux minutes.'];
        }
        if ($failedLastHour > 0) {
            $alerts[] = ['tone' => 'warning', 'label' => 'Échecs récents', 'value' => $failedLastHour, 'message' => 'Échecs détectés durant les soixante dernières minutes.'];
        }

        if ($alerts === []) {
            $alerts[] = ['tone' => 'success', 'label' => 'Flux stable', 'value' => 0, 'message' => 'Aucune exception prioritaire sur le périmètre affiché.'];
        }

        return $alerts;
    }

    protected function healthSummary(int $exceptions, int $unknown, int $reversed): array
    {
        if ($unknown > 0 || $reversed > 0) {
            return ['tone' => 'danger', 'label' => 'Intervention requise', 'message' => 'Des opérations financières non résolues exigent une décision.'];
        }

        if ($exceptions > 0) {
            return ['tone' => 'warning', 'label' => 'Sous surveillance', 'message' => 'Des paiements doivent être rapprochés.'];
        }

        return ['tone' => 'success', 'label' => 'Flux stable', 'message' => 'Aucune exception financière prioritaire.'];
    }

    protected function needsAttention($payment, Carbon $now): bool
    {
        $status = $this->canonicalStatus($payment->status);

        return in_array($status, ['failed', 'unknown', 'reversed', 'disputed'], true)
            || $this->isStaleUnresolved($payment, $now);
    }

    protected function isStaleUnresolved($payment, Carbon $now): bool
    {
        $status = $this->canonicalStatus($payment->status);

        return in_array($status, self::UNRESOLVED_STATUSES, true)
            && Carbon::parse($payment->updated_at ?? $payment->created_at)->lte($now->copy()->subMinutes(2));
    }

    protected function severity(string $status, int $ageMinutes): string
    {
        if (in_array($status, ['unknown', 'reversed', 'disputed'], true)) {
            return 'critical';
        }
        if ($status === 'failed' || ($ageMinutes >= 2 && in_array($status, self::UNRESOLVED_STATUSES, true))) {
            return 'warning';
        }

        return 'normal';
    }

    protected function ageLabel(int $minutes): string
    {
        if ($minutes < 1) {
            return 'À l’instant';
        }
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);

        return $hours < 24 ? $hours . ' h' : intdiv($hours, 24) . ' j';
    }

    protected function decodeMeta($meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }

        if (!is_string($meta) || trim($meta) === '') {
            return [];
        }

        $decoded = json_decode($meta, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function extractPhone(array $meta): string
    {
        $candidates = [
            data_get($meta, 'phone'),
            data_get($meta, 'payer'),
            data_get($meta, 'customer.phone'),
            data_get($meta, 'request.phone'),
            data_get($meta, 'payment.phone'),
        ];

        foreach ($candidates as $candidate) {
            $digits = preg_replace('/\D+/', '', (string) $candidate);
            if ($digits !== '') {
                if (strlen($digits) > 9) {
                    $digits = substr($digits, -9);
                }

                return trim(chunk_split($digits, 2, ' '));
            }
        }

        return 'Non renseigné';
    }

    protected function extractReason(array $meta): ?string
    {
        $candidates = [
            data_get($meta, 'failure_reason'),
            data_get($meta, 'provider_status.reason'),
            data_get($meta, 'provider_status.message'),
            data_get($meta, 'message'),
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function canonicalStatus(?string $status): string
    {
        return match (strtoupper(trim((string) $status))) {
            'INITIATED' => 'initiated',
            'PENDING' => 'pending',
            'AUTHORIZED', 'PROCESSING' => 'processing',
            'SUCCESS', 'SUCCESSFUL' => 'success',
            'PAID' => 'paid',
            'FAILED', 'REJECTED', 'DECLINED' => 'failed',
            'CANCELLED', 'CANCELED' => 'cancelled',
            'EXPIRED', 'TIMEOUT' => 'expired',
            'REFUNDED', 'PARTIALLY_REFUNDED' => 'refunded',
            'REVERSED', 'REVERSAL', 'ROLLED_BACK' => 'reversed',
            'DISPUTED', 'CHARGEBACK' => 'disputed',
            'UNKNOWN', '' => 'unknown',
            default => 'unknown',
        };
    }

    protected function statusLabel(string $status): string
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

    protected function providerLabel(?string $provider): string
    {
        return match (strtolower(trim((string) $provider))) {
            'mtn_momo', 'momo', 'mtn' => 'MTN MoMo',
            'airtel_money', 'airtel' => 'Airtel Money',
            'cash' => 'Espèces',
            'paypal' => 'PayPal',
            'stripe', 'card' => 'Carte',
            default => strtoupper(trim((string) $provider)) ?: 'Autre',
        };
    }

    protected function normalizeFilters(array $filters): array
    {
        $provider = strtolower(trim((string) ($filters['provider'] ?? 'all'));
        $status = strtolower(trim((string) ($filters['status'] ?? 'all'));

        $allowedProviders = collect($this->filterOptions()['providers'])->pluck('value')->all();
        $allowedStatuses = collect($this->filterOptions()['statuses'])->pluck('value')->all();

        return [
            'provider' => in_array($provider, $allowedProviders, true) ? $provider : 'all',
            'status' => in_array($status, $allowedStatuses, true) ? $status : 'all',
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'providers' => [
                ['value' => 'all', 'label' => 'Tous les canaux'],
                ['value' => 'mtn', 'label' => 'MTN MoMo'],
                ['value' => 'airtel', 'label' => 'Airtel Money'],
                ['value' => 'cash', 'label' => 'Espèces'],
                ['value' => 'paypal', 'label' => 'PayPal'],
                ['value' => 'card', 'label' => 'Carte'],
            ],
            'statuses' => [
                ['value' => 'all', 'label' => 'Tous les statuts'],
                ['value' => 'initiated', 'label' => 'Initialisation'],
                ['value' => 'pending', 'label' => 'En attente'],
                ['value' => 'processing', 'label' => 'Traitement'],
                ['value' => 'paid', 'label' => 'Payé'],
                ['value' => 'success', 'label' => 'Réussi'],
                ['value' => 'failed', 'label' => 'Échoué'],
                ['value' => 'unknown', 'label' => 'Inconnu'],
                ['value' => 'reversed', 'label' => 'Inversé'],
                ['value' => 'disputed', 'label' => 'Contesté'],
                ['value' => 'cancelled', 'label' => 'Annulé'],
                ['value' => 'expired', 'label' => 'Expiré'],
                ['value' => 'refunded', 'label' => 'Remboursé'],
            ],
        ];
    }

    protected function applyFilters($query, array $filters)
    {
        if (($filters['provider'] ?? 'all') !== 'all') {
            $query->whereIn('provider', $this->rawProvidersForFilter($filters['provider']));
        }

        if (($filters['status'] ?? 'all') !== 'all') {
            $query->whereIn('status', $this->rawStatusesForCanonical($filters['status']));
        }

        return $query;
    }

    protected function rawStatusesForCanonical(string $status): array
    {
        return match ($status) {
            'initiated' => ['INITIATED'],
            'pending' => ['PENDING'],
            'processing' => ['AUTHORIZED', 'PROCESSING'],
            'success' => ['SUCCESS', 'SUCCESSFUL'],
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

    protected function rawProvidersForFilter(string $provider): array
    {
        return match ($provider) {
            'mtn' => ['momo', 'mtn_momo', 'mtn'],
            'airtel' => ['airtel', 'airtel_money'],
            'card' => ['card', 'stripe'],
            default => [$provider],
        };
    }
}
