@extends('layouts.driver-modern')
@section('title', 'Mes gains | ' . \App\Services\ConfigService::getCompanyName())
@section('nav_gains', 'is-active')
@section('driver_initials', strtoupper(substr($driver->name ?? 'L', 0, 2)))
@section('driver_name', $driver->name ?? 'Livreur')
@section('driver_phone', $driver->phone ?? '')
@section('online_pill_class', ($driver->status ?? 'offline') === 'online' ? '' : 'offline')
@section('online_pill_label', ($driver->status ?? 'offline') === 'online' ? 'En ligne' : 'Hors ligne')
@section('page_title', 'Mes gains')

@section('style')
<style>
.gn-body { padding: 20px 24px 48px; display: flex; flex-direction: column; gap: 18px; }

/* Summary bar */
.gn-summary {
    background: var(--c-dark);
    border-radius: 14px;
    padding: 20px 22px;
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    flex-wrap: wrap;
}
.gn-summary-left { display: flex; flex-direction: column; gap: 2px; }
.gn-summary-label { font-size: .7rem; font-weight: 700; color: rgba(255,255,255,.45); text-transform: uppercase; letter-spacing: .09em; }
.gn-summary-amount { font-size: 2rem; font-weight: 900; color: #fff; letter-spacing: -.03em; line-height: 1; }
.gn-summary-sub { font-size: .72rem; color: rgba(255,255,255,.4); font-weight: 600; margin-top: 2px; }
.gn-summary-kpis { display: flex; gap: 0; }
.gn-kpi-cell { padding: 10px 18px; text-align: center; border-left: 1px solid rgba(255,255,255,.08); }
.gn-kpi-val { font-size: 1.05rem; font-weight: 900; color: #fff; line-height: 1; }
.gn-kpi-lbl { font-size: .62rem; font-weight: 700; color: rgba(255,255,255,.35); text-transform: uppercase; letter-spacing: .05em; margin-top: 3px; }

/* Section header */
.gn-sec { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.gn-sec-title {
    font-size: .75rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .1em; color: var(--c-text-muted);
    display: flex; align-items: center; gap: 6px;
}

/* Stats quick */
.gn-stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.gn-stat {
    background: var(--c-surface); border: 1px solid var(--c-border);
    border-radius: 12px; padding: 14px 12px; text-align: center;
}
.gn-stat-icon { font-size: .9rem; color: var(--c-text-dim); margin-bottom: 6px; }
.gn-stat-val { font-size: 1.35rem; font-weight: 900; color: var(--c-text); line-height: 1; }
.gn-stat-lbl { font-size: .63rem; font-weight: 700; color: var(--c-text-dim); text-transform: uppercase; letter-spacing: .05em; margin-top: 3px; }

/* Chart */
.gn-chart-card { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 14px; padding: 18px; }
.gn-chart-title { font-size: .85rem; font-weight: 800; color: var(--c-text); margin-bottom: 4px; }
.gn-chart-meta  { font-size: .7rem; color: var(--c-text-muted); margin-bottom: 14px; }
.gn-bar-chart   { display: flex; align-items: flex-end; gap: 3px; height: 90px; }
.gn-bar-wrap    { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px; }
.gn-bar         { width: 100%; border-radius: 3px 3px 0 0; background: var(--c-primary); min-height: 3px; }
.gn-bar.zero    { background: var(--c-border); }
.gn-bar-lbl     { font-size: .55rem; font-weight: 600; color: var(--c-text-dim); }

/* Detail */
.gn-detail-card { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 14px; overflow: hidden; margin-bottom: 10px; }
.gn-day-header  { padding: 10px 16px; background: var(--c-bg); border-bottom: 1px solid var(--c-border); display: flex; align-items: center; justify-content: space-between; }
.gn-day-lbl     { font-size: .8rem; font-weight: 800; color: var(--c-text); }
.gn-day-total   { font-size: .85rem; font-weight: 900; color: var(--c-primary); }
.gn-row         { display: flex; align-items: center; gap: 10px; padding: 11px 16px; border-bottom: 1px solid var(--c-bg); }
.gn-row:last-child { border-bottom: none; }
.gn-row-dot     { width: 7px; height: 7px; border-radius: 50%; background: var(--c-green-lt); flex-shrink: 0; }
.gn-row-ref     { font-size: .78rem; font-weight: 800; color: var(--c-text); font-family: monospace; flex: 1; min-width: 0; }
.gn-row-rest    { font-size: .7rem; color: var(--c-text-muted); margin-top: 1px; }
.gn-row-fee     { font-size: .88rem; font-weight: 900; color: var(--c-green-lt); white-space: nowrap; flex-shrink: 0; }
.gn-row-time    { font-size: .65rem; color: var(--c-text-dim); white-space: nowrap; flex-shrink: 0; }

.gn-empty { text-align: center; padding: 36px 20px; }
.gn-empty-lbl { font-size: .85rem; font-weight: 700; color: var(--c-text-muted); margin-top: 10px; }

@media (max-width: 768px) {
    .gn-body { padding: 14px 14px 40px; }
    .gn-summary { flex-direction: column; align-items: flex-start; }
    .gn-summary-kpis { flex-wrap: wrap; }
    .gn-kpi-cell:first-child { border-left: none; padding-left: 0; }
    .gn-bar-chart { height: 64px; }
}
</style>
@endsection

@section('content')
@php
    use App\Delivery;
    $finNet = 0; $finAvailable = 0; $finGross = 0;
    if (!empty($financialDashboard['rows'])) {
        foreach ($financialDashboard['rows'] as $row) {
            foreach ((array)$row as $card) {
                if (!is_array($card)) continue;
                $lbl = strtolower($card['label'] ?? '');
                if (str_contains($lbl,'brut'))           $finGross     = $card['amount'] ?? 0;
                if (str_contains($lbl,'net partenaire')) $finNet       = $card['amount'] ?? 0;
                if (str_contains($lbl,'disponible'))     $finAvailable = $card['amount'] ?? 0;
            }
        }
    }
    $todayGains  = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->whereDate('delivered_at',today())->sum('delivery_fee');
    $weekGains   = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->whereBetween('delivered_at',[now()->startOfWeek(),now()->endOfWeek()])->sum('delivery_fee');
    $monthGains  = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->whereBetween('delivered_at',[now()->startOfMonth(),now()->endOfMonth()])->sum('delivery_fee');
    $todayCount  = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->whereDate('delivered_at',today())->count();
    $weekCount   = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->whereBetween('delivered_at',[now()->startOfWeek(),now()->endOfWeek()])->count();
    $monthCount  = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->whereBetween('delivered_at',[now()->startOfMonth(),now()->endOfMonth()])->count();

    $chartDays = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = now()->subDays($i);
        $amt = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->whereDate('delivered_at',$d)->sum('delivery_fee');
        $chartDays[] = ['label'=>$d->format('d'), 'amount'=>(float)$amt, 'full'=>$d->format('D d/m')];
    }
    $chartMax = max(max(array_column($chartDays,'amount')), 1);

    $detailDays = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = now()->subDays($i);
        $delivs = Delivery::with(['order.user','restaurant'])
            ->where('driver_id',$driver->id)->where('status','DELIVERED')
            ->whereDate('delivered_at',$d)->orderByDesc('delivered_at')->get();
        if ($delivs->count() > 0) $detailDays[] = ['date'=>$d,'deliveries'=>$delivs,'total'=>$delivs->sum('delivery_fee')];
    }
@endphp

<div class="gn-body">

    {{-- ── SUMMARY BAR ── --}}
    <div class="gn-summary">
        <div class="gn-summary-left">
            <div class="gn-summary-label">Solde disponible</div>
            <div class="gn-summary-amount">{{ number_format(round($finAvailable),0,',',' ') }} <span style="font-size:1rem;font-weight:600;opacity:.5;">FCFA</span></div>
            <div class="gn-summary-sub">Prêt à retirer</div>
            @if($finAvailable >= 500)
            <button id="gnPayoutBtn" onclick="gnRequestPayout()"
                style="margin-top:12px;padding:8px 18px;background:#fff;color:#007836;border:none;border-radius:20px;font-size:.78rem;font-weight:800;cursor:pointer;letter-spacing:.02em;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-money-bill-transfer"></i> Demander un versement
            </button>
            @else
            <div style="margin-top:10px;font-size:.72rem;color:rgba(255,255,255,.35);">
                Minimum 500 FCFA pour demander un versement
            </div>
            @endif
        </div>
        <div class="gn-summary-kpis">
            <div class="gn-kpi-cell">
                <div class="gn-kpi-val">{{ number_format(round($todayGains),0,',',' ') }}</div>
                <div class="gn-kpi-lbl">Auj. FCFA</div>
            </div>
            <div class="gn-kpi-cell">
                <div class="gn-kpi-val">{{ number_format(round($weekGains),0,',',' ') }}</div>
                <div class="gn-kpi-lbl">Semaine</div>
            </div>
            <div class="gn-kpi-cell">
                <div class="gn-kpi-val">{{ number_format(round($monthGains),0,',',' ') }}</div>
                <div class="gn-kpi-lbl">Ce mois</div>
            </div>
        </div>
    </div>

    {{-- ── STATS COURSES ── --}}
    <div class="gn-stats-row">
        <div class="gn-stat">
            <div class="gn-stat-icon"><i class="fas fa-motorcycle" style="color:var(--c-primary);"></i></div>
            <div class="gn-stat-val">{{ $todayCount }}</div>
            <div class="gn-stat-lbl">Aujourd'hui</div>
        </div>
        <div class="gn-stat">
            <div class="gn-stat-icon"><i class="fas fa-calendar-week" style="color:var(--c-info);"></i></div>
            <div class="gn-stat-val">{{ $weekCount }}</div>
            <div class="gn-stat-lbl">Cette semaine</div>
        </div>
        <div class="gn-stat">
            <div class="gn-stat-icon"><i class="fas fa-calendar" style="color:var(--c-green);"></i></div>
            <div class="gn-stat-val">{{ $monthCount }}</div>
            <div class="gn-stat-lbl">Ce mois</div>
        </div>
    </div>

    {{-- ── GRAPHE 30 JOURS ── --}}
    <div>
        <div class="gn-sec">
            <div class="gn-sec-title"><i class="fas fa-chart-bar" style="color:var(--c-primary);"></i> Évolution 30 jours</div>
        </div>
        <div class="gn-chart-card">
            <div class="gn-chart-title">Gains journaliers</div>
            <div class="gn-chart-meta">30 derniers jours &middot; FCFA</div>
            <div class="gn-bar-chart">
                @foreach($chartDays as $cd)
                @php $barH = $chartMax > 0 ? max(3, round($cd['amount'] / $chartMax * 90)) : 3; @endphp
                <div class="gn-bar-wrap" title="{{ $cd['full'] }} · {{ number_format($cd['amount'],0,',',' ') }} FCFA">
                    <div class="gn-bar {{ $cd['amount'] > 0 ? '' : 'zero' }}" style="height:{{ $barH }}px;"></div>
                    @if($loop->index % 5 === 0)
                    <span class="gn-bar-lbl">{{ $cd['label'] }}</span>
                    @else
                    <span class="gn-bar-lbl">&nbsp;</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── DÉTAIL 7 JOURS ── --}}
    <div>
        <div class="gn-sec">
            <div class="gn-sec-title"><i class="fas fa-list-ul" style="color:var(--c-primary);"></i> Détail par livraison</div>
            <span style="font-size:.7rem;color:var(--c-text-dim);font-weight:600;">7 derniers jours</span>
        </div>

        @forelse($detailDays as $dd)
        <div class="gn-detail-card">
            <div class="gn-day-header">
                <span class="gn-day-lbl">{{ $dd['date']->translatedFormat('l d F') }}</span>
                <span class="gn-day-total">{{ number_format($dd['total'],0,',',' ') }} FCFA &middot; {{ $dd['deliveries']->count() }} course{{ $dd['deliveries']->count()>1?'s':'' }}</span>
            </div>
            @foreach($dd['deliveries'] as $dlv)
            <div class="gn-row">
                <span class="gn-row-dot"></span>
                <div style="flex:1;min-width:0;">
                    <div class="gn-row-ref">#{{ $dlv->order->order_no ?? $dlv->order_id }}</div>
                    <div class="gn-row-rest">{{ $dlv->restaurant->name ?? '—' }}</div>
                </div>
                <div class="gn-row-fee">+{{ number_format($dlv->delivery_fee??0,0,',',' ') }} FCFA</div>
                <div class="gn-row-time">{{ $dlv->delivered_at ? $dlv->delivered_at->format('H:i') : '—' }}</div>
            </div>
            @endforeach
        </div>
        @empty
        <div class="gn-detail-card">
            <div class="gn-empty">
                <i class="fas fa-inbox" style="font-size:1.8rem;color:var(--c-text-dim);"></i>
                <div class="gn-empty-lbl">Aucune livraison sur les 7 derniers jours</div>
            </div>
        </div>
        @endforelse
    </div>

</div>
@endsection

@section('script')
<script>
function gnRequestPayout() {
    var btn = document.getElementById('gnPayoutBtn');
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours…';

    fetch('{{ route("driver.payout.request") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Demande envoyée';
            btn.style.background = '#dcfce7';
            btn.style.color = '#166534';
            // Toast si disponible
            if (typeof window.showDriverToast === 'function') window.showDriverToast(d.message, 'success');
            else alert(d.message);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-money-bill-transfer"></i> Demander un versement';
            if (typeof window.showDriverToast === 'function') window.showDriverToast(d.message, 'error');
            else alert(d.message);
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-money-bill-transfer"></i> Demander un versement';
    });
}

(function() {
    function easeOut(t){ return 1-Math.pow(1-t,4); }
    function countUp(el, v, dur) {
        var s=null;
        function step(ts){
            if(!s)s=ts; var p=Math.min((ts-s)/dur,1);
            el.textContent=Math.round(easeOut(p)*v).toLocaleString('fr-FR');
            if(p<1)requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
    document.querySelectorAll('[data-countup]').forEach(function(el){
        var v=parseFloat(el.getAttribute('data-countup'))||0;
        if(v>0) countUp(el,Math.round(v),800);
    });
})();
</script>
@endsection
