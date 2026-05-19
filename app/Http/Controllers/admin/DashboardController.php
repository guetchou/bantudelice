<?php

namespace App\Http\Controllers\admin;

use App\Driver;
use App\Restaurant;
use App\User;
use App\Delivery;
use App\Http\Controllers\Controller;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(private DashboardMetricsService $metrics) {}

    public function index(Request $request)
    {
        $workspace = in_array($request->query('workspace'), ['bantudelice', 'kende', 'mema'], true)
            ? (string) $request->query('workspace')
            : 'bantudelice';

        $period = in_array((int) $request->query('period', 30), [7, 30, 90], true)
            ? (int) $request->query('period', 30)
            : 30;

        $startDate      = now()->subDays($period - 1)->startOfDay();
        $chartStartDate = now()->subDays(29)->startOfDay();

        $data = array_merge(
            $this->metrics->collect($startDate, $chartStartDate),
            ['period' => $period]
        );

        $workspaceDashboard = $this->buildWorkspaceDashboard($workspace, $data);

        return view('admin.home', [
            'workspace'       => $workspace,
            'period'          => $period,
            'periodOptions'   => [7, 30, 90],
            'workspaceMeta'   => $workspaceDashboard['meta'],
            'kpis'            => $workspaceDashboard['kpis'],
            'summaryCards'    => [
                'restaurants'     => $data['restaurantsCount'],
                'drivers'         => $data['driverCount'],
                'clients'         => $data['clientsCount'],
                'activeDeliveries'=> $data['activeDriverDeliveries'],
            ],
            'revenueChart'      => $workspaceDashboard['trend_chart'],
            'primaryTable'      => $workspaceDashboard['primary_table'],
            'driverMarkers'     => $data['driverMarkers']->all(),
            'alerts'            => $workspaceDashboard['alerts'],
            'pendingOrdersCount'  => $data['pendingOrdersCount'],
            'activeShipmentsCount'=> $data['activeShipmentsCount'],
            'activeTripsCount'    => $data['activeTripsCount'],
            'driversOnlineCount'  => $data['driversOnlineCount'],
            'serviceBreakdown'    => $workspaceDashboard['breakdown'],
            'topRestaurants'      => $data['topRestaurants'],
            'liveActivities'      => $workspaceDashboard['live_activities'],
            'operationalMetrics'  => $workspaceDashboard['operational_metrics'],
            'secondaryList'       => $workspaceDashboard['secondary_list'],
        ]);
    }

    public function notifications()
    {
        $orders = DB::table('orders')
            ->leftJoin('restaurants', 'orders.restaurant_id', '=', 'restaurants.id')
            ->select('orders.order_no', 'orders.created_at', 'restaurants.name as restaurant_name')
            ->where(function ($query) {
                $query->where('orders.status', 'pending');
                if (Schema::hasColumn('orders', 'business_status')) {
                    $query->orWhereIn('orders.business_status', ['pending_restaurant_acceptance', 'accepted']);
                }
            })
            ->orderByDesc('orders.created_at')
            ->get()
            ->unique('order_no')
            ->values();

        $new = false;

        foreach ($orders as $order) {
            $createdAt    = Carbon::parse($order->created_at);
            $order->time  = $createdAt->diffForHumans();
            if ($createdAt->diffInSeconds() < 10) {
                $new = true;
            }
        }

        return response()->json([
            'status' => true,
            'orders' => $orders,
            'count'  => $orders->count(),
            'new'    => $new,
        ]);
    }

    // -------------------------------------------------------------------------
    // Assemblage workspace — délègue au service pour les helpers de présentation
    // -------------------------------------------------------------------------

    private function buildWorkspaceDashboard(string $workspace, array $data): array
    {
        return match ($workspace) {
            'kende' => $this->buildKendeDashboard($data),
            'mema'  => $this->buildMemaDashboard($data),
            default => $this->buildFoodDashboard($data),
        };
    }

    private function buildFoodDashboard(array $data): array
    {
        return [
            'meta' => [
                'short_title'    => 'BantuDelice',
                'page_title'     => 'Dashboard food',
                'eyebrow'        => 'Food ops',
                'heading'        => 'Pilotage des commandes et de la flotte food',
                'intro'          => 'Vue prioritaire sur les commandes, les restaurants, les livreurs et les exceptions a traiter.',
                'global_state'   => ucfirst($data['globalState']),
                'chart_title'    => 'Revenus food — 30 derniers jours',
                'table_title'    => 'Commandes recentes',
                'table_cta_label'=> 'Voir les commandes',
                'table_cta_url'  => route('admin.all_orders', ['workspace' => 'bantudelice']),
                'breakdown_title'=> 'Repartition des services',
                'secondary_title'=> 'Performance restaurants',
                'alerts_title'   => 'Incidents et alertes',
                'metrics_title'  => 'Indicateurs food',
                'chart_color'    => '#1db860',
                'chart_mode'     => 'currency',
            ],
            'kpis' => [
                ['label' => 'Commandes aujourd hui',       'value' => number_format($data['ordersTodayCount'], 0, ',', ' '),                      'meta' => 'Volume journalier',       'icon' => 'fas fa-shopping-bag'],
                ['label' => 'Chiffre traite aujourd hui',  'value' => number_format($data['revenueToday'], 0, ',', ' ') . ' FCFA',                'meta' => 'Encaissement du jour',    'icon' => 'fas fa-coins'],
                ['label' => 'Commandes bloquees',          'value' => number_format($data['pendingOrdersCount'], 0, ',', ' '),                    'meta' => 'Demandent une action',    'icon' => 'fas fa-clock'],
                ['label' => 'Incidents critiques',         'value' => number_format($data['criticalIncidentCount'], 0, ',', ' '),                 'meta' => strtoupper($data['globalState']), 'icon' => 'fas fa-exclamation-circle'],
            ],
            'trend_chart'   => array_merge($data['foodTrend'], ['label' => 'Revenus', 'mode' => 'currency', 'color' => '#1db860']),
            'primary_table' => [
                'headers' => ['Reference', 'Type', 'Zone', 'Responsable', 'Delai', 'Statut', 'Action'],
                'rows'    => $data['recentOrders']->map(function ($order) {
                    return [
                        'reference'    => '#' . $order->order_no,
                        'type'         => ($order->fulfillment_mode === 'pickup') ? 'Pickup' : 'Livraison',
                        'zone'         => $this->metrics->inferOrderZone($order->delivery_address ?? null),
                        'owner'        => $order->restaurant_name ?: 'Dispatch',
                        'delay'        => $this->metrics->formatDelayFromDate(Carbon::parse($order->created_at)),
                        'status_label' => $order->display_status_label ?? str_replace('_', ' ', (string) $order->display_status),
                        'action_label' => 'Ouvrir',
                        'action_url'   => route('admin.show_order', ['order' => $order->order_no, 'workspace' => 'bantudelice']),
                    ];
                })->all(),
            ],
            'breakdown'      => $data['foodBreakdown'],
            'secondary_list' => [
                ['rank' => number_format($data['restaurantsToValidateCount'], 0, ',', ' '), 'title' => 'Restaurants a valider',         'badge' => 'validation', 'meta' => 'Profils en attente d activation',          'action_label' => 'Traiter',   'action_url' => route('admin.pending', ['workspace' => 'bantudelice'])],
                ['rank' => number_format($data['refundsToApproveCount'], 0, ',', ' '),      'title' => 'Remboursements a approuver',    'badge' => 'finance',    'meta' => 'Demandes refund ou pending_refund',          'action_label' => 'Ouvrir',    'action_url' => route('admin.support-tickets.index', ['workspace' => 'bantudelice', 'status' => 'pending_refund'])],
                ['rank' => number_format($data['escalatedTicketsCount'], 0, ',', ' '),      'title' => 'Tickets support escalades',     'badge' => 'support',    'meta' => 'Priorite haute encore ouverte',              'action_label' => 'Voir',      'action_url' => route('admin.support-tickets.index', ['workspace' => 'bantudelice'])],
                ['rank' => number_format($data['pendingWithdrawalsCount'], 0, ',', ' '),    'title' => 'Retraits en attente',           'badge' => 'payout',     'meta' => 'Demandes restaurant et livreur',             'action_label' => 'Verifier',  'action_url' => route('restaurant_payout', ['workspace' => 'bantudelice'])],
            ],
            'alerts' => [
                ['level' => 'warning', 'title' => 'Commandes bloquees',     'message' => $data['pendingOrdersCount'] . ' commande(s) attendent une validation ou un debloquage.',          'count' => $data['pendingOrdersCount'],      'action_label' => 'Ouvrir',    'action_url' => route('admin.pending_orders', ['workspace' => 'bantudelice'])],
                ['level' => 'warning', 'title' => 'Restaurants en retard',  'message' => $data['delayedRestaurantsCount'] . ' restaurant(s) depassent le seuil de preparation retenu.',    'count' => $data['delayedRestaurantsCount'], 'action_label' => 'Verifier',  'action_url' => route('restaurant.index', ['workspace' => 'bantudelice'])],
                ['level' => 'warning', 'title' => 'Livreurs indisponibles', 'message' => $data['driversUnavailableCount'] . ' profil(s) sont hors ligne ou sans position exploitable.',   'count' => $data['driversUnavailableCount'], 'action_label' => 'Suivre',    'action_url' => route('driver.index', ['workspace' => 'bantudelice'])],
                ['level' => 'warning', 'title' => 'Paiements en anomalie',  'message' => $data['paymentAnomaliesCount'] . ' transaction(s) signalent un statut de paiement a revoir.',    'count' => $data['paymentAnomaliesCount'],   'action_label' => 'Controler', 'action_url' => route('admin.payments.dashboard', ['workspace' => 'bantudelice'])],
            ],
            'live_activities'      => $data['liveActivities']->take(6),
            'operational_metrics'  => [
                ['label' => 'Volume vs hier',               'value' => $data['volumeVsYesterday']],
                ['label' => 'Taux d annulation',            'value' => $data['cancellationRate']],
                ['label' => 'Temps moyen de preparation',   'value' => $data['avgPrepTime']],
                ['label' => 'Temps moyen de livraison',     'value' => $data['avgDeliveryTime']],
            ],
        ];
    }

    private function buildKendeDashboard(array $data): array
    {
        return [
            'meta' => [
                'short_title'    => 'Kende',
                'page_title'     => 'Dashboard Kende',
                'eyebrow'        => 'Kende',
                'heading'        => 'Pilotage des trajets, chauffeurs et files d attribution',
                'intro'          => 'Le premier niveau doit remonter les courses a attribuer, les trajets actifs et la disponibilite terrain.',
                'global_state'   => $data['transportPendingCount'] >= 10 ? 'Sous tension' : 'Stable',
                'chart_title'    => 'Reservations — 30 derniers jours',
                'table_title'    => 'Courses recentes',
                'table_cta_label'=> 'Voir les reservations',
                'table_cta_url'  => route('admin.transport.bookings.index', ['workspace' => 'kende']),
                'breakdown_title'=> 'Repartition des verticales transport',
                'secondary_title'=> 'Queue transport',
                'alerts_title'   => 'Points de friction Kende',
                'metrics_title'  => 'Indicateurs transport',
                'chart_color'    => '#f97316',
                'chart_mode'     => 'count',
            ],
            'kpis' => [
                ['label' => 'Reservations',   'value' => number_format($data['transportVolumeCount'], 0, ',', ' '),                      'meta' => 'Periode selectionnee',    'icon' => 'fas fa-route'],
                ['label' => 'A dispatcher',   'value' => number_format($data['transportPendingCount'], 0, ',', ' '),                     'meta' => 'Demandes a attribuer',    'icon' => 'fas fa-broadcast-tower'],
                ['label' => 'Trajets actifs', 'value' => number_format($data['activeTripsCount'], 0, ',', ' '),                          'meta' => 'Courses en cours',        'icon' => 'fas fa-car-side'],
                ['label' => 'Revenu traite',  'value' => number_format($data['transportRevenueCurrent'], 0, ',', ' ') . ' FCFA',         'meta' => 'Transport cumule',        'icon' => 'fas fa-wallet'],
            ],
            'trend_chart'   => array_merge($data['transportTrend'], ['label' => 'Reservations', 'mode' => 'count', 'color' => '#f97316']),
            'primary_table' => [
                'headers' => ['Reference', 'Type', 'Zone', 'Responsable', 'Delai', 'Statut', 'Action'],
                'rows'    => collect($data['recentTransportBookings'])->map(fn ($b) => [
                    'reference'    => $b['reference'],
                    'type'         => $b['service_label'],
                    'zone'         => $b['detail'],
                    'owner'        => $b['subject_sub'],
                    'delay'        => $b['date'],
                    'status_label' => $b['status_label'],
                    'action_label' => 'Ouvrir',
                    'action_url'   => route('admin.transport.bookings.index', ['workspace' => 'kende']),
                ])->all(),
            ],
            'breakdown'      => $data['transportBreakdown'],
            'secondary_list' => [
                ['rank' => number_format($data['transportPendingCount'], 0, ',', ' '),        'title' => 'Courses a dispatcher',        'badge' => 'dispatch', 'meta' => 'Demandes a attribuer cote exploitation',    'action_label' => 'Ouvrir',    'action_url' => route('admin.transport.bookings.index', ['workspace' => 'kende'])],
                ['rank' => number_format($data['driversWithoutLocationCount'], 0, ',', ' '),  'title' => 'Profils flotte a verifier',   'badge' => 'flotte',   'meta' => 'Chauffeurs sans position exploitable',       'action_label' => 'Verifier',  'action_url' => route('admin.transport.vehicles.index', ['workspace' => 'kende'])],
                ['rank' => number_format($data['activeTripsCount'], 0, ',', ' '),             'title' => 'Trajets en suivi',            'badge' => 'live',     'meta' => 'Courses encore ouvertes dans le flux',       'action_label' => 'Suivre',    'action_url' => route('admin.transport.dashboard', ['workspace' => 'kende'])],
                ['rank' => number_format($data['transportRevenueCurrent'], 0, ',', ' ') . ' FCFA', 'title' => 'Revenu transport traite','badge' => 'finance',  'meta' => 'Montant cumule sur la periode',              'action_label' => 'Controler', 'action_url' => route('admin.payments.dashboard', ['workspace' => 'kende'])],
            ],
            'alerts' => [
                ['level' => 'warning', 'title' => 'Courses a dispatcher',    'message' => $data['transportPendingCount'] . ' reservation(s) attendent une attribution ou un retour.',    'count' => $data['transportPendingCount'],       'action_label' => 'Dispatcher', 'action_url' => route('admin.transport.bookings.index', ['workspace' => 'kende'])],
                ['level' => 'warning', 'title' => 'Trajets actifs',          'message' => $data['activeTripsCount'] . ' trajet(s) restent ouverts dans le flux live.',                    'count' => $data['activeTripsCount'],            'action_label' => 'Suivre',     'action_url' => route('admin.transport.dashboard', ['workspace' => 'kende'])],
                ['level' => 'warning', 'title' => 'Chauffeurs indisponibles','message' => $data['driversWithoutLocationCount'] . ' profil(s) doivent etre verifie(s) cote flotte.',     'count' => $data['driversWithoutLocationCount'], 'action_label' => 'Verifier',   'action_url' => route('admin.transport.vehicles.index', ['workspace' => 'kende'])],
                ['level' => 'warning', 'title' => 'Paiements a controler',   'message' => number_format($data['transportRevenueCurrent'], 0, ',', ' ') . ' FCFA traites sur la periode.','count' => $data['transportPendingCount'],       'action_label' => 'Controler',  'action_url' => route('admin.payments.dashboard', ['workspace' => 'kende'])],
            ],
            'live_activities'     => collect($data['recentTransportBookings'])->take(6)->map(fn ($b) => [
                'kind' => 'blue',
                'text' => 'Course ' . $b['reference'] . ' - ' . $b['status_label'],
                'time' => $b['date'],
            ]),
            'operational_metrics' => $data['transportOperationalMetrics'],
        ];
    }

    private function buildMemaDashboard(array $data): array
    {
        return [
            'meta' => [
                'short_title'    => 'Mema',
                'page_title'     => 'Dashboard Mema',
                'eyebrow'        => 'Mema',
                'heading'        => 'Pilotage des expeditions, statuts et preuves de remise',
                'intro'          => 'La vue d accueil doit remonter les colis a lancer, les flux en transit et les incidents logistiques.',
                'global_state'   => $data['shipmentsPendingCount'] >= 10 ? 'Sous tension' : 'Stable',
                'chart_title'    => 'Expeditions — 30 derniers jours',
                'table_title'    => 'Expeditions recentes',
                'table_cta_label'=> 'Voir les expeditions',
                'table_cta_url'  => route('admin.colis.index', ['workspace' => 'mema']),
                'breakdown_title'=> 'Etat des flux colis',
                'secondary_title'=> 'Queue logistique',
                'alerts_title'   => 'Points de friction Mema',
                'metrics_title'  => 'Indicateurs colis',
                'chart_color'    => '#e8a020',
                'chart_mode'     => 'count',
            ],
            'kpis' => [
                ['label' => 'Expeditions', 'value' => number_format($data['shipmentVolumeCount'], 0, ',', ' '),                'meta' => 'Periode selectionnee',       'icon' => 'fas fa-box-open'],
                ['label' => 'A lancer',    'value' => number_format($data['shipmentsPendingCount'], 0, ',', ' '),              'meta' => 'Colis en file d attente',    'icon' => 'fas fa-inbox'],
                ['label' => 'En transit',  'value' => number_format($data['activeShipmentsCount'], 0, ',', ' '),               'meta' => 'Flux logistique actif',      'icon' => 'fas fa-shipping-fast'],
                ['label' => 'COD suivi',   'value' => number_format($data['shipmentCodCurrent'], 0, ',', ' ') . ' FCFA',      'meta' => 'Montant encaisse',           'icon' => 'fas fa-money-bill-wave'],
            ],
            'trend_chart'   => array_merge($data['shipmentTrend'], ['label' => 'Expeditions', 'mode' => 'count', 'color' => '#e8a020']),
            'primary_table' => [
                'headers' => ['Reference', 'Type', 'Zone', 'Responsable', 'Delai', 'Statut', 'Action'],
                'rows'    => collect($data['recentShipments'])->map(fn ($s) => [
                    'reference'    => $s['reference'],
                    'type'         => $s['service_label'],
                    'zone'         => $s['detail'],
                    'owner'        => $s['subject_sub'],
                    'delay'        => $s['date'],
                    'status_label' => $s['status_label'],
                    'action_label' => 'Ouvrir',
                    'action_url'   => route('admin.colis.index', ['workspace' => 'mema']),
                ])->all(),
            ],
            'breakdown'      => $data['shipmentBreakdown'],
            'secondary_list' => [
                ['rank' => number_format($data['shipmentsPendingCount'], 0, ',', ' '),  'title' => 'Expeditions a lancer',  'badge' => 'queue',   'meta' => 'Tarification, paiement ou prise en charge',    'action_label' => 'Ouvrir',    'action_url' => route('admin.colis.index', ['workspace' => 'mema', 'status' => 'created'])],
                ['rank' => number_format($data['activeShipmentsCount'], 0, ',', ' '),   'title' => 'Flux en transit',       'badge' => 'transit', 'meta' => 'Colis encore actifs dans le reseau',           'action_label' => 'Suivre',    'action_url' => route('admin.colis.index', ['workspace' => 'mema'])],
                ['rank' => number_format($data['shipmentsDeliveredToday'], 0, ',', ' '),'title' => 'Remises confirmees',    'badge' => 'preuve',  'meta' => 'Livraisons cloturees aujourd hui',             'action_label' => 'Verifier',  'action_url' => route('admin.colis.index', ['workspace' => 'mema'])],
                ['rank' => number_format($data['shipmentCodCurrent'], 0, ',', ' ') . ' FCFA', 'title' => 'COD a reconcilier', 'badge' => 'finance', 'meta' => 'Encaissement colis sur la periode',         'action_label' => 'Controler', 'action_url' => route('admin.colis.finance', ['workspace' => 'mema'])],
            ],
            'alerts' => [
                ['level' => 'warning', 'title' => 'Colis a lancer',      'message' => $data['shipmentsPendingCount'] . ' expedition(s) attendent tarification, paiement ou prise en charge.', 'count' => $data['shipmentsPendingCount'],  'action_label' => 'Ouvrir',    'action_url' => route('admin.colis.index', ['workspace' => 'mema', 'status' => 'created'])],
                ['level' => 'warning', 'title' => 'Flux en circulation', 'message' => $data['activeShipmentsCount'] . ' colis restent actifs dans le reseau.',                                 'count' => $data['activeShipmentsCount'],   'action_label' => 'Suivre',    'action_url' => route('admin.colis.index', ['workspace' => 'mema'])],
                ['level' => 'warning', 'title' => 'Remises du jour',     'message' => $data['shipmentsDeliveredToday'] . ' remise(s) ont ete confirmees aujourd hui.',                         'count' => $data['shipmentsDeliveredToday'],'action_label' => 'Verifier',  'action_url' => route('admin.colis.index', ['workspace' => 'mema'])],
                ['level' => 'warning', 'title' => 'COD a reconcilier',   'message' => number_format($data['shipmentCodCurrent'], 0, ',', ' ') . ' FCFA encaisse(s) sur la periode.',           'count' => $data['shipmentsPendingCount'],  'action_label' => 'Controler', 'action_url' => route('admin.colis.finance', ['workspace' => 'mema'])],
            ],
            'live_activities'     => collect($data['recentShipments'])->take(6)->map(fn ($s) => [
                'kind' => 'gold',
                'text' => 'Colis ' . $s['reference'] . ' - ' . $s['status_label'],
                'time' => $s['date'],
            ]),
            'operational_metrics' => $data['shipmentOperationalMetrics'],
        ];
    }
}
