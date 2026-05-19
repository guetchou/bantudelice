@extends('layouts.app')
@section('title','Toutes les commandes')
@section('style')
<link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset('plugins/sweetalert2/sweetalert2.css')}}">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css">
@endsection
@section('order_nav', 'active')
@section('order_nav_open', 'menu-open')
@section('order_nav_all', 'active')

@php
    $ordersCollection = collect($orders instanceof \Illuminate\Contracts\Pagination\Paginator ? $orders->items() : $orders);
    $uniqueOrders = $ordersCollection->unique('order_no');
    $pendingOrders = $uniqueOrders->where('status', 'pending')->count();
    $assignedOrders = $uniqueOrders->where('status', 'assign')->count();
    $completedOrders = $uniqueOrders->where('status', 'completed')->count();
    $ordersRevenue = $ordersCollection->sum('total');
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
    <div class="content-header">
        <div class="container-fluid">
            @if(session()->has('alert'))
                <div class="alert alert-{{ session()->get('alert.type') }}">
                    {{ session()->get('alert.message') }}
                </div>
            @endif

            <div class="bd-ops-shell">
                <section class="bd-ops-hero">
                    <div>
                        <h1>Toutes les commandes</h1>
                        <p>Vue consolidee des commandes, des montants et des statuts pour le suivi d'exploitation.</p>
                    </div>
                    <div class="bd-ops-hero__actions">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-light">Retour au dashboard</a>
                    </div>
                </section>

                <section class="bd-ops-stat-grid">
                    <article class="bd-ops-stat-card is-orange">
                        <span>Commandes uniques</span>
                        <strong>{{ $uniqueOrders->count() }}</strong>
                        <small>Sur le jeu charge</small>
                    </article>
                    <article class="bd-ops-stat-card is-lemon">
                        <span>En attente</span>
                        <strong>{{ $pendingOrders }}</strong>
                        <small>Demandes a traiter</small>
                    </article>
                    <article class="bd-ops-stat-card is-dark">
                        <span>Livreurs assignes</span>
                        <strong>{{ $assignedOrders }}</strong>
                        <small>Preparation ou livraison</small>
                    </article>
                    <article class="bd-ops-stat-card is-soft">
                        <span>Montant visible</span>
                        <strong>{{ number_format($ordersRevenue, 0, ',', ' ') }} FCFA</strong>
                        <small>{{ $completedOrders }} terminees</small>
                    </article>
                </section>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card bd-ops-filter-card">
                <div class="card-body">
                    <form action="" method="get" class="row align-items-end">
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
                    <table class="table table-hover text-nowrap" id="example1">
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
    th { white-space: nowrap; }
    .bd-ops-shell { display: grid; gap: 20px; }
    .bd-ops-hero {
        display: flex; align-items: flex-end; justify-content: space-between; gap: 20px;
        padding: 30px 34px; border-radius: 34px;
        background: #1f242d;
        color: #e8eaf0; box-shadow: none;
        border: 1px solid rgba(255,255,255,.06);
    }
    .bd-ops-hero__eyebrow {
        margin: 0 0 8px; font-size: .78rem; text-transform: uppercase; letter-spacing: .18em;
        font-weight: 800; color: #1db860;
    }
    .bd-ops-hero h1 { margin: 0; color: #e8eaf0; font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 900; line-height: 1.03; max-width: 760px; }
    .bd-ops-hero p { margin: 14px 0 0; max-width: 760px; color: #b6bed0; line-height: 1.8; }
    .bd-ops-stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 18px; }
    .bd-ops-stat-card {
        padding: 22px; border-radius: 26px; background: rgba(255,255,255,.98);
        border: 1px solid rgba(249,115,22,.12); box-shadow: 0 12px 34px rgba(245,158,11,.08);
    }
    .bd-ops-stat-card span { display:block; color:#78716c; font-size:.86rem; font-weight:700; }
    .bd-ops-stat-card strong { display:block; margin-top:10px; font-size:2rem; line-height:1; font-weight:900; color:#111827; }
    .bd-ops-stat-card small { display:block; margin-top:8px; color:#a8a29e; }
    .bd-ops-stat-card.is-orange strong { color:#c2410c; }
    .bd-ops-stat-card.is-lemon strong { color:#65a30d; }
    .bd-ops-stat-card.is-dark strong { color:#111827; }
    .bd-ops-stat-card.is-soft strong { color:#b45309; }
    .bd-ops-filter-card,
    .bd-ops-table-card { border:1px solid rgba(249,115,22,.12) !important; border-radius:28px !important; box-shadow:0 14px 36px rgba(245,158,11,.08) !important; overflow:hidden; }
    .bd-ops-label { display:block; margin-bottom:10px; color:#78716c; font-weight:800; font-size:.82rem; text-transform:uppercase; letter-spacing:.08em; }
    .bd-ops-primary-btn {
        min-height: 48px; border:0; border-radius:16px; font-weight:800; color:#fff;
        background: linear-gradient(135deg, #ff5a1f 0%, #f59e0b 58%, #a3e635 100%);
        box-shadow: 0 14px 30px rgba(249,115,22,.24);
    }
    .bd-ops-table-card__header { display:flex; align-items:flex-end; justify-content:space-between; gap:18px; }
    .bd-ops-table-card__header h3 { margin:0; font-size:1.3rem; font-weight:900; color:#111827; }
    .bd-ops-table-card__header p { margin:8px 0 0; color:#78716c; line-height:1.7; }
    .bd-ops-table-card__badge {
        display:inline-flex; align-items:center; min-height:44px; padding:0 16px; border-radius:999px;
        background:#fff7ed; color:#c2410c; font-weight:800;
    }
    .bd-ops-status {
        display:inline-flex; align-items:center; min-height:34px; padding:0 12px; border-radius:999px;
        font-size:.78rem; font-weight:800; text-transform:capitalize;
    }
    .bd-ops-status.is-orange { background:#ffedd5; color:#c2410c; }
    .bd-ops-status.is-lemon { background:#fef9c3; color:#4d7c0f; }
    .bd-ops-status.is-success { background:#dcfce7; color:#007836; }
    .bd-ops-status.is-danger { background:#fee2e2; color:#b91c1c; }
    .bd-ops-status.is-soft { background:#f3f4f6; color:#374151; }
    .bd-chat-badge {
        display:inline-flex; align-items:center; width:fit-content; padding:5px 10px; border-radius:999px;
        background:#fff7ed; color:#c2410c; font-size:.74rem; font-weight:800;
    }
    @media (max-width: 992px) {
        .bd-ops-hero,
        .bd-ops-table-card__header { flex-direction: column; align-items: flex-start; }
        .bd-ops-stat-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    }
    @media (max-width: 576px) {
        .bd-ops-stat-grid { grid-template-columns: 1fr; }
        .bd-ops-hero { padding: 24px; }
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
