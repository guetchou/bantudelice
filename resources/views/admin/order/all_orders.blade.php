@extends('layouts.admin-modern')
@section('title','Toutes les commandes')
@section('page_title', 'Toutes les commandes')
@section('nav_active', 'orders')
@section('style')
<link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset('plugins/sweetalert2/sweetalert2.css')}}">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css">
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
    $recentOrders = $uniqueOrders->take(5)->values();
    $queueItems = [
        [
            'title' => $acceptanceQueue . ' commandes attendent une validation',
            'body' => 'Priorite sur la prise en charge restaurant et la verification des refus.',
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
        'assign' => 'Livreur assigne',
        'driver_assigned' => 'Livreur assigne',
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
        <div class="container-fluid">
            @if(session()->has('alert'))
                <div class="alert alert-{{ session()->get('alert.type') }}">
                    {{ session()->get('alert.message') }}
                </div>
            @endif

            <div class="bd-orders-shell">
                <section class="ops-context">
                    <div class="ops-panel">
                        <div class="ops-row">
                            <div>
                                <div class="ops-title">Ops commandes</div>
                                <div class="ops-sub">Execution terrain, arbitrage restaurant et suivi des exceptions sur le flux food.</div>
                            </div>
                            <div class="ops-meta">
                                <span class="ops-chip ops-chip--soft">Food ops</span>
                                <span class="ops-pill {{ $acceptanceQueue > 0 ? 'ops-pill--danger' : 'ops-pill--ok' }}">{{ $acceptanceQueue > 0 ? $acceptanceQueue . ' en attente' : 'Flux stable' }}</span>
                            </div>
                        </div>
                        <div class="ops-strip">
                            <div class="ops-group">
                                <strong>Menus</strong>
                                <span class="ops-chip">Dashboard</span>
                                <span class="ops-chip">Commandes</span>
                                <span class="ops-chip">Restaurants</span>
                                <span class="ops-chip">Livreurs</span>
                                <span class="ops-chip">Incidents</span>
                                <span class="ops-chip">Support</span>
                            </div>
                            <div class="ops-group">
                                <strong>Files</strong>
                                <span class="ops-chip">{{ $pendingOrders }} en attente</span>
                                <span class="ops-chip">{{ $assignedOrders }} en cours</span>
                                <span class="ops-chip">{{ $cancelledOrders }} echecs</span>
                                <span class="ops-chip">{{ $ordersWithUnreadChat }} chats</span>
                            </div>
                        </div>
                        <div class="ops-kpi-grid">
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
                                <div class="ops-kpi__sub">{{ number_format($completedOrders, 0, ',', ' ') }} deja terminees</div>
                            </div>
                            <div class="ops-kpi">
                                <div class="ops-kpi__label">Exceptions</div>
                                <div class="ops-kpi__value">{{ number_format($ordersWithoutDriver + $ordersWithoutRestaurant + $cancelledOrders, 0, ',', ' ') }}</div>
                                <div class="ops-kpi__sub">Restaurant, livreur ou statut a arbitrer</div>
                            </div>
                        </div>
                    </div>
                    <div class="ops-panel ops-alerts">
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
                </section>

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
                                            <div class="text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
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

            <div class="card bd-ops-filter-card">
                <div class="card-body">
                    <form action="{{ route('admin.all_orders') }}" method="get" class="row align-items-end">
                        <div class="col-lg-8 mb-3 mb-lg-0">
                            <label class="bd-ops-label" for="reservationtime">Periode</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                                <input type="text" class="form-control float-right" name="date" id="reservationtime" placeholder="Selectionner une periode">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <button class="btn bd-ops-primary-btn" type="submit"><i class="fas fa-search mr-1"></i>Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card bd-ops-table-card">
                <div class="card-header border-0">
                    <div class="bd-ops-table-card__header">
                        <div>
                            <h3>Flux commandes</h3>
                            <p>Commandes, restaurant, client et statut reunis dans une meme vue de pilotage.</p>
                        </div>
                        <span class="bd-ops-table-card__badge">Total {{ number_format($ordersRevenue, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover" id="example1">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Commande</th>
                            <th>Client</th>
                            <th>Restaurant</th>
                            <th>Montant</th>
                            <th>Recu le</th>
                            <th>Statut</th>
                            <th class="text-right">Action</th>
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
                                <td class="text-right">
                                    <a href="{{route('admin.show_order',$order->order_no)}}" class="btn btn-sm btn-outline-primary">Ouvrir</a>
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
    .bd-ops-filter-card,
    .bd-ops-table-card { border:1px solid rgba(249,115,22,.12) !important; border-radius:16px !important; box-shadow:0 8px 20px rgba(245,158,11,.06) !important; overflow:hidden; }
    .bd-ops-label { display:block; margin-bottom:8px; color:#78716c; font-weight:800; font-size:.76rem; text-transform:uppercase; letter-spacing:.08em; }
    .bd-ops-primary-btn {
        min-height: 40px; border:0; border-radius:12px; font-weight:800; color:#fff;
        background: linear-gradient(135deg, #ff5a1f 0%, #f59e0b 58%, #a3e635 100%);
        box-shadow: 0 8px 18px rgba(249,115,22,.18);
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
<script src="{{asset('plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<script src="{{asset('plugins/daterangepicker/daterangepicker.js')}}"></script>
<script src="{{asset('plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js')}}"></script>
<script src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
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
@endsection
