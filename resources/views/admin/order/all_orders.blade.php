@extends('layouts.admin-modern')
@section('title','Toutes les commandes')
@section('page_title', 'Toutes les commandes')
@section('nav_active', 'orders')
@section('style')
@endsection

@php
    $ordersCollection = collect($orders instanceof \Illuminate\Contracts\Pagination\Paginator ? $orders->items() : $orders);
    $uniqueOrders = $ordersCollection->unique('order_no');
    $assignedStatuses = ['assign', 'driver_assigned', 'out_for_delivery', 'picked_up'];
    $criticalStatuses = ['pending', 'pending_restaurant_acceptance', 'cancelled', 'canceled', 'failed'];
    $pendingOrders = $uniqueOrders->where('status', 'pending')->count();
    $acceptanceQueue = $uniqueOrders->filter(fn ($order) => in_array(strtolower((string) ($order->business_status ?? $order->status)), ['pending', 'pending_restaurant_acceptance']))->count();
    $assignedOrders = $uniqueOrders->filter(fn ($order) => in_array(strtolower((string) ($order->business_status ?? $order->status)), $assignedStatuses))->count();
    $completedOrders = $uniqueOrders->filter(fn ($order) => in_array(strtolower((string) ($order->business_status ?? $order->status)), ['completed', 'delivered']))->count();
    $cancelledOrders = $uniqueOrders->filter(fn ($order) => in_array(strtolower((string) ($order->business_status ?? $order->status)), ['cancelled', 'canceled', 'failed']))->count();
    $ordersRevenue = $ordersCollection->sum('total');
    $averageBasket = $uniqueOrders->count() > 0 ? $ordersRevenue / $uniqueOrders->count() : 0;
    $ordersWithoutRestaurant = $uniqueOrders->filter(fn ($order) => empty(optional($order->restaurant)->name))->count();
    $ordersWithoutDriver = $uniqueOrders->filter(fn ($order) => empty(optional($order->driver)->name) && empty(optional(optional($order->delivery)->driver)->name))->count();
    $ordersWithUnreadChat = $uniqueOrders->filter(fn ($order) => !empty($order->chatBadge['has_unread']))->count();
    $cashDisputesCount = \App\Order::whereIn('cash_collection_status', ['disputed', 'collection_failed'])->distinct('order_no')->count('order_no');
    $recentOrders = $uniqueOrders->take(5)->values();
    $queueItems = [
        [
            'title' => $acceptanceQueue . ' commandes attendent une validation',
            'body' => 'Priorité sur la prise en charge restaurant et la vérification des refus.',
            'class' => $acceptanceQueue > 0 ? 'ops-queue-dot--danger' : 'ops-queue-dot--ok',
        ],
        [
            'title' => $ordersWithoutDriver . ' commandes sans livreur',
            'body' => 'Affectation a surveiller sur les zones tendues et les heures de pointe.',
            'class' => $ordersWithoutDriver > 0 ? 'ops-queue-dot--warn' : 'ops-queue-dot--ok',
        ],
        [
            'title' => $ordersWithUnreadChat . ' conversations non traitees',
            'body' => 'Messages clients ou restaurants encore ouverts sur le flux commande.',
            'class' => $ordersWithUnreadChat > 0 ? 'ops-queue-dot--warn' : 'ops-queue-dot--ok',
        ],
    ];
    $statusLabels = [
        'pending' => 'En attente',
        'pending_restaurant_acceptance' => 'En attente de validation restaurant',
        'accepted' => 'Acceptee',
        'assign' => 'Livreur assigné',
        'driver_assigned' => 'Livreur assigné',
        'in_kitchen' => 'En preparation',
        'ready_for_pickup' => 'Pret pour retrait',
        'picked_up' => 'Recuperee',
        'picked_up_by_customer' => 'Retiree par le client',
        'out_for_delivery' => 'En cours de livraison',
        'delivered' => 'Livree',
        'completed' => 'Terminee',
        'cancelled' => 'Annulee',
        'canceled' => 'Annulee',
        'failed' => 'Echouee',
    ];
@endphp

@section('content')
    <section class="content">
        <div>
            @if(session()->has('alert'))
                <div class="bd-ops-alert bd-ops-alert--{{ session()->get('alert.type') }}" style="padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:14px;">
                    {{ session()->get('alert.message') }}
                </div>
            @endif

            <div class="bd-orders-shell">
                <div class="adm-page-bar">
                    <div class="adm-page-bar__left">
                        <nav class="adm-page-bar__breadcrumb">
                            <span>Operations</span><span class="sep">/</span><span>Commandes</span>
                        </nav>
                        <h1 class="adm-page-bar__title">Commandes</h1>
                    </div>
                    <div class="adm-page-bar__right">
                        <span class="adm-page-bar__badge {{ $acceptanceQueue > 0 ? 'adm-page-bar__badge--danger' : 'adm-page-bar__badge--ok' }}">{{ $acceptanceQueue > 0 ? $acceptanceQueue . ' en attente' : 'Flux stable' }}</span>
                    </div>
                </div>
                <div class="ops-kpi-grid" style="margin-bottom:1rem;">
                    <div class="ops-kpi">
                        <div class="ops-kpi__label">Commandes visibles</div>
                        <div class="ops-kpi__value">{{ number_format($uniqueOrders->count(), 0, ',', ' ') }}</div>
                        <div class="ops-kpi__sub">Perimetre charge sur l ecran</div>
                    </div>
                    <div class="ops-kpi">
                        <div class="ops-kpi__label">Montant traite</div>
                        <div class="ops-kpi__value">{{ number_format($ordersRevenue, 0, ',', ' ') }} FCFA</div>
                        <div class="ops-kpi__sub">Panier moyen {{ number_format($averageBasket, 0, ',', ' ') }} FCFA</div>
                    </div>
                    <div class="ops-kpi">
                        <div class="ops-kpi__label">En execution</div>
                        <div class="ops-kpi__value">{{ number_format($assignedOrders, 0, ',', ' ') }}</div>
                        <div class="ops-kpi__sub">{{ number_format($completedOrders, 0, ',', ' ') }} déjà terminées</div>
                    </div>
                    <div class="ops-kpi">
                        <div class="ops-kpi__label">Exceptions</div>
                        <div class="ops-kpi__value">{{ number_format($ordersWithoutDriver + $ordersWithoutRestaurant + $cancelledOrders, 0, ',', ' ') }}</div>
                        <div class="ops-kpi__sub">Restaurant, livreur ou statut a arbitrer</div>
                    </div>
                </div>
                <div class="ops-panel ops-alerts" style="margin-bottom:1rem;">
                    @foreach($queueItems as $item)
                        <article class="ops-alert">
                            <div class="ops-alert__top">
                                <h3>{{ $item['title'] }}</h3>
                                <span class="ops-tag {{ str_contains($item['class'], 'danger') ? 'ops-tag--danger' : (str_contains($item['class'], 'warn') ? 'ops-tag--warn' : 'ops-pill--ok') }}">
                                    {{ str_contains($item['class'], 'danger') ? 'Critique' : (str_contains($item['class'], 'warn') ? 'Attention' : 'Stable') }}
                                </span>
                            </div>
                            <p>{{ $item['body'] }}</p>
                        </article>
                    @endforeach
                </div>

                <section class="ops-grid ops-grid--2">
                    <div class="ops-card">
                        <div class="ops-card__header">
                            <div>
                                <h2>Incidents a traiter</h2>
                                <p>Ce qui bloque la promesse de service en priorite.</p>
                            </div>
                        </div>
                        <div class="ops-queue">
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot {{ $acceptanceQueue > 0 ? 'ops-queue-dot--danger' : 'ops-queue-dot--ok' }}"></span>
                                <div>
                                    <h3>Validation restaurant</h3>
                                    <p>{{ $acceptanceQueue }} commandes attendent encore une decision cote restaurant.</p>
                                </div>
                                <a href="{{ route('admin.pending_orders') }}">Ouvrir</a>
                            </div>
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot {{ $ordersWithoutDriver > 0 ? 'ops-queue-dot--warn' : 'ops-queue-dot--ok' }}"></span>
                                <div>
                                    <h3>Affectation livreur</h3>
                                    <p>{{ $ordersWithoutDriver }} commandes restent sans livreur ou sans trace d affectation exploitable.</p>
                                </div>
                                <a href="{{ route('admin.all_orders') }}">Suivre</a>
                            </div>
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot {{ $cancelledOrders > 0 ? 'ops-queue-dot--warn' : 'ops-queue-dot--ok' }}"></span>
                                <div>
                                    <h3>Annulations et echecs</h3>
                                    <p>{{ $cancelledOrders }} commandes requierent une revue de cause ou un retour support.</p>
                                </div>
                                <a href="{{ route('admin.cancel_orders') }}">Revoir</a>
                            </div>
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot {{ $cashDisputesCount > 0 ? 'ops-queue-dot--danger' : 'ops-queue-dot--ok' }}"></span>
                                <div>
                                    <h3>Litiges encaissement cash</h3>
                                    <p>{{ $cashDisputesCount }} commande(s) avec un litige ou un echec de collecte a trancher.</p>
                                </div>
                                <a href="{{ route('admin.cash_disputes') }}">Traiter</a>
                            </div>
                        </div>
                    </div>
                    <div class="ops-card">
                        <div class="ops-card__header">
                            <div>
                                <h2>Suivi court</h2>
                                <p>Lecture rapide avant arbitrage.</p>
                            </div>
                        </div>
                        <div class="ops-stats-grid">
                            <div class="ops-stat">
                                <strong>{{ number_format($pendingOrders, 0, ',', ' ') }}</strong>
                                <span>Demandes en attente immediate</span>
                            </div>
                            <div class="ops-stat">
                                <strong>{{ number_format($completedOrders, 0, ',', ' ') }}</strong>
                                <span>Commandes bouclees</span>
                            </div>
                            <div class="ops-stat">
                                <strong>{{ number_format($ordersWithoutRestaurant, 0, ',', ' ') }}</strong>
                                <span>Lignes sans restaurant resolu</span>
                            </div>
                            <div class="ops-stat">
                                <strong>{{ number_format($ordersWithUnreadChat, 0, ',', ' ') }}</strong>
                                <span>Conversations encore ouvertes</span>
                            </div>
                        </div>
                        <div class="ops-trend">
                            <svg viewBox="0 0 300 80" preserveAspectRatio="none" aria-hidden="true">
                                <path d="M0,62 C30,56 42,26 76,28 C112,30 118,56 148,50 C182,42 188,18 220,20 C252,22 266,40 300,26" fill="none" stroke="#009543" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </section>

                <section class="ops-grid ops-grid--2">
                    <div class="ops-card">
                        <div class="ops-card__header">
                            <div>
                                <h2>Actions requises</h2>
                                <p>Routines prioritaires du poste ops commandes.</p>
                            </div>
                        </div>
                        <div class="ops-queue">
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot ops-queue-dot--danger"></span>
                                <div>
                                    <h3>Traiter les validations restaurant</h3>
                                    <p>Priorite sur les commandes en attente de confirmation pour limiter le delai initial.</p>
                                </div>
                                <a href="{{ route('admin.pending_orders') }}">Traiter</a>
                            </div>
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot ops-queue-dot--warn"></span>
                                <div>
                                    <h3>Surveiller les conversations commande</h3>
                                    <p>Eviter qu un client ou un restaurant reste sans reponse sur un ordre actif.</p>
                                </div>
                                <a href="{{ route('admin.all_orders') }}">Suivre</a>
                            </div>
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot ops-queue-dot--ok"></span>
                                <div>
                                    <h3>Verifier les sorties du jour</h3>
                                    <p>Comparer volume execute, affectation livreur et panier moyen visible.</p>
                                </div>
                                <a href="{{ route('admin.dashboard') }}">Retour</a>
                            </div>
                        </div>
                    </div>
                    <div class="ops-card">
                        <div class="ops-card__header">
                            <div>
                                <h2>Tableau principal</h2>
                                <p>Lecture directe des lignes recentes et de leur statut operationnel.</p>
                            </div>
                        </div>
                        <div class="ops-table-wrap">
                            <table class="ops-table">
                                <thead>
                                <tr>
                                    <th>Commande</th>
                                    <th>Client</th>
                                    <th>Restaurant</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($recentOrders as $order)
                                    @php
                                        $statusValue = strtolower((string) ($order->business_status ?? $order->status));
                                        $statusClass = in_array($statusValue, ['completed', 'delivered']) ? 'ops-pill--ok' : (in_array($statusValue, $criticalStatuses) ? 'ops-pill--danger' : 'ops-pill--warn');
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $order->order_no }}</strong>
                                            <div style="font-size:11px;color:#9ca3af;">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td>{{ optional($order->user)->name ?? 'N/A' }}</td>
                                        <td>{{ optional($order->restaurant)->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                                        <td><span class="ops-pill {{ $statusClass }}">{{ $statusLabels[$statusValue] ?? ucfirst(str_replace('_', ' ', $statusValue)) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">Aucune commande disponible.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <div class="bd-ops-filter-card">
                <label class="bd-ops-label" for="reservationtime">Filtrer par période</label>
                <form action="{{ route('admin.all_orders') }}" method="get" class="bd-ops-filter-row">
                    <div class="bd-ops-filter-input">
                        <i class="far fa-clock"></i>
                        <input type="text" name="date" id="reservationtime" placeholder="Sélectionner une période…">
                    </div>
                    <button class="bd-ops-primary-btn" type="submit"><i class="fas fa-search"></i> Filtrer</button>
                </form>
            </div>

            <div class="bd-ops-table-card">
                <div class="bd-ops-table-card__header">
                    <div>
                        <h3>Flux commandes</h3>
                        <p>Commandes, restaurant, client et statut reunis dans une meme vue de pilotage.</p>
                    </div>
                    <span class="bd-ops-table-card__badge">Total {{ number_format($ordersRevenue, 0, ',', ' ') }} FCFA</span>
                </div>
                <div style="overflow-x:auto;">
                    <table id="example1">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Commande</th>
                            <th>Client</th>
                            <th>Restaurant</th>
                            <th>Montant</th>
                            <th>Recu le</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $index => $order)
                            @php
                                $statusValue = $order->business_status ?? $order->status;
                                $statusClass = 'is-soft';
                                if (in_array($statusValue, ['completed', 'delivered'])) {
                                    $statusClass = 'is-success';
                                } elseif (in_array($statusValue, ['assign', 'driver_assigned', 'out_for_delivery', 'picked_up'])) {
                                    $statusClass = 'is-orange';
                                } elseif (in_array($statusValue, ['pending', 'pending_restaurant_acceptance'])) {
                                    $statusClass = 'is-lemon';
                                } elseif (in_array($statusValue, ['cancelled', 'failed'])) {
                                    $statusClass = 'is-danger';
                                }
                            @endphp
                            <tr>
                                <td>{{ ++$index }}</td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:0.35rem;">
                                        <strong>{{ $order->order_no }}</strong>
                                        @if(!empty($order->chatBadge['has_unread']))
                                            <span class="bd-chat-badge">{{ $order->chatBadge['label'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $order->user->name ?? 'N/A' }}</td>
                                <td>{{ $order->restaurant->name ?? 'N/A' }}</td>
                                <td>{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                                <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                                <td><span class="bd-ops-status {{ $statusClass }}">{{ $statusLabels[strtolower((string) $statusValue)] ?? ucfirst(str_replace('_', ' ', strtolower((string) $statusValue))) }}</span></td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a href="{{route('admin.show_order',$order->order_no)}}" class="bd-ops-action-btn" title="Voir détail"><i class="fas fa-eye"></i></a>
                                    @if(!in_array(strtolower((string) ($order->business_status ?? $order->status ?? '')), ['cancelled','canceled','delivered','picked_up_by_customer','closed']))
                                        <button type="button"
                                            class="bd-ops-action-btn bd-ops-action-btn--danger"
                                            title="Annuler la commande"
                                            onclick="bdOpenCancelModal('{{ $order->id }}','{{ $order->order_no }}')"
                                            style="margin-left:4px;background:#fef2f2;color:#ef4444;border-color:#fecaca;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="4"></th>
                            <th>Total:</th>
                            <th colspan="3"></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>


<style>
    .bd-orders-shell { display:grid; gap:14px; margin-bottom:14px; }
    .bd-ops-filter-card {
        background:#fff; border:1px solid rgba(249,115,22,.12); border-radius:16px;
        box-shadow:0 8px 20px rgba(245,158,11,.06); padding:16px 20px; margin-bottom:14px;
    }
    .bd-ops-filter-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:8px; }
    .bd-ops-filter-input {
        display:flex; align-items:center; gap:8px; flex:1; min-width:220px;
        border:1px solid #d1d5db; border-radius:8px; padding:9px 12px; background:#fff;
    }
    .bd-ops-filter-input i { color:#9ca3af; }
    .bd-ops-filter-input input { border:none; outline:none; font-size:13px; color:#111827; background:transparent; width:100%; font-family:'Manrope',sans-serif; }
    .bd-ops-table-card { border:1px solid rgba(249,115,22,.12); border-radius:16px; box-shadow:0 8px 20px rgba(245,158,11,.06); overflow:hidden; margin-bottom:14px; }
    .bd-ops-table-card .bd-ops-table-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; }
    .bd-ops-label { display:block; margin-bottom:8px; color:#78716c; font-weight:800; font-size:.76rem; text-transform:uppercase; letter-spacing:.08em; }
    #example1 { width:100%; border-collapse:collapse; font-size:13px; }
    #example1 thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
    #example1 tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
    #example1 tbody tr:last-child { border-bottom:none; }
    #example1 tbody tr:hover { background:#fafaf9; }
    #example1 td { padding:11px 14px; color:#374151; vertical-align:middle; }
    #example1 tfoot th { padding:9px 14px; font-size:12px; font-weight:700; color:#374151; border-top:2px solid #e5e7eb; }
    .bd-ops-primary-btn {
        display:inline-flex; align-items:center; gap:6px;
        padding:9px 18px; border:0; border-radius:12px; font-weight:800; font-size:13px; color:#fff;
        background: linear-gradient(135deg, #ff5a1f 0%, #f59e0b 58%, #a3e635 100%);
        box-shadow: 0 8px 18px rgba(249,115,22,.18); cursor:pointer; white-space:nowrap;
    }
    .bd-ops-table-card__header { display:flex; align-items:flex-end; justify-content:space-between; gap:14px; }
    .bd-ops-table-card__header h3 { margin:0; font-size:1.05rem; font-weight:900; color:#111827; }
    .bd-ops-table-card__header p { margin:6px 0 0; color:#78716c; line-height:1.5; }
    .bd-ops-table-card__badge {
        display:inline-flex; align-items:center; min-height:34px; padding:0 12px; border-radius:999px;
        background:#fff7ed; color:#c2410c; font-weight:800;
    }
    .bd-ops-status {
        display:inline-flex; align-items:center; min-height:28px; padding:0 10px; border-radius:999px;
        font-size:.72rem; font-weight:800; text-transform:capitalize; line-height:1.35; white-space:normal;
    }
    .bd-ops-status.is-orange { background:#ffedd5; color:#c2410c; }
    .bd-ops-status.is-lemon { background:#fef9c3; color:#4d7c0f; }
    .bd-ops-status.is-success { background:#dcfce7; color:#007836; }
    .bd-ops-status.is-danger { background:#fee2e2; color:#b91c1c; }
    .bd-ops-status.is-soft { background:#f3f4f6; color:#374151; }
    .bd-chat-badge {
        display:inline-flex; align-items:center; width:fit-content; padding:4px 8px; border-radius:999px;
        background:#fff7ed; color:#c2410c; font-size:.68rem; font-weight:800;
    }
    @media (max-width: 992px) {
        .bd-ops-table-card__header { flex-direction: column; align-items: flex-start; }
    }
</style>
@endsection
@section('script')
<script src="{{asset('plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{asset('plugins/daterangepicker/daterangepicker.js')}}"></script>
<script>
    $(function () {
        $("#example1").DataTable({
            dom: 'Bfrtip',
            language: window.bdAdminDataTableLanguage,
            buttons: window.bdAdminExportButtons(true),
            "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api(), data;
                var intVal = function ( i ) {
                    return window.bdAdminParseMoney(i);
                };
                total = api.column( 4 ).data().reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );
                pageTotal = api.column( 4, { page: 'current'} ).data().reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );
                $( api.column( 5 ).footer() ).html(window.bdAdminMoneyFooterText(pageTotal, total));
            }
        });
        $('#reservationtime').daterangepicker({
            timePicker: true,
            timePickerIncrement: 30,
            locale: {
                format: 'DD/MM/YYYY HH:mm',
                applyLabel: 'Appliquer',
                cancelLabel: 'Annuler',
                fromLabel: 'Du',
                toLabel: 'Au',
                customRangeLabel: 'Personnalisee'
            }
        });
    });
</script>

{{-- Modal d'annulation partagé (all_orders) --}}
<div id="bd-cancel-modal-bg" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;"
     onclick="if(event.target===this) bdCloseCancelModal()">
    <div style="background:#fff;border-radius:14px;padding:28px 28px 24px;max-width:480px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;"><i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> Annuler la commande</h3>
        <p id="bd-cancel-modal-sub" style="font-size:13px;color:#6b7280;margin:0 0 16px;"></p>
        <form id="bd-cancel-modal-form" method="POST" action="">
            @csrf
            <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Motif d'annulation <span style="color:#ef4444;">*</span></label>
            <textarea name="reason" id="bd-cancel-reason" required minlength="5" maxlength="500"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:7px;font-size:13px;resize:vertical;min-height:80px;font-family:inherit;box-sizing:border-box;"
                placeholder="Ex: Commande en doublon, problème de paiement, demande du client…"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
                <button type="button" onclick="bdCloseCancelModal()"
                    style="padding:9px 18px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;">
                    Fermer
                </button>
                <button type="submit"
                    style="padding:9px 22px;background:#ef4444;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;">
                    Confirmer l'annulation
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function bdOpenCancelModal(orderId, orderNo) {
    // Construction DOM sans innerHTML pour éviter XSS
    var sub = document.getElementById('bd-cancel-modal-sub');
    sub.textContent = '';
    var t1 = document.createTextNode('Commande #');
    var strong = document.createElement('strong');
    strong.textContent = orderNo;          // textContent échappe automatiquement
    var t2 = document.createTextNode(' — Cette action est irréversible. Le client sera notifié.');
    sub.appendChild(t1);
    sub.appendChild(strong);
    sub.appendChild(t2);

    // L'action du formulaire utilise l'ID numérique (non affiché à l'utilisateur)
    var safeId = parseInt(orderId, 10);
    if (!safeId) return;
    document.getElementById('bd-cancel-modal-form').action = '/admin/cancel_order/' + safeId;
    document.getElementById('bd-cancel-reason').value = '';
    document.getElementById('bd-cancel-modal-bg').style.display = 'flex';
}
function bdCloseCancelModal() {
    document.getElementById('bd-cancel-modal-bg').style.display = 'none';
}
</script>
@endsection
