@extends('layouts.driver-modern')
@section('title', 'Historique | ' . \App\Services\ConfigService::getCompanyName())
@section('nav_historique', 'is-active')
@section('driver_initials', strtoupper(substr($driver->name ?? 'L', 0, 2)))
@section('driver_name', $driver->name ?? 'Livreur')
@section('driver_phone', $driver->phone ?? '')
@section('online_pill_class', ($driver->status ?? 'offline') === 'online' ? '' : 'offline')
@section('online_pill_label', ($driver->status ?? 'offline') === 'online' ? 'En ligne' : 'Hors ligne')
@section('page_title', 'Historique des courses')

@section('style')
<style>
.hx-body { padding: 20px 24px 48px; display: flex; flex-direction: column; gap: 16px; }

/* Filtres */
.hx-filters { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.hx-filter-select {
    padding: 7px 32px 7px 12px; border-radius: 8px; font-size: .8rem; font-weight: 600;
    border: 1px solid var(--c-border); background: var(--c-surface); color: var(--c-text-2);
    cursor: pointer; appearance: none; font-family: var(--font-body);
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239ca3af' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
    transition: border-color .15s;
}
.hx-filter-select:focus { outline: none; border-color: var(--c-primary); }

/* Stats */
.hx-stats-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.hx-stat { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 12px; padding: 13px 12px; text-align: center; }
.hx-stat-val { font-size: 1.3rem; font-weight: 900; color: var(--c-text); line-height: 1; }
.hx-stat-val.green  { color: var(--c-green-lt); }
.hx-stat-val.red    { color: var(--c-err); }
.hx-stat-val.orange { color: var(--c-warn); }
.hx-stat-lbl { font-size: .63rem; font-weight: 700; color: var(--c-text-dim); text-transform: uppercase; letter-spacing: .05em; margin-top: 3px; }

/* Day separator */
.hx-day-sep {
    font-size: .75rem; font-weight: 800; color: var(--c-text-2);
    display: flex; align-items: center; gap: 8px; padding: 4px 0 2px;
}
.hx-day-sep::after { content:''; flex:1; height:1px; background:var(--c-border); }
.hx-day-count { font-size: .68rem; font-weight: 600; color: var(--c-text-dim); }

/* Row */
.hx-row {
    background: var(--c-surface); border: 1px solid var(--c-border);
    border-radius: 12px; padding: 12px 14px;
    display: flex; align-items: center; gap: 11px;
    transition: box-shadow .15s, border-color .15s;
}
.hx-row:hover { border-color: var(--c-primary); box-shadow: 0 2px 12px rgba(255,90,31,.08); }

.hx-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
.hx-icon.delivered { background: rgba(34,197,94,.1);   color: var(--c-green-lt); }
.hx-icon.cancelled { background: rgba(239,68,68,.1);   color: var(--c-err); }
.hx-icon.incident  { background: rgba(245,158,11,.1);  color: var(--c-warn); }

.hx-content { flex: 1; min-width: 0; }
.hx-ref     { font-size: .82rem; font-weight: 800; color: var(--c-text); font-family: monospace; }
.hx-detail  { font-size: .72rem; color: var(--c-text-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hx-badge   { display: inline-flex; align-items: center; gap: 3px; padding: 2px 6px; border-radius: 4px; font-size: .6rem; font-weight: 800; text-transform: uppercase; margin-top: 4px; }
.hx-badge.delivered { background: rgba(34,197,94,.12);  color: #15803d; }
.hx-badge.cancelled { background: rgba(239,68,68,.1);   color: #b91c1c; }
.hx-badge.incident  { background: rgba(245,158,11,.12); color: #92400e; }

.hx-right   { text-align: right; flex-shrink: 0; }
.hx-fee     { font-size: .88rem; font-weight: 900; color: var(--c-green-lt); }
.hx-fee.none { color: var(--c-text-dim); }
.hx-time    { font-size: .65rem; color: var(--c-text-dim); margin-top: 2px; }

/* Empty */
.hx-empty { text-align: center; padding: 44px 20px; background: var(--c-surface); border-radius: 12px; border: 1px dashed var(--c-border); }
.hx-empty-icon { font-size: 1.8rem; color: var(--c-text-dim); margin-bottom: 10px; }

@media (max-width: 768px) {
    .hx-body { padding: 14px 14px 40px; }
    .hx-stats-strip { grid-template-columns: repeat(2, 1fr); }
}
</style>
@endsection

@section('content')
@php
    use App\Delivery;
    $page     = request()->get('page', 1);
    $perPage  = 30;
    $status   = request()->get('status', 'all');
    $period   = request()->get('period', '30');

    $query = Delivery::with(['order.user','restaurant'])
        ->where('driver_id', $driver->id)
        ->whereIn('status', ['DELIVERED','CANCELLED'])
        ->when($status !== 'all', fn($q) => $q->where('status', strtoupper($status)))
        ->when($period, fn($q) => $q->where('created_at', '>=', now()->subDays((int)$period)))
        ->orderByDesc('created_at');

    $historique = $query->paginate($perPage);

    $totalDelivered = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->count();
    $totalCancelled = Delivery::where('driver_id',$driver->id)->where('status','CANCELLED')->count();
    $totalFees      = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->sum('delivery_fee');
    $avgFee         = $totalDelivered > 0 ? round($totalFees / $totalDelivered) : 0;

    $grouped = $historique->getCollection()->groupBy(fn($d) => $d->created_at->format('Y-m-d'));
@endphp

<div class="hx-body">

    {{-- ── FILTRES ── --}}
    <div class="hx-filters">
        <form method="GET" style="display:contents;">
            <select name="period" class="hx-filter-select" onchange="this.form.submit()">
                <option value="7"  {{ $period=='7'  ? 'selected':'' }}>7 derniers jours</option>
                <option value="30" {{ $period=='30' ? 'selected':'' }}>30 derniers jours</option>
                <option value="90" {{ $period=='90' ? 'selected':'' }}>3 derniers mois</option>
                <option value="365"{{ $period=='365'? 'selected':'' }}>Cette année</option>
            </select>
            <select name="status" class="hx-filter-select" onchange="this.form.submit()">
                <option value="all"       {{ $status=='all'       ? 'selected':'' }}>Tous les statuts</option>
                <option value="delivered" {{ $status=='delivered' ? 'selected':'' }}>Livrées</option>
                <option value="cancelled" {{ $status=='cancelled' ? 'selected':'' }}>Annulées</option>
            </select>
        </form>
    </div>

    {{-- ── STATS ── --}}
    <div class="hx-stats-strip">
        <div class="hx-stat">
            <div class="hx-stat-val green">{{ $totalDelivered }}</div>
            <div class="hx-stat-lbl">Livrées</div>
        </div>
        <div class="hx-stat">
            <div class="hx-stat-val red">{{ $totalCancelled }}</div>
            <div class="hx-stat-lbl">Annulées</div>
        </div>
        <div class="hx-stat">
            <div class="hx-stat-val orange">{{ $totalFees >= 1000 ? number_format(round($totalFees/1000),0,',',' ').'k' : number_format(round($totalFees),0,',',' ') }}</div>
            <div class="hx-stat-lbl">FCFA total</div>
        </div>
        <div class="hx-stat">
            <div class="hx-stat-val">{{ number_format($avgFee,0,',',' ') }}</div>
            <div class="hx-stat-lbl">Moy./course</div>
        </div>
    </div>

    {{-- ── LISTE ── --}}
    @forelse($grouped as $dateStr => $dayDeliveries)
    @php $d = \Carbon\Carbon::parse($dateStr); @endphp
    <div>
        <div class="hx-day-sep">
            {{ $d->isToday() ? "Aujourd'hui" : ($d->isYesterday() ? 'Hier' : $d->translatedFormat('l d F Y')) }}
            <span class="hx-day-count">{{ $dayDeliveries->count() }} course{{ $dayDeliveries->count()>1?'s':'' }}</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:7px;">
        @foreach($dayDeliveries as $dlv)
        @php
            $isDelivered = $dlv->status === 'DELIVERED';
            $isCancelled = $dlv->status === 'CANCELLED';
            $hasIncident = $dlv->incident_status === 'open';
            $iconCls     = $hasIncident ? 'incident' : ($isDelivered ? 'delivered' : 'cancelled');
            $iconFa      = $hasIncident ? 'fa-triangle-exclamation' : ($isDelivered ? 'fa-circle-check' : 'fa-circle-xmark');
        @endphp
        <div class="hx-row">
            <div class="hx-icon {{ $iconCls }}">
                <i class="fas {{ $iconFa }}"></i>
            </div>
            <div class="hx-content">
                <div class="hx-ref">#{{ $dlv->order->order_no ?? $dlv->order_id }}</div>
                <div class="hx-detail">
                    {{ $dlv->restaurant->name ?? '—' }}
                    @if($dlv->order->delivery_address ?? null) &middot; {{ Str::limit($dlv->order->delivery_address, 35) }}@endif
                </div>
                <div>
                    <span class="hx-badge {{ $iconCls }}">
                        <i class="fas {{ $iconFa }}" style="font-size:.55rem;"></i>
                        {{ $hasIncident ? 'Incident' : ($isDelivered ? 'Livrée' : 'Annulée') }}
                    </span>
                </div>
            </div>
            <div class="hx-right">
                <div class="hx-fee {{ $isDelivered ? '' : 'none' }}">
                    {{ $isDelivered ? '+'.number_format($dlv->delivery_fee??0,0,',',' ').' FCFA' : '—' }}
                </div>
                <div class="hx-time">{{ $dlv->created_at->format('H:i') }}</div>
            </div>
        </div>
        @endforeach
        </div>
    </div>
    @empty
    <div class="hx-empty">
        <div class="hx-empty-icon"><i class="fas fa-inbox"></i></div>
        <div style="font-weight:700;color:var(--c-text);font-size:.9rem;margin-bottom:6px;">Aucune course sur cette période</div>
        <div style="font-size:.82rem;color:var(--c-text-muted);line-height:1.6;">Modifiez le filtre ou attendez la prochaine livraison.</div>
    </div>
    @endforelse

    {{-- Pagination --}}
    @if($historique->hasPages())
    <div style="display:flex;justify-content:center;padding:8px 0;">
        {{ $historique->appends(request()->query())->links() }}
    </div>
    @endif

</div>
@endsection
