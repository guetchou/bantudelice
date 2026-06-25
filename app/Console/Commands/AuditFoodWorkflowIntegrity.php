<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditFoodWorkflowIntegrity extends Command
{
    protected $signature = 'food:audit-integrity {--json : Retourner le rapport au format JSON}';

    protected $description = 'Détecte les doublons et incohérences avant activation des contraintes SQL du workflow food';

    public function handle(): int
    {
        $checks = [
            'duplicate_payments' => $this->duplicatePayments(),
            'duplicate_deliveries' => $this->duplicateDeliveries(),
            'inconsistent_order_groups' => $this->inconsistentOrderGroups(),
            'cash_paid_without_collection' => $this->cashPaidWithoutCollection(),
            'scheduled_orders_without_date' => $this->scheduledOrdersWithoutDate(),
            'orphan_restaurant_staff' => $this->orphanRestaurantStaff(),
        ];

        $total = collect($checks)->sum(fn (array $rows) => count($rows));
        $report = [
            'generated_at' => now()->toIso8601String(),
            'status' => $total === 0 ? 'clean' : 'violations_detected',
            'violations_count' => $total,
            'checks' => $checks,
        ];

        Log::log($total === 0 ? 'info' : 'warning', 'Food workflow integrity audit', $report);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info('Audit d’intégrité du workflow restaurant');
            $this->line('Statut : ' . $report['status']);
            $this->line('Violations : ' . $total);

            foreach ($checks as $name => $rows) {
                $this->line(sprintf('- %s : %d', $name, count($rows)));
                foreach (array_slice($rows, 0, 10) as $row) {
                    $this->line('  ' . json_encode($row, JSON_UNESCAPED_UNICODE));
                }
                if (count($rows) > 10) {
                    $this->line('  … ' . (count($rows) - 10) . ' autre(s)');
                }
            }
        }

        return $total === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function duplicatePayments(): array
    {
        if (! Schema::hasTable('payments')) {
            return [];
        }

        $query = DB::table('payments')
            ->select('order_id', 'provider', DB::raw('COUNT(*) as occurrences'))
            ->whereNotNull('order_id');

        if (Schema::hasColumn('payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query
            ->groupBy('order_id', 'provider')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('occurrences')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function duplicateDeliveries(): array
    {
        if (! Schema::hasTable('deliveries')) {
            return [];
        }

        return DB::table('deliveries')
            ->select('order_id', DB::raw('COUNT(*) as occurrences'))
            ->whereNotNull('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('occurrences')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function inconsistentOrderGroups(): array
    {
        if (! Schema::hasTable('orders')) {
            return [];
        }

        return DB::table('orders')
            ->select(
                'order_no',
                DB::raw('COUNT(DISTINCT restaurant_id) as restaurants'),
                DB::raw('COUNT(DISTINCT user_id) as customers'),
                DB::raw('COUNT(DISTINCT total) as totals'),
                DB::raw('COUNT(DISTINCT payment_method) as payment_methods')
            )
            ->whereNotNull('order_no')
            ->groupBy('order_no')
            ->havingRaw(
                'COUNT(DISTINCT restaurant_id) > 1 '
                . 'OR COUNT(DISTINCT user_id) > 1 '
                . 'OR COUNT(DISTINCT total) > 1 '
                . 'OR COUNT(DISTINCT payment_method) > 1'
            )
            ->limit(500)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function cashPaidWithoutCollection(): array
    {
        if (! Schema::hasTable('orders')
            || ! Schema::hasColumn('orders', 'cash_collection_confirmed_at')) {
            return [];
        }

        return DB::table('orders')
            ->select('order_no', DB::raw('MIN(id) as first_order_id'))
            ->where('payment_method', 'cash')
            ->where('payment_status', 'paid')
            ->whereNull('cash_collection_confirmed_at')
            ->whereNotIn('business_status', ['cancelled', 'refunded'])
            ->groupBy('order_no')
            ->limit(500)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function scheduledOrdersWithoutDate(): array
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'scheduled_date')) {
            return [];
        }

        return DB::table('orders')
            ->select('order_no', 'business_status')
            ->whereIn('business_status', ['accepted_scheduled', 'preparation_due'])
            ->whereNull('scheduled_date')
            ->groupBy('order_no', 'business_status')
            ->limit(500)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function orphanRestaurantStaff(): array
    {
        if (! Schema::hasTable('restaurant_staff_members')) {
            return [];
        }

        return DB::table('restaurant_staff_members as staff')
            ->leftJoin('users', 'users.id', '=', 'staff.user_id')
            ->leftJoin('restaurants', 'restaurants.id', '=', 'staff.restaurant_id')
            ->where(function ($query) {
                $query->whereNull('users.id')->orWhereNull('restaurants.id');
            })
            ->select('staff.id', 'staff.user_id', 'staff.restaurant_id', 'staff.role')
            ->limit(500)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
