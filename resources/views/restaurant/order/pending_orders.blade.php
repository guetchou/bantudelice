@extends('layouts.restaurant_app')
@section('title', 'Commandes en livraison | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'En livraison')
@section('order_nav', 'active')

@section('content')
@include('restaurant.order._ord_shared', ['activeTab' => 'delivering'])

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
                <a href="{{ route('restaurant.pending_orders') }}" class="ord-btn ord-btn--outline">
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
                <div class="ord-card__title">Commandes en livraison</div>
                <div class="ord-card__meta">Assignées aux livreurs · en cours de route</div>
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
                            @php
                                $stMap = [
                                    'assigned'  => ['Assignée',  'delivering'],
                                    'picked_up' => ['Récupérée', 'delivering'],
                                    'on_the_way'=> ['En route',  'delivering'],
                                    'delivering'=> ['En route',  'delivering'],
                                ];
                                $st = $stMap[strtolower($order->status ?? '')] ?? ['En livraison', 'delivering'];
                            @endphp
                            <tr>
                                <td>
                                    <span class="ord-ref">{{ $order->order_no }}</span>
                                    <span class="ord-ref-time">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m · H:i') }}</span>
                                    @if(!empty($order->chatBadge['has_unread']))
                                        <div class="ord-chat"><i class="fas fa-comment-dots" style="font-size:9px;"></i> {{ $order->chatBadge['label'] }}</div>
                                    @endif
                                </td>
                                <td class="ord-col-hide">{{ $order->user->name ?? $order->customer_name ?? '—' }}</td>
                                <td>
                                    <span class="ord-amount">{{ number_format((float) $order->total, 0, ',', ' ') }}</span>
                                    <span class="ord-amount-cur">FCFA</span>
                                </td>
                                <td class="ord-col-hide">
                                    <div class="ord-address" title="{{ $order->delivery_address }}">{{ $order->delivery_address ?? '—' }}</div>
                                </td>
                                <td><span class="ord-badge ord-badge--{{ $st[1] }}">{{ $st[0] }}</span></td>
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
                    <i class="fas fa-truck" style="color:var(--bd-text-3);"></i>
                    Aucune commande en livraison actuellement
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

{{-- Polling nouvelles commandes (hérité de la page originale) --}}
<script>
(function () {
    var POLL_URL = "{{ route('restaurant.notifications.poll') }}";
    var POLL_MS  = 15000;
    var lastCount = -1;
    var audioUnlocked = false;
    var audioCtx = null;

    ['click','keydown','touchstart'].forEach(function(ev){
        document.addEventListener(ev, function(){ audioUnlocked = true; }, { once:true, passive:true });
    });

    function beep() {
        if (!audioUnlocked) return;
        try {
            var C = window.AudioContext || window.webkitAudioContext;
            if (!C) return;
            if (!audioCtx || audioCtx.state === 'closed') audioCtx = new C();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            var osc = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.connect(gain); gain.connect(audioCtx.destination);
            osc.frequency.value = 880;
            var t = audioCtx.currentTime;
            gain.gain.setValueAtTime(0.001, t);
            gain.gain.exponentialRampToValueAtTime(0.22, t + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.35);
            osc.start(t); osc.stop(t + 0.36);
        } catch (e) {}
    }

    function poll() {
        fetch(POLL_URL, { headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin', cache:'no-store' })
        .then(function(r){ return r.ok ? r.json() : null; })
        .then(function(data) {
            if (!data || !data.status) return;
            var count = parseInt(data.count) || 0;
            if (data.new && lastCount !== -1 && count > lastCount) {
                beep();
                if (typeof toastr !== 'undefined') toastr.warning('🔔 Nouvelle commande reçue !');
                setTimeout(function(){ window.location.reload(); }, 1200);
            }
            lastCount = count;
        }).catch(function(){});
    }

    poll();
    setInterval(poll, POLL_MS);

    @php $restaurantModel = auth()->user() ? \App\Restaurant::where('user_id', auth()->id())->first() : null; @endphp
    @if($restaurantModel && config('broadcasting.default') !== 'log')
    if (window.Echo) {
        window.Echo.private('food.restaurant.{{ $restaurantModel->id }}.orders')
            .listen('.food.order.status.updated', function(e) {
                if ((e.business_status||'') === 'pending_restaurant_acceptance') {
                    beep();
                    if (typeof toastr !== 'undefined') toastr.warning('🔔 Nouvelle commande reçue !');
                    setTimeout(function(){ window.location.reload(); }, 1200);
                }
            });
    }
    @endif
}());
</script>
@endsection
