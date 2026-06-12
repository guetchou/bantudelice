<?php

namespace App\Services;

use App\CompletedOrder;
use App\Order;
use App\Restaurant;
use Illuminate\Support\Facades\DB;

class RestaurantDashboardDataService
{
    public function build(Restaurant $restaurant): array
    {
        $restaurantId = $restaurant->id;
        $financialDashboard = app(PartnerFinancialDashboardService::class)->forRestaurant($restaurant);
        $monthLabelExpression = $this->monthLabelExpression('created_at');
        $yearExpression = $this->yearExpression('created_at');

        $orderGroups = Order::where('restaurant_id', $restaurantId)
            ->select('order_no', 'status', 'business_status', 'created_at')
            ->orderByDesc('id')
            ->get()
            ->unique('order_no');

        $trackedOrdersCount = $orderGroups->pluck('order_no')
            ->merge(
                DB::table('completed_orders')
                    ->where('restaurant_id', $restaurantId)
                    ->pluck('order_no')
            )
            ->filter()
            ->unique()
            ->count();
        $categories = DB::table('categories')->where('restaurant_id', $restaurantId)->get();
        $subscriptions = $orderGroups->filter(function ($order) {
            $business = $order->business_status ?: $order->status;

            return in_array($business, ['ready_for_pickup', 'dispatching', 'driver_assigned', 'driver_arrived_at_restaurant', 'assign'], true);
        });
        $products = DB::table('products')->where('restaurant_id', $restaurantId)->get();
        $scheduleOrders = Order::where('restaurant_id', $restaurantId)
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>=', now())
            ->select('order_no')
            ->distinct()
            ->count('order_no');

        $orders = CompletedOrder::select(
                DB::raw("(COUNT(*)) as count"),
                DB::raw('SUM(total) as totals'),
                DB::raw($monthLabelExpression . ' as monthname')
            )
            ->where('restaurant_id', $restaurantId)
            ->whereYear('created_at', date('Y'))
            ->groupBy('monthname')
            ->get();

        $OrdersByYear = CompletedOrder::select(
                DB::raw("(COUNT(*)) as count"),
                DB::raw('SUM(total) as totals'),
                DB::raw($yearExpression . ' as year')
            )
            ->where('restaurant_id', $restaurantId)
            ->groupBy('year')
            ->get();

        $getPendings = $orderGroups->filter(function ($order) {
            $business = $order->business_status ?: $order->status;

            return in_array($business, ['pending_restaurant_acceptance', 'accepted', 'pending'], true);
        })->count();

        $getComleted = DB::table('completed_orders')
            ->where('status', 'completed')
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->unique('order_no')
            ->count();

        $getCancel = DB::table('completed_orders')
            ->where('status', 'cancelled')
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->unique('order_no')
            ->count();

        $statusBreakdownTotal = $getPendings + $getComleted + $getCancel;

        if ($statusBreakdownTotal > 0) {
            $getPendingAvg = intval($getPendings / $statusBreakdownTotal * 100);
            $getCompletedAvg = intval($getComleted / $statusBreakdownTotal * 100);
            $getCanceledAvg = intval($getCancel / $statusBreakdownTotal * 100);
        } else {
            $getPendingAvg = 0;
            $getCompletedAvg = 0;
            $getCanceledAvg = 0;
        }

        $months = $orders->pluck('monthname')->toArray();
        $monthstring = "'" . implode("', '", $months) . "'";
        $counts = $orders->pluck('count')->toArray();
        $count = implode(', ', $counts);
        $totals = $orders->pluck('totals')->toArray();
        $total = implode(', ', $totals);

        $years = $OrdersByYear->pluck('year')->toArray();
        $yearstring = "'" . implode("', '", $years) . "'";
        $totalsByYear = $OrdersByYear->pluck('totals')->toArray();
        $totalYearBy = implode(', ', $totalsByYear);
        $financeSummary = collect($financialDashboard['cards'] ?? [])->mapWithKeys(function ($card) {
            return [strtolower(str_replace([' ', '’', "'"], ['_', '', ''], $card['label'])) => (float) ($card['amount'] ?? 0)];
        })->all();

        return compact(
            'restaurant',
            'trackedOrdersCount',
            'categories',
            'subscriptions',
            'products',
            'scheduleOrders',
            'yearstring',
            'totalYearBy',
            'monthstring',
            'count',
            'total',
            'getPendingAvg',
            'getCompletedAvg',
            'getCanceledAvg',
            'getPendings',
            'getComleted',
            'financeSummary',
            'financialDashboard'
        );
    }

    private function monthLabelExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CASE strftime('%m', {$column})
                WHEN '01' THEN 'January'
                WHEN '02' THEN 'February'
                WHEN '03' THEN 'March'
                WHEN '04' THEN 'April'
                WHEN '05' THEN 'May'
                WHEN '06' THEN 'June'
                WHEN '07' THEN 'July'
                WHEN '08' THEN 'August'
                WHEN '09' THEN 'September'
                WHEN '10' THEN 'October'
                WHEN '11' THEN 'November'
                WHEN '12' THEN 'December'
            END";
        }

        return "MONTHNAME({$column})";
    }

    private function yearExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "strftime('%Y', {$column})";
        }

        return "YEAR({$column})";
    }
}
