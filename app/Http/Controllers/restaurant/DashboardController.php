<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (! $user->restaurant) {
            return redirect()->route('login')->with('error', 'Aucun restaurant associé à votre compte.');
        }

        $restaurant = $user->restaurant;
        $now        = now();

        $todayStart = $now->copy()->startOfDay();
        $todayEnd   = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();

        $stats = $this->buildStats(
            restaurant: $restaurant,
            todayStart: $todayStart,
            todayEnd:   $todayEnd,
            monthStart: $monthStart,
            monthEnd:   $monthEnd,
        );

        return view('restaurant.dashboard', [
            'restaurant'       => $this->buildRestaurantPayload($restaurant),
            'kpis'             => $stats['kpis'],
            'pipeline'         => $stats['pipeline'],
            'quickActions'     => $this->buildQuickActions(),
            'performanceCards' => $stats['performance_cards'],
            'recentOrders'     => $stats['recent_orders'],
            'salesSeries'      => $stats['sales_series'],
            'salesLabels'      => $stats['sales_labels'],
            'topDishes'        => $this->buildTopDishes($restaurant, $todayStart, $todayEnd),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Restaurant payload
    // ──────────────────────────────────────────────────────────────

    private function buildRestaurantPayload(mixed $restaurant): array
    {
        if (! $restaurant) {
            return ['name' => 'Restaurant', 'status' => 'Actif', 'phone' => null, 'city' => null, 'logo_url' => null];
        }

        $logo = data_get($restaurant, 'logo_url')
             ?? data_get($restaurant, 'logo')
             ?? data_get($restaurant, 'media.logo_url');

        if ($logo && ! str_starts_with($logo, 'http')) {
            $logo = asset('images/restaurant_images/' . $logo);
        }

        return [
            'name'     => data_get($restaurant, 'name') ?? data_get($restaurant, 'restaurant_name') ?? 'Restaurant',
            'status'   => (string) (data_get($restaurant, 'status') ?? 'Actif'),
            'phone'    => data_get($restaurant, 'phone') ?? data_get($restaurant, 'phone_number'),
            'city'     => data_get($restaurant, 'city') ?? data_get($restaurant, 'address_city'),
            'logo_url' => $logo,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Quick actions
    // ──────────────────────────────────────────────────────────────

    private function buildQuickActions(): array
    {
        return [
            [
                'label' => 'Ajouter un produit',
                'route' => $this->safeRoute('restaurant.products.create', [], $this->safeRoute('product.index')),
            ],
            [
                'label' => 'Voir les commandes',
                'route' => $this->safeRoute('restaurant.orders.index', [], $this->safeRoute('restaurant.all_orders')),
            ],
            [
                'label' => 'Historique paiements',
                'route' => $this->safeRoute('restaurant.payments.index', [], $this->safeRoute('restaurant.payment_history')),
            ],
            [
                'label' => 'Gérer le catalogue',
                'route' => $this->safeRoute('restaurant.products.index', [], $this->safeRoute('product.index')),
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Stats aggregation
    // ──────────────────────────────────────────────────────────────

    private function buildStats(
        mixed  $restaurant,
        Carbon $todayStart,
        Carbon $todayEnd,
        Carbon $monthStart,
        Carbon $monthEnd,
    ): array {
        $orders      = $this->fetchOrders($restaurant);
        $products    = $this->fetchProducts($restaurant);
        $categories  = $this->fetchCategories($restaurant);
        $settlements = $this->fetchSettlements($restaurant);

        $todayOrders = $orders->filter(
            fn (array $o) => $this->dateBetween(data_get($o, 'created_at'), $todayStart, $todayEnd)
        );
        $monthOrders = $orders->filter(
            fn (array $o) => $this->dateBetween(data_get($o, 'created_at'), $monthStart, $monthEnd)
        );

        $grossToday    = $todayOrders->sum(fn ($o) => (float) data_get($o, 'gross_amount', 0));
        $ordersToday   = $todayOrders->count();
        $averageTicket = $ordersToday > 0 ? $grossToday / $ordersToday : 0;

        $grossMonth      = $monthOrders->sum(fn ($o) => (float) data_get($o, 'gross_amount', 0));
        $commissionMonth = $monthOrders->sum(fn ($o) => (float) data_get($o, 'commission_amount', 0));
        $netPartnerMonth = max(0, $grossMonth - $commissionMonth);

        $alreadyPaid = $settlements
            ->where('status', 'paid')
            ->sum(fn ($s) => (float) data_get($s, 'amount', 0));

        $pendingSettlement = $settlements
            ->whereIn('status', ['pending', 'held', 'processing'])
            ->sum(fn ($s) => (float) data_get($s, 'amount', 0));

        $availableWithdrawal = max(0, $netPartnerMonth - $alreadyPaid - $pendingSettlement);

        $newCount        = $todayOrders->whereIn('status', ['new', 'pending', 'received'])->count();
        $preparingCount  = $todayOrders->whereIn('status', ['preparing', 'accepted', 'confirmed', 'cooking'])->count();
        $deliveringCount = $todayOrders->whereIn('status', ['assigned', 'picked_up', 'delivering', 'on_the_way'])->count();
        $completedCount  = $todayOrders->whereIn('status', ['completed', 'delivered'])->count();

        $acceptedOrders = $monthOrders->whereNotIn('status', ['new', 'pending', 'cancelled', 'rejected'])->count();
        $receivedOrders = max(1, $monthOrders->count());
        $acceptanceRate = round(($acceptedOrders / $receivedOrders) * 100);

        return [
            'kpis' => [
                'gross_today'          => round($grossToday),
                'orders_today'         => $ordersToday,
                'average_ticket'       => round($averageTicket),
                'available_withdrawal' => round($availableWithdrawal),
                'gross_month'          => round($grossMonth),
                'commission_month'     => round($commissionMonth),
                'net_partner_month'    => round($netPartnerMonth),
                'pending_settlement'   => round($pendingSettlement),
            ],
            'pipeline' => [
                [
                    'label' => 'Nouvelles',
                    'value' => $newCount,
                    'hint'  => 'À traiter rapidement',
                    'route' => $this->safeRoute('restaurant.all_orders'),
                ],
                [
                    'label' => 'En préparation',
                    'value' => $preparingCount,
                    'hint'  => 'Cuisine en cours',
                    'route' => $this->safeRoute('restaurant.all_orders'),
                ],
                [
                    'label' => 'En livraison',
                    'value' => $deliveringCount,
                    'hint'  => 'Courses en cours',
                    'route' => $this->safeRoute('restaurant.pending_orders'),
                ],
                [
                    'label' => 'Terminées',
                    'value' => $completedCount,
                    'hint'  => "Clôturées aujourd'hui",
                    'route' => $this->safeRoute('restaurant.complete_orders'),
                ],
            ],
            'performance_cards' => [
                [
                    'label' => 'Produits actifs',
                    'value' => $products->where('is_active', true)->count(),
                    'hint'  => 'Disponibles à la vente',
                    'route' => $this->safeRoute('product.index'),
                ],
                [
                    'label' => 'Catégories',
                    'value' => $categories->count(),
                    'hint'  => 'Structuration du menu',
                    'route' => $this->safeRoute('category.index'),
                ],
                [
                    'label' => 'Programmées',
                    'value' => $orders->where('is_scheduled', true)->count(),
                    'hint'  => 'Commandes planifiées',
                    'route' => $this->safeRoute('restaurant.schedule_orders'),
                ],
                [
                    'label' => "Taux d'acceptation",
                    'value' => $acceptanceRate . '%',
                    'hint'  => 'Sur les commandes du mois',
                    'route' => null,
                ],
            ],
            'recent_orders' => $orders
                ->sortByDesc('created_at')
                ->take(8)
                ->map(fn (array $o) => [
                    'ref'      => (string) data_get($o, 'reference', '—'),
                    'customer' => (string) data_get($o, 'customer_name', 'Client'),
                    'amount'   => (float) data_get($o, 'gross_amount', 0),
                    'status'   => $this->mapOrderStatusLabel((string) data_get($o, 'status', 'new')),
                    'time'     => $this->formatOrderTime(data_get($o, 'created_at')),
                ])
                ->values()
                ->all(),
            'sales_series' => $this->buildWeeklySalesSeries($orders, now()),
            'sales_labels' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Chart helper
    // ──────────────────────────────────────────────────────────────

    private function buildWeeklySalesSeries(Collection $orders, Carbon $referenceDate): array
    {
        $startOfWeek = $referenceDate->copy()->startOfWeek(Carbon::MONDAY);
        $series = [];

        for ($i = 0; $i < 7; $i++) {
            $dayStart = $startOfWeek->copy()->addDays($i)->startOfDay();
            $dayEnd   = $startOfWeek->copy()->addDays($i)->endOfDay();

            $series[] = (int) round(
                $orders
                    ->filter(fn (array $o) => $this->dateBetween(data_get($o, 'created_at'), $dayStart, $dayEnd))
                    ->sum(fn (array $o) => (float) data_get($o, 'gross_amount', 0))
            );
        }

        return $series;
    }

    // ──────────────────────────────────────────────────────────────
    // Formatters
    // ──────────────────────────────────────────────────────────────

    private function mapOrderStatusLabel(string $status): string
    {
        return match ($status) {
            'new', 'pending', 'received'                         => 'Nouvelle',
            'accepted', 'confirmed', 'preparing', 'cooking'      => 'Préparation',
            'assigned', 'picked_up', 'delivering', 'on_the_way' => 'Livraison',
            'completed', 'delivered'                             => 'Terminée',
            'cancelled', 'rejected'                              => 'Annulée',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function formatOrderTime(mixed $date): string
    {
        if (! $date) {
            return '—:—';
        }
        try {
            return Carbon::parse($date)->format('H:i');
        } catch (\Throwable) {
            return '—:—';
        }
    }

    private function dateBetween(mixed $date, Carbon $start, Carbon $end): bool
    {
        if (! $date) {
            return false;
        }
        try {
            return Carbon::parse($date)->betweenIncluded($start, $end);
        } catch (\Throwable) {
            return false;
        }
    }

    private function safeRoute(string $name, array $parameters = [], string $fallback = '#'): string
    {
        return app('router')->has($name) ? route($name, $parameters) : $fallback;
    }

    // ──────────────────────────────────────────────────────────────
    // Data fetchers — Eloquent-safe avec fallbacks DB
    // ──────────────────────────────────────────────────────────────

    private function fetchOrders(mixed $restaurant): Collection
    {
        if (! $restaurant) {
            return collect();
        }

        if (method_exists($restaurant, 'orders')) {
            try {
                return $restaurant->orders()
                    ->latest()
                    ->get()
                    ->map(fn ($order) => [
                        'reference'         => $order->reference ?? $order->code ?? ('#CMD-' . $order->id),
                        'customer_name'     => $order->customer_name ?? $order->customer?->name ?? $order->user?->name ?? 'Client',
                        'gross_amount'      => (float) ($order->gross_amount ?? $order->total_amount ?? $order->total ?? 0),
                        'commission_amount' => (float) ($order->commission_amount ?? $order->platform_fee ?? 0),
                        'status'            => (string) ($order->status ?? 'new'),
                        'is_scheduled'      => (bool) ($order->is_scheduled ?? isset($order->scheduled_date)),
                        'created_at'        => $order->created_at,
                    ]);
            } catch (\Throwable) {
                // fall through
            }
        }

        try {
            return Order::where('restaurant_id', $restaurant->id)
                ->latest()
                ->get()
                ->map(fn ($order) => [
                    'reference'         => $order->reference ?? $order->order_no ?? ('#CMD-' . $order->id),
                    'customer_name'     => $order->customer_name ?? 'Client',
                    'gross_amount'      => (float) ($order->total ?? 0),
                    'commission_amount' => 0.0,
                    'status'            => (string) ($order->status ?? 'new'),
                    'is_scheduled'      => isset($order->scheduled_date),
                    'created_at'        => $order->created_at,
                ]);
        } catch (\Throwable) {
            return collect();
        }
    }

    private function fetchProducts(mixed $restaurant): Collection
    {
        if (! $restaurant) {
            return collect();
        }

        if (method_exists($restaurant, 'products')) {
            try {
                return $restaurant->products()
                    ->get()
                    ->map(fn ($p) => ['is_active' => (bool) ($p->is_active ?? $p->active ?? true)]);
            } catch (\Throwable) {
                // fall through
            }
        }

        try {
            return \Illuminate\Support\Facades\DB::table('products')
                ->where('restaurant_id', $restaurant->id)
                ->get()
                ->map(fn ($p) => ['is_active' => (bool) ($p->is_active ?? 1)]);
        } catch (\Throwable) {
            return collect();
        }
    }

    private function fetchCategories(mixed $restaurant): Collection
    {
        if (! $restaurant) {
            return collect();
        }

        if (method_exists($restaurant, 'categories')) {
            try {
                return $restaurant->categories()->get()->map(fn ($c) => ['id' => $c->id]);
            } catch (\Throwable) {
                // fall through
            }
        }

        try {
            return \Illuminate\Support\Facades\DB::table('categories')
                ->where('restaurant_id', $restaurant->id)
                ->get()
                ->map(fn ($c) => ['id' => $c->id]);
        } catch (\Throwable) {
            return collect();
        }
    }

    private function fetchSettlements(mixed $restaurant): Collection
    {
        if (! $restaurant) {
            return collect();
        }

        foreach (['settlements', 'payouts', 'withdrawals'] as $relation) {
            if (method_exists($restaurant, $relation)) {
                try {
                    return $restaurant->{$relation}()
                        ->latest()
                        ->get()
                        ->map(fn ($s) => [
                            'amount' => (float) ($s->amount ?? 0),
                            'status' => (string) ($s->status ?? 'pending'),
                        ]);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return collect();
    }

    // ──────────────────────────────────────────────────────────────
    // Notifications AJAX — conservée de l'original
    // ──────────────────────────────────────────────────────────────

    public function notifications($id)
    {
        try {
            if (! auth()->check() || ! auth()->user()->restaurant) {
                return response()->json(['status' => false, 'message' => 'Utilisateur non autorisé'], 403);
            }

            if (auth()->user()->restaurant->id != $id) {
                return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
            }

            $orders = Order::where('restaurant_id', $id)
                ->groupBy('order_no')
                ->select('id', 'order_no', 'created_at')
                ->where(function ($query) {
                    $query->where('status', 'pending');
                    if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'business_status')) {
                        $query->orWhereIn('business_status', ['pending_restaurant_acceptance', 'accepted']);
                    }
                })
                ->get();

            $count = $orders->count();
            $new   = false;

            foreach ($orders as $value) {
                $value['time'] = Carbon::parse($value->created_at)->diffForhumans();
                if (Carbon::parse($value->created_at)->diffInSeconds() < 10) {
                    $new = true;
                }
            }

            return response()->json(['status' => true, 'orders' => $orders, 'count' => $count, 'new' => $new]);
        } catch (\Exception $e) {
            \Log::error('Erreur dans notifications: ' . $e->getMessage());

            return response()->json(['status' => false, 'message' => 'Une erreur est survenue', 'error' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Top plats du jour
    // ──────────────────────────────────────────────────────────────

    private function buildTopDishes(mixed $restaurant, Carbon $todayStart, Carbon $todayEnd): array
    {
        if (! $restaurant) {
            return [];
        }

        try {
            $rows = \Illuminate\Support\Facades\DB::table('order_details as od')
                ->join('orders as o', 'o.id', '=', 'od.order_id')
                ->join('products as p', 'p.id', '=', 'od.product_id')
                ->where('o.restaurant_id', $restaurant->id)
                ->whereBetween('o.created_at', [$todayStart, $todayEnd])
                ->whereNotIn('o.status', ['cancelled', 'rejected'])
                ->select(
                    'p.id',
                    'p.name',
                    'p.image',
                    \Illuminate\Support\Facades\DB::raw('SUM(od.quantity) as total_qty'),
                    \Illuminate\Support\Facades\DB::raw('SUM(od.price * od.quantity) as total_revenue')
                )
                ->groupBy('p.id', 'p.name', 'p.image')
                ->orderByDesc('total_qty')
                ->limit(3)
                ->get();

            return $rows->map(function ($row) {
                $img = $row->image ?? null;
                $imgSrc = $img
                    ? (str_starts_with($img, 'http') ? $img : asset('images/product_images/' . $img))
                    : null;
                return [
                    'name'    => $row->name,
                    'qty'     => (int) $row->total_qty,
                    'revenue' => (float) $row->total_revenue,
                    'image'   => $imgSrc,
                ];
            })->all();
        } catch (\Throwable) {
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Mini-stats sidebar (polling toutes les 30 s)
    // ──────────────────────────────────────────────────────────────

    public function sidebarStats()
    {
        try {
            $restaurant = auth()->user()?->restaurant;
            if (!$restaurant) {
                return response()->json([], 403);
            }

            $today = now()->toDateString();

            $ordersToday = \App\Order::where('restaurant_id', $restaurant->id)
                ->whereDate('created_at', $today)
                ->distinct('order_no')
                ->count('order_no');

            $pendingToday = \App\Order::where('restaurant_id', $restaurant->id)
                ->whereDate('created_at', $today)
                ->where('status', 'pending')
                ->distinct('order_no')
                ->count('order_no');

            $revenueToday = \App\Order::where('restaurant_id', $restaurant->id)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['delivered', 'completed'])
                ->sum('total_price');

            $avgRating = \App\Rating::where('restaurant_id', $restaurant->id)->avg('rating');

            return response()->json([
                'orders_today'  => $ordersToday,
                'pending_today' => $pendingToday,
                'revenue_today' => (float) $revenueToday,
                'avg_rating'    => $avgRating ? round($avgRating, 1) : null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([]);
        }
    }
}
