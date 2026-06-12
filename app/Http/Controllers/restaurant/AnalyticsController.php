<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Rating;
use App\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.dashboard');
        }

        $period = (int) $request->get('period', 30);
        $period = in_array($period, [7, 30, 90]) ? $period : 30;
        $since  = now()->subDays($period)->startOfDay();
        $restId = $restaurant->id;

        // ── KPIs ────────────────────────────────────────────────────────────
        $completedStatuses = ['completed', 'delivered'];

        $allOrders = DB::table('orders')
            ->where('restaurant_id', $restId)
            ->where('created_at', '>=', $since)
            ->select('order_no', 'status', 'business_status', 'total', 'sub_total', 'payment_method')
            ->get();

        $distinctOrders = $allOrders->groupBy('order_no')->map(fn($rows) => $rows->first());

        $totalOrders     = $distinctOrders->count();
        $completedOrders = $distinctOrders->filter(fn($o) =>
            in_array($o->status, $completedStatuses) || in_array($o->business_status, $completedStatuses)
        );
        $completedCount  = $completedOrders->count();
        $cancelledCount  = $distinctOrders->filter(fn($o) =>
            $o->status === 'cancelled' || $o->business_status === 'cancelled'
        )->count();

        $revenue      = DB::table('orders')
            ->where('restaurant_id', $restId)
            ->where('created_at', '>=', $since)
            ->whereIn('status', $completedStatuses)
            ->sum('sub_total');

        $avgBasket    = $completedCount > 0
            ? DB::table('orders')
                ->where('restaurant_id', $restId)
                ->where('created_at', '>=', $since)
                ->whereIn('status', $completedStatuses)
                ->select('order_no', DB::raw('max(total) as t'))
                ->groupBy('order_no')
                ->get()->avg('t')
            : 0;

        $completionRate = $totalOrders > 0 ? round(($completedCount / $totalOrders) * 100) : 0;

        // ── Top produits ─────────────────────────────────────────────────────
        $topProducts = DB::table('orders')
            ->join('products', 'products.id', '=', 'orders.product_id')
            ->where('orders.restaurant_id', $restId)
            ->where('orders.created_at', '>=', $since)
            ->select(
                'products.id',
                'products.name',
                'products.image',
                DB::raw('SUM(orders.qty) as total_qty'),
                DB::raw('SUM(orders.sub_total) as revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // ── Heatmap heures de pointe ────────────────────────────────────────
        $hourlyRaw = DB::table('orders')
            ->where('restaurant_id', $restId)
            ->where('created_at', '>=', $since)
            ->select(
                DB::raw('HOUR(COALESCE(ordered_time, created_at)) as hr'),
                DB::raw('COUNT(DISTINCT order_no) as cnt')
            )
            ->groupBy('hr')
            ->orderBy('hr')
            ->get()
            ->keyBy('hr');

        $hourly = [];
        for ($h = 0; $h < 24; $h++) {
            $hourly[$h] = (int) ($hourlyRaw->get($h)?->cnt ?? 0);
        }
        $maxHourly = max(array_merge($hourly, [1]));

        // ── CA journalier ────────────────────────────────────────────────────
        $dailyRaw = DB::table('orders')
            ->where('restaurant_id', $restId)
            ->where('created_at', '>=', $since)
            ->whereIn('status', $completedStatuses)
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('SUM(sub_total) as revenue')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $daily = [];
        for ($i = $period - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $daily[$day] = (float) ($dailyRaw->get($day)?->revenue ?? 0);
        }
        $maxDaily = max(array_merge(array_values($daily), [1]));

        // ── Répartition paiements ────────────────────────────────────────────
        $paymentMethods = DB::table('orders')
            ->where('restaurant_id', $restId)
            ->where('created_at', '>=', $since)
            ->whereIn('status', $completedStatuses)
            ->select('payment_method', DB::raw('COUNT(DISTINCT order_no) as cnt'))
            ->groupBy('payment_method')
            ->get();

        $totalPayments = max($paymentMethods->sum('cnt'), 1);

        // ── Note moyenne ─────────────────────────────────────────────────────
        $ratingStats = Rating::where('restaurant_id', $restId)
            ->selectRaw('AVG(rating) as avg, COUNT(*) as total')
            ->first();

        return view('restaurant.analytics.index', compact(
            'restaurant', 'period',
            'totalOrders', 'completedCount', 'cancelledCount', 'completionRate',
            'revenue', 'avgBasket',
            'topProducts', 'maxHourly', 'hourly',
            'daily', 'maxDaily',
            'paymentMethods', 'totalPayments',
            'ratingStats'
        ));
    }
}
