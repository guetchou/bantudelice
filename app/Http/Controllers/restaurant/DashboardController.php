<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $restaurant = auth()->user()?->restaurant;
        if (! $restaurant) {
            return redirect()->route('login')->with('error', 'Aucun restaurant associé à votre compte.');
        }

        $now = now();
        $stats = $this->buildStats(
            restaurant: $restaurant,
            todayStart: $now->copy()->startOfDay(),
            todayEnd: $now->copy()->endOfDay(),
            monthStart: $now->copy()->startOfMonth(),
            monthEnd: $now->copy()->endOfMonth(),
        );

        return view('restaurant.dashboard', [
            'restaurant' => $this->buildRestaurantPayload($restaurant),
            'kpis' => $stats['kpis'],
            'pipeline' => $stats['pipeline'],
            'quickActions' => $this->buildQuickActions(),
            'performanceCards' => $stats['performance_cards'],
            'recentOrders' => $stats['recent_orders'],
            'salesSeries' => $stats['sales_series'],
            'salesLabels' => $stats['sales_labels'],
            'topDishes' => $this->buildTopDishes($restaurant, $now->copy()->startOfDay(), $now->copy()->endOfDay()),
        ]);
    }

    private function buildRestaurantPayload(mixed $restaurant): array
    {
        $logo = data_get($restaurant, 'logo_url')
            ?? data_get($restaurant, 'logo')
            ?? data_get($restaurant, 'media.logo_url');

        if ($logo && ! str_starts_with($logo, 'http')) {
            $logo = asset('images/restaurant_images/' . $logo);
        }

        return [
            'name' => data_get($restaurant, 'name') ?? data_get($restaurant, 'restaurant_name') ?? 'Restaurant',
            'status' => (string) (data_get($restaurant, 'status') ?? 'Actif'),
            'phone' => data_get($restaurant, 'phone') ?? data_get($restaurant, 'phone_number'),
            'city' => data_get($restaurant, 'city') ?? data_get($restaurant, 'address_city'),
            'logo_url' => $logo,
        ];
    }

    private function buildQuickActions(): array
    {
        return [
            ['label' => 'Ajouter un produit', 'route' => $this->safeRoute('product.create')],
            ['label' => 'Voir les commandes', 'route' => $this->safeRoute('restaurant.all_orders')],
            ['label' => 'Historique paiements', 'route' => $this->safeRoute('r_earnings.index')],
            ['label' => 'Gérer le catalogue', 'route' => $this->safeRoute('product.index')],
            ['label' => "Horaires d'ouverture", 'route' => $this->safeRoute('working-hour.index')],
            ['label' => 'Créer une promotion', 'route' => $this->safeRoute('voucher.create')],
        ];
    }

    private function buildStats(
        mixed $restaurant,
        Carbon $todayStart,
        Carbon $todayEnd,
        Carbon $monthStart,
        Carbon $monthEnd,
    ): array {
        // Une entrée de cette collection représente UNE commande, jamais une ligne produit.
        $orders = $this->fetchOrders($restaurant);
        $products = $this->fetchProducts($restaurant);
        $categories = $this->fetchCategories($restaurant);
        $settlements = $this->fetchSettlements($restaurant);

        $todayOrders = $orders->filter(
            fn (array $order) => $this->dateBetween($order['created_at'] ?? null, $todayStart, $todayEnd)
        );
        $monthOrders = $orders->filter(
            fn (array $order) => $this->dateBetween($order['created_at'] ?? null, $monthStart, $monthEnd)
        );

        // Le revenu n'est reconnu que pour une commande terminale et réellement encaissée.
        $todayRecognized = $todayOrders->where('revenue_recognized', true);
        $monthRecognized = $monthOrders->where('revenue_recognized', true);

        $grossToday = $todayRecognized->sum('gross_amount');
        $ordersToday = $todayOrders->count();
        $averageTicket = $todayRecognized->count() > 0
            ? $grossToday / $todayRecognized->count()
            : 0;

        $grossMonth = $monthRecognized->sum('gross_amount');
        $commissionMonth = $monthRecognized->sum('commission_amount');
        $netPartnerMonth = max(0, $grossMonth - $commissionMonth);

        $alreadyPaid = $settlements->where('status', 'paid')->sum('amount');
        $pendingSettlement = $settlements
            ->whereIn('status', ['pending', 'held', 'processing'])
            ->sum('amount');
        $availableWithdrawal = max(0, $netPartnerMonth - $alreadyPaid - $pendingSettlement);

        $businessStatus = static fn (array $order): string =>
            (string) ($order['business_status'] ?? $order['status'] ?? '');

        $newCount = $todayOrders->filter(fn ($order) => in_array(
            $businessStatus($order),
            ['pending_restaurant_acceptance', 'new', 'pending', 'received'],
            true
        ))->count();
        $preparingCount = $todayOrders->filter(fn ($order) => in_array(
            $businessStatus($order),
            ['accepted_awaiting_payment', 'confirmed', 'accepted', 'in_kitchen', 'preparing', 'cooking'],
            true
        ))->count();
        $deliveringCount = $todayOrders->filter(fn ($order) => in_array(
            $businessStatus($order),
            ['ready_for_pickup', 'dispatching', 'driver_assigned', 'picked_up', 'out_for_delivery'],
            true
        ))->count();
        $completedCount = $todayOrders->filter(fn ($order) => in_array(
            $businessStatus($order),
            ['delivered', 'closed', 'picked_up_by_customer', 'completed'],
            true
        ))->count();

        $acceptedOrders = $monthOrders->filter(fn ($order) => ! in_array(
            $businessStatus($order),
            ['pending_restaurant_acceptance', 'new', 'pending', 'cancelled', 'rejected'],
            true
        ))->count();
        $receivedOrders = $monthOrders->count();
        $acceptanceRate = $receivedOrders > 0
            ? round(($acceptedOrders / $receivedOrders) * 100)
            : 0;

        return [
            'kpis' => [
                'gross_today' => round($grossToday),
                'orders_today' => $ordersToday,
                'average_ticket' => round($averageTicket),
                'available_withdrawal' => round($availableWithdrawal),
                'gross_month' => round($grossMonth),
                'commission_month' => round($commissionMonth),
                'net_partner_month' => round($netPartnerMonth),
                'pending_settlement' => round($pendingSettlement),
            ],
            'pipeline' => [
                ['label' => 'Nouvelles', 'value' => $newCount, 'hint' => 'À traiter rapidement', 'route' => $this->safeRoute('restaurant.all_orders')],
                ['label' => 'En préparation', 'value' => $preparingCount, 'hint' => 'Cuisine ou paiement en cours', 'route' => $this->safeRoute('restaurant.kitchen')],
                ['label' => 'En livraison', 'value' => $deliveringCount, 'hint' => 'Courses en cours', 'route' => $this->safeRoute('restaurant.pending_orders')],
                ['label' => 'Terminées', 'value' => $completedCount, 'hint' => "Clôturées aujourd'hui", 'route' => $this->safeRoute('restaurant.complete_orders')],
            ],
            'performance_cards' => [
                ['label' => 'Produits actifs', 'value' => $products->where('is_active', true)->count(), 'hint' => 'Disponibles à la vente', 'route' => $this->safeRoute('product.index')],
                ['label' => 'Catégories', 'value' => $categories->count(), 'hint' => 'Structuration du menu', 'route' => $this->safeRoute('category.index')],
                ['label' => 'Programmées', 'value' => $orders->where('is_scheduled', true)->count(), 'hint' => 'Commandes planifiées', 'route' => $this->safeRoute('restaurant.schedule_orders')],
                ['label' => "Taux d'acceptation", 'value' => $acceptanceRate . '%', 'hint' => 'Sur les commandes du mois', 'route' => null],
            ],
            'recent_orders' => $orders
                ->sortByDesc('created_at')
                ->take(8)
                ->map(fn (array $order) => [
                    'ref' => (string) ($order['reference'] ?? '—'),
                    'customer' => (string) ($order['customer_name'] ?? 'Client'),
                    'amount' => (float) ($order['gross_amount'] ?? 0),
                    'status' => $this->mapOrderStatusLabel((string) ($order['business_status'] ?? 'new')),
                    'time' => $this->formatOrderTime($order['created_at'] ?? null),
                ])
                ->values()
                ->all(),
            'sales_series' => $this->buildWeeklySalesSeries($orders->where('revenue_recognized', true), now()),
            'sales_labels' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
        ];
    }

    private function buildWeeklySalesSeries(Collection $orders, Carbon $referenceDate): array
    {
        $startOfWeek = $referenceDate->copy()->startOfWeek(Carbon::MONDAY);
        $series = [];

        for ($day = 0; $day < 7; $day++) {
            $dayStart = $startOfWeek->copy()->addDays($day)->startOfDay();
            $dayEnd = $startOfWeek->copy()->addDays($day)->endOfDay();
            $series[] = (int) round($orders
                ->filter(fn (array $order) => $this->dateBetween($order['created_at'] ?? null, $dayStart, $dayEnd))
                ->sum('gross_amount'));
        }

        return $series;
    }

    private function mapOrderStatusLabel(string $status): string
    {
        return match ($status) {
            'pending_restaurant_acceptance', 'new', 'pending', 'received' => 'Nouvelle',
            'accepted_awaiting_payment' => 'Paiement attendu',
            'accepted_scheduled' => 'Programmée',
            'preparation_due', 'accepted', 'in_kitchen', 'confirmed', 'preparing', 'cooking' => 'Préparation',
            'ready_for_pickup', 'dispatching', 'driver_assigned', 'picked_up', 'out_for_delivery' => 'Livraison',
            'delivered', 'closed', 'picked_up_by_customer', 'completed' => 'Terminée',
            'cancelled', 'rejected', 'no_show' => 'Annulée',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function formatOrderTime(mixed $date): string
    {
        try {
            return $date ? Carbon::parse($date)->format('H:i') : '—:—';
        } catch (\Throwable) {
            return '—:—';
        }
    }

    private function dateBetween(mixed $date, Carbon $start, Carbon $end): bool
    {
        try {
            return $date && Carbon::parse($date)->betweenIncluded($start, $end);
        } catch (\Throwable) {
            return false;
        }
    }

    private function safeRoute(string $name, array $parameters = [], string $fallback = '#'): string
    {
        return app('router')->has($name) ? route($name, $parameters) : $fallback;
    }

    private function fetchOrders(mixed $restaurant): Collection
    {
        if (! $restaurant) {
            return collect();
        }

        try {
            return Order::query()
                ->with('user:id,name')
                ->where('restaurant_id', $restaurant->id)
                ->orderByDesc('id')
                ->get()
                ->groupBy('order_no')
                ->map(function (Collection $lines): array {
                    /** @var Order $order */
                    $order = $lines->sortBy('id')->first();
                    $total = (float) ($order->total ?? 0);
                    $commissionRate = (float) ($order->admin_commission ?? 0);

                    return [
                        'reference' => $order->order_no ?: ('#CMD-' . $order->id),
                        'customer_name' => $order->user?->name ?? 'Client',
                        'gross_amount' => $total,
                        'commission_amount' => $commissionRate > 0 ? $total * ($commissionRate / 100) : 0.0,
                        'status' => (string) ($order->status ?? 'new'),
                        'business_status' => (string) ($order->business_status ?? $order->status ?? 'new'),
                        'payment_method' => strtolower((string) ($order->payment_method ?? 'cash')),
                        'payment_status' => strtolower((string) ($order->payment_status ?? 'pending')),
                        'cash_collection_status' => strtolower((string) ($order->cash_collection_status ?? '')),
                        'revenue_recognized' => $this->isRevenueRecognized($order),
                        'is_scheduled' => $order->scheduled_date !== null,
                        'created_at' => $order->created_at,
                    ];
                })
                ->values();
        } catch (\Throwable) {
            return collect();
        }
    }

    private function isRevenueRecognized(Order $order): bool
    {
        $businessStatus = (string) ($order->business_status ?? $order->status ?? '');
        if (! in_array($businessStatus, ['delivered', 'closed', 'picked_up_by_customer', 'completed'], true)) {
            return false;
        }

        $paymentMethod = strtolower((string) ($order->payment_method ?? 'cash'));
        if ($paymentMethod === 'cash') {
            return $order->cash_collection_confirmed_at !== null
                || in_array(strtolower((string) $order->cash_collection_status), [
                    'confirmed', 'reconciled', 'settled',
                ], true);
        }

        return strtolower((string) $order->payment_status) === 'paid';
    }

    private function fetchProducts(mixed $restaurant): Collection
    {
        try {
            return DB::table('products')
                ->where('restaurant_id', $restaurant->id)
                ->get()
                ->map(fn ($product) => ['is_active' => (bool) ($product->is_active ?? 1)]);
        } catch (\Throwable) {
            return collect();
        }
    }

    private function fetchCategories(mixed $restaurant): Collection
    {
        try {
            return DB::table('categories')
                ->where('restaurant_id', $restaurant->id)
                ->get()
                ->map(fn ($category) => ['id' => $category->id]);
        } catch (\Throwable) {
            return collect();
        }
    }

    private function fetchSettlements(mixed $restaurant): Collection
    {
        try {
            return DB::table('restaurant_payments')
                ->where('restaurant_id', $restaurant->id)
                ->get()
                ->map(fn ($settlement) => [
                    'amount' => (float) ($settlement->payout_amount ?? 0),
                    'status' => (string) ($settlement->status ?? 'pending'),
                ]);
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Polling fiable : le client transmet son dernier cursor_id. Une commande reste
     * "nouvelle" jusqu'à ce que ce curseur soit acquitté, sans fenêtre arbitraire.
     */
    public function notifications(Request $request, $id)
    {
        if (! auth()->check() || (int) auth()->user()?->restaurant?->id !== (int) $id) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $afterId = max(0, (int) $request->query('after_id', 0));
        $orders = Order::query()
            ->where('restaurant_id', $id)
            ->where('business_status', 'pending_restaurant_acceptance')
            ->selectRaw('order_no, MAX(id) as cursor_id, MIN(created_at) as created_at')
            ->groupBy('order_no')
            ->orderByDesc('cursor_id')
            ->get()
            ->map(function ($order) {
                $order->time = Carbon::parse($order->created_at)->diffForHumans();
                return $order;
            });

        $nextCursor = (int) ($orders->max('cursor_id') ?? $afterId);

        return response()->json([
            'status' => true,
            'orders' => $orders,
            'count' => $orders->count(),
            'new' => $orders->contains(fn ($order) => (int) $order->cursor_id > $afterId),
            'next_cursor' => max($afterId, $nextCursor),
        ]);
    }

    private function buildTopDishes(mixed $restaurant, Carbon $todayStart, Carbon $todayEnd): array
    {
        try {
            return DB::table('orders as order_lines')
                ->join('products as products', 'products.id', '=', 'order_lines.product_id')
                ->where('order_lines.restaurant_id', $restaurant->id)
                ->whereBetween('order_lines.created_at', [$todayStart, $todayEnd])
                ->whereNotIn('order_lines.business_status', ['cancelled', 'rejected', 'no_show'])
                ->select(
                    'products.id',
                    'products.name',
                    'products.image',
                    DB::raw('SUM(order_lines.qty) as total_qty'),
                    DB::raw('SUM(order_lines.price * order_lines.qty) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.image')
                ->orderByDesc('total_qty')
                ->limit(3)
                ->get()
                ->map(function ($row) {
                    $image = $row->image ?? null;
                    return [
                        'name' => $row->name,
                        'qty' => (int) $row->total_qty,
                        'revenue' => (float) $row->total_revenue,
                        'image' => $image
                            ? (str_starts_with($image, 'http') ? $image : asset('images/product_images/' . $image))
                            : null,
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public function sidebarStats()
    {
        $restaurant = auth()->user()?->restaurant;
        if (! $restaurant) {
            return response()->json([], 403);
        }

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $orders = $this->fetchOrders($restaurant)
            ->filter(fn (array $order) => $this->dateBetween($order['created_at'] ?? null, $todayStart, $todayEnd));

        return response()->json([
            'orders_today' => $orders->count(),
            'pending_today' => $orders->where('business_status', 'pending_restaurant_acceptance')->count(),
            'revenue_today' => (float) $orders->where('revenue_recognized', true)->sum('gross_amount'),
            'avg_rating' => ($rating = \App\Rating::where('restaurant_id', $restaurant->id)->avg('rating'))
                ? round($rating, 1)
                : null,
        ]);
    }
}
