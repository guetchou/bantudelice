@extends('layouts.restaurant_app')
@section('title', 'Analytics')
@section('topbar_title', 'Analytics')
@section('analytics_nav', 'active')

@section('style')
<style>
/* ── Analytics restaurant ─────────────────────────────────────────────────── */
.ral-page { padding: 0 0 60px; }

/* Sélecteur période */
.ral-period-bar {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    margin-bottom: 22px;
}
.ral-period-btn {
    padding: 5px 16px; border-radius: 20px; font-size: .8rem; font-weight: 600;
    border: 1.5px solid #e5e7eb; background: #fff; color: #374151;
    text-decoration: none; transition: background .15s, color .15s, border-color .15s;
}
.ral-period-btn:hover,
.ral-period-btn.active { background: #007836; color: #fff; border-color: #007836; }

/* KPI cards */
.ral-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
@media (max-width: 900px) { .ral-kpis { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .ral-kpis { grid-template-columns: 1fr; } }
.ral-kpi {
    background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
    padding: 18px 16px; position: relative; overflow: hidden;
}
.ral-kpi::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 3px; background: var(--ral-accent, #007836);
}
.ral-kpi-icon { font-size: 1.2rem; color: var(--ral-accent, #007836); margin-bottom: 10px; }
.ral-kpi-val  { font-size: 1.6rem; font-weight: 900; color: #111827; line-height: 1; margin-bottom: 4px; letter-spacing: -.03em; }
.ral-kpi-lbl  { font-size: .72rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .07em; }

/* Section title */
.ral-sec { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #9ca3af; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

/* 2-col layout */
.ral-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
@media (max-width: 768px) { .ral-grid-2 { grid-template-columns: 1fr; } }
.ral-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; }

/* Top produits */
.ral-prod-row { display: flex; align-items: center; gap: 12px; padding: 9px 0; border-bottom: 1px solid #f3f4f6; }
.ral-prod-row:last-child { border-bottom: none; }
.ral-prod-rank { width: 22px; font-size: .75rem; font-weight: 800; color: #9ca3af; flex-shrink: 0; text-align: center; }
.ral-prod-rank.gold   { color: #f59e0b; }
.ral-prod-rank.silver { color: #94a3b8; }
.ral-prod-rank.bronze { color: #b45309; }
.ral-prod-img { width: 38px; height: 38px; border-radius: 8px; object-fit: cover; flex-shrink: 0; background: #f3f4f6; }
.ral-prod-name { flex: 1; min-width: 0; font-size: .85rem; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ral-prod-qty  { font-size: .78rem; color: #6b7280; white-space: nowrap; flex-shrink: 0; }
.ral-prod-rev  { font-size: .85rem; font-weight: 700; color: #007836; white-space: nowrap; flex-shrink: 0; min-width: 80px; text-align: right; }
.ral-prod-bar-wrap { width: 80px; height: 5px; background: #f3f4f6; border-radius: 3px; flex-shrink: 0; }
.ral-prod-bar { height: 100%; background: #007836; border-radius: 3px; }

/* Heatmap heures */
.ral-heatmap { display: flex; align-items: flex-end; gap: 3px; height: 80px; }
.ral-heat-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px; }
.ral-heat-bar { width: 100%; border-radius: 3px 3px 0 0; min-height: 3px; transition: background .2s; }
.ral-heat-lbl { font-size: .5rem; color: #9ca3af; font-weight: 600; }
.ral-heat-peak { background: #007836 !important; }

/* Daily chart */
.ral-daily { display: flex; align-items: flex-end; gap: 2px; height: 70px; }
.ral-day-bar { flex: 1; border-radius: 2px 2px 0 0; min-height: 2px; background: #bbf7d0; transition: background .15s; }
.ral-day-bar:hover { background: #007836; }

/* Payment breakdown */
.ral-pay-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.ral-pay-label { font-size: .82rem; color: #374151; flex: 1; font-weight: 500; }
.ral-pay-bar-wrap { flex: 2; height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
.ral-pay-bar { height: 100%; border-radius: 4px; background: #007836; }
.ral-pay-pct { font-size: .78rem; font-weight: 700; color: #374151; min-width: 36px; text-align: right; }

/* Rating */
.ral-star { color: #f59e0b; }
.ral-star.off { color: #e5e7eb; }
</style>
@endsection

@section('content')
@php
    $maxProduct = $topProducts->max('total_qty') ?: 1;
    $payLabels  = ['mobile_money' => 'Mobile Money', 'cash' => 'Espèces', 'paypal' => 'PayPal', 'card' => 'Carte'];
@endphp
<div class="ral-page">

    {{-- Sélecteur période ─────────────────────────────────────────────────── --}}
    <div class="ral-period-bar">
        <span style="font-size:.78rem;font-weight:700;color:#6b7280;">Période :</span>
        @foreach([7 => '7 jours', 30 => '30 jours', 90 => '90 jours'] as $days => $label)
        <a href="{{ route('restaurant.analytics', ['period' => $days]) }}"
           class="ral-period-btn {{ $period === $days ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        <span style="font-size:.75rem;color:#9ca3af;margin-left:4px;">
            Du {{ now()->subDays($period)->format('d/m/Y') }} au {{ now()->format('d/m/Y') }}
        </span>
    </div>

    {{-- KPI cards ────────────────────────────────────────────────────────── --}}
    <div class="ral-kpis">
        <div class="ral-kpi" style="--ral-accent:#007836;">
            <div class="ral-kpi-icon"><i class="fas fa-wallet"></i></div>
            <div class="ral-kpi-val">{{ number_format((float)$revenue, 0, ',', ' ') }}</div>
            <div class="ral-kpi-lbl">CA FCFA</div>
        </div>
        <div class="ral-kpi" style="--ral-accent:#2563eb;">
            <div class="ral-kpi-icon"><i class="fas fa-receipt"></i></div>
            <div class="ral-kpi-val">{{ $totalOrders }}</div>
            <div class="ral-kpi-lbl">Commandes</div>
        </div>
        <div class="ral-kpi" style="--ral-accent:#7c3aed;">
            <div class="ral-kpi-icon"><i class="fas fa-basket-shopping"></i></div>
            <div class="ral-kpi-val">{{ number_format((float)$avgBasket, 0, ',', ' ') }}</div>
            <div class="ral-kpi-lbl">Panier moyen FCFA</div>
        </div>
        <div class="ral-kpi" style="--ral-accent:{{ $completionRate >= 80 ? '#007836' : ($completionRate >= 60 ? '#d97706' : '#dc2626') }};">
            <div class="ral-kpi-icon"><i class="fas fa-circle-check"></i></div>
            <div class="ral-kpi-val">{{ $completionRate }}%</div>
            <div class="ral-kpi-lbl">Taux de complétion</div>
        </div>
    </div>

    {{-- Ligne 1 : Top produits + Heures de pointe ─────────────────────────── --}}
    <div class="ral-grid-2">

        {{-- Top produits --}}
        <div class="ral-card">
            <div class="ral-sec"><i class="fas fa-trophy"></i> Top produits</div>
            @if($topProducts->isEmpty())
                <p style="color:#9ca3af;font-size:.85rem;text-align:center;padding:20px 0;">Aucune vente sur cette période</p>
            @else
                @foreach($topProducts as $i => $prod)
                @php
                    $rankClass = match($i) { 0 => 'gold', 1 => 'silver', 2 => 'bronze', default => '' };
                    $barPct    = $maxProduct > 0 ? round(($prod->total_qty / $maxProduct) * 100) : 0;
                @endphp
                <div class="ral-prod-row">
                    <div class="ral-prod-rank {{ $rankClass }}">{{ $i + 1 }}</div>
                    @if($prod->image)
                    <img src="{{ asset('images/product_images/' . $prod->image) }}" alt="{{ $prod->name }}" class="ral-prod-img">
                    @else
                    <div class="ral-prod-img" style="display:flex;align-items:center;justify-content:center;color:#d1d5db;font-size:.9rem;">
                        <i class="fas fa-bowl-food"></i>
                    </div>
                    @endif
                    <div style="flex:1;min-width:0;">
                        <div class="ral-prod-name">{{ $prod->name }}</div>
                        <div class="ral-prod-bar-wrap" style="margin-top:4px;">
                            <div class="ral-prod-bar" style="width:{{ $barPct }}%;"></div>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div class="ral-prod-rev">{{ number_format((float)$prod->revenue, 0, ',', ' ') }} FCFA</div>
                        <div class="ral-prod-qty">{{ $prod->total_qty }} vendu{{ $prod->total_qty > 1 ? 's' : '' }}</div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>

        {{-- Heures de pointe --}}
        <div class="ral-card">
            <div class="ral-sec"><i class="fas fa-fire"></i> Heures de pointe</div>
            @php
                $peakHour = array_search(max($hourly), $hourly);
            @endphp
            @if(max($hourly) === 0)
                <p style="color:#9ca3af;font-size:.85rem;text-align:center;padding:20px 0;">Aucune commande sur cette période</p>
            @else
                <p style="font-size:.8rem;color:#6b7280;margin-bottom:12px;">
                    Pic : <strong style="color:#007836;">{{ str_pad($peakHour, 2, '0', STR_PAD_LEFT) }}h</strong>
                    avec <strong>{{ $hourly[$peakHour] }}</strong> commande{{ $hourly[$peakHour] > 1 ? 's' : '' }}
                </p>
                <div class="ral-heatmap">
                    @foreach($hourly as $h => $cnt)
                    @php
                        $pct  = $maxHourly > 0 ? round(($cnt / $maxHourly) * 100) : 0;
                        $isPeak = $h === $peakHour && $cnt > 0;
                        $color = $isPeak ? '#007836' : ($cnt > 0 ? '#86efac' : '#f3f4f6');
                    @endphp
                    <div class="ral-heat-col" title="{{ str_pad($h,2,'0',STR_PAD_LEFT) }}h : {{ $cnt }} commande{{ $cnt > 1 ? 's' : '' }}">
                        <div class="ral-heat-bar {{ $isPeak ? 'ral-heat-peak' : '' }}"
                             style="height:{{ max($pct, $cnt > 0 ? 10 : 2) }}%;background:{{ $color }};"></div>
                        @if($h % 4 === 0)
                        <div class="ral-heat-lbl">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</div>
                        @else
                        <div class="ral-heat-lbl"> </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Ligne 2 : CA journalier + Paiements + Note ─────────────────────────── --}}
    <div class="ral-grid-2">

        {{-- CA journalier --}}
        <div class="ral-card">
            <div class="ral-sec"><i class="fas fa-chart-area"></i> CA journalier (FCFA)</div>
            @if(array_sum(array_values($daily)) === 0.0)
                <p style="color:#9ca3af;font-size:.85rem;text-align:center;padding:20px 0;">Aucun CA complété sur cette période</p>
            @else
                <div class="ral-daily">
                    @foreach($daily as $day => $rev)
                    @php $barH = $maxDaily > 0 ? max(round(($rev / $maxDaily) * 100), $rev > 0 ? 5 : 1) : 1; @endphp
                    <div class="ral-day-bar" style="height:{{ $barH }}%;"
                         title="{{ \Carbon\Carbon::parse($day)->format('d/m') }} : {{ number_format($rev, 0, ',', ' ') }} FCFA"></div>
                    @endforeach
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:6px;">
                    <span style="font-size:.68rem;color:#9ca3af;">{{ \Carbon\Carbon::parse(array_key_first($daily))->format('d/m') }}</span>
                    <span style="font-size:.68rem;color:#9ca3af;">{{ \Carbon\Carbon::parse(array_key_last($daily))->format('d/m') }}</span>
                </div>
            @endif
        </div>

        {{-- Paiements + Note --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Répartition paiements --}}
            <div class="ral-card" style="flex:1;">
                <div class="ral-sec"><i class="fas fa-credit-card"></i> Modes de paiement</div>
                @if($paymentMethods->isEmpty())
                    <p style="color:#9ca3af;font-size:.82rem;">Aucune donnée</p>
                @else
                    @foreach($paymentMethods as $pm)
                    @php $pct = round(($pm->cnt / $totalPayments) * 100); @endphp
                    <div class="ral-pay-row">
                        <div class="ral-pay-label">{{ $payLabels[$pm->payment_method] ?? ucfirst($pm->payment_method) }}</div>
                        <div class="ral-pay-bar-wrap"><div class="ral-pay-bar" style="width:{{ $pct }}%;"></div></div>
                        <div class="ral-pay-pct">{{ $pct }}%</div>
                    </div>
                    @endforeach
                @endif
            </div>

            {{-- Note moyenne --}}
            <div class="ral-card" style="flex:none;">
                <div class="ral-sec"><i class="fas fa-star"></i> Note clients</div>
                @if(!$ratingStats || !$ratingStats->total)
                    <p style="color:#9ca3af;font-size:.82rem;">Aucun avis reçu</p>
                @else
                @php $avg = round((float)$ratingStats->avg, 1); @endphp
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="font-size:2.2rem;font-weight:900;color:#111827;line-height:1;">{{ number_format($avg, 1) }}</div>
                    <div>
                        <div style="display:flex;gap:2px;margin-bottom:4px;">
                            @for($s = 1; $s <= 5; $s++)
                            <i class="fas fa-star ral-star{{ $s > round($avg) ? ' off' : '' }}" style="font-size:.9rem;"></i>
                            @endfor
                        </div>
                        <div style="font-size:.78rem;color:#6b7280;">{{ $ratingStats->total }} avis clients</div>
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Résumé statuts ─────────────────────────────────────────────────────── --}}
    <div class="ral-card" style="display:flex;gap:24px;flex-wrap:wrap;">
        <div style="text-align:center;flex:1;min-width:80px;">
            <div style="font-size:1.4rem;font-weight:900;color:#007836;">{{ $completedCount }}</div>
            <div style="font-size:.72rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">Complétées</div>
        </div>
        <div style="text-align:center;flex:1;min-width:80px;">
            <div style="font-size:1.4rem;font-weight:900;color:#dc2626;">{{ $cancelledCount }}</div>
            <div style="font-size:.72rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">Annulées</div>
        </div>
        <div style="text-align:center;flex:1;min-width:80px;">
            <div style="font-size:1.4rem;font-weight:900;color:#374151;">{{ $totalOrders - $completedCount - $cancelledCount }}</div>
            <div style="font-size:.72rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">En cours</div>
        </div>
        <div style="text-align:center;flex:1;min-width:80px;border-left:1px solid #f3f4f6;padding-left:24px;">
            <div style="font-size:1.4rem;font-weight:900;color:#374151;">{{ $completionRate }}%</div>
            <div style="font-size:.72rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">Complétion</div>
        </div>
    </div>

</div>
@endsection
