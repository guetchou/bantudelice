<?php

namespace App\Services;

use App\Order;
use App\Payment;
use App\Delivery;
use App\User;
use App\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service de calcul des métriques et KPIs
 */
class MetricsService
{
    /**
     * Calculer les métriques temps réel
     * 
     * @return array
     */
    public function getRealtimeMetrics(): array
    {
        $cacheKey = 'metrics_realtime';
        $ttl = 60; // Cache 1 minute

        return Cache::remember($cacheKey, $ttl, function () {
            $now = now();
            $today = $now->copy()->startOfDay();
            $yesterday = $today->copy()->subDay();
            $last7Days = $now->copy()->subDays(7);
            $last30Days = $now->copy()->subDays(30);

            return [
                // Commandes
                'orders' => [
                    'today' => $this->countOrders($today, $now),
                    'yesterday' => $this->countOrders($yesterday, $yesterday->copy()->endOfDay()),
                    'last_7_days' => $this->countOrders($last7Days, $now),
                    'last_30_days' => $this->countOrders($last30Days, $now),
                    'pending' => $this->countOrdersByStatus('pending'),
                    'in_progress' => $this->countOrdersByStatus(['assign', 'prepairing']),
                    'completed_today' => $this->countOrdersByStatus('completed', $today, $now),
                ],

                // Revenus
                'revenue' => [
                    'today' => $this->calculateRevenue($today, $now),
                    'yesterday' => $this->calculateRevenue($yesterday, $yesterday->copy()->endOfDay()),
                    'last_7_days' => $this->calculateRevenue($last7Days, $now),
                    'last_30_days' => $this->calculateRevenue($last30Days, $now),
                ],

                // Livraisons
                'deliveries' => [
                    'pending' => $this->countDeliveriesByStatus('PENDING'),
                    'assigned' => $this->countDeliveriesByStatus('ASSIGNED'),
                    'in_progress' => $this->countDeliveriesByStatus(['PICKED_UP', 'ON_THE_WAY']),
                    'completed_today' => $this->countDeliveriesByStatus('DELIVERED', $today, $now),
                    'avg_delivery_time' => $this->calculateAvgDeliveryTime($today, $now),
                ],

                // Paiements
                'payments' => [
                    'pending' => $this->countPaymentsByStatus('PENDING'),
                    'paid_today' => $this->countPaymentsByStatus('PAID', $today, $now),
                    'failed_today' => $this->countPaymentsByStatus('FAILED', $today, $now),
                    'success_rate' => $this->calculatePaymentSuccessRate($today, $now),
                ],

                // Utilisateurs
                'users' => [
                    'total' => User::where('type', 'user')->count(),
                    'new_today' => User::where('type', 'user')
                        ->whereDate('created_at', $today)
                        ->count(),
                    'active_last_30_days' => $this->countActiveUsers($last30Days),
                ],

                // Restaurants
                'restaurants' => [
                    'total' => Restaurant::where('approved', true)->count(),
                    'active_today' => $this->countActiveRestaurants($today, $now),
                ],

                // Alertes
                'alerts' => $this->checkAlerts(),
            ];
        });
    }

    /**
     * Compter les commandes dans une période
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return int
     */
    protected function countOrders(Carbon $start, Carbon $end): int
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->distinct('order_no')
            ->count('order_no');
    }

    /**
     * Compter les commandes par statut
     * 
     * @param string|array $status
     * @param Carbon|null $start
     * @param Carbon|null $end
     * @return int
     */
    protected function countOrdersByStatus($status, ?Carbon $start = null, ?Carbon $end = null): int
    {
        $query = Order::query();
        
        if (is_array($status)) {
            $query->whereIn('status', $status);
        } else {
            $query->where('status', $status);
        }
        
        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }
        
        return $query->distinct('order_no')->count('order_no');
    }

    /**
     * Calculer les revenus dans une période
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return float
     */
    protected function calculateRevenue(Carbon $start, Carbon $end): float
    {
        return (float) Order::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->distinct('order_no')
            ->sum('total') ?? 0;
    }

    /**
     * Compter les livraisons par statut
     * 
     * @param string|array $status
     * @param Carbon|null $start
     * @param Carbon|null $end
     * @return int
     */
    protected function countDeliveriesByStatus($status, ?Carbon $start = null, ?Carbon $end = null): int
    {
        $query = Delivery::query();
        
        if (is_array($status)) {
            $query->whereIn('status', $status);
        } else {
            $query->where('status', $status);
        }
        
        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }
        
        return $query->count();
    }

    /**
     * Calculer le temps moyen de livraison
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return float|null Minutes
     */
    protected function calculateAvgDeliveryTime(Carbon $start, Carbon $end): ?float
    {
        $deliveries = Delivery::where('status', 'DELIVERED')
            ->whereNotNull('assigned_at')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$start, $end])
            ->get();

        if ($deliveries->isEmpty()) {
            return null;
        }

        $totalMinutes = $deliveries->sum(function ($delivery) {
            return $delivery->assigned_at->diffInMinutes($delivery->delivered_at);
        });

        return round($totalMinutes / $deliveries->count(), 2);
    }

    /**
     * Compter les paiements par statut
     * 
     * @param string $status
     * @param Carbon|null $start
     * @param Carbon|null $end
     * @return int
     */
    protected function countPaymentsByStatus(string $status, ?Carbon $start = null, ?Carbon $end = null): int
    {
        $query = Payment::where('status', $status);
        
        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }
        
        return $query->count();
    }

    /**
     * Calculer le taux de succès des paiements
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return float|null Pourcentage
     */
    protected function calculatePaymentSuccessRate(Carbon $start, Carbon $end): ?float
    {
        $total = Payment::whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['PAID', 'FAILED'])
            ->count();

        if ($total === 0) {
            return null;
        }

        $success = Payment::whereBetween('created_at', [$start, $end])
            ->where('status', 'PAID')
            ->count();

        return round(($success / $total) * 100, 2);
    }

    /**
     * Compter les utilisateurs actifs
     * 
     * @param Carbon $since
     * @return int
     */
    protected function countActiveUsers(Carbon $since): int
    {
        return Order::where('created_at', '>=', $since)
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Compter les restaurants actifs (avec commandes)
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return int
     */
    protected function countActiveRestaurants(Carbon $start, Carbon $end): int
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->distinct('restaurant_id')
            ->count('restaurant_id');
    }

    /**
     * Vérifier les alertes (commandes en attente, paiements échoués, etc.)
     * 
     * @return array
     */
    protected function checkAlerts(): array
    {
        $alerts = [];

        // Commandes en attente > 30 minutes
        $oldPendingOrders = Order::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(30))
            ->count();

        if ($oldPendingOrders > 0) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'medium',
                'message' => "{$oldPendingOrders} commande(s) en attente depuis plus de 30 minutes",
                'count' => $oldPendingOrders,
            ];
        }

        // Livraisons en attente d'assignation > 10 minutes
        $oldPendingDeliveries = Delivery::where('status', 'PENDING')
            ->where('created_at', '<', now()->subMinutes(10))
            ->count();

        if ($oldPendingDeliveries > 5) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'high',
                'message' => "{$oldPendingDeliveries} livraison(s) en attente d'assignation",
                'count' => $oldPendingDeliveries,
            ];
        }

        // Paiements échoués récents
        $failedPayments = Payment::where('status', 'FAILED')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($failedPayments > 10) {
            $alerts[] = [
                'type' => 'error',
                'severity' => 'high',
                'message' => "{$failedPayments} paiement(s) échoué(s) dans la dernière heure",
                'count' => $failedPayments,
            ];
        }

        // Taux de succès paiement < 80%
        $successRate = $this->calculatePaymentSuccessRate(now()->subHour(), now());
        if ($successRate !== null && $successRate < 80) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'medium',
                'message' => "Taux de succès paiement faible: {$successRate}%",
                'rate' => $successRate,
            ];
        }

        return $alerts;
    }

    /**
     * Générer les métriques quotidiennes (pour historique)
     * 
     * @param Carbon|null $date
     * @return array
     */
    public function generateDailyMetrics(?Carbon $date = null): array
    {
        $date = $date ?? now()->subDay(); // Par défaut, hier
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $metrics = [
            'date' => $date->format('Y-m-d'),
            'orders_count' => $this->countOrders($start, $end),
            'orders_completed' => $this->countOrdersByStatus('completed', $start, $end),
            'orders_cancelled' => $this->countOrdersByStatus('cancelled', $start, $end),
            'revenue' => $this->calculateRevenue($start, $end),
            'deliveries_completed' => $this->countDeliveriesByStatus('DELIVERED', $start, $end),
            'avg_delivery_time' => $this->calculateAvgDeliveryTime($start, $end),
            'payments_paid' => $this->countPaymentsByStatus('PAID', $start, $end),
            'payments_failed' => $this->countPaymentsByStatus('FAILED', $start, $end),
            'payment_success_rate' => $this->calculatePaymentSuccessRate($start, $end),
            'new_users' => User::where('type', 'user')
                ->whereDate('created_at', $date)
                ->count(),
            'active_restaurants' => $this->countActiveRestaurants($start, $end),
        ];

        return $metrics;
    }

    /**
     * Récupérer les métriques historiques
     * 
     * @param int $days
     * @return array
     */
    public function getHistoricalMetrics(int $days = 30): array
    {
        $cacheKey = "metrics_historical_{$days}";
        $ttl = 3600; // Cache 1 heure

        return Cache::remember($cacheKey, $ttl, function () use ($days) {
            $start = now()->subDays($days)->startOfDay();
            
            // Récupérer depuis daily_metrics si disponible
            try {
                $dailyMetrics = DB::table('daily_metrics')
                    ->where('date', '>=', $start->format('Y-m-d'))
                    ->orderBy('date', 'asc')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'date' => $row->date,
                            'orders_count' => $row->orders_count,
                            'revenue' => $row->revenue,
                            'avg_delivery_time' => $row->avg_delivery_time,
                            'payment_success_rate' => $row->payment_success_rate,
                        ];
                    })
                    ->toArray();

                if (!empty($dailyMetrics)) {
                    return $dailyMetrics;
                }
            } catch (\Exception $e) {
                // Table n'existe pas encore, calculer à la volée
            }

            // Calculer à la volée si pas de daily_metrics
            $metrics = [];
            for ($i = $days; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $metrics[] = $this->generateDailyMetrics($date);
            }

            return $metrics;
        });
    }
}

