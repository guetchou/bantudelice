@extends('layouts.restaurant_app')
@section('title', 'Commandes terminées | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Commandes terminées')
@section('order_nav', 'active')

@section('content')
@include('restaurant.order._ord_shared', ['activeTab' => 'complete'])

<div class="ord" style="margin-top:20px;">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Filtre date --}}
    <div class="ord-toolbar">
        <form method="get" action="" style="display:contents;">
            <label class="ord-dateinput">
                <i class="fas fa-calendar-range"></i>
                <input type="text" name="date" id="ordDateRange"
                       value="{{ request('date','') }}" placeholder="Filtrer par période…" autocomplete="off">
            </label>
            <button type="submit" class="ord-btn ord-btn--outline"><i class="fas fa-filter"></i> Filtrer</button>
            @if(request('date'))
                <a href="{{ route('restaurant.complete_orders') }}" class="ord-btn ord-btn--outline">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
            @endif
        </form>
        <span style="font-size:12px;color:var(--bd-text-3);">{{ $orders->count() }} commande(s)</span>
    </div>

    {{-- Tableau --}}
    <div class="ord-card">
        <div class="ord-card__head">
            <div>
                <div class="ord-card__title">Commandes terminées</div>
                <div class="ord-card__meta">Livrées et complétées</div>
            </div>
            <div>
                <div class="ord-total">{{ number_format((float) $orders->sum('total'), 0, ',', ' ') }} <small>FCFA</small></div>
                <div style="font-size:10px;color:var(--bd-text-3);text-align:right;margin-top:2px;">Total période</div>
            </div>
        </div>
        <div class="ord-table-wrap">
            @if($orders->count() > 0)
                <table class="ord-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th class="ord-col-hide">Client</th>
                            <th>Montant</th>
                            <th class="ord-col-hide">Adresse</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>
                                    <span class="ord-ref">{{ $order->order_no }}</span>
                                    <span class="ord-ref-time">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m · H:i') }}</span>
                                </td>
                                <td class="ord-col-hide">{{ $order->user->name ?? $order->customer_name ?? '—' }}</td>
                                <td>
                                    <span class="ord-amount">{{ number_format((float) $order->total, 0, ',', ' ') }}</span>
                                    <span class="ord-amount-cur">FCFA</span>
                                </td>
                                <td class="ord-col-hide">
                                    <div class="ord-address" title="{{ $order->delivery_address }}">{{ $order->delivery_address ?? '—' }}</div>
                                </td>
                                <td><span class="ord-badge ord-badge--done">Terminée</span></td>
                                <td>
                                    <a href="{{ route('restaurant.show_order', $order->order_no) }}" class="ord-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="ord-empty">
                    <i class="fas fa-check-circle" style="color:var(--bd-green);"></i>
                    Aucune commande terminée sur cette période
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
$(function () {
    if ($.fn.daterangepicker) {
        $('#ordDateRange').daterangepicker({
            autoUpdateInput: false,
            locale: { cancelLabel:'Vider', applyLabel:'Appliquer', format:'DD/MM/YYYY',
                      daysOfWeek:['Di','Lu','Ma','Me','Je','Ve','Sa'],
                      monthNames:['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
                      firstDay:1 }
        });
        $('#ordDateRange').on('apply.daterangepicker', function(ev,p){ $(this).val(p.startDate.format('DD/MM/YYYY')+' - '+p.endDate.format('DD/MM/YYYY')); });
        $('#ordDateRange').on('cancel.daterangepicker', function(){ $(this).val(''); });
    }
});
</script>
@endsection
