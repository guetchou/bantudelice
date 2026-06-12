@extends('layouts.admin-modern')
@section('title','Commandes annulées')
@section('page_title', 'Commandes annulées')
@section('nav_active', 'orders')
@section('style')
<style>
.ord-wrap { display:flex; flex-direction:column; gap:16px; }

/* Alert */
.ord-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; }
.ord-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.ord-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.ord-alert--warning { background:#fefce8; color:#854d0e; border-color:#fde68a; }

/* Filter card */
.ord-filter { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px 20px; }
.ord-filter__label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; display:block; margin-bottom:6px; }
.ord-filter__row { display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap; }
.ord-filter__date { display:flex; align-items:center; gap:8px; flex:1; min-width:200px; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; background:#fff; }
.ord-filter__date i { color:#9ca3af; }
.ord-filter__input { border:none; outline:none; font-size:13px; color:#111827; background:transparent; width:100%; font-family:'Manrope',sans-serif; }
.ord-filter__btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
.ord-filter__btn:hover { background:#152d4a; }

/* Table card */
.ord-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.ord-card__head { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f3f4f6; flex-wrap:wrap; gap:8px; }
.ord-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.ord-card__count { font-size:11px; color:#6b7280; margin-top:2px; }

.ord-table-wrap { overflow-x:auto; }
.ord-table { width:100%; border-collapse:collapse; font-size:13px; }
.ord-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
.ord-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
.ord-table tbody tr:last-child { border-bottom:none; }
.ord-table tbody tr:hover { background:#f9fafb; }
.ord-table td { padding:11px 14px; color:#374151; vertical-align:middle; }
.ord-table tfoot th { padding:9px 14px; font-size:12px; font-weight:700; color:#374151; border-top:2px solid #e5e7eb; }

/* Status pill */
.ord-pill { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; white-space:nowrap; }
.ord-pill--ok      { background:#f0fdf4; color:#166534; }
.ord-pill--warn    { background:#fefce8; color:#854d0e; }
.ord-pill--danger  { background:#fef2f2; color:#991b1b; }
.ord-pill--soft    { background:#f3f4f6; color:#374151; }

/* Action btn */
.ord-btn-see { display:inline-flex; align-items:center; gap:4px; padding:5px 12px; border:1px solid #1e3a5f; border-radius:6px; color:#1e3a5f; font-size:12px; font-weight:600; text-decoration:none; transition:.12s; }
.ord-btn-see:hover { background:#1e3a5f; color:#fff; }
</style>
@endsection

@section('content')
<div class="ord-wrap" style="padding:20px;">

    @if(session()->has('alert'))
        <div class="ord-alert ord-alert--{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif

    {{-- Filtre --}}
    <div class="ord-filter">
        <span class="ord-filter__label">Filtrer par période</span>
        <form action="{{ route('admin.cancel_orders') }}" method="get">
            <div class="ord-filter__row">
                <div class="ord-filter__date">
                    <i class="far fa-clock"></i>
                    <input type="text" class="ord-filter__input" name="date" id="reservationtime" placeholder="Sélectionner une période…">
                </div>
                <button class="ord-filter__btn" type="submit">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="ord-card">
        <div class="ord-card__head">
            <div>
                <p class="ord-card__title">Commandes annulées</p>
                <p class="ord-card__count">{{ $orders->count() }} commande(s)</p>
            </div>
        </div>
        <div class="ord-table-wrap">
            <table class="ord-table" id="ord-table-cancel">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>N° commande</th>
                        <th>Client</th>
                        <th>Restaurant</th>
                        <th>Montant</th>
                        <th>Reçu le</th>
                        <th>Annulée par</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $index => $order)
                    <tr>
                        <td>{{ ++$index }}</td>
                        <td>{{ $order->order_no }}</td>
                        <td>{{ $order->user->name ?? '—' }}</td>
                        <td>{{ $order->restaurant->name ?? '—' }}</td>
                        <td>{{ number_format((float)($order->total ?? 0), 0, ',', ' ') }} FCFA</td>
                        <td>{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') : '—' }}</td>
                        <td>{{ $order->cancel_by }}</td>
                        <td><span class="ord-pill ord-pill--danger">{{ $order->status }}</span></td>
                        <td>
                            <a href="{{ route('admin.show_order', $order->order_no) }}" class="ord-btn-see" title="Voir">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Total :</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('plugins/datatables/jquery.dataTables.js') }}"></script>
<script>
    $(function () {
        $("#ord-table-cancel").DataTable({
            dom: 'Bfrtip',
            language: window.bdAdminDataTableLanguage,
            buttons: window.bdAdminExportButtons(true),
            "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api(), data;

                var intVal = function ( i ) {
                    return window.bdAdminParseMoney(i);
                };

                total = api
                    .column( 4 )
                    .data()
                    .reduce( function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0 );

                pageTotal = api
                    .column( 4, { page: 'current'} )
                    .data()
                    .reduce( function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0 );

                $( api.column( 6 ).footer() ).html(
                    window.bdAdminMoneyFooterText(pageTotal, total)
                );
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
