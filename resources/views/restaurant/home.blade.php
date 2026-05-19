@extends('layouts.restaurant_app')
@section('title','Tableau de bord | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Tableau de bord restaurant')
@section('dashboard_nav', 'active')

@section('style')
<style>
/* ══════════════════════════════════════════════
   KLEON DASHBOARD — Restaurant · {{ \App\Services\ConfigService::getCompanyName() }}
   ══════════════════════════════════════════════ */
:root {
    --kd-green:       #009543;
    --kd-green-2:     #22c55e;
    --kd-green-pale:  rgba(0,149,67,.10);
    --kd-green-mid:   rgba(0,149,67,.18);
    --kd-surface:     #ffffff;
    --kd-bg:          #f4f6f9;
    --kd-border:      rgba(15,23,42,.08);
    --kd-text:        #1a2035;
    --kd-text-2:      #64748b;
    --kd-text-3:      #94a3b8;
    --kd-radius:      14px;
    --kd-radius-sm:   8px;
    --kd-radius-pill: 999px;
    --kd-shadow:      0 2px 12px rgba(15,23,42,.07);
    --kd-shadow-md:   0 4px 24px rgba(15,23,42,.10);
    --kd-amber:       #d97706;
    --kd-amber-pale:  rgba(217,119,6,.10);
    --kd-blue:        #1d4ed8;
    --kd-blue-pale:   rgba(29,78,216,.10);
}

/* ── Base ─────────────────────────────────────── */
.bd-kl-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* ── Impersonation banner ─────────────────────── */
.bd-kl-banner {
    border-radius: var(--kd-radius);
    background: #fef9c3;
    border: 1px solid #fde68a;
    color: #854d0e;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.bd-kl-banner strong { font-family: 'Syne', sans-serif; font-weight: 700; }
.bd-kl-banner .btn-sm {
    border-radius: var(--kd-radius-sm);
    font-size: .78rem; font-weight: 700; padding: 5px 14px;
    background: #fff; border: 1px solid #fde68a; color: #854d0e;
}

/* ── Restaurant hero card ─────────────────────── */
.bd-kl-hero {
    background: linear-gradient(135deg, #0d6b2e 0%, #009543 60%, #22c55e 100%);
    border-radius: var(--kd-radius);
    padding: 28px 32px;
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: 2rem;
    box-shadow: 0 8px 32px rgba(0,149,67,.25);
    position: relative;
    overflow: hidden;
}
.bd-kl-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
}
.bd-kl-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 40%;
    width: 280px; height: 280px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
}
.bd-kl-hero__eyebrow {
    display: inline-flex; align-items: center; gap: 6px;
    font-family: 'Syne', sans-serif;
    font-size: .65rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase;
    background: rgba(255,255,255,.18); color: #fff;
    padding: 4px 12px; border-radius: var(--kd-radius-pill);
    margin-bottom: 12px;
}
.bd-kl-hero__title {
    font-family: 'Syne', sans-serif;
    font-size: clamp(1.3rem, 2.5vw, 1.75rem);
    font-weight: 800; color: #fff;
    letter-spacing: -.03em; margin: 0 0 8px;
}
.bd-kl-hero__desc {
    font-size: .875rem; color: rgba(255,255,255,.75);
    line-height: 1.6; margin: 0; max-width: 520px;
}
.bd-kl-hero__stats {
    display: flex; gap: 1.5rem; margin-top: 20px; flex-wrap: wrap;
}
.bd-kl-hero__stat {
    display: flex; flex-direction: column;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.20);
    border-radius: var(--kd-radius-sm);
    padding: 10px 18px; backdrop-filter: blur(8px);
    min-width: 100px;
}
.bd-kl-hero__stat-val {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem; font-weight: 800; color: #fff;
    letter-spacing: -.04em; line-height: 1;
}
.bd-kl-hero__stat-lbl {
    font-size: .68rem; color: rgba(255,255,255,.70);
    margin-top: 4px; letter-spacing: .02em;
}
.bd-kl-hero__identity {
    display: flex; flex-direction: column; align-items: center;
    gap: 10px; position: relative; z-index: 1;
}
.bd-kl-hero__avatar {
    width: 72px; height: 72px; border-radius: 18px;
    border: 3px solid rgba(255,255,255,.40);
    object-fit: cover; background: rgba(255,255,255,.12);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.bd-kl-hero__avatar img { width: 100%; height: 100%; object-fit: cover; }
.bd-kl-hero__avatar-fallback {
    font-family: 'Syne', sans-serif; font-size: 1.5rem;
    font-weight: 800; color: rgba(255,255,255,.8);
}
.bd-kl-hero__name {
    font-family: 'Syne', sans-serif; font-size: .82rem; font-weight: 700;
    color: #fff; text-align: center; max-width: 140px; line-height: 1.3;
}
.bd-kl-hero__sub {
    font-size: .7rem; color: rgba(255,255,255,.65); text-align: center;
}

/* ── Stats row ─────────────────────────────────── */
.bd-kl-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
.bd-kl-stat-card {
    background: var(--kd-surface);
    border: 1px solid var(--kd-border);
    border-radius: var(--kd-radius);
    padding: 20px 22px;
    box-shadow: var(--kd-shadow);
    display: flex; flex-direction: column; gap: 4px;
    position: relative; overflow: hidden;
    transition: box-shadow .2s, transform .15s;
}
.bd-kl-stat-card:hover {
    box-shadow: var(--kd-shadow-md);
    transform: translateY(-2px);
}
.bd-kl-stat-card__top {
    display: flex; align-items: center;
    justify-content: space-between; margin-bottom: 10px;
}
.bd-kl-stat-card__label {
    font-family: 'Syne', sans-serif;
    font-size: .68rem; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: var(--kd-text-3);
}
.bd-kl-stat-card__icon {
    width: 36px; height: 36px; border-radius: var(--kd-radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
}
.bd-kl-stat-card__icon--green  { background: var(--kd-green-pale);  color: var(--kd-green); }
.bd-kl-stat-card__icon--amber  { background: var(--kd-amber-pale);  color: var(--kd-amber); }
.bd-kl-stat-card__icon--blue   { background: var(--kd-blue-pale);   color: var(--kd-blue); }
.bd-kl-stat-card__icon--purple { background: rgba(124,58,237,.10);  color: #7c3aed; }
.bd-kl-stat-card__value {
    font-family: 'Syne', sans-serif;
    font-size: 2rem; font-weight: 800;
    color: var(--kd-text); letter-spacing: -.04em; line-height: 1;
}
.bd-kl-stat-card__hint {
    font-size: .75rem; color: var(--kd-text-3); line-height: 1.5; margin-top: 4px;
}
.bd-kl-stat-card__bar {
    position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
}
.bd-kl-stat-card__bar--green  { background: linear-gradient(90deg, var(--kd-green), var(--kd-green-2)); }
.bd-kl-stat-card__bar--amber  { background: linear-gradient(90deg, #d97706, #f59e0b); }
.bd-kl-stat-card__bar--blue   { background: linear-gradient(90deg, #1d4ed8, #3b82f6); }
.bd-kl-stat-card__bar--purple { background: linear-gradient(90deg, #7c3aed, #a78bfa); }

/* ── Finance section ───────────────────────────── */
.bd-kl-finance-section { display: grid; gap: 1rem; }
.bd-kl-finance-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.bd-kl-finance-card {
    background: var(--kd-surface);
    border: 1px solid var(--kd-border);
    border-radius: var(--kd-radius);
    padding: 20px 22px;
    box-shadow: var(--kd-shadow);
    display: flex; flex-direction: column; gap: 6px;
}
.bd-kl-finance-card.is-primary { border-left: 4px solid var(--kd-blue); }
.bd-kl-finance-card.is-success { border-left: 4px solid var(--kd-green); }
.bd-kl-finance-card.is-orange  { border-left: 4px solid var(--kd-amber); }
.bd-kl-finance-card.is-warning { border-left: 4px solid #f59e0b; }
.bd-kl-finance-card__label {
    font-family: 'Syne', sans-serif;
    font-size: .65rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: var(--kd-text-3);
}
.bd-kl-finance-card__amount {
    font-family: 'Syne', sans-serif;
    font-size: 1.6rem; font-weight: 800;
    color: var(--kd-text); letter-spacing: -.04em; line-height: 1;
}
.bd-kl-finance-card.is-primary  .bd-kl-finance-card__amount { color: var(--kd-blue); }
.bd-kl-finance-card.is-success  .bd-kl-finance-card__amount { color: var(--kd-green); }
.bd-kl-finance-card.is-orange   .bd-kl-finance-card__amount { color: #c2410c; }
.bd-kl-finance-card.is-warning  .bd-kl-finance-card__amount { color: #b45309; }
.bd-kl-finance-card__desc,
.bd-kl-finance-card__formula {
    font-size: .78rem; line-height: 1.5; color: var(--kd-text-2); margin: 0;
}
.bd-kl-finance-card__formula { color: var(--kd-text-3); font-size: .72rem; }

/* ── Action cards grid ─────────────────────────── */
.bd-kl-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.bd-kl-action {
    background: var(--kd-surface);
    border: 1px solid var(--kd-border);
    border-radius: var(--kd-radius);
    padding: 20px 20px 16px;
    box-shadow: var(--kd-shadow);
    display: flex; flex-direction: column; gap: 0;
    transition: box-shadow .2s, transform .15s;
    position: relative; overflow: hidden;
}
.bd-kl-action:hover {
    box-shadow: var(--kd-shadow-md);
    transform: translateY(-2px);
}
.bd-kl-action__top {
    display: flex; align-items: flex-start;
    justify-content: space-between; gap: .75rem; margin-bottom: 14px;
}
.bd-kl-action__eyebrow {
    font-family: 'Syne', sans-serif;
    font-size: .62rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: var(--kd-green);
}
.bd-kl-action__title {
    font-family: 'Syne', sans-serif;
    font-size: .98rem; font-weight: 800;
    color: var(--kd-text); margin: 4px 0 6px; line-height: 1.25;
}
.bd-kl-action__desc {
    font-size: .78rem; color: var(--kd-text-2); line-height: 1.55; margin: 0;
}
.bd-kl-action__icon {
    width: 42px; height: 42px; border-radius: var(--kd-radius-sm);
    display: flex; align-items: center; justify-content: center;
    background: var(--kd-green-pale); color: var(--kd-green);
    font-size: .95rem; flex-shrink: 0;
}
.bd-kl-action__bottom {
    display: flex; align-items: flex-end;
    justify-content: space-between; gap: .75rem;
    margin-top: auto; padding-top: 14px;
    border-top: 1px solid var(--kd-border);
}
.bd-kl-action__value {
    font-family: 'Syne', sans-serif;
    font-size: 2rem; font-weight: 800;
    color: var(--kd-text); letter-spacing: -.05em; line-height: 1;
}
.bd-kl-action__hint {
    font-size: .7rem; color: var(--kd-text-3); margin-top: 2px;
}
.bd-kl-action__link {
    display: inline-flex; align-items: center; gap: 6px;
    font-family: 'Syne', sans-serif;
    font-size: .76rem; font-weight: 700; color: var(--kd-green);
    text-decoration: none; white-space: nowrap;
    padding: 7px 14px; border-radius: var(--kd-radius-pill);
    background: var(--kd-green-pale);
    transition: background .15s, gap .15s;
}
.bd-kl-action__link:hover {
    background: var(--kd-green-mid); color: var(--kd-green); gap: 10px;
}
.bd-kl-action__link.is-muted {
    color: var(--kd-text-3); background: var(--kd-bg);
    pointer-events: none;
}

/* ── Charts row ────────────────────────────────── */
.bd-kl-charts-row {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1rem;
}

/* ── Panel (chart card) ────────────────────────── */
.bd-kl-panel {
    background: var(--kd-surface);
    border: 1px solid var(--kd-border);
    border-radius: var(--kd-radius);
    box-shadow: var(--kd-shadow);
    overflow: hidden;
}
.bd-kl-panel__head {
    display: flex; align-items: center;
    justify-content: space-between; gap: 1rem;
    padding: 16px 20px;
    border-bottom: 1px solid var(--kd-border);
    flex-wrap: wrap;
}
.bd-kl-panel__title {
    font-family: 'Syne', sans-serif;
    font-size: .92rem; font-weight: 800;
    color: var(--kd-text); margin: 0;
    letter-spacing: -.02em;
}
.bd-kl-panel__sub {
    font-size: .76rem; color: var(--kd-text-3); margin: 3px 0 0;
}
.bd-kl-panel__body { padding: 20px; }
.bd-kl-chart-wrap { position: relative; height: 280px; }
.bd-kl-chart-wrap canvas { width: 100% !important; height: 100% !important; }

/* Tab switch (Kleon pill tabs) */
.bd-kl-tab-switch {
    display: inline-flex;
    background: var(--kd-bg); border: 1px solid var(--kd-border);
    border-radius: var(--kd-radius-pill); padding: 3px;
}
.bd-kl-tab-switch .nav-link {
    padding: 5px 16px !important; font-size: .76rem !important;
    font-weight: 600 !important; border-radius: var(--kd-radius-pill) !important;
    color: var(--kd-text-3) !important; background: transparent !important;
    border: none !important; text-decoration: none !important;
    transition: background .15s, color .15s !important;
    font-family: 'Syne', sans-serif !important;
}
.bd-kl-tab-switch .nav-link.active {
    background: var(--kd-green) !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(0,149,67,.30) !important;
}

/* ── KPI stack ─────────────────────────────────── */
.bd-kl-kpi-stack {
    display: flex; flex-direction: column; gap: .75rem;
}
.bd-kl-kpi {
    background: var(--kd-surface);
    border: 1px solid var(--kd-border);
    border-radius: var(--kd-radius);
    padding: 18px 20px;
    box-shadow: var(--kd-shadow);
    display: flex; flex-direction: column; gap: 4px;
}
.bd-kl-kpi.is-cool { border-left: 3px solid var(--kd-amber); }
.bd-kl-kpi.is-soft { border-left: 3px solid var(--kd-green); }
.bd-kl-kpi__label {
    font-family: 'Syne', sans-serif;
    font-size: .65rem; font-weight: 800; letter-spacing: .1em;
    text-transform: uppercase; color: var(--kd-text-3);
}
.bd-kl-kpi__value {
    font-family: 'Syne', sans-serif;
    font-size: 1.4rem; font-weight: 800;
    color: var(--kd-text); letter-spacing: -.03em;
}
.bd-kl-kpi.is-cool .bd-kl-kpi__value { color: var(--kd-amber); }
.bd-kl-kpi.is-soft .bd-kl-kpi__value { color: var(--kd-green); }
.bd-kl-kpi__text {
    font-size: .74rem; color: var(--kd-text-3); line-height: 1.5; margin: 0;
}

/* ── Annual chart full width ───────────────────── */
.bd-kl-annual .bd-kl-chart-wrap {
    height: 220px;
}

/* ── Responsive ────────────────────────────────── */
@media (max-width: 1199px) {
    .bd-kl-stats { grid-template-columns: repeat(2, 1fr); }
    .bd-kl-charts-row { grid-template-columns: 1fr; }
    .bd-kl-finance-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 767px) {
    .bd-kl-stats { grid-template-columns: 1fr 1fr; }
    .bd-kl-actions { grid-template-columns: 1fr; }
    .bd-kl-finance-grid { grid-template-columns: 1fr; }
    .bd-kl-hero { grid-template-columns: 1fr; }
    .bd-kl-hero__identity { display: none; }
    .bd-kl-hero__stats { gap: .75rem; }
}
</style>
@endsection

@section('content')
@php
    $dashboardContext    = $dashboardContext ?? [];
    $dashboardReadOnly  = (bool) ($dashboardContext['read_only'] ?? false);
    $ordersLink         = $dashboardReadOnly ? null : route('restaurant.all_orders');
    $categoriesLink     = $dashboardReadOnly ? null : route('category.index');
    $productsLink       = $dashboardReadOnly ? null : route('product.index');
    $pendingOrdersLink  = $dashboardReadOnly ? null : route('restaurant.pending_orders');
    $completedOrdersLink= $dashboardReadOnly ? null : route('restaurant.complete_orders');
    $scheduleOrdersLink = $dashboardReadOnly ? null : route('restaurant.schedule_orders');

    $restaurantLogoUrl = null;
    if (!empty($restaurant->logo ?? null)) {
        $restaurantLogoUrl = \Illuminate\Support\Str::startsWith($restaurant->logo, ['http://', 'https://'])
            ? $restaurant->logo
            : asset('images/restaurant_images/' . $restaurant->logo);
    }

    $financeByLabel = collect($financialDashboard['cards'] ?? [])->keyBy('label');
    $financeRows    = $financialDashboard['rows'] ?? [];

    $actionCards = [
        ['eyebrow'=>'Flux commandes',  'title'=>'Commandes suivies',   'desc'=>'Vue consolidée des commandes remontées dans votre espace restaurant.',        'value'=>number_format($trackedOrdersCount,0,',',' '), 'hint'=>'Toutes les commandes visibles côté restaurant.',            'icon'=>'fas fa-receipt',          'color'=>'green',  'link'=>$ordersLink,          'lbl'=>'Ouvrir les commandes'],
        ['eyebrow'=>'Catalogue',       'title'=>'Catégories actives',  'desc'=>'Structure actuelle de votre menu et familles de produits visibles.',           'value'=>number_format($categories->count(),0,',',' '),'hint'=>'Catégories actuellement configurées.',                     'icon'=>'fas fa-layer-group',      'color'=>'blue',   'link'=>$categoriesLink,      'lbl'=>'Gérer les catégories'],
        ['eyebrow'=>'Produits',        'title'=>'Catalogue actif',     'desc'=>'Produits publiés et utilisables pour la prise de commande.',                   'value'=>number_format($products->count(),0,',',''),   'hint'=>'Produits visibles ou configurés dans le menu.',             'icon'=>'fas fa-utensils',         'color'=>'purple', 'link'=>$productsLink,        'lbl'=>'Gérer les produits'],
        ['eyebrow'=>'Nouveautés',      'title'=>'Nouvelles commandes', 'desc'=>'Commandes reçues qui demandent une prise en charge rapide.',                   'value'=>number_format($getPendings,0,',',' '),         'hint'=>'Volume entrant nécessitant une réaction opérationnelle.',   'icon'=>'fas fa-bell-concierge',   'color'=>'amber',  'link'=>$ordersLink,          'lbl'=>'Traiter maintenant'],
        ['eyebrow'=>'Préparation',     'title'=>'Commandes assignées', 'desc'=>'Commandes déjà engagées dans le flux cuisine ou livraison.',                   'value'=>number_format($subscriptions->count(),0,',',' '),'hint'=>'Ordres actuellement coordonnés avec le terrain.',           'icon'=>'fas fa-people-carry',     'color'=>'green',  'link'=>$pendingOrdersLink,   'lbl'=>'Voir les assignées'],
        ['eyebrow'=>'Clôture',         'title'=>'Commandes terminées', 'desc'=>'Commandes closes dans le cycle opérationnel restaurant.',                       'value'=>number_format($getComleted,0,',',' '),         'hint'=>'Historique finalisé sur la période observée.',              'icon'=>'fas fa-circle-check',     'color'=>'blue',   'link'=>$completedOrdersLink, 'lbl'=>'Voir les terminées'],
    ];
@endphp

<section class="content">
<div class="container-fluid">
<div class="bd-kl-page">

    {{-- ── Impersonation banner ──────────────────────── --}}
    @if($dashboardReadOnly)
        <div class="bd-kl-banner">
            <div>
                <strong>{{ $dashboardContext['banner_title'] ?? 'Aperçu du dashboard' }}</strong>
                <div style="font-size:.85rem;margin-top:2px">{{ $dashboardContext['banner_message'] ?? 'Consultation du dashboard restaurant.' }}</div>
            </div>
            @if(!empty($dashboardContext['back_url']))
                <a href="{{ $dashboardContext['back_url'] }}" class="btn btn-sm">{{ $dashboardContext['back_label'] ?? 'Retour' }}</a>
            @endif
        </div>
    @endif

    {{-- ── Hero card restaurant ──────────────────────── --}}
    <div class="bd-kl-hero">
        <div style="position:relative;z-index:1;">
            <div class="bd-kl-hero__eyebrow"><i class="fas fa-store fa-xs"></i> Espace restaurant</div>
            <h1 class="bd-kl-hero__title">
                Pilotage restaurant
                @if(!empty($restaurant->name ?? null))
                    <span style="opacity:.75">·</span> {{ $restaurant->name }}
                @endif
            </h1>
            <p class="bd-kl-hero__desc">Suivez vos commandes, votre catalogue actif et votre trésorerie nette sans mélanger activité brute, commission plateforme et reversements.</p>
            <div class="bd-kl-hero__stats">
                <div class="bd-kl-hero__stat">
                    <span class="bd-kl-hero__stat-val">{{ number_format($getComleted, 0, ',', ' ') }}</span>
                    <span class="bd-kl-hero__stat-lbl">Commandes validées</span>
                </div>
                <div class="bd-kl-hero__stat">
                    <span class="bd-kl-hero__stat-val">{{ number_format($getPendings, 0, ',', ' ') }}</span>
                    <span class="bd-kl-hero__stat-lbl">Nouvelles commandes</span>
                </div>
                <div class="bd-kl-hero__stat">
                    <span class="bd-kl-hero__stat-val">{{ number_format($products->count(), 0, ',', ' ') }} <span style="font-size:1rem;font-weight:600;">produits</span></span>
                    <span class="bd-kl-hero__stat-lbl">Catalogue actif</span>
                </div>
            </div>
        </div>
        <div class="bd-kl-hero__identity">
            <div class="bd-kl-hero__avatar">
                @if($restaurantLogoUrl)
                    <img src="{{ $restaurantLogoUrl }}" alt="{{ $restaurant->name ?? 'Restaurant' }}"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <span class="bd-kl-hero__avatar-fallback" style="display:none">{{ strtoupper(substr($restaurant->name ?? 'R', 0, 2)) }}</span>
                @else
                    <span class="bd-kl-hero__avatar-fallback">{{ strtoupper(substr($restaurant->name ?? 'R', 0, 2)) }}</span>
                @endif
            </div>
            <div class="bd-kl-hero__name">{{ $restaurant->name ?? 'Restaurant' }}</div>
            <div class="bd-kl-hero__sub">{{ trim(($restaurant->city ?? 'Brazzaville') . ' · ' . ($restaurant->phone ?? '')) }}</div>
        </div>
    </div>

    {{-- ── 4 stat cards ───────────────────────────────── --}}
    <div class="bd-kl-stats">
        <div class="bd-kl-stat-card">
            <div class="bd-kl-stat-card__top">
                <span class="bd-kl-stat-card__label">Commandes suivies</span>
                <span class="bd-kl-stat-card__icon bd-kl-stat-card__icon--green"><i class="fas fa-receipt"></i></span>
            </div>
            <div class="bd-kl-stat-card__value">{{ number_format($trackedOrdersCount, 0, ',', ' ') }}</div>
            <p class="bd-kl-stat-card__hint">Volume global remontée sur votre espace.</p>
            <div class="bd-kl-stat-card__bar bd-kl-stat-card__bar--green"></div>
        </div>
        <div class="bd-kl-stat-card">
            <div class="bd-kl-stat-card__top">
                <span class="bd-kl-stat-card__label">Nouvelles commandes</span>
                <span class="bd-kl-stat-card__icon bd-kl-stat-card__icon--amber"><i class="fas fa-bell-concierge"></i></span>
            </div>
            <div class="bd-kl-stat-card__value">{{ number_format($getPendings, 0, ',', ' ') }}</div>
            <p class="bd-kl-stat-card__hint">Demandes en attente d'une prise en charge.</p>
            <div class="bd-kl-stat-card__bar bd-kl-stat-card__bar--amber"></div>
        </div>
        <div class="bd-kl-stat-card">
            <div class="bd-kl-stat-card__top">
                <span class="bd-kl-stat-card__label">Commandes terminées</span>
                <span class="bd-kl-stat-card__icon bd-kl-stat-card__icon--blue"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="bd-kl-stat-card__value">{{ number_format($getComleted, 0, ',', ' ') }}</div>
            <p class="bd-kl-stat-card__hint">Cycles opérationnels clôturés.</p>
            <div class="bd-kl-stat-card__bar bd-kl-stat-card__bar--blue"></div>
        </div>
        <div class="bd-kl-stat-card">
            <div class="bd-kl-stat-card__top">
                <span class="bd-kl-stat-card__label">Produits actifs</span>
                <span class="bd-kl-stat-card__icon bd-kl-stat-card__icon--purple"><i class="fas fa-utensils"></i></span>
            </div>
            <div class="bd-kl-stat-card__value">{{ number_format($products->count(), 0, ',', ' ') }}</div>
            <p class="bd-kl-stat-card__hint">Produits publiés dans votre catalogue.</p>
            <div class="bd-kl-stat-card__bar bd-kl-stat-card__bar--purple"></div>
        </div>
    </div>

    {{-- ── Finance cards ───────────────────────────────── --}}
    @if(!empty($financeRows))
        <div class="bd-kl-finance-section">
            @foreach($financeRows as $row)
                <div class="bd-kl-finance-grid">
                    @foreach($row as $card)
                        <div class="bd-kl-finance-card is-{{ $card['tone'] }}">
                            <span class="bd-kl-finance-card__label">{{ $card['label'] }}</span>
                            <strong class="bd-kl-finance-card__amount">{{ number_format(round((float)($card['amount'] ?? 0)), 0, ',', ' ') }} FCFA</strong>
                            <p class="bd-kl-finance-card__desc">{{ $card['description'] }}</p>
                            <p class="bd-kl-finance-card__formula">{{ $card['formula'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Action cards ────────────────────────────────── --}}
    <div class="bd-kl-actions">
        @foreach($actionCards as $card)
            <article class="bd-kl-action">
                <div class="bd-kl-action__top">
                    <div>
                        <span class="bd-kl-action__eyebrow">{{ $card['eyebrow'] }}</span>
                        <h3 class="bd-kl-action__title">{{ $card['title'] }}</h3>
                        <p class="bd-kl-action__desc">{{ $card['desc'] }}</p>
                    </div>
                    <span class="bd-kl-action__icon"><i class="{{ $card['icon'] }}"></i></span>
                </div>
                <div class="bd-kl-action__bottom">
                    <div>
                        <div class="bd-kl-action__value">{{ $card['value'] }}</div>
                        <div class="bd-kl-action__hint">{{ $card['hint'] }}</div>
                    </div>
                    @if(!empty($card['link']))
                        <a href="{{ $card['link'] }}" class="bd-kl-action__link">
                            {{ $card['lbl'] }} <i class="fas fa-arrow-right fa-xs"></i>
                        </a>
                    @else
                        <span class="bd-kl-action__link is-muted">Aperçu</span>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    {{-- ── Charts row ─────────────────────────────────── --}}
    <div class="bd-kl-charts-row">
        {{-- Revenue / Donut chart --}}
        <div class="bd-kl-panel">
            <div class="bd-kl-panel__head">
                <div>
                    <h3 class="bd-kl-panel__title">Pilotage commercial</h3>
                    <p class="bd-kl-panel__sub">Ventes mensuelles et répartition des commandes.</p>
                </div>
                <div class="bd-kl-tab-switch nav nav-pills" role="tablist">
                    <a class="nav-link active" href="#revenue-chart" data-toggle="tab">Ventes</a>
                    <a class="nav-link" href="#sales-chart" data-toggle="tab">Commandes</a>
                </div>
            </div>
            <div class="bd-kl-panel__body">
                <div class="tab-content p-0">
                    <div class="tab-pane fade show active" id="revenue-chart">
                        <div class="bd-kl-chart-wrap">
                            <canvas id="revenue-chart-canvas" height="280"></canvas>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="sales-chart">
                        <div class="bd-kl-chart-wrap">
                            <canvas id="sales-chart-canvas" height="280"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI stack --}}
        <div class="bd-kl-kpi-stack">
            <div class="bd-kl-kpi">
                <span class="bd-kl-kpi__label">Commandes programmées</span>
                <span class="bd-kl-kpi__value">{{ number_format($scheduleOrders ?? 0, 0, ',', ' ') }}</span>
                <p class="bd-kl-kpi__text">Demandes planifiées en attente de leur fenêtre de traitement.</p>
            </div>
            <div class="bd-kl-kpi is-cool">
                <span class="bd-kl-kpi__label">Reversement en attente</span>
                <span class="bd-kl-kpi__value">{{ number_format((float) data_get($financeByLabel->get('En attente de reversement'), 'amount', 0), 0, ',', ' ') }} FCFA</span>
                <p class="bd-kl-kpi__text">Montant encore retenu par validation, sécurité, litige ou rapprochement.</p>
            </div>
            <div class="bd-kl-kpi is-soft">
                <span class="bd-kl-kpi__label">Disponible au retrait</span>
                <span class="bd-kl-kpi__value">{{ number_format((float) data_get($financeByLabel->get('Disponible au retrait'), 'amount', 0), 0, ',', ' ') }} FCFA</span>
                <p class="bd-kl-kpi__text">Net partenaire réellement libéré selon le ledger de reversement.</p>
            </div>
        </div>
    </div>

    {{-- ── Annual chart ────────────────────────────────── --}}
    <div class="bd-kl-panel bd-kl-annual">
        <div class="bd-kl-panel__head">
            <div>
                <h3 class="bd-kl-panel__title">Historique annuel</h3>
                <p class="bd-kl-panel__sub">Tendance des commandes agrégées pour piloter la charge et la saisonnalité.</p>
            </div>
            @if($scheduleOrdersLink)
                <a href="{{ $scheduleOrdersLink }}" class="bd-kl-action__link">
                    Voir les programmées <i class="fas fa-arrow-right fa-xs"></i>
                </a>
            @endif
        </div>
        <div class="bd-kl-panel__body">
            <div class="bd-kl-chart-wrap">
                <canvas id="line-chart" height="220"></canvas>
            </div>
        </div>
    </div>

</div>{{-- /.bd-kl-page --}}
</div>{{-- /.container-fluid --}}
</section>
@endsection

@section('script')
<script>
$(function () {
    'use strict';

    var green    = '#009543';
    var green2   = '#22c55e';
    var greenPale= 'rgba(0,149,67,.12)';
    var gray     = 'rgba(100,116,139,0.15)';
    var textMid  = '#94a3b8';
    var gridLine = 'rgba(15,23,42,0.05)';

    var baseOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: { display: false },
        scales: {
            xAxes: [{ gridLines: { display: false }, ticks: { fontColor: textMid, fontSize: 11 } }],
            yAxes: [{ gridLines: { display: true, color: gridLine, drawBorder: false }, ticks: { fontColor: textMid, fontSize: 11 } }]
        }
    };

    /* Revenue line chart */
    var revenueCtx = document.getElementById('revenue-chart-canvas').getContext('2d');
    var revenueGrad = revenueCtx.createLinearGradient(0, 0, 0, 280);
    revenueGrad.addColorStop(0, 'rgba(0,149,67,0.25)');
    revenueGrad.addColorStop(1, 'rgba(0,149,67,0.02)');

    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: [{!!$monthstring!!}],
            datasets: [{
                label: 'Total Revenus',
                backgroundColor: revenueGrad,
                borderColor: green,
                borderWidth: 2.5,
                pointBackgroundColor: green,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                lineTension: 0.4,
                data: [{{$total}}]
            }, {
                label: 'Total Commandes',
                backgroundColor: 'transparent',
                borderColor: gray,
                borderWidth: 1.5,
                pointRadius: 3,
                pointBackgroundColor: '#94a3b8',
                fill: false,
                lineTension: 0.4,
                data: [{{$count}}]
            }]
        },
        options: baseOptions
    });

    /* Donut chart */
    var pieCtx = $('#sales-chart-canvas').get(0).getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['En attente', 'Terminées', 'Annulées'],
            datasets: [{
                data: [{{$getPendingAvg}}, {{$getCompletedAvg}}, {{$getCanceledAvg}}],
                backgroundColor: ['#fef9c3', '#dcfce7', '#fee2e2'],
                borderColor: ['#d97706', '#009543', '#dc2626'],
                borderWidth: 2,
                hoverOffset: 6,
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            cutoutPercentage: 70,
            legend: { display: true, position: 'bottom', labels: { fontSize: 11, fontColor: '#64748b', padding: 16 } }
        }
    });

    /* Annual line chart */
    var annualCtx = $('#line-chart').get(0).getContext('2d');
    var annualGrad = annualCtx.createLinearGradient(0, 0, 0, 220);
    annualGrad.addColorStop(0, 'rgba(0,149,67,0.18)');
    annualGrad.addColorStop(1, 'rgba(0,149,67,0.02)');

    new Chart(annualCtx, {
        type: 'line',
        data: {
            labels: [{!!$yearstring!!}],
            datasets: [{
                label: 'Commandes',
                fill: true,
                backgroundColor: annualGrad,
                borderColor: green,
                borderWidth: 2,
                lineTension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: green,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                data: [{{$totalYearBy}}]
            }]
        },
        options: $.extend(true, {}, baseOptions, {
            scales: {
                yAxes: [{ gridLines: { display: true, color: gridLine, drawBorder: false }, ticks: { fontColor: textMid, fontSize: 11 } }]
            }
        })
    });
});
</script>
@endsection
