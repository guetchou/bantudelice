@extends('layouts.restaurant_app')
@section('title','Tableau de bord | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Tableau de bord')
@section('dashboard_nav', 'active')

@section('style')
<style>
.db { display: flex; flex-direction: column; gap: 20px; }

/* ── Bandeau statut ─────────────────────────────────────── */
.db-status {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    padding: 13px 18px;
    border-radius: var(--bd-radius);
    border: 1px solid var(--bd-border);
    background: var(--bd-surface);
    transition: background .2s, border-color .2s;
}
.db-status--online { border-color: rgba(34,197,94,.3); background: rgba(34,197,94,.04); }
.db-status--paused { border-color: rgba(245,158,11,.3); background: rgba(245,158,11,.04); }
[data-theme="dark"] .db-status--online { background: rgba(0,201,87,.06); }
[data-theme="dark"] .db-status--paused { background: rgba(245,158,11,.06); }
.db-status__dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.db-status__dot--online { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.2); animation: bd-pulse 2s infinite; }
.db-status__dot--paused { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.2); }
.db-status__text { flex: 1; font-size: 13px; font-weight: 600; color: var(--bd-text); }
.db-status__sub  { font-size: 11px; color: var(--bd-text-3); margin-top: 1px; }
.db-status__controls { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.db-status__select {
    border: 1px solid var(--bd-border); border-radius: 7px;
    padding: 5px 9px; font-size: 12px; font-weight: 500;
    background: var(--bd-surface); color: var(--bd-text);
    font-family: var(--bd-font); cursor: pointer;
}
.db-status__btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 13px; border-radius: 7px; border: none;
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font);
}
.db-status__btn--pause  { background: #f59e0b; color: #fff; }
.db-status__btn--resume { background: var(--bd-green); color: #fff; }

/* ── Ligne supérieure : KPIs financiers (aujourd'hui) ─── */
.db-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.db-kpi {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    padding: 18px 20px 16px;
    text-decoration: none; color: inherit;
    display: flex; flex-direction: column;
    gap: 4px;
    transition: border-color .12s, box-shadow .12s, background .2s;
    position: relative; overflow: hidden;
}
.db-kpi:hover { border-color: var(--bd-green); box-shadow: var(--bd-shadow-md); }
.db-kpi__accent {
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: var(--bd-radius) var(--bd-radius) 0 0;
}
.db-kpi__accent--green  { background: var(--bd-green); }
.db-kpi__accent--amber  { background: #f59e0b; }
.db-kpi__accent--blue   { background: #3b82f6; }
.db-kpi__accent--purple { background: #8b5cf6; }
.db-kpi__header { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
.db-kpi__icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
}
.db-kpi__icon--green  { background: rgba(0,149,67,.1);  color: var(--bd-green); }
.db-kpi__icon--amber  { background: rgba(245,158,11,.1); color: #d97706; }
.db-kpi__icon--blue   { background: rgba(59,130,246,.1); color: #2563eb; }
.db-kpi__icon--purple { background: rgba(139,92,246,.1); color: #7c3aed; }
[data-theme="dark"] .db-kpi__icon--green  { background: rgba(0,201,87,.12);  color: #00c957; }
[data-theme="dark"] .db-kpi__icon--amber  { background: rgba(251,191,36,.12); color: #fbbf24; }
[data-theme="dark"] .db-kpi__icon--blue   { background: rgba(96,165,250,.12); color: #60a5fa; }
[data-theme="dark"] .db-kpi__icon--purple { background: rgba(167,139,250,.12);color: #a78bfa; }
.db-kpi__label {
    font-size: 11px; font-weight: 600; letter-spacing: .05em;
    text-transform: uppercase; color: var(--bd-text-3);
    margin-top: 12px;
}
.db-kpi__value {
    font-family: var(--bd-font-display);
    font-size: 28px; font-weight: 800; color: var(--bd-text);
    letter-spacing: -.03em; line-height: 1.1;
}
.db-kpi__value--green  { color: var(--bd-green); }
.db-kpi__value--amber  { color: #f59e0b; }
.db-kpi__value--blue   { color: #3b82f6; }
.db-kpi__value--purple { color: #8b5cf6; }
.db-kpi__sub { font-size: 11px; color: var(--bd-text-3); font-family: var(--bd-font-body); }
.db-kpi__trend {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 999px;
}
.db-kpi__trend--up   { background: rgba(34,197,94,.1);  color: #16a34a; }
.db-kpi__trend--down { background: rgba(239,68,68,.1);  color: #dc2626; }
.db-kpi__trend--flat { background: var(--bd-surface-2); color: var(--bd-text-3); }
[data-theme="dark"] .db-kpi__trend--up   { background: rgba(0,201,87,.12);  color: #00c957; }
[data-theme="dark"] .db-kpi__trend--down { background: rgba(248,113,113,.12); color: #f87171; }

/* ── Deuxième ligne : pipeline opérationnel ─────────────── */
.db-pipeline {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2px;
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
}
.db-pipe {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 3px;
    padding: 14px 12px; text-decoration: none; color: inherit;
    background: var(--bd-surface);
    border-right: 1px solid var(--bd-border-2);
    transition: background .12s;
    position: relative;
}
.db-pipe:last-child { border-right: none; }
.db-pipe:hover { background: var(--bd-surface-2); }
.db-pipe__num {
    font-family: var(--bd-font-display);
    font-size: 24px; font-weight: 800; line-height: 1;
}
.db-pipe__num--amber  { color: #f59e0b; }
.db-pipe__num--blue   { color: #3b82f6; }
.db-pipe__num--indigo { color: #6366f1; }
.db-pipe__num--green  { color: var(--bd-green); }
.db-pipe__label { font-size: 11px; font-weight: 600; color: var(--bd-text-2); text-align: center; }
.db-pipe__arrow {
    position: absolute; right: -7px; top: 50%; transform: translateY(-50%);
    width: 14px; height: 14px; background: var(--bd-surface);
    border-right: 1px solid var(--bd-border-2); border-top: 1px solid var(--bd-border-2);
    transform: translateY(-50%) rotate(45deg);
    z-index: 1;
}
.db-pipe:last-child .db-pipe__arrow { display: none; }

/* ── Troisième ligne : grille principale ────────────────── */
.db-main {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 16px;
    align-items: start;
}

/* ── Carte générique ─────────────────────────────────────── */
.db-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
    transition: background .2s;
}
.db-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
}
.db-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.db-card__sub   { font-size: 11px; color: var(--bd-text-3); }
.db-card__body  { padding: 20px; }

/* Chart tabs */
.db-tabs {
    display: inline-flex; border: 1px solid var(--bd-border);
    border-radius: 6px; overflow: hidden; background: var(--bd-surface-2);
}
.db-tabs a {
    display: inline-flex; align-items: center;
    padding: 4px 12px; font-size: 12px; font-weight: 500;
    color: var(--bd-text-2); background: transparent;
    cursor: pointer; transition: .12s; text-decoration: none; border: none;
}
.db-tabs a.active { background: var(--bd-surface); color: var(--bd-green); font-weight: 600; }
.db-tabs a:not(:last-child) { border-right: 1px solid var(--bd-border); }
.db-chart-wrap { position: relative; height: 210px; }
.db-chart-wrap canvas { width: 100% !important; height: 100% !important; }

/* ── Panneau droite (commandes + top plats) ─────────────── */
.db-right { display: flex; flex-direction: column; gap: 16px; }

/* Commandes actives */
.db-orders { display: flex; flex-direction: column; }
.db-order {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px; border-bottom: 1px solid var(--bd-border-2);
    text-decoration: none; color: inherit; transition: background .1s;
}
.db-order:last-child { border-bottom: none; }
.db-order:hover { background: var(--bd-surface-2); }
.db-order__ref  { font-size: 12px; font-weight: 700; color: var(--bd-text); min-width: 72px; font-family: var(--bd-font-display); }
.db-order__name { font-size: 12px; color: var(--bd-text-2); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-order__amt  { font-size: 12px; font-weight: 700; color: var(--bd-green); white-space: nowrap; }
.db-order__time { font-size: 10px; color: var(--bd-text-3); white-space: nowrap; }

/* Badges statut */
.db-badge {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 700;
    flex-shrink: 0;
}
.db-badge::before { content:''; width:5px;height:5px;border-radius:50%;background:currentColor;display:block; }
.db-badge--new       { background:rgba(245,158,11,.12); color:#d97706; }
.db-badge--preparing { background:rgba(59,130,246,.12);  color:#2563eb; }
.db-badge--delivering{ background:rgba(99,102,241,.12);  color:#4f46e5; }
.db-badge--done      { background:rgba(0,149,67,.1);     color:var(--bd-green); }
.db-badge--cancelled { background:rgba(239,68,68,.1);    color:#dc2626; }
[data-theme="dark"] .db-badge--new        { background:rgba(251,191,36,.15); color:#fbbf24; }
[data-theme="dark"] .db-badge--preparing  { background:rgba(96,165,250,.15); color:#60a5fa; }
[data-theme="dark"] .db-badge--delivering { background:rgba(129,140,248,.15);color:#818cf8; }
[data-theme="dark"] .db-badge--done       { background:rgba(0,201,87,.15);   color:#00c957; }
[data-theme="dark"] .db-badge--cancelled  { background:rgba(248,113,113,.15);color:#f87171; }

/* Top plats */
.db-dish {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 16px; border-bottom: 1px solid var(--bd-border-2);
}
.db-dish:last-child { border-bottom: none; }
.db-dish__rank {
    font-family: var(--bd-font-display);
    font-size: 18px; font-weight: 800; color: var(--bd-text-3);
    min-width: 24px; text-align: center; line-height: 1;
}
.db-dish__rank--1 { color: #f59e0b; }
.db-dish__rank--2 { color: var(--bd-text-2); }
.db-dish__rank--3 { color: #cd7c3a; }
.db-dish__img {
    width: 40px; height: 40px; border-radius: 8px; object-fit: cover;
    border: 1px solid var(--bd-border); flex-shrink: 0;
}
.db-dish__img--placeholder {
    width: 40px; height: 40px; border-radius: 8px;
    background: var(--bd-surface-2); border: 1px solid var(--bd-border);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0; color: var(--bd-text-3);
}
.db-dish__info { flex: 1; min-width: 0; }
.db-dish__name { font-size: 12px; font-weight: 600; color: var(--bd-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-dish__meta { font-size: 11px; color: var(--bd-text-3); margin-top: 1px; }
.db-dish__qty  { font-family: var(--bd-font-display); font-size: 16px; font-weight: 800; color: var(--bd-green); white-space: nowrap; }

/* Actions rapides */
.db-actions {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
    padding: 14px 16px;
}
.db-action {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 12px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface-2);
    text-decoration: none; color: var(--bd-text-2);
    font-size: 12px; font-weight: 500;
    transition: border-color .12s, background .12s, color .12s;
}
.db-action:hover { border-color: var(--bd-green); background: var(--bd-surface); color: var(--bd-green); }
.db-action i { font-size: 12px; width: 14px; text-align: center; }

.db-empty {
    padding: 28px 16px; text-align: center;
    font-size: 12px; color: var(--bd-text-3); font-family: var(--bd-font-body);
}
.db-empty i { font-size: 22px; display: block; margin-bottom: 8px; }
.db-see-all {
    display: block; text-align: center;
    padding: 10px; font-size: 12px; font-weight: 600;
    color: var(--bd-green); text-decoration: none;
    border-top: 1px solid var(--bd-border-2);
    transition: background .12s;
}
.db-see-all:hover { background: var(--bd-surface-2); }

/* Responsive */
@media (max-width: 1100px) { .db-main { grid-template-columns: 1fr; } }
@media (max-width: 900px)  { .db-kpis { grid-template-columns: 1fr 1fr; } .db-pipeline { grid-template-columns: 1fr 1fr; } }
@media (max-width: 480px)  { .db-kpis { grid-template-columns: 1fr 1fr; } }
</style>
@endsection

@section('content')
@php
    $rest     = \App\Restaurant::where('user_id', auth()->id())->first();
    $isPaused = $rest && $rest->is_paused;
    $pauseLabels = [
        'e2c'        => 'Coupure électrique',
        'weather'    => 'Routes impraticables',
        'overloaded' => 'Trop de commandes',
        'short_break'=> 'Pause courte',
        'manual'     => 'Fermeture manuelle',
        'other'      => 'Autre raison',
    ];

    // Ventes du jour
    $venteDuJour     = $kpis['gross_today'] ?? 0;
    $commandesAujourd = ($pipeline[0]['value'] ?? 0) + ($pipeline[1]['value'] ?? 0) + ($pipeline[2]['value'] ?? 0) + ($pipeline[3]['value'] ?? 0);
    $ticketMoyen     = $kpis['average_ticket'] ?? 0;
    $revenuNet       = $kpis['available_withdrawal'] ?? 0;
@endphp

<div class="db">

    {{-- ── 1. Bandeau statut ──────────────────────────────── --}}
    <div class="db-status {{ $isPaused ? 'db-status--paused' : 'db-status--online' }}">
        <span class="db-status__dot {{ $isPaused ? 'db-status__dot--paused' : 'db-status__dot--online' }}"></span>
        <div style="flex:1;min-width:0;">
            <div class="db-status__text">
                @if($isPaused)
                    Restaurant en pause
                    @if($rest->pause_reason && isset($pauseLabels[$rest->pause_reason])) — {{ $pauseLabels[$rest->pause_reason] }} @endif
                    @if($rest->paused_until) <span class="db-status__sub">· Réouverture à {{ $rest->paused_until->format('H:i') }}</span> @endif
                @else
                    En ligne — vous recevez des commandes
                @endif
            </div>
        </div>
        <div class="db-status__controls">
            @if(!$isPaused)
                <select id="db-pause-reason" class="db-status__select">
                    @foreach($pauseLabels as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
                <select id="db-pause-duration" class="db-status__select">
                    <option value="">Durée libre</option>
                    <option value="30">30 min</option>
                    <option value="60">1 h</option>
                    <option value="120">2 h</option>
                </select>
                <button onclick="dbAvail('pause')" class="db-status__btn db-status__btn--pause">
                    <i class="fas fa-pause"></i> Mettre en pause
                </button>
            @else
                <button onclick="dbAvail('resume')" class="db-status__btn db-status__btn--resume">
                    <i class="fas fa-play"></i> Reprendre
                </button>
            @endif
        </div>
    </div>

    {{-- ── 2. KPIs financiers du jour ─────────────────────── --}}
    <div class="db-kpis">

        {{-- Vente du jour --}}
        <a href="{{ route('r_earnings.index') }}" class="db-kpi">
            <div class="db-kpi__accent db-kpi__accent--green"></div>
            <div class="db-kpi__header">
                <div class="db-kpi__icon db-kpi__icon--green"><i class="fas fa-coins"></i></div>
            </div>
            <div class="db-kpi__label">Vente du jour</div>
            <div class="db-kpi__value db-kpi__value--green">{{ number_format($venteDuJour, 0, ',', ' ') }} <small style="font-size:14px;font-weight:600;">F</small></div>
            <div class="db-kpi__sub">CA brut · {{ now()->format('d/m/Y') }}</div>
        </a>

        {{-- Commandes aujourd'hui --}}
        <a href="{{ route('restaurant.all_orders') }}" class="db-kpi">
            <div class="db-kpi__accent db-kpi__accent--amber"></div>
            <div class="db-kpi__header">
                <div class="db-kpi__icon db-kpi__icon--amber"><i class="fas fa-bag-shopping"></i></div>
                @if($commandesAujourd > 0)
                    <span class="db-kpi__trend db-kpi__trend--up"><i class="fas fa-arrow-up" style="font-size:8px;"></i> {{ $commandesAujourd }}</span>
                @endif
            </div>
            <div class="db-kpi__label">Commandes</div>
            <div class="db-kpi__value db-kpi__value--amber">{{ $commandesAujourd }}</div>
            <div class="db-kpi__sub">Reçues aujourd'hui</div>
        </a>

        {{-- Ticket moyen --}}
        <div class="db-kpi" style="cursor:default;">
            <div class="db-kpi__accent db-kpi__accent--blue"></div>
            <div class="db-kpi__header">
                <div class="db-kpi__icon db-kpi__icon--blue"><i class="fas fa-receipt"></i></div>
            </div>
            <div class="db-kpi__label">Ticket moyen</div>
            <div class="db-kpi__value db-kpi__value--blue">{{ number_format($ticketMoyen, 0, ',', ' ') }} <small style="font-size:14px;font-weight:600;">F</small></div>
            <div class="db-kpi__sub">Par commande · aujourd'hui</div>
        </div>

        {{-- À encaisser --}}
        <a href="{{ route('r_earnings.index') }}" class="db-kpi">
            <div class="db-kpi__accent db-kpi__accent--purple"></div>
            <div class="db-kpi__header">
                <div class="db-kpi__icon db-kpi__icon--purple"><i class="fas fa-wallet"></i></div>
            </div>
            <div class="db-kpi__label">À encaisser</div>
            <div class="db-kpi__value db-kpi__value--purple">{{ number_format($revenuNet, 0, ',', ' ') }} <small style="font-size:14px;font-weight:600;">F</small></div>
            <div class="db-kpi__sub">Solde disponible · mois</div>
        </a>

    </div>

    {{-- ── 3. Pipeline opérationnel (commandes en cours) ───── --}}
    <div class="db-pipeline">
        @php
            $pipeConfig = [
                ['num' => $pipeline[0]['value']??0, 'label' => 'Nouvelles',     'cls' => 'amber',  'route' => route('restaurant.all_orders')],
                ['num' => $pipeline[1]['value']??0, 'label' => 'Préparation',   'cls' => 'blue',   'route' => route('restaurant.all_orders')],
                ['num' => $pipeline[2]['value']??0, 'label' => 'Livraison',     'cls' => 'indigo', 'route' => route('restaurant.pending_orders')],
                ['num' => $pipeline[3]['value']??0, 'label' => 'Terminées auj.','cls' => 'green',  'route' => route('restaurant.complete_orders')],
            ];
        @endphp
        @foreach($pipeConfig as $p)
            <a href="{{ $p['route'] }}" class="db-pipe">
                <span class="db-pipe__num db-pipe__num--{{ $p['cls'] }}">{{ $p['num'] }}</span>
                <span class="db-pipe__label">{{ $p['label'] }}</span>
                @if(!$loop->last)<span class="db-pipe__arrow"></span>@endif
            </a>
        @endforeach
    </div>

    {{-- ── 4. Grille principale : graphique + colonne droite ── --}}
    <div class="db-main">

        {{-- Graphique ventes de la semaine --}}
        <div class="db-card">
            <div class="db-card__head">
                <div>
                    <div class="db-card__title">Ventes de la semaine</div>
                    <div class="db-card__sub">CA brut · semaine en cours</div>
                </div>
                <div class="db-tabs nav nav-pills">
                    <a class="active" href="#db-bar"  data-toggle="tab">Barres</a>
                    <a             href="#db-line" data-toggle="tab">Courbe</a>
                </div>
            </div>
            <div class="db-card__body">
                <div class="tab-content p-0">
                    <div class="tab-pane fade show active" id="db-bar">
                        <div class="db-chart-wrap"><canvas id="barCanvas"></canvas></div>
                    </div>
                    <div class="tab-pane fade" id="db-line">
                        <div class="db-chart-wrap"><canvas id="lineCanvas"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne droite --}}
        <div class="db-right">

            {{-- Commandes actives --}}
            <div class="db-card">
                <div class="db-card__head">
                    <div>
                        <div class="db-card__title">À traiter maintenant</div>
                        <div class="db-card__sub">Nouvelles &amp; en préparation</div>
                    </div>
                    @php $activeCount = collect($recentOrders)->whereIn('status', ['Nouvelle','Préparation'])->count(); @endphp
                    @if($activeCount > 0)
                        <span style="background:rgba(245,158,11,.12);color:#d97706;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;">{{ $activeCount }}</span>
                    @endif
                </div>
                @php
                    $activeOrders = collect($recentOrders)
                        ->sortByDesc(fn($o) => $o['status'] === 'Nouvelle' ? 1 : 0)
                        ->take(5);
                @endphp
                @if($activeOrders->isNotEmpty())
                    <div class="db-orders">
                        @foreach($activeOrders as $order)
                            @php
                                $bCls = match($order['status']) {
                                    'Nouvelle'    => 'db-badge--new',
                                    'Préparation' => 'db-badge--preparing',
                                    'Livraison'   => 'db-badge--delivering',
                                    'Terminée'    => 'db-badge--done',
                                    default       => 'db-badge--cancelled',
                                };
                            @endphp
                            <div class="db-order">
                                <span class="db-order__ref">{{ $order['ref'] }}</span>
                                <span class="db-order__name">{{ $order['customer'] }}</span>
                                <span class="db-badge {{ $bCls }}">{{ $order['status'] }}</span>
                                <span class="db-order__time">{{ $order['time'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="db-empty">
                        <i class="fas fa-check-circle" style="color:var(--bd-green);"></i>
                        Aucune commande active
                    </div>
                @endif
                <a href="{{ route('restaurant.all_orders') }}" class="db-see-all">
                    Toutes les commandes <i class="fas fa-arrow-right fa-xs"></i>
                </a>
            </div>

            {{-- Top plats du jour --}}
            <div class="db-card">
                <div class="db-card__head">
                    <div>
                        <div class="db-card__title">Top plats du jour</div>
                        <div class="db-card__sub">Par quantité vendue · {{ now()->format('d/m') }}</div>
                    </div>
                    <i class="fas fa-fire" style="color:#f59e0b;font-size:14px;"></i>
                </div>
                @if(!empty($topDishes))
                    <div class="db-orders">
                        @foreach($topDishes as $i => $dish)
                            <div class="db-dish">
                                <span class="db-dish__rank db-dish__rank--{{ $i+1 }}">{{ $i+1 }}</span>
                                @if($dish['image'])
                                    <img src="{{ $dish['image'] }}" alt="{{ $dish['name'] }}" class="db-dish__img" onerror="this.style.display='none'">
                                @else
                                    <div class="db-dish__img--placeholder">🍽</div>
                                @endif
                                <div class="db-dish__info">
                                    <div class="db-dish__name">{{ $dish['name'] }}</div>
                                    <div class="db-dish__meta">{{ number_format($dish['revenue'], 0, ',', ' ') }} FCFA</div>
                                </div>
                                <span class="db-dish__qty">×{{ $dish['qty'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="db-empty">
                        <i class="fas fa-utensils"></i>
                        Aucune vente aujourd'hui
                    </div>
                @endif
                <a href="{{ route('restaurant.menu.index') }}" class="db-see-all">
                    Gérer le menu <i class="fas fa-arrow-right fa-xs"></i>
                </a>
            </div>

            {{-- Actions rapides --}}
            <div class="db-card">
                <div class="db-card__head">
                    <div class="db-card__title">Actions rapides</div>
                </div>
                <div class="db-actions">
                    <a href="{{ route('product.create') }}" class="db-action">
                        <i class="fas fa-plus-circle"></i> Ajouter un plat
                    </a>
                    <a href="{{ route('restaurant.all_orders') }}" class="db-action">
                        <i class="fas fa-receipt"></i> Commandes
                    </a>
                    <a href="{{ route('restaurant.profile') }}?tab=horaires" class="db-action">
                        <i class="fas fa-clock"></i> Horaires
                    </a>
                    <a href="{{ route('r_earnings.index') }}" class="db-action">
                        <i class="fas fa-wallet"></i> Finances
                    </a>
                    <a href="{{ route('voucher.create') }}" class="db-action">
                        <i class="fas fa-tag"></i> Créer promo
                    </a>
                    <a href="{{ route('restaurant.profile') }}" class="db-action">
                        <i class="fas fa-gear"></i> Paramètres
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
function dbAvail(action) {
    var url = action === 'pause'
        ? '{{ route("restaurant.availability.pause") }}'
        : '{{ route("restaurant.availability.resume") }}';
    var payload = action === 'pause' ? {
        reason:           document.getElementById('db-pause-reason')?.value || 'manual',
        duration_minutes: parseInt(document.getElementById('db-pause-duration')?.value) || null,
    } : {};
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(payload),
    }).then(r => r.json()).then(d => { if (d.status) location.reload(); });
}
</script>
@endsection

@section('script')
<script>
$(function () {
    var labels  = {!! json_encode($salesLabels) !!};
    var series  = {!! json_encode($salesSeries) !!};
    var isDark  = document.documentElement.getAttribute('data-theme') === 'dark';
    var green   = isDark ? '#00c957' : '#009543';
    var barPale = isDark ? 'rgba(0,201,87,.18)' : 'rgba(0,149,67,.15)';
    var grid    = isDark ? 'rgba(255,255,255,.05)' : '#f3f4f6';
    var tick    = isDark ? '#4a5568' : '#9ca3af';
    var today   = (new Date().getDay() + 6) % 7;
    var tip     = { callbacks: { label: function(t){ return ' ' + parseInt(t.yLabel).toLocaleString('fr-FR') + ' FCFA'; } } };
    var axes    = {
        xAxes: [{ gridLines:{display:false}, ticks:{fontColor:tick,fontSize:11,fontFamily:'Poppins'} }],
        yAxes: [{ gridLines:{color:grid,drawBorder:false}, ticks:{fontColor:tick,fontSize:11,fontFamily:'Poppins'} }]
    };
    new Chart(document.getElementById('barCanvas').getContext('2d'), {
        type: 'bar',
        data: { labels:labels, datasets:[{ data:series, backgroundColor:series.map((_,i)=>i===today?green:barPale), borderRadius:4, borderWidth:0 }] },
        options: { maintainAspectRatio:false, responsive:true, legend:{display:false}, tooltips:{callbacks:tip}, scales:axes }
    });
    var lCtx = document.getElementById('lineCanvas').getContext('2d');
    var grad = lCtx.createLinearGradient(0,0,0,210);
    grad.addColorStop(0, isDark ? 'rgba(0,201,87,.2)' : 'rgba(0,149,67,.15)');
    grad.addColorStop(1, 'rgba(0,149,67,0)');
    new Chart(lCtx, {
        type: 'line',
        data: { labels:labels, datasets:[{ data:series, borderColor:green, backgroundColor:grad, borderWidth:2,
            pointBackgroundColor:green, pointBorderColor:isDark?'#1a1d27':'#fff',
            pointBorderWidth:2, pointRadius:4, fill:true, lineTension:0.4 }] },
        options: { maintainAspectRatio:false, responsive:true, legend:{display:false}, tooltips:{callbacks:tip}, scales:axes }
    });
});
</script>
@endsection
