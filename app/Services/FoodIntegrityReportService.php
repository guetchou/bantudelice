<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FoodIntegrityReportService
{
    public function report(): array
    {
        $checks = [
            'duplicate_payments' => $this->duplicatePayments(),
            'duplicate_deliveries' => $this->duplicateDeliveries(),
            'inconsistent_order_groups' => $this->inconsistentOrderGroups(),
            'cash_paid_without_collection' => $this->cashPaidWithoutCollection(),
            'delivery_order_status_mismatches' => $this->deliveryOrderStatusMismatches(),
            'scheduled_orders_without_date' => $this->scheduledOrdersWithoutDate(),
            'orphan_restaurant_staff' => $this->orphanRestaurantStaff(),
        ];

        $total = collect($checks)->sum(fn (array $rows) => count($rows));
        $violations = collect($checks)
            ->flatMap(fn (array $rows, string $check) => collect($rows)->map(function (array $row) use ($check) {
                return ['type' => $check] + $row;
            }))
            ->values()
            ->all();

        return [
            'generated_at' => now()->toIso8601String(),
            'status' => $total === 0 ? 'clean' : 'violations_detected',
            'violations_count' => $total,
            'violations' => $violations,
            'checks' => $checks,
        ];
    }

    public function isClean(): bool
    {
        return $this->report()['violations_count'] === 0;
    }

    public function duplicatePayments(bool $includeSoftDeleted = true): array
    {
        if (! Schema::hasTable('payments')
            || ! Schema::hasColumn('payments', 'order_id')
            || ! Schema::hasColumn('payments', 'provider')) {
            return [];
        }

        $query = DB::table('payments')
            ->select('order_id', 'provider', DB::raw('COUNT(*) as occurrences'))
            ->whereNotNull('order_id');

        if (! $includeSoftDeleted && Schema::hasColumn('payments', 'deleted_at')) {
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

    public function duplicateDeliveries(): array
    {
        if (! Schema::hasTable('deliveries') || ! Schema::hasColumn('deliveries', 'order_id')) {
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

    public function inconsistentOrderGroups(): array
    {
        if (! Schema::hasTable('orders')) {
            return [];
        }

        foreach (['order_no', 'restaurant_id', 'user_id', 'total', 'payment_method'] as $column) {
            if (! Schema::hasColumn('orders', $column)) {
                return [];
            }
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

    public function cashPaidWithoutCollection(): array
    {
        if (! Schema::hasTable('orders')) {
            return [];
        }

        foreach ([
            'payment_method',
            'payment_status',
            'cash_collection_confirmed_at',
            'business_status',
            'order_no',
        ] as $column) {
            if (! Schema::hasColumn('orders', $column)) {
                return [];
            }
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

    public function scheduledOrdersWithoutDate(): array
    {
        if (! Schema::hasTable('orders')
            || ! Schema::hasColumn('orders', 'scheduled_date')
            || ! Schema::hasColumn('orders', 'business_status')
            || ! Schema::hasColumn('orders', 'order_no')) {
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

    public function deliveryOrderStatusMismatches(): array
    {
        if (! Schema::hasTable('deliveries') || ! Schema::hasTable('orders')) {
            return [];
        }

        foreach (['order_id', 'status'] as $column) {
            if (! Schema::hasColumn('deliveries', $column)) {
                return [];
            }
        }

        foreach (['id', 'order_no', 'business_status'] as $column) {
            if (! Schema::hasColumn('orders', $column)) {
                return [];
            }
        }

        $expected = [
            'PENDING' => ['ready_for_pickup', 'dispatching'],
            'ASSIGNED' => ['driver_assigned'],
            'PICKED_UP' => ['picked_up'],
            'ON_THE_WAY' => ['out_for_delivery'],
            'DELIVERED' => ['delivered', 'closed'],
        ];

        return DB::table('deliveries')
            ->join('orders', 'orders.id', '=', 'deliveries.order_id')
            ->select(
                'deliveries.id as delivery_id',
                'deliveries.order_id',
                'orders.order_no',
                'deliveries.status as delivery_status',
                'orders.business_status'
            )
            ->whereIn('deliveries.status', array_keys($expected))
            ->get()
            ->filter(function ($row) use ($expected) {
                return ! in_array((string) $row->business_status, $expected[(string) $row->delivery_status] ?? [], true);
            })
            ->map(function ($row) use ($expected) {
                $payload = (array) $row;
                $payload['expected_business_status'] = $expected[(string) $row->delivery_status] ?? [];

                return $payload;
            })
            ->values()
            ->all();
    }

    public function orphanRestaurantStaff(): array
    {
        if (! Schema::hasTable('restaurant_staff_members')
            || ! Schema::hasTable('users')
            || ! Schema::hasTable('restaurants')) {
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
