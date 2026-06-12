<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentDashboardService
{
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
            ->where('created_at', '>=', $todayStart)
            ->orderByDesc('updated_at'),
            $filters
        )
            ->get();

        $windowPayments = $this->applyFilters(
            DB::table('payments')
            ->where('created_at', '>=', $windowStart)
            ->orderBy('created_at'),
            $filters
        )
            ->get();

        $recentPayments = $this->applyFilters(
            DB::table('payments')
            ->orderByDesc('updated_at')
            ->limit(14),
            $filters
        )
            ->get();

        $statusBreakdown = $this->statusBreakdown($todayPayments);
        $providerBreakdown = $this->providerBreakdown($todayPayments);
        $successCount = $statusBreakdown['success'] + $statusBreakdown['paid'];
        $totalCount = max(1, $todayPayments->count());
        $pendingCount = $statusBreakdown['pending'] + $statusBreakdown['initiated'] + $statusBreakdown['processing'];
        $stalePending = $todayPayments->filter(function ($payment) use ($now) {
            return in_array($this->canonicalStatus($payment->status), ['pending', 'initiated', 'processing'], true)
                && Carbon::parse($payment->updated_at ?? $payment->created_at)->lte($now->copy()->subMinutes(2));
        })->count();

        $failedLastHour = $todayPayments->filter(function ($payment) use ($now) {
            return $this->canonicalStatus($payment->status) === 'failed'
                && Carbon::parse($payment->updated_at ?? $payment->created_at)->gte($now->copy()->subHour());
        })->count();

        $cancelledToday = $todayPayments->filter(function ($payment) {
            return $this->canonicalStatus($payment->status) === 'cancelled';
        })->count();

        $successfulAmount = $todayPayments->filter(function ($payment) {
            return in_array($this->canonicalStatus($payment->status), ['success', 'paid'], true);
        })->sum('amount');

        return [
            'generatedAt' => $now,
            'hours' => $hours,
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
            'kpis' => [
                'turnover' => (int) $successfulAmount,
                'transactions' => (int) $todayPayments->count(),
                'success_rate' => round(($successCount / $totalCount) * 100, 1),
                'pending' => (int) $pendingCount,
            ],
            'statusBreakdown' => $statusBreakdown,
            'providerBreakdown' => $providerBreakdown,
            'hourlySeries' => $this->hourlySeries($windowPayments, $windowStart, $hours, $bucketSize),
            'livePayments' => $this->transformPayments($recentPayments->take(6)),
            'tablePayments' => $this->transformPayments($recentPayments),
            'alerts' => [
                [
                    'tone' => $stalePending > 0 ? 'warning' : 'success',
                    'label' => 'En attente prolongée',
                    'value' => $stalePending,
                    'message' => $stalePending > 0
                        ? $stalePending . ' paiement(s) en attente depuis plus de 2 minutes.'
                        : 'Aucun paiement bloqué au-delà de 2 minutes.',
                ],
                [
                    'tone' => $failedLastHour > 0 ? 'danger' : 'success',
                    'label' => 'Échecs sur 60 min',
                    'value' => $failedLastHour,
                    'message' => $failedLastHour > 0
                        ? $failedLastHour . ' échec(s) détecté(s) durant la dernière heure.'
                        : 'Aucun échec récent côté paiement.',
                ],
                [
                    'tone' => $cancelledToday > 0 ? 'info' : 'success',
                    'label' => 'Annulations du jour',
                    'value' => $cancelledToday,
                    'message' => $cancelledToday > 0
                        ? $cancelledToday . ' paiement(s) annulé(s) aujourd\'hui.'
                        : 'Aucune annulation signalée aujourd\'hui.',
                ],
            ],
        ];
    }

    protected function emptyDashboard(int $hours, array $filters): array
    {
        return [
            'generatedAt' => now(),
            'hours' => $hours,
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
            'kpis' => [
                'turnover' => 0,
                'transactions' => 0,
                'success_rate' => 0,
                'pending' => 0,
            ],
            'statusBreakdown' => [
                'initiated' => 0,
                'pending' => 0,
                'processing' => 0,
                'success' => 0,
                'paid' => 0,
                'failed' => 0,
                'cancelled' => 0,
                'expired' => 0,
                'refunded' => 0,
            ],
            'providerBreakdown' => collect(),
            'hourlySeries' => ['labels' => [], 'amounts' => [], 'counts' => []],
            'livePayments' => collect(),
            'tablePayments' => collect(),
            'alerts' => [],
        ];
    }

    protected function statusBreakdown(Collection $payments): array
    {
        $seed = [
            'initiated' => 0,
            'pending' => 0,
            'processing' => 0,
            'success' => 0,
            'paid' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'expired' => 0,
            'refunded' => 0,
        ];

        foreach ($payments as $payment) {
            $status = $this->canonicalStatus($payment->status);
            if (!array_key_exists($status, $seed)) {
                $seed[$status] = 0;
            }
            $seed[$status]++;
        }

        return $seed;
    }

    protected function providerBreakdown(Collection $payments): Collection
    {
        $items = $payments
            ->groupBy(function ($payment) {
                return $this->providerLabel($payment->provider);
            })
            ->map(function (Collection $group, string $provider) {
                return [
                    'provider' => $provider,
                    'count' => $group->count(),
                    'amount' => (int) $group->sum('amount'),
                    'success_rate' => round(
                        ($group->filter(function ($payment) {
                            return in_array($this->canonicalStatus($payment->status), ['success', 'paid'], true);
                        })->count() / max(1, $group->count())) * 100,
                        1
                    ),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $maxAmount = max(1, (int) $items->max('amount'));

        return $items->map(function (array $item) use ($maxAmount) {
            $item['share_percent'] = round(($item['amount'] / $maxAmount) * 100, 1);

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

            $buckets[$bucketKey]['amount'] += (int) $payment->amount;
            $buckets[$bucketKey]['count']++;
        }

        foreach ($buckets as $bucket) {
            $amounts[] = $bucket['amount'];
            $counts[] = $bucket['count'];
        }

        return compact('labels', 'amounts', 'counts');
    }

    protected function transformPayments(Collection $payments): Collection
    {
        return $payments->map(function ($payment) {
            $meta = $this->decodeMeta($payment->meta ?? null);
            $canonicalStatus = $this->canonicalStatus($payment->status);
            $activityAt = Carbon::parse($payment->updated_at ?? $payment->created_at);

            return [
                'id' => 'TX' . str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT),
                'phone' => $this->extractPhone($meta),
                'amount' => (int) $payment->amount,
                'status' => $canonicalStatus,
                'status_label' => $this->statusLabel($canonicalStatus),
                'provider' => $this->providerLabel($payment->provider),
                'reference' => $payment->provider_reference ?: 'n/a',
                'updated_at_human' => $activityAt->diffForHumans(),
                'updated_at_iso' => $activityAt->toIso8601String(),
                'reason' => $this->extractReason($meta),
            ];
        });
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
            'REFUNDED' => 'refunded',
            default => 'pending',
        };
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'initiated' => 'Initialisation',
            'pending' => 'En attente',
            'processing' => 'Traitement',
            'success', 'paid' => 'Réussi',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            'expired' => 'Expiré',
            'refunded' => 'Remboursé',
            default => 'En attente',
        };
    }

    protected function providerLabel(?string $provider): string
    {
        return match (strtolower(trim((string) $provider))) {
            'mtn_momo', 'momo' => 'MTN MoMo',
            'airtel_money', 'airtel' => 'Airtel Money',
            'cash' => 'Espèces',
            'paypal' => 'PayPal',
            'stripe', 'card' => 'Carte',
            default => strtoupper(trim((string) $provider)) ?: 'Autre',
        };
    }

    protected function normalizeFilters(array $filters): array
    {
        $provider = strtolower(trim((string) ($filters['provider'] ?? 'all')));
        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));

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
                ['value' => 'all', 'label' => 'Tous'],
                ['value' => 'mtn', 'label' => 'MTN MoMo'],
                ['value' => 'airtel', 'label' => 'Airtel Money'],
                ['value' => 'cash', 'label' => 'Espèces'],
                ['value' => 'paypal', 'label' => 'PayPal'],
                ['value' => 'card', 'label' => 'Carte'],
            ],
            'statuses' => [
                ['value' => 'all', 'label' => 'Tous'],
                ['value' => 'initiated', 'label' => 'Initialisation'],
                ['value' => 'pending', 'label' => 'En attente'],
                ['value' => 'processing', 'label' => 'Traitement'],
                ['value' => 'paid', 'label' => 'Payé'],
                ['value' => 'success', 'label' => 'Réussi'],
                ['value' => 'failed', 'label' => 'Échoué'],
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
            $statusFilter = $filters['status'];

            if (in_array($statusFilter, ['success', 'processing', 'initiated', 'expired'], true)) {
                $query->whereIn('status', $this->rawStatusesForCanonical($statusFilter));
            } else {
                $query->where('status', strtoupper($statusFilter));
            }
        }

        return $query;
    }

    protected function rawStatusesForCanonical(string $status): array
    {
        return match ($status) {
            'initiated' => ['INITIATED'],
            'processing' => ['AUTHORIZED', 'PROCESSING'],
            'success' => ['SUCCESS', 'SUCCESSFUL'],
            'expired' => ['EXPIRED', 'TIMEOUT'],
            default => [strtoupper($status)],
        };
    }

    protected function rawProvidersForFilter(string $provider): array
    {
        return match ($provider) {
            'mtn' => ['momo', 'mtn_momo'],
            'airtel' => ['airtel', 'airtel_money'],
            'card' => ['card', 'stripe'],
            default => [$provider],
        };
    }
}
