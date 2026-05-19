<?php

namespace App\Services;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\CompletedOrder;
use App\Delivery;
use App\Driver;
use App\Order;
use App\Restaurant;
use App\SupportTicket;
use App\User;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Transport\Models\TransportBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardMetricsService
{
    /**
     * Collecte toutes les métriques dashboard pour une période donnée.
     * Retourne un tableau à plat consommé par DashboardController.
     */
    public function collect(Carbon $startDate, Carbon $chartStartDate): array
    {
        $ordersWindow      = $this->ordersWindow($startDate);
        $uniqueOrdersWindow = $ordersWindow->unique('order_no')->values();

        $ordersToday     = $this->ordersForDay(today()->startOfDay(), now());
        $ordersYesterday = $this->ordersForDay(today()->subDay()->startOfDay(), today()->startOfDay());

        $ordersTodayCount     = $ordersToday->count();
        $ordersYesterdayCount = $ordersYesterday->count();

        $driverMarkers           = $this->buildDriverMarkers();
        $driversOnlineCount      = collect($driverMarkers)->where('is_active', true)->count();
        $pendingOrdersCount      = $this->pendingOrdersCount();
        $delayedRestaurantsCount = $this->delayedRestaurantsCount($startDate);
        $driversUnavailableCount = $this->driversUnavailableCount();
        $paymentAnomaliesCount   = $this->paymentAnomaliesCount($uniqueOrdersWindow);

        $criticalIncidentCount = $pendingOrdersCount + $delayedRestaurantsCount + $driversUnavailableCount + $paymentAnomaliesCount;
        $globalState = $this->globalState($criticalIncidentCount, $pendingOrdersCount, $paymentAnomaliesCount, $delayedRestaurantsCount);

        $prepDurations = $ordersWindow
            ->filter(fn ($o) => !empty($o->accepted_at) && !empty($o->ready_at))
            ->map(fn ($o) => Carbon::parse($o->accepted_at)->diffInMinutes(Carbon::parse($o->ready_at), false))
            ->filter(fn ($m) => is_numeric($m) && $m >= 0)
            ->values();

        $deliveryDurations = $ordersWindow
            ->filter(fn ($o) => !empty($o->ordered_time) && !empty($o->delivered_time))
            ->map(fn ($o) => Carbon::parse($o->ordered_time)->diffInMinutes(Carbon::parse($o->delivered_time), false))
            ->filter(fn ($m) => is_numeric($m) && $m >= 0)
            ->values();

        $activeShipmentsCount = Shipment::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['delivered', 'cancelled', 'completed'])
            ->count();

        $activeTripsCount = TransportBooking::query()
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['completed', 'cancelled', 'closed'])
            ->count();

        $transportPendingCount = TransportBooking::query()
            ->whereNull('deleted_at')
            ->whereIn('status', ['requested', 'offered', 'assigned', 'booked', 'confirmed', 'driver_arriving'])
            ->count();

        $shipmentsPendingCount = Shipment::query()
            ->whereNull('deleted_at')
            ->whereIn('status', ['created', 'priced', 'paid', 'picked_up', 'at_relay'])
            ->count();

        $shipmentsDeliveredToday = Shipment::query()
            ->whereDate('delivered_at', today())
            ->count();

        $transportRevenueCurrent = (float) TransportBooking::query()
            ->whereBetween('created_at', [$startDate, now()])
            ->sum('total_price');

        $shipmentCodCurrent = (float) Shipment::query()
            ->whereBetween('created_at', [$startDate, now()])
            ->sum('cod_amount');

        $refundsToApproveCount = Schema::hasTable('support_tickets')
            ? SupportTicket::query()
                ->where(function ($q) {
                    $q->where('category', 'refund')->orWhere('status', 'pending_refund');
                })
                ->whereIn('status', ['open', 'pending_review', 'pending_refund'])
                ->count()
            : 0;

        $escalatedTicketsCount = Schema::hasTable('support_tickets')
            ? SupportTicket::query()
                ->where('priority', 'high')
                ->whereIn('status', ['open', 'pending_review', 'pending_redelivery', 'pending_refund'])
                ->count()
            : 0;

        $pendingWithdrawalsCount =
            (Schema::hasTable('restaurant_payments') ? DB::table('restaurant_payments')->where('status', 'pending')->count() : 0)
            + (Schema::hasTable('driver_payments') ? DB::table('driver_payments')->where('status', 'pending')->count() : 0);

        return [
            'period'                      => null, // injecté par le contrôleur
            'revenueCurrent'              => $this->sumCompletedRevenue($startDate, now()),
            'transportRevenueCurrent'     => $transportRevenueCurrent,
            'shipmentCodCurrent'          => $shipmentCodCurrent,
            'pendingOrdersCount'          => $pendingOrdersCount,
            'activeDriverDeliveries'      => Delivery::query()->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])->count(),
            'activeShipmentsCount'        => $activeShipmentsCount,
            'shipmentsPendingCount'       => $shipmentsPendingCount,
            'shipmentsDeliveredToday'     => $shipmentsDeliveredToday,
            'activeTripsCount'            => $activeTripsCount,
            'transportPendingCount'       => $transportPendingCount,
            'driversOnlineCount'          => $driversOnlineCount,
            'driversWithoutLocationCount' => Driver::query()->where(fn ($q) => $q->whereNull('latitude')->orWhereNull('longitude'))->count(),
            'restaurantsCount'            => Restaurant::count(),
            'clientsCount'                => User::where('type', 'user')->count(),
            'driverCount'                 => Driver::count(),
            'foodVolumeCount'             => $uniqueOrdersWindow->count(),
            'transportVolumeCount'        => TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->count(),
            'shipmentVolumeCount'         => Shipment::query()->whereBetween('created_at', [$startDate, now()])->count(),
            'foodTrend'                   => $this->buildRevenueSeries($chartStartDate),
            'transportTrend'              => $this->buildTransportTrendSeries($chartStartDate),
            'shipmentTrend'               => $this->buildShipmentTrendSeries($chartStartDate),
            'foodBreakdown'               => $this->buildServiceBreakdown($startDate),
            'transportBreakdown'          => $this->buildTransportBreakdown($startDate),
            'shipmentBreakdown'           => $this->buildShipmentBreakdown($startDate),
            'recentOrders'                => $this->buildRecentOrders(),
            'recentTransportBookings'     => $this->buildRecentTransportBookings(),
            'recentShipments'             => $this->buildRecentShipments(),
            'topRestaurants'              => $this->buildTopRestaurants($startDate),
            'driverMarkers'               => collect($driverMarkers),
            'foodOperationalMetrics'      => $this->buildOperationalMetrics($startDate),
            'transportOperationalMetrics' => $this->buildTransportOperationalMetrics($startDate),
            'shipmentOperationalMetrics'  => $this->buildShipmentOperationalMetrics($startDate),
            'liveActivities'              => $this->buildLiveActivities(),
            'ordersTodayCount'            => $ordersTodayCount,
            'revenueToday'                => $this->sumCompletedRevenue(today()->startOfDay(), now()),
            'criticalIncidentCount'       => $criticalIncidentCount,
            'globalState'                 => $globalState,
            'delayedRestaurantsCount'     => $delayedRestaurantsCount,
            'driversUnavailableCount'     => $driversUnavailableCount,
            'paymentAnomaliesCount'       => $paymentAnomaliesCount,
            'restaurantsToValidateCount'  => Restaurant::query()->where('approved', 0)->count(),
            'refundsToApproveCount'       => $refundsToApproveCount,
            'escalatedTicketsCount'       => $escalatedTicketsCount,
            'pendingWithdrawalsCount'     => $pendingWithdrawalsCount,
            'volumeVsYesterday'           => $this->formatVariationLabel($ordersTodayCount, $ordersYesterdayCount, 'vs hier'),
            'cancellationRate'            => $this->formatPercent(
                $uniqueOrdersWindow->filter(fn ($o) => in_array(strtolower((string) ($o->business_status ?? $o->status ?? '')), ['cancelled', 'canceled', 'refunded'], true))->count(),
                max($uniqueOrdersWindow->count(), 1)
            ),
            'avgPrepTime'                 => $this->formatDurationMinutes($prepDurations->avg()),
            'avgDeliveryTime'             => $this->formatDurationMinutes($deliveryDurations->avg()),
        ];
    }

    // -------------------------------------------------------------------------
    // Données brutes
    // -------------------------------------------------------------------------

    private function ordersWindow(Carbon $startDate): Collection
    {
        return Order::query()
            ->whereBetween('created_at', [$startDate, now()])
            ->orderByDesc('created_at')
            ->get();
    }

    private function ordersForDay(Carbon $from, Carbon $to): Collection
    {
        return Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get()
            ->unique('order_no')
            ->values();
    }

    private function pendingOrdersCount(): int
    {
        return Order::query()
            ->where(function ($q) {
                $q->where('status', 'pending');
                if (Schema::hasColumn('orders', 'business_status')) {
                    $q->orWhereIn('business_status', ['pending_restaurant_acceptance', 'accepted', 'in_kitchen', 'ready_for_pickup']);
                }
            })
            ->get()
            ->unique('order_no')
            ->count();
    }

    private function delayedRestaurantsCount(Carbon $startDate): int
    {
        return Order::query()
            ->whereBetween('created_at', [$startDate, now()->subMinutes(45)])
            ->where(function ($q) {
                if (Schema::hasColumn('orders', 'business_status')) {
                    $q->whereIn('business_status', ['accepted', 'in_kitchen', 'ready_for_pickup']);
                } else {
                    $q->whereIn('status', ['prepairing', 'assign']);
                }
            })
            ->get()
            ->unique('restaurant_id')
            ->count();
    }

    private function driversUnavailableCount(): int
    {
        return Driver::query()
            ->get(['approved', 'status', 'latitude', 'longitude'])
            ->filter(function ($driver) {
                $status = strtolower((string) ($driver->status ?? ''));
                return !((bool) ($driver->approved ?? true))
                    || empty($driver->latitude)
                    || empty($driver->longitude)
                    || in_array($status, ['0', 'inactive', 'offline'], true);
            })
            ->count();
    }

    private function paymentAnomaliesCount(Collection $uniqueOrders): int
    {
        return $uniqueOrders
            ->filter(fn ($o) => in_array(strtolower((string) ($o->payment_status ?? '')), [OrderPaymentStatus::FAILED->value, OrderPaymentStatus::REFUNDED->value, OrderPaymentStatus::CANCELLED->value], true))
            ->count();
    }

    private function sumCompletedRevenue(Carbon $from, Carbon $to): float
    {
        if (Schema::hasTable('completed_orders')) {
            return (float) CompletedOrder::query()->whereBetween('created_at', [$from, $to])->sum('total');
        }

        return (float) Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->unique('order_no')
            ->sum('total');
    }

    // -------------------------------------------------------------------------
    // Séries temporelles
    // -------------------------------------------------------------------------

    private function buildRevenueSeries(Carbon $startDate): array
    {
        $rows = Schema::hasTable('completed_orders')
            ? CompletedOrder::query()
                ->selectRaw('DATE(created_at) as day, SUM(total) as total')
                ->whereBetween('created_at', [$startDate, now()])
                ->groupBy('day')->orderBy('day')->get()
            : collect();

        return $this->fillDailySeries($startDate, collect($rows)->keyBy('day'), 'total');
    }

    private function buildTransportTrendSeries(Carbon $startDate): array
    {
        $rows = TransportBooking::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$startDate, now()])
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');

        return $this->fillDailySeries($startDate, $rows, 'total', 'int');
    }

    private function buildShipmentTrendSeries(Carbon $startDate): array
    {
        $rows = Shipment::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$startDate, now()])
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');

        return $this->fillDailySeries($startDate, $rows, 'total', 'int');
    }

    private function fillDailySeries(Carbon $startDate, Collection $rowsByDay, string $field, string $cast = 'float'): array
    {
        $labels = [];
        $values = [];

        for ($date = $startDate->copy(); $date->lte(now()); $date->addDay()) {
            $key      = $date->format('Y-m-d');
            $labels[] = $date->format('d M');
            $raw      = optional($rowsByDay->get($key))->{$field};
            $values[] = $cast === 'int' ? (int) $raw : (float) $raw;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    // -------------------------------------------------------------------------
    // Listes récentes
    // -------------------------------------------------------------------------

    private function buildRecentOrders(): Collection
    {
        return Order::query()
            ->leftJoin('restaurants', 'orders.restaurant_id', '=', 'restaurants.id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->select([
                'orders.order_no', 'orders.created_at', 'orders.total',
                'orders.status', 'orders.business_status', 'orders.payment_status',
                'orders.fulfillment_mode', 'orders.delivery_address',
                'restaurants.name as restaurant_name', 'users.name as customer_name',
            ])
            ->orderByDesc('orders.created_at')
            ->get()
            ->unique('order_no')
            ->take(8)
            ->values()
            ->map(function ($order) {
                $order->display_status = $order->business_status ?: $order->status;
                $order->display_status_label = $this->translateStatus($order->display_status);
                return $order;
            });
    }

    private function buildRecentTransportBookings(): Collection
    {
        return TransportBooking::query()
            ->with(['user:id,name', 'driver:id,name'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (TransportBooking $booking) {
                $type        = $booking->type?->label() ?? ucfirst((string) $booking->type);
                $statusLabel = $booking->status?->label() ?? $this->translateStatus($this->stringifyStatus($booking->status));
                $driverName  = optional($booking->driver)->name;

                return [
                    'reference'     => '#' . $booking->booking_no,
                    'subject'       => optional($booking->user)->name ?? 'Passager',
                    'subject_sub'   => $driverName ? 'Chauffeur: ' . $driverName : 'Chauffeur non assigne',
                    'service_label' => $type,
                    'service_class' => 'st-taxi',
                    'detail'        => $booking->pickup_address ?: ($booking->dropoff_address ?: 'Trajet Kende'),
                    'amount'        => number_format((float) $booking->total_price, 0, ',', ' ') . ' FCFA',
                    'status_label'  => $statusLabel,
                    'status_class'  => $this->transportStatusClass($this->stringifyStatus($booking->status)),
                    'date'          => optional($booking->created_at)->format('d/m/Y H:i'),
                ];
            });
    }

    private function buildRecentShipments(): Collection
    {
        return Shipment::query()
            ->with(['customer:id,name', 'courier:id,name'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (Shipment $shipment) {
                $status      = $shipment->status?->value ?? $this->stringifyStatus($shipment->status);
                $statusLabel = $shipment->status?->label() ?? ucfirst(str_replace('_', ' ', $status));
                $courierName = optional($shipment->courier)->name;

                return [
                    'reference'     => '#' . $shipment->tracking_number,
                    'subject'       => optional($shipment->customer)->name ?? 'Client',
                    'subject_sub'   => $courierName ? 'Coursier: ' . $courierName : 'Coursier non assigne',
                    'service_label' => 'Colis',
                    'service_class' => 'st-colis',
                    'detail'        => number_format((float) $shipment->weight_kg, 1, ',', ' ') . ' kg',
                    'amount'        => number_format((float) $shipment->total_price, 0, ',', ' ') . ' FCFA',
                    'status_label'  => $statusLabel,
                    'status_class'  => $this->shipmentStatusClass($status),
                    'date'          => optional($shipment->created_at)->format('d/m/Y H:i'),
                ];
            });
    }

    private function buildTopRestaurants(Carbon $startDate): Collection
    {
        return Order::query()
            ->join('restaurants', 'orders.restaurant_id', '=', 'restaurants.id')
            ->selectRaw('restaurants.name as name, COUNT(DISTINCT orders.order_no) as orders_count, SUM(orders.total) as revenue')
            ->whereBetween('orders.created_at', [$startDate, now()])
            ->groupBy('restaurants.id', 'restaurants.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->values();
    }

    // -------------------------------------------------------------------------
    // Carte des livreurs
    // -------------------------------------------------------------------------

    public function buildDriverMarkers(): array
    {
        return Driver::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'name', 'latitude', 'longitude', 'status', 'updated_at'])
            ->map(function ($driver) {
                $status = strtolower((string) $driver->status);
                return [
                    'id'         => $driver->id,
                    'name'       => $driver->name,
                    'lat'        => (float) $driver->latitude,
                    'lng'        => (float) $driver->longitude,
                    'status'     => $driver->status,
                    'updated_at' => optional($driver->updated_at)->format('d/m H:i'),
                    'is_active'  => !in_array($status, ['0', 'inactive', 'offline'], true),
                ];
            })
            ->all();
    }

    // -------------------------------------------------------------------------
    // Répartitions
    // -------------------------------------------------------------------------

    private function buildServiceBreakdown(Carbon $startDate): array
    {
        $food  = Order::query()->whereBetween('created_at', [$startDate, now()])->get()->unique('order_no')->count();
        $colis = Shipment::query()->whereBetween('created_at', [$startDate, now()])->count();
        $taxi  = TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->count();
        $total = max($food + $colis + $taxi, 1);

        return [
            'total' => $food + $colis + $taxi,
            'items' => [
                ['label' => 'Repas', 'value' => $food,  'color' => '#1db860', 'percent' => round(($food / $total) * 100)],
                ['label' => 'Colis', 'value' => $colis, 'color' => '#e8a020', 'percent' => round(($colis / $total) * 100)],
                ['label' => 'Taxi',  'value' => $taxi,  'color' => '#f1c232', 'percent' => round(($taxi / $total) * 100)],
            ],
        ];
    }

    private function buildTransportBreakdown(Carbon $startDate): array
    {
        $types = [
            ['label' => 'Taxi',        'value' => TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->where('type', 'taxi')->count(),    'color' => '#f97316'],
            ['label' => 'Covoiturage', 'value' => TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->where('type', 'carpool')->count(), 'color' => '#f1c232'],
            ['label' => 'Location',    'value' => TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->where('type', 'rental')->count(),  'color' => '#65a30d'],
            ['label' => 'Bus',         'value' => TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->where('type', 'bus')->count(),     'color' => '#2563eb'],
        ];
        $total = max(collect($types)->sum('value'), 1);

        return [
            'total' => collect($types)->sum('value'),
            'items' => collect($types)->map(fn ($item) => array_merge($item, ['percent' => round(($item['value'] / $total) * 100)]))->all(),
        ];
    }

    private function buildShipmentBreakdown(Carbon $startDate): array
    {
        $statuses = [
            ['label' => 'A lancer',   'value' => Shipment::query()->whereBetween('created_at', [$startDate, now()])->whereIn('status', ['created', 'priced', 'paid'])->count(),                                    'color' => '#f97316'],
            ['label' => 'En transit', 'value' => Shipment::query()->whereBetween('created_at', [$startDate, now()])->whereIn('status', ['picked_up', 'in_transit', 'at_relay', 'out_for_delivery'])->count(),     'color' => '#f1c232'],
            ['label' => 'Livres',     'value' => Shipment::query()->whereBetween('created_at', [$startDate, now()])->where('status', 'delivered')->count(),                                                        'color' => '#16a34a'],
            ['label' => 'Incidents',  'value' => Shipment::query()->whereBetween('created_at', [$startDate, now()])->whereIn('status', ['failed', 'returned', 'damaged', 'lost', 'canceled'])->count(),           'color' => '#dc2626'],
        ];
        $total = max(collect($statuses)->sum('value'), 1);

        return [
            'total' => collect($statuses)->sum('value'),
            'items' => collect($statuses)->map(fn ($item) => array_merge($item, ['percent' => round(($item['value'] / $total) * 100)]))->all(),
        ];
    }

    // -------------------------------------------------------------------------
    // Activité live
    // -------------------------------------------------------------------------

    private function buildLiveActivities(): Collection
    {
        $orders = Order::query()
            ->leftJoin('restaurants', 'orders.restaurant_id', '=', 'restaurants.id')
            ->select(['orders.order_no as ref', 'orders.created_at as activity_at', 'restaurants.name as label'])
            ->orderByDesc('orders.created_at')
            ->limit(4)
            ->get()
            ->map(fn ($item) => [
                'kind' => 'green',
                'text' => 'Commande #' . $item->ref . ' - ' . ($item->label ?: 'restaurant'),
                'time' => optional($item->activity_at)->diffForHumans(),
            ]);

        $shipments = Shipment::query()
            ->select(['tracking_number as ref', 'created_at as activity_at', 'status'])
            ->orderByDesc('created_at')->limit(3)->get()
            ->map(fn ($item) => [
                'kind' => 'gold',
                'text' => 'Colis ' . $item->ref . ' - ' . $this->stringifyStatus($item->status),
                'time' => optional($item->activity_at)->diffForHumans(),
            ]);

        $transport = TransportBooking::query()
            ->select(['booking_no as ref', 'created_at as activity_at', 'status'])
            ->orderByDesc('created_at')->limit(3)->get()
            ->map(fn ($item) => [
                'kind' => 'gold',
                'text' => 'Trajet ' . $item->ref . ' - ' . $this->stringifyStatus($item->status),
                'time' => optional($item->activity_at)->diffForHumans(),
            ]);

        return $orders->merge($shipments)->merge($transport)->take(6)->values();
    }

    // -------------------------------------------------------------------------
    // Métriques opérationnelles
    // -------------------------------------------------------------------------

    private function buildOperationalMetrics(Carbon $startDate): array
    {
        $foodTotal      = max(Order::query()->whereBetween('created_at', [$startDate, now()])->get()->unique('order_no')->count(), 1);
        $foodDelivered  = Order::query()->whereBetween('created_at', [$startDate, now()])->whereIn('business_status', ['delivered', 'picked_up_by_customer'])->get()->unique('order_no')->count();
        $shipTotal      = max(Shipment::query()->whereBetween('created_at', [$startDate, now()])->count(), 1);
        $shipDelivered  = Shipment::query()->whereBetween('created_at', [$startDate, now()])->where('status', 'delivered')->count();
        $tripTotal      = max(TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->count(), 1);
        $tripCompleted  = TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->where('status', 'completed')->count();

        return [
            $this->metricRow('Taux de livraison reussie - Repas',    $foodDelivered, $foodTotal, '#1db860'),
            $this->metricRow('Taux de remise confirmee - Colis',     $shipDelivered, $shipTotal, '#e8a020'),
            $this->metricRow('Taux de finalisation - Trajets',       $tripCompleted, $tripTotal, '#f1c232'),
        ];
    }

    private function buildTransportOperationalMetrics(Carbon $startDate): array
    {
        $total     = max(TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->count(), 1);
        $completed = TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->where('status', 'completed')->count();
        $assigned  = TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->whereIn('status', ['assigned', 'booked', 'confirmed', 'driver_arriving', 'picked_up', 'in_progress', 'completed'])->count();
        $cancelled = TransportBooking::query()->whereBetween('created_at', [$startDate, now()])->where('status', 'cancelled')->count();

        return [
            $this->metricRow('Taux de finalisation des trajets', $completed, $total, '#f97316'),
            $this->metricRow('Taux d attribution',               $assigned,  $total, '#65a30d'),
            $this->metricRow('Taux d annulation',                $cancelled, $total, '#dc2626'),
        ];
    }

    private function buildShipmentOperationalMetrics(Carbon $startDate): array
    {
        $total     = max(Shipment::query()->whereBetween('created_at', [$startDate, now()])->count(), 1);
        $delivered = Shipment::query()->whereBetween('created_at', [$startDate, now()])->where('status', 'delivered')->count();
        $transit   = Shipment::query()->whereBetween('created_at', [$startDate, now()])->whereIn('status', ['picked_up', 'in_transit', 'at_relay', 'out_for_delivery'])->count();
        $incident  = Shipment::query()->whereBetween('created_at', [$startDate, now()])->whereIn('status', ['failed', 'returned', 'damaged', 'lost', 'canceled'])->count();

        return [
            $this->metricRow('Taux de remise confirmee',       $delivered, $total, '#16a34a'),
            $this->metricRow('Colis encore en circulation',    $transit,   $total, '#f59e0b'),
            $this->metricRow('Taux d incident',                $incident,  $total, '#dc2626'),
        ];
    }

    private function metricRow(string $label, int $part, int $total, string $color): array
    {
        $percent = round(($part / max($total, 1)) * 100, 1);
        return ['label' => $label, 'value' => $percent . '%', 'percent' => $percent, 'color' => $color];
    }

    // -------------------------------------------------------------------------
    // Utilitaires
    // -------------------------------------------------------------------------

    private function globalState(int $critical, int $pending, int $anomalies, int $delayed): string
    {
        if ($critical >= 25 || $pending >= 10 || $anomalies >= 5) {
            return 'critique';
        }

        if ($critical >= 8 || $pending >= 4 || $delayed >= 2) {
            return 'sous tension';
        }

        return 'stable';
    }

    public function translateStatus($status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            'pending'                        => 'En attente',
            'pending_restaurant_acceptance'  => 'En attente de validation restaurant',
            'accepted'                       => 'Acceptee',
            'assign', 'driver_assigned'      => 'Livreur assigne',
            'in_kitchen'                     => 'En preparation',
            'ready_for_pickup'               => 'Pret pour retrait',
            'picked_up'                      => 'Recuperee',
            'picked_up_by_customer'          => 'Retiree par le client',
            'out_for_delivery'               => 'En cours de livraison',
            'delivered'                      => 'Livree',
            'completed'                      => 'Terminee',
            'cancelled', 'canceled'          => 'Annulee',
            'failed'                         => 'Echouee',
            default                          => ucfirst(str_replace('_', ' ', $value)),
        };
    }

    public function transportStatusClass(string $status): string
    {
        return in_array($status, ['completed', 'closed', 'paid'], true)
            ? 'sp-delivered'
            : (in_array($status, ['cancelled'], true)
                ? 'sp-cancelled'
                : (in_array($status, ['requested', 'offered', 'draft'], true) ? 'sp-pending' : 'sp-transit'));
    }

    public function shipmentStatusClass(string $status): string
    {
        return in_array($status, ['delivered'], true)
            ? 'sp-delivered'
            : (in_array($status, ['canceled', 'failed', 'returned', 'damaged', 'lost'], true)
                ? 'sp-cancelled'
                : (in_array($status, ['created', 'priced', 'paid'], true) ? 'sp-pending' : 'sp-transit'));
    }

    public function formatVariationLabel(int $current, int $previous, string $suffix = ''): string
    {
        if ($previous <= 0) {
            return $current > 0 ? '+' . $current . ' ' . trim($suffix) : '0 ' . trim($suffix);
        }

        $delta = round((($current - $previous) / $previous) * 100, 1);
        return ($delta > 0 ? '+' : '') . $delta . '% ' . trim($suffix);
    }

    public function formatPercent(int $value, int $total): string
    {
        return round(($value / max($total, 1)) * 100, 1) . '%';
    }

    public function formatDurationMinutes($minutes): string
    {
        $minutes = (int) round((float) $minutes);

        if ($minutes <= 0) {
            return 'n/d';
        }

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $rest  = $minutes % 60;

        return $rest > 0 ? $hours . ' h ' . $rest . ' min' : $hours . ' h';
    }

    public function formatDelayFromDate(Carbon $date): string
    {
        return $this->formatDurationMinutes(max($date->diffInMinutes(now()), 0));
    }

    public function inferOrderZone(?string $address): string
    {
        $address  = trim((string) $address);
        $segments = preg_split('/[,\-]/', $address);
        $zone     = trim((string) ($segments[0] ?? ''));

        return $zone !== '' ? $zone : 'Brazzaville';
    }

    public function stringifyStatus($status): string
    {
        if (is_object($status) && (method_exists($status, 'value') || property_exists($status, 'value'))) {
            return (string) $status->value;
        }

        if (is_scalar($status) || $status === null) {
            return (string) $status;
        }

        return 'inconnu';
    }
}
