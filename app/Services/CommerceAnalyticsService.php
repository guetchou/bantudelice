<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommerceAnalyticsService
{
    public function overview(int $days = null): array
    {
        $days = $days ?? (int) config('commerce.analytics.window_days', 7);
        $since = now()->subDays(max(1, $days));

        $ordersTable = Schema::hasTable('orders');
        $deliveriesTable = Schema::hasTable('deliveries');
        $paymentsTable = Schema::hasTable('payments');
        $signalsTable = Schema::hasTable('commerce_signals');
        $ticketsTable = Schema::hasTable('support_tickets');
        $ledgerTable = Schema::hasTable('financial_ledger_entries');
        $riskTable = Schema::hasTable('risk_assessments');

        $orders = $ordersTable ? DB::table('orders')->where('created_at', '>=', $since) : collect();
        $deliveries = $deliveriesTable ? DB::table('deliveries')->where('created_at', '>=', $since) : collect();
        $payments = $paymentsTable ? DB::table('payments')->where('created_at', '>=', $since) : collect();
        $signals = $signalsTable ? DB::table('commerce_signals')->where('created_at', '>=', $since) : collect();
        $tickets = $ticketsTable ? DB::table('support_tickets')->where('created_at', '>=', $since) : collect();
        $ledger = $ledgerTable ? DB::table('financial_ledger_entries')->where('created_at', '>=', $since) : collect();

        return [
            'window_days' => $days,
            'sla' => $this->slaSnapshot(),
            'orders' => [
                'count' => $ordersTable ? DB::table('orders')->where('created_at', '>=', $since)->distinct('order_no')->count('order_no') : 0,
                'completed' => $ordersTable ? DB::table('orders')->where('created_at', '>=', $since)->where(function ($q) {
                    $q->where('business_status', 'delivered')->orWhere('status', 'completed');
                })->distinct('order_no')->count('order_no') : 0,
                'cancelled' => $ordersTable ? DB::table('orders')->where('created_at', '>=', $since)->where(function ($q) {
                    $q->where('business_status', 'cancelled')->orWhere('status', 'cancelled');
                })->distinct('order_no')->count('order_no') : 0,
                'scheduled' => $ordersTable ? DB::table('orders')->where('created_at', '>=', $since)->where(function ($q) {
                    $q->where('status', 'scheduled')->orWhereNotNull('scheduled_date');
                })->distinct('order_no')->count('order_no') : 0,
                'average_value' => $ordersTable ? round((float) DB::table('orders')
                    ->select('order_no', DB::raw('max(total) as total'))
                    ->where('created_at', '>=', $since)
                    ->groupBy('order_no')
                    ->get()
                    ->avg('total'), 2) : 0,
                'unique_customers' => $ordersTable ? DB::table('orders')->where('created_at', '>=', $since)->distinct('user_id')->count('user_id') : 0,
            ],
            'deliveries' => [
                'count' => $deliveriesTable ? DB::table('deliveries')->where('created_at', '>=', $since)->count() : 0,
                'open_incidents' => $deliveriesTable ? DB::table('deliveries')->where('incident_status', 'open')->count() : 0,
                'resolved_incidents' => $deliveriesTable ? DB::table('deliveries')->where('incident_status', 'resolved')->count() : 0,
                'on_the_way' => $deliveriesTable ? DB::table('deliveries')->where('status', 'ON_THE_WAY')->count() : 0,
                'average_completion_minutes' => $deliveriesTable ? $this->averageCompletionMinutes($since) : 0,
            ],
            'payments' => [
                'count' => $paymentsTable ? DB::table('payments')->where('created_at', '>=', $since)->count() : 0,
                'paid' => $paymentsTable ? DB::table('payments')->where('status', 'PAID')->count() : 0,
                'pending' => $paymentsTable ? DB::table('payments')->where('status', 'PENDING')->count() : 0,
                'failed' => $paymentsTable ? DB::table('payments')->where('status', 'FAILED')->count() : 0,
                'refunded' => $paymentsTable ? DB::table('payments')->where('status', 'REFUNDED')->count() : 0,
                'cash_ratio' => $paymentsTable ? $this->paymentRatio('cash', $since) : 0,
                'momo_ratio' => $paymentsTable ? $this->paymentRatio('momo', $since) : 0,
                'paypal_ratio' => $paymentsTable ? $this->paymentRatio('paypal', $since) : 0,
            ],
            'tickets' => [
                'count' => $ticketsTable ? DB::table('support_tickets')->where('created_at', '>=', $since)->count() : 0,
                'open' => $ticketsTable ? DB::table('support_tickets')->whereIn('status', ['open', 'pending_review', 'pending_refund', 'pending_redelivery'])->count() : 0,
                'resolved' => $ticketsTable ? DB::table('support_tickets')->whereIn('status', ['resolved', 'closed'])->count() : 0,
                'high_priority' => $ticketsTable ? DB::table('support_tickets')->where('priority', 'high')->count() : 0,
            ],
            'signals' => [
                'count' => $signalsTable ? DB::table('commerce_signals')->where('created_at', '>=', $since)->count() : 0,
                'order_created' => $signalsTable ? DB::table('commerce_signals')->where('signal_type', 'order.created')->where('created_at', '>=', $since)->count() : 0,
                'refunds' => $signalsTable ? DB::table('commerce_signals')->where('signal_type', 'like', 'payment.refund%')->where('created_at', '>=', $since)->count() : 0,
                'chat_messages' => $signalsTable ? DB::table('commerce_signals')->where('signal_type', 'order_chat.message_sent')->where('created_at', '>=', $since)->count() : 0,
            ],
            'risk' => [
                'count' => $riskTable ? DB::table('risk_assessments')->where('created_at', '>=', $since)->count() : 0,
                'low' => $riskTable ? DB::table('risk_assessments')->where('created_at', '>=', $since)->where('level', 'low')->count() : 0,
                'medium' => $riskTable ? DB::table('risk_assessments')->where('created_at', '>=', $since)->where('level', 'medium')->count() : 0,
                'high' => $riskTable ? DB::table('risk_assessments')->where('created_at', '>=', $since)->where('level', 'high')->count() : 0,
                'critical' => $riskTable ? DB::table('risk_assessments')->where('created_at', '>=', $since)->where('level', 'critical')->count() : 0,
                'top_orders' => $riskTable ? DB::table('risk_assessments')
                    ->select('order_id', 'level', DB::raw('max(score) as max_score'), DB::raw('count(*) as total'))
                    ->where('created_at', '>=', $since)
                    ->groupBy('order_id', 'level')
                    ->orderByDesc('max_score')
                    ->limit((int) config('commerce.analytics.top_limit', 10))
                    ->get() : collect(),
                'recent' => $riskTable ? DB::table('risk_assessments')
                    ->orderByDesc('id')
                    ->limit((int) config('commerce.analytics.top_limit', 10))
                    ->get() : collect(),
            ],
            'ledger' => [
                'count' => $ledgerTable ? DB::table('financial_ledger_entries')->where('created_at', '>=', $since)->count() : 0,
                'refunds' => $ledgerTable ? DB::table('financial_ledger_entries')->where('entry_type', 'refund')->where('created_at', '>=', $since)->count() : 0,
                'captures' => $ledgerTable ? DB::table('financial_ledger_entries')->where('entry_type', 'capture')->where('created_at', '>=', $since)->count() : 0,
                'releases' => $ledgerTable ? DB::table('financial_ledger_entries')->where('entry_type', 'release')->where('created_at', '>=', $since)->count() : 0,
            ],
            'top_support_categories' => $ticketsTable
                ? DB::table('support_tickets')
                    ->select('category', DB::raw('count(*) as total'))
                    ->groupBy('category')
                    ->orderByDesc('total')
                    ->limit((int) config('commerce.analytics.top_limit', 10))
                    ->get()
                : collect(),
            'top_restaurants' => $ordersTable
                ? DB::table('orders')
                    ->select('restaurant_id', DB::raw('count(distinct order_no) as total'), DB::raw('sum(sub_total) as gross_total'))
                    ->where('created_at', '>=', $since)
                    ->groupBy('restaurant_id')
                    ->orderByDesc('total')
                    ->limit((int) config('commerce.analytics.top_limit', 10))
                    ->get()
                : collect(),
            'top_products' => $ordersTable
                ? DB::table('orders')
                    ->select('product_id', DB::raw('count(*) as total'), DB::raw('sum(qty) as units'))
                    ->where('created_at', '>=', $since)
                    ->groupBy('product_id')
                    ->orderByDesc('units')
                    ->limit((int) config('commerce.analytics.top_limit', 10))
                    ->get()
                : collect(),
            'top_signal_types' => $signalsTable
                ? DB::table('commerce_signals')
                    ->select('signal_type', DB::raw('count(*) as total'))
                    ->where('created_at', '>=', $since)
                    ->groupBy('signal_type')
                    ->orderByDesc('total')
                    ->limit((int) config('commerce.analytics.top_limit', 10))
                    ->get()
                : collect(),
            'recent_support_tickets' => $ticketsTable
                ? DB::table('support_tickets')
                    ->orderByDesc('last_activity_at')
                    ->orderByDesc('id')
                    ->limit((int) config('commerce.analytics.top_limit', 10))
                    ->get()
                : collect(),
        ];
    }

    public function slaSnapshot(): array
    {
        return [
            'restaurant_accept_minutes' => (int) data_get(config('commerce.analytics.slas', []), 'restaurant_accept_minutes', 3),
            'delivery_assign_minutes' => (int) data_get(config('commerce.analytics.slas', []), 'delivery_assign_minutes', 2),
            'delivery_complete_minutes' => (int) data_get(config('commerce.analytics.slas', []), 'delivery_complete_minutes', 45),
            'restaurant_accept_overdue' => $this->restaurantAcceptOverdueCount(),
            'delivery_assign_overdue' => $this->deliveryAssignOverdueCount(),
            'delivery_complete_overdue' => $this->deliveryCompleteOverdueCount(),
        ];
    }

    protected function restaurantAcceptOverdueCount(): int
    {
        if (!Schema::hasTable('orders')) {
            return 0;
        }

        $minutes = max(1, (int) data_get(config('commerce.analytics.slas', []), 'restaurant_accept_minutes', 3));

        return DB::table('orders')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->where(function ($q) {
                $q->whereNull('business_status')
                    ->orWhereIn('business_status', ['pending_restaurant_acceptance', 'accepted']);
            })
            ->distinct('order_no')
            ->count('order_no');
    }

    protected function deliveryAssignOverdueCount(): int
    {
        if (!Schema::hasTable('deliveries')) {
            return 0;
        }

        $minutes = max(1, (int) data_get(config('commerce.analytics.slas', []), 'delivery_assign_minutes', 2));

        return DB::table('deliveries')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->whereIn('status', ['PENDING', 'ASSIGNED'])
            ->count();
    }

    protected function deliveryCompleteOverdueCount(): int
    {
        if (!Schema::hasTable('deliveries')) {
            return 0;
        }

        $minutes = max(1, (int) data_get(config('commerce.analytics.slas', []), 'delivery_complete_minutes', 45));

        return DB::table('deliveries')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->whereIn('status', ['PICKED_UP', 'ON_THE_WAY', 'ASSIGNED'])
            ->count();
    }

    protected function averageCompletionMinutes($since): float
    {
        if (!Schema::hasTable('deliveries')) {
            return 0.0;
        }

        $deliveries = DB::table('deliveries')
            ->where('created_at', '>=', $since)
            ->whereNotNull('delivered_at')
            ->get(['created_at', 'delivered_at']);

        if ($deliveries->isEmpty()) {
            return 0.0;
        }

        return round($deliveries->avg(function ($delivery) {
            return \Carbon\Carbon::parse($delivery->created_at)->diffInMinutes(\Carbon\Carbon::parse($delivery->delivered_at));
        }), 2);
    }

    protected function paymentRatio(string $method, $since): float
    {
        if (!Schema::hasTable('payments')) {
            return 0.0;
        }

        $total = DB::table('payments')->where('created_at', '>=', $since)->count();
        if ($total <= 0) {
            return 0.0;
        }

        return round(DB::table('payments')->where('created_at', '>=', $since)->where('provider', $method)->count() / $total, 2);
    }
}
