@extends('layouts.admin-modern')
@section('title', ($workspaceMeta['page_title'] ?? 'Pilotage') . ' | Admin')
@section('nav_active', 'dashboard')
@section('page_title', $workspaceMeta['page_title'] ?? 'Tableau de bord')

@php
    $workspace = $workspace ?? 'bantudelice';
    $period = $period ?? 30;
    $workspaceLabel = $workspaceMeta['short_title'] ?? ucfirst($workspace);
    $workspaceHeading = $workspaceMeta['heading'] ?? 'Pilotage operationnel';
    $workspaceIntro = $workspaceMeta['intro'] ?? 'Vue de synthese des operations, revenus et files a traiter.';
    $globalState = strtolower((string) ($workspaceMeta['global_state'] ?? 'stable'));
    $globalStateLabel = $workspaceMeta['global_state'] ?? 'Stable';
    $chartLabels = collect($revenueChart['labels'] ?? []);
    $chartValues = collect($revenueChart['values'] ?? [])->map(function ($value) {
        return (float) $value;
    })->values();
    $chartPointCount = max($chartValues->count(), 1);
    $chartMax = max((float) $chartValues->max(), 1);
    $chartLatest = (float) ($chartValues->last() ?? 0);
    $chartAverage = (float) ($chartValues->avg() ?? 0);
    $chartPoints = $chartValues->map(function ($value, $index) use ($chartPointCount, $chartMax) {
        $x = $chartPointCount === 1 ? 50 : round(($index / ($chartPointCount - 1)) * 100, 2);
        $y = round(100 - (($value / $chartMax) * 100), 2);

        return $x . ',' . $y;
    })->implode(' ');
    $chartColumns = $chartValues->map(function ($value) use ($chartMax) {
        return (int) round(($value / $chartMax) * 100);
    });
    $summaryMetrics = collect($summaryCards ?? []);
    $alertsCollection = collect($alerts ?? []);
    $breakdownItems = collect(data_get($serviceBreakdown ?? [], 'items', []));
    $activityCollection = collect($liveActivities ?? []);
    $operationalCollection = collect($operationalMetrics ?? []);
    $decisionCollection = collect($secondaryList ?? []);
    $tableRows = collect(data_get($primaryTable ?? [], 'rows', []));
    $tableHeaders = collect(data_get($primaryTable ?? [], 'headers', []));
    $visibleTableRows = $tableRows->take(4);
    $topRestaurantsCollection = collect($topRestaurants ?? []);
    $visibleTopRestaurants = $topRestaurantsCollection->take(4);
    $topRestaurantRevenueMax = max((float) $topRestaurantsCollection->max('revenue'), 1);
    $visibleKpis = collect($kpis ?? [])->take(4)->values();
    $visibleAlerts = $alertsCollection->take(4);
    $visibleActivities = $activityCollection->take(4);
    $visibleDecisions = $decisionCollection->take(3);
    $visibleOperationalMetrics = $operationalCollection->take(4);
    $visibleStructureMetrics = collect($summaryMetrics)->take(4);
    $heroStats = collect([
        [
            'label' => 'Etat global',
            'value' => $globalStateLabel,
            'meta' => 'Lecture consolidee du perimetre ' . $workspaceLabel,
        ],
        [
            'label' => 'Incidents ouverts',
            'value' => number_format($alertsCollection->sum('count'), 0, ',', ' '),
            'meta' => 'Alertes et files visibles dans le cockpit',
        ],
        [
            'label' => 'Volume observe',
            'value' => number_format(data_get($serviceBreakdown, 'total', 0), 0, ',', ' '),
            'meta' => 'Operations cumulees sur la periode',
        ],
    ]);
    $workspaceTabs = [
        ['key' => 'bantudelice', 'label' => 'BantuDelice', 'href' => route('admin.dashboard', ['workspace' => 'bantudelice', 'period' => $period])],
        ['key' => 'kende', 'label' => 'Kende', 'href' => route('admin.dashboard', ['workspace' => 'kende', 'period' => $period])],
        ['key' => 'mema', 'label' => 'Mema', 'href' => route('admin.dashboard', ['workspace' => 'mema', 'period' => $period])],
    ];
    $periodTabs = collect($periodOptions ?? [7, 30, 90])->map(function ($option) use ($workspace) {
        return [
            'value' => (int) $option,
            'href' => route('admin.dashboard', ['workspace' => $workspace, 'period' => (int) $option]),
        ];
    });
    $workspaceMediaLink = $workspace === 'bantudelice'
        ? ['label' => 'Media produits', 'route' => 'total.pro', 'params' => ['media_status' => 'missing'], 'meta' => 'Backlog']
        : ['label' => 'Media accueil', 'route' => 'admin.home-content.edit', 'params' => ['workspace' => $workspace, 'focus' => 'media'], 'meta' => 'Visuels CMS'];
    $moduleLinks = collect([
        ['label' => 'Commandes', 'route' => 'admin.all_orders', 'params' => ['workspace' => $workspace], 'meta' => 'Flux food'],
        ['label' => 'Restaurants', 'route' => 'restaurant.index', 'params' => ['workspace' => $workspace], 'meta' => 'Partenaires'],
        $workspaceMediaLink,
        ['label' => 'Paiements', 'route' => 'admin.payments.dashboard', 'params' => ['workspace' => $workspace], 'meta' => 'Finance'],
        ['label' => 'Transport', 'route' => 'admin.transport.dashboard', 'params' => ['workspace' => 'kende'], 'meta' => 'Courses'],
        ['label' => 'Colis', 'route' => 'admin.colis.index', 'params' => ['workspace' => 'mema'], 'meta' => 'Expeditions'],
        ['label' => 'Support', 'route' => 'admin.support-tickets.index', 'params' => ['workspace' => $workspace], 'meta' => 'Tickets'],
        ['label' => 'Accueil', 'route' => 'admin.home-content.edit', 'params' => ['workspace' => $workspace], 'meta' => 'CMS'],
        ['label' => 'Observabilite', 'route' => 'admin.metrics', 'params' => ['workspace' => $workspace], 'meta' => 'KPIs'],
    ])->filter(function ($link) {
        return app('router')->has($link['route']);
    })->values();
    $visibleModuleLinks = $moduleLinks->take(9);
    $primaryModuleLinks = $visibleModuleLinks->take(4);
    $secondaryModuleLinks = $visibleModuleLinks->slice(4)->values();
    $heroActionLinks = $moduleLinks->take(3);
@endphp

@section('style')
<style>
/* ── DASHBOARD V2 ─────────────────────────────────────── */
.dash-grid { display:grid; gap:20px; }
.dash-row  { display:grid; gap:16px; grid-template-columns:repeat(4,1fr); }
.dash-row-3{ display:grid; gap:16px; grid-template-columns:repeat(3,1fr); }
.dash-row-2{ display:grid; gap:16px; grid-template-columns:2fr 1fr; }
.dash-row-main { display:grid; gap:16px; grid-template-columns:1fr 340px; }

/* KPI card */
.dash-kpi {
  background:#fff;
  border-radius:16px;
  padding:20px;
  display:flex;
  flex-direction:column;
  gap:8px;
  border:1px solid #e5e7eb;
  position:relative;
  overflow:hidden;
}
.dash-kpi::before {
  content:'';
  position:absolute;
  top:0; left:0; right:0;
  height:3px;
}
.dash-kpi.c-blue::before   { background:linear-gradient(90deg,#3b82f6,#6366f1); }
.dash-kpi.c-green::before  { background:linear-gradient(90deg,#10b981,#34d399); }
.dash-kpi.c-orange::before { background:linear-gradient(90deg,#f97316,#fb923c); }
.dash-kpi.c-red::before    { background:linear-gradient(90deg,#ef4444,#f87171); }
.dash-kpi-icon {
  width:44px; height:44px;
  border-radius:12px;
  display:flex; align-items:center; justify-content:center;
  font-size:18px; color:#fff; flex-shrink:0;
  align-self:flex-start;
}
.dash-kpi.c-blue   .dash-kpi-icon { background:linear-gradient(135deg,#3b82f6,#6366f1); }
.dash-kpi.c-green  .dash-kpi-icon { background:linear-gradient(135deg,#10b981,#34d399); }
.dash-kpi.c-orange .dash-kpi-icon { background:linear-gradient(135deg,#f97316,#fb923c); }
.dash-kpi.c-red    .dash-kpi-icon { background:linear-gradient(135deg,#ef4444,#f87171); }
.dash-kpi-val {
  font-size:1.7rem; font-weight:900; color:#111827; line-height:1;
}
.dash-kpi-lbl { font-size:.75rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; }
.dash-kpi-meta { font-size:.72rem; color:#9ca3af; margin-top:2px; }

/* Card générique */
.dash-card {
  background:#fff;
  border-radius:16px;
  border:1px solid #e5e7eb;
  overflow:hidden;
}
.dash-card-head {
  padding:16px 20px 0;
  display:flex; align-items:center; justify-content:space-between;
}
.dash-card-title { font-size:.88rem; font-weight:800; color:#111827; }
.dash-card-sub   { font-size:.72rem; color:#9ca3af; margin-top:2px; }
.dash-card-body  { padding:16px 20px 20px; }

/* Hero band */
.dash-hero {
  background:linear-gradient(135deg,#111827 0%,#1e3a5f 100%);
  border-radius:16px;
  padding:24px 28px;
  color:#fff;
  display:flex; align-items:center; justify-content:space-between; gap:20px;
}
.dash-hero-title { font-size:1.4rem; font-weight:900; margin-bottom:4px; }
.dash-hero-sub   { font-size:.82rem; color:#94a3b8; }
.dash-hero-stats { display:flex; gap:24px; margin-top:16px; flex-wrap:wrap; }
.dash-hero-stat-val { font-size:1.2rem; font-weight:900; }
.dash-hero-stat-lbl { font-size:.68rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; }
.dash-hero-state {
  padding:6px 14px; border-radius:99px; font-size:.72rem; font-weight:800; letter-spacing:.05em;
}
.dash-hero-state.stable  { background:#052e16; color:#4ade80; }
.dash-hero-state.warning { background:#422006; color:#fb923c; }
.dash-hero-state.critical{ background:#450a0a; color:#f87171; }

/* Workspace tabs */
.dash-tabs { display:flex; gap:6px; flex-wrap:wrap; }
.dash-tab {
  padding:6px 14px; border-radius:8px; font-size:.78rem; font-weight:700;
  text-decoration:none; color:#6b7280; background:#f1f5f9; border:1px solid #e5e7eb;
  transition:all .15s;
}
.dash-tab.active, .dash-tab:hover { background:#1e3a5f; color:#fff; border-color:#1e3a5f; }

/* Period selector */
.dash-period { display:flex; gap:4px; }
.dash-period-btn {
  padding:5px 12px; border-radius:8px; font-size:.72rem; font-weight:700;
  text-decoration:none; color:#6b7280; background:#f8fafc; border:1px solid #e5e7eb;
}
.dash-period-btn.active { background:#e0f2fe; color:#0369a1; border-color:#bae6fd; }

/* Donut chart */
.dash-donut { position:relative; width:160px; height:160px; flex-shrink:0; }
.dash-donut svg { transform:rotate(-90deg); }
.dash-donut-center {
  position:absolute; inset:0;
  display:flex; flex-direction:column; align-items:center; justify-content:center;
}
.dash-donut-val { font-size:1.2rem; font-weight:900; color:#111827; line-height:1; }
.dash-donut-lbl { font-size:.65rem; color:#9ca3af; font-weight:700; text-transform:uppercase; }

/* Table dense */
.dash-table { width:100%; border-collapse:collapse; }
.dash-table th {
  font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
  color:#9ca3af; padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:left;
}
.dash-table td {
  padding:10px 10px; border-bottom:1px solid #f9fafb;
  font-size:.78rem; color:#374151; vertical-align:middle;
}
.dash-table tr:last-child td { border-bottom:none; }
.dash-table tr:hover td { background:#fafafa; }
.dash-badge {
  display:inline-block; padding:3px 8px; border-radius:6px;
  font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em;
}
.dash-badge.green  { background:#dcfce7; color:#15803d; }
.dash-badge.blue   { background:#dbeafe; color:#1d4ed8; }
.dash-badge.orange { background:#fff7ed; color:#c2410c; }
.dash-badge.red    { background:#fee2e2; color:#b91c1c; }
.dash-badge.gray   { background:#f3f4f6; color:#6b7280; }
.dash-badge.purple { background:#ede9fe; color:#7c3aed; }

/* Alert items */
.dash-alert {
  display:flex; align-items:flex-start; gap:12px;
  padding:12px 14px; border-radius:12px; margin-bottom:8px;
  border:1px solid;
}
.dash-alert.warning { background:#fffbeb; border-color:#fde68a; }
.dash-alert.critical{ background:#fef2f2; border-color:#fecaca; }
.dash-alert.ok      { background:#f0fdf4; border-color:#bbf7d0; }
.dash-alert-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:14px; }
.dash-alert.warning  .dash-alert-icon { background:#fef3c7; color:#d97706; }
.dash-alert.critical .dash-alert-icon { background:#fee2e2; color:#dc2626; }
.dash-alert-title { font-size:.82rem; font-weight:800; color:#111827; }
.dash-alert-msg   { font-size:.72rem; color:#6b7280; margin-top:2px; line-height:1.4; }
.dash-alert-count { font-size:1.1rem; font-weight:900; color:#111827; margin-left:auto; flex-shrink:0; }

/* Activity feed */
.dash-activity { display:flex; flex-direction:column; gap:10px; }
.dash-activity-item { display:flex; align-items:center; gap:10px; }
.dash-activity-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.dash-activity-dot.green  { background:#22c55e; }
.dash-activity-dot.blue   { background:#3b82f6; }
.dash-activity-dot.gold   { background:#f59e0b; }
.dash-activity-text { font-size:.78rem; color:#374151; flex:1; line-height:1.4; }
.dash-activity-time { font-size:.68rem; color:#9ca3af; white-space:nowrap; }

/* Op metrics */
.dash-opmetric { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f1f5f9; }
.dash-opmetric:last-child { border-bottom:none; }
.dash-opmetric-lbl { font-size:.78rem; color:#6b7280; font-weight:600; }
.dash-opmetric-val { font-size:.88rem; font-weight:800; color:#111827; }

/* Decision list */
.dash-decision { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:10px; background:#f8fafc; border:1px solid #e5e7eb; margin-bottom:8px; }
.dash-decision-rank { font-size:1.1rem; font-weight:900; color:#111827; width:60px; flex-shrink:0; }
.dash-decision-body { flex:1; min-width:0; }
.dash-decision-title { font-size:.8rem; font-weight:800; color:#111827; }
.dash-decision-meta  { font-size:.7rem; color:#9ca3af; }
.dash-decision-action { font-size:.72rem; font-weight:700; color:#3b82f6; text-decoration:none; white-space:nowrap; }

/* Bar sparkline (top restaurants) */
.dash-top-bar { height:6px; border-radius:99px; background:#e5e7eb; overflow:hidden; margin-top:4px; }
.dash-top-bar-fill { height:100%; border-radius:99px; background:linear-gradient(90deg,#3b82f6,#6366f1); }

/* Responsive */
@media (max-width:1200px) {
  .dash-row { grid-template-columns:repeat(2,1fr); }
  .dash-row-main { grid-template-columns:1fr; }
}
@media (max-width:768px) {
  .dash-row, .dash-row-3, .dash-row-2, .dash-row-main { grid-template-columns:1fr; }
}

/* ── CSS EXISTANT ops-* conservé ─────────────────────── */
.ops-dashboard,
.ops-shell,
.ops-stack,
.ops-breakdown,
.ops-alert-list,
.ops-activity-list,
.ops-action-list,
.ops-top-list {
    display: grid;
    gap: .85rem;
}

.ops-dashboard {
    max-width: 1460px;
    margin: 0 auto;
}

.ops-hero {
    position: relative;
    overflow: hidden;
    padding: 1rem 1.05rem;
    border-radius: 26px;
    border: 1px solid rgba(255,255,255,.1);
    background:
        radial-gradient(circle at top right, rgba(255,255,255,.16), transparent 24%),
        radial-gradient(circle at bottom left, rgba(34,197,94,.24), transparent 36%),
        linear-gradient(135deg, #07130c 0%, #0d2d19 48%, #009543 100%);
    color: #f8fafc;
    box-shadow: 0 22px 58px rgba(15,23,42,.18);
}

.ops-hero::after {
    content: '';
    position: absolute;
    inset: auto -90px -100px auto;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,.16), transparent 62%);
    pointer-events: none;
}

.ops-toolbar,
.ops-toolbar__group,
.ops-alert-item__top,
.ops-top-item__top,
.ops-breakdown__row-top,
.ops-panel__header,
.ops-fold summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
}

.ops-toolbar {
    margin-bottom: .85rem;
}

.ops-shell {
    gap: 1rem;
}

.ops-pill,
.ops-state,
.ops-status,
.ops-alert-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    padding: 0 .85rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.ops-pill {
    text-decoration: none;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.08);
    color: #e2e8f0;
}

.ops-pill.is-active {
    background: #ffffff;
    border-color: #ffffff;
    color: #0f172a;
}

.ops-pill--ghost {
    background: rgba(255,255,255,.04);
}

.ops-state {
    gap: .45rem;
    padding: 0 .95rem;
}

.ops-state::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    box-shadow: 0 0 0 6px rgba(255,255,255,.08);
}

.ops-state--stable {
    background: rgba(34,197,94,.16);
    color: #bbf7d0;
}

.ops-state--warning {
    background: rgba(245,158,11,.15);
    color: #fde68a;
}

.ops-state--critical {
    background: rgba(239,68,68,.16);
    color: #fecaca;
}

.ops-hero__grid,
.ops-main,
.ops-grid-2,
.ops-chart__summary,
.ops-kpi-grid,
.ops-metrics,
.ops-module-grid,
.ops-signal__metrics {
    display: grid;
    gap: .8rem;
}

.ops-hero__grid {
    grid-template-columns: minmax(0, 1.32fr) minmax(290px, .78fr);
    position: relative;
    z-index: 1;
}

.ops-hero__eyebrow {
    margin: 0 0 .55rem;
    font-size: .74rem;
    font-weight: 800;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: rgba(226,232,240,.76);
}

.ops-hero__title {
    margin: 0;
    font-family: var(--f-d);
    font-size: clamp(1.75rem, 3vw, 2.5rem);
    line-height: 1.02;
    letter-spacing: -.05em;
    color: #ffffff;
}

.ops-hero__intro {
    margin: .65rem 0 0;
    max-width: 760px;
    color: rgba(226,232,240,.84);
    font-size: .88rem;
    line-height: 1.65;
}

.ops-hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: .55rem;
    margin-top: 1rem;
}

.ops-hero__link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 0 1rem;
    border-radius: 999px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.12);
    color: #f8fafc;
    text-decoration: none;
    font-size: .76rem;
    font-weight: 800;
    letter-spacing: .02em;
}

.ops-hero__link:hover {
    color: #07130c;
    background: #ffffff;
    border-color: #ffffff;
}

.ops-hero__highlights {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .65rem;
    margin-top: .9rem;
}

.ops-hero__tile,
.ops-kpi,
.ops-panel,
.ops-fold {
    border-radius: 20px;
    border: 1px solid rgba(15,23,42,.08);
    box-shadow: 0 16px 38px rgba(15,23,42,.06);
}

.ops-hero__tile {
    padding: .82rem .9rem;
    border-color: rgba(255,255,255,.1);
    background: rgba(255,255,255,.09);
    backdrop-filter: blur(10px);
}

.ops-hero__tile span,
.ops-kpi__label,
.ops-chart__metric span,
.ops-metric-card span,
.ops-panel__eyebrow {
    display: block;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #64748b;
}

.ops-hero__tile span {
    color: rgba(226,232,240,.76);
}

.ops-hero__tile strong,
.ops-kpi__value,
.ops-chart__metric strong,
.ops-breakdown__total strong,
.ops-metric-card strong,
.ops-signal__value,
.ops-signal__metric strong {
    display: block;
    margin-top: .35rem;
    font-family: var(--f-d);
    line-height: 1;
    letter-spacing: -.05em;
}

.ops-hero__tile strong {
    font-size: 1.15rem;
    color: #ffffff;
}

.ops-hero__tile small {
    display: block;
    margin-top: .22rem;
    color: rgba(226,232,240,.7);
    font-size: .7rem;
    line-height: 1.4;
}

.ops-signal {
    display: grid;
    gap: .75rem;
    align-content: start;
}

.ops-signal__card {
    padding: .95rem;
    border-radius: 20px;
    background: linear-gradient(180deg, rgba(255,255,255,.16), rgba(255,255,255,.08));
    border: 1px solid rgba(255,255,255,.12);
}

.ops-signal__card h2 {
    margin: 0;
    font-size: .8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: rgba(226,232,240,.74);
}

.ops-signal__value {
    margin-top: .55rem;
    font-size: 1.85rem;
    color: #ffffff;
}

.ops-signal__meta {
    margin-top: .32rem;
    color: rgba(226,232,240,.74);
    font-size: .76rem;
    line-height: 1.45;
}

.ops-sparkline {
    height: 68px;
    margin-top: .7rem;
}

.ops-sparkline svg {
    width: 100%;
    height: 100%;
}

.ops-signal__metrics {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-top: .75rem;
}

.ops-signal__metric {
    padding: .78rem .84rem;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,.1);
    background: rgba(255,255,255,.08);
}

.ops-signal__metric span {
    display: block;
    font-size: .66rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(226,232,240,.72);
}

.ops-signal__metric strong {
    font-size: 1rem;
    color: #ffffff;
}

.ops-kpi-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .7rem;
}

.ops-kpi {
    position: relative;
    overflow: hidden;
    padding: .95rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.ops-kpi::after {
    content: '';
    position: absolute;
    inset: auto -34px -36px auto;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0,149,67,.12), transparent 70%);
}

.ops-kpi__label,
.ops-kpi__value,
.ops-kpi__meta {
    position: relative;
    z-index: 1;
}

.ops-kpi__value {
    margin-top: .42rem;
    font-size: 1.45rem;
    color: #0f172a;
}

.ops-kpi__meta {
    margin-top: .32rem;
    color: #475569;
    font-size: .74rem;
    line-height: 1.42;
}

.ops-main {
    grid-template-columns: minmax(0, 1.24fr) minmax(320px, .84fr);
    align-items: start;
    gap: .9rem;
}

.ops-grid-2 {
    grid-template-columns: minmax(0, 1.06fr) minmax(280px, .94fr);
    align-items: start;
}

.ops-secondary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .8rem;
}

.ops-sidebar-stack {
    display: grid;
    gap: .8rem;
    align-content: start;
    position: static;
}

.ops-panel,
.ops-fold {
    padding: .95rem;
    background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(248,250,252,.98));
}

.ops-panel__header {
    align-items: flex-start;
    margin-bottom: .8rem;
}

.ops-panel__header h2 {
    margin: 0;
    font-size: .98rem;
    font-weight: 800;
    letter-spacing: -.03em;
    color: #0f172a;
}

.ops-panel__header p {
    margin: .2rem 0 0;
    color: #64748b;
    font-size: .74rem;
    line-height: 1.44;
}

.ops-panel__link {
    color: var(--green);
    font-size: .76rem;
    font-weight: 800;
    text-decoration: none;
}

.ops-chart {
    display: grid;
    gap: .78rem;
}

.ops-chart__summary {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.ops-chart__metric {
    padding: .76rem;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid rgba(15,23,42,.06);
}

.ops-chart__metric strong {
    font-size: 1.12rem;
    color: #0f172a;
}

.ops-chart__canvas {
    position: relative;
    height: 154px;
    padding: .8rem 0 .45rem;
    border-radius: 18px;
    background:
        linear-gradient(180deg, rgba(0,149,67,.08), rgba(0,149,67,0) 38%),
        repeating-linear-gradient(to top, rgba(148,163,184,.14), rgba(148,163,184,.14) 1px, transparent 1px, transparent 46px);
    border: 1px solid rgba(15,23,42,.08);
}

.ops-chart__canvas svg {
    width: 100%;
    height: 100%;
}

.ops-chart__columns {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
    gap: .35rem;
    align-items: end;
    height: 52px;
}

.ops-chart__bar {
    display: grid;
    gap: .3rem;
    align-items: end;
}

.ops-chart__bar-fill,
.ops-top-item__bar > span {
    display: block;
    border-radius: inherit;
    background: linear-gradient(180deg, rgba(34,197,94,.92), rgba(0,149,67,.98));
}

.ops-chart__bar-fill {
    min-height: 12px;
    border-radius: 999px 999px 6px 6px;
}

.ops-chart__bar-label {
    color: #94a3b8;
    font-size: .66rem;
    text-align: center;
}

.ops-breakdown__total,
.ops-chart__metric,
.ops-empty {
    border-radius: 16px;
}

.ops-breakdown__total {
    padding: .76rem .9rem;
    background: #f8fafc;
    border: 1px solid rgba(15,23,42,.06);
}

.ops-breakdown__total strong {
    font-size: 1.24rem;
    color: #0f172a;
}

.ops-breakdown__list {
    gap: .72rem;
}

.ops-breakdown__row {
    display: grid;
    gap: .4rem;
}

.ops-breakdown__row-top {
    color: #334155;
    font-size: .78rem;
    font-weight: 700;
}

.ops-breakdown__row-top span:last-child {
    color: #64748b;
    font-size: .72rem;
}

.ops-breakdown__track,
.ops-top-item__bar {
    height: 10px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}

.ops-metrics {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .7rem;
}

.ops-metric-card {
    padding: .78rem;
    border-radius: 16px;
    background: #ffffff;
    border: 1px solid rgba(15,23,42,.08);
}

.ops-metric-card strong {
    font-size: 1rem;
    color: #0f172a;
}

.ops-table {
    width: 100%;
    border-collapse: collapse;
}

.ops-table th,
.ops-table td {
    padding: .62rem .5rem;
    border-bottom: 1px solid rgba(15,23,42,.08);
    text-align: left;
    vertical-align: top;
}

.ops-table th {
    font-size: .66rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .12em;
}

.ops-table td {
    color: #334155;
    font-size: .76rem;
    line-height: 1.42;
}

.ops-table strong,
.ops-module strong,
.ops-alert-item h3,
.ops-action-item h3,
.ops-activity-item h3,
.ops-top-item h3 {
    color: #0f172a;
}

.ops-status {
    background: rgba(0,149,67,.1);
    color: #007836;
}

.ops-status.is-warning {
    background: rgba(245,158,11,.14);
    color: #b45309;
}

.ops-status.is-critical {
    background: rgba(239,68,68,.14);
    color: #b91c1c;
}

.ops-alert-item,
.ops-activity-item,
.ops-action-item,
.ops-top-item {
    padding: .8rem .88rem;
    border-radius: 16px;
    border: 1px solid rgba(15,23,42,.08);
    background: #ffffff;
}

.ops-alert-item {
    display: grid;
    gap: .42rem;
}

.ops-alert-item h3,
.ops-action-item h3,
.ops-activity-item h3,
.ops-top-item h3 {
    margin: 0;
    font-size: .83rem;
    font-weight: 800;
}

.ops-alert-item p,
.ops-action-item p,
.ops-activity-item p,
.ops-top-item p,
.ops-module span {
    margin: 0;
    color: #64748b;
    font-size: .72rem;
    line-height: 1.42;
}

.ops-alert-count {
    min-width: 40px;
    min-height: 28px;
    padding: 0 .68rem;
    background: #f8fafc;
    color: #0f172a;
}

.ops-alert-item--warning {
    background: rgba(245,158,11,.08);
    border-color: rgba(245,158,11,.18);
}

.ops-alert-item--critical {
    background: rgba(239,68,68,.08);
    border-color: rgba(239,68,68,.16);
}

.ops-alert-item--ok {
    background: rgba(34,197,94,.08);
    border-color: rgba(34,197,94,.16);
}

.ops-module-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.ops-module {
    display: grid;
    gap: .26rem;
    padding: .8rem;
    border-radius: 16px;
    border: 1px solid rgba(15,23,42,.08);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    text-decoration: none;
}

.ops-module strong {
    font-size: .78rem;
}

.ops-module--soft {
    background: linear-gradient(180deg, rgba(255,255,255,.94), rgba(241,245,249,.98));
}

.ops-activity-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: .6rem;
    align-items: center;
}

.ops-activity-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
}

.ops-activity-dot.is-green { background: #009543; }
.ops-activity-dot.is-gold { background: #f59e0b; }
.ops-activity-dot.is-blue { background: #2563eb; }

.ops-activity-time {
    color: #94a3b8;
    font-size: .7rem;
    white-space: nowrap;
}

.ops-top-item__bar {
    margin-top: .6rem;
}

.ops-flow-list {
    display: grid;
    gap: .7rem;
}

.ops-flow-card {
    display: grid;
    gap: .55rem;
    padding: .82rem .88rem;
    border-radius: 16px;
    border: 1px solid rgba(15,23,42,.08);
    background: #ffffff;
}

.ops-flow-card__top,
.ops-flow-card__footer,
.ops-flow-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    flex-wrap: wrap;
}

.ops-flow-card__ref {
    font-size: .84rem;
    font-weight: 800;
    color: #0f172a;
}

.ops-flow-card__meta span,
.ops-flow-card__footer span {
    color: #64748b;
    font-size: .72rem;
    line-height: 1.4;
}

.ops-flow-card__meta strong {
    color: #334155;
    font-size: .74rem;
    font-weight: 700;
}

.ops-panel--muted {
    background: linear-gradient(180deg, rgba(255,255,255,.94), rgba(248,250,252,.98));
}

.ops-empty {
    padding: .84rem;
    background: #f8fafc;
    color: #64748b;
    font-size: .76rem;
}

.ops-fold {
    padding: 0;
    overflow: hidden;
}

.ops-fold summary {
    list-style: none;
    cursor: pointer;
    padding: .95rem 1rem;
    font-size: .8rem;
    font-weight: 800;
    color: #0f172a;
}

.ops-fold summary::-webkit-details-marker {
    display: none;
}

.ops-fold summary::after {
    content: '+';
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 999px;
    background: rgba(0,149,67,.1);
    color: #007836;
    font-size: 1rem;
    line-height: 1;
}

.ops-fold[open] summary::after {
    content: '−';
}

.ops-fold__body {
    display: grid;
    gap: .85rem;
    padding: 0 1rem 1rem;
}

@media (max-width: 1180px) {
    .ops-hero__grid,
    .ops-main,
    .ops-grid-2,
    .ops-secondary-grid {
        grid-template-columns: 1fr;
    }

    .ops-kpi-grid,
    .ops-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 780px) {
    .ops-hero,
    .ops-panel {
        padding: 1rem;
    }

    .ops-hero__highlights,
    .ops-chart__summary,
    .ops-kpi-grid,
    .ops-metrics,
    .ops-module-grid,
    .ops-signal__metrics,
    .ops-secondary-grid {
        grid-template-columns: 1fr;
    }

    .ops-flow-card__top,
    .ops-flow-card__footer,
    .ops-flow-card__meta {
        display: grid;
        justify-content: flex-start;
    }

    .ops-table thead {
        display: none;
    }

    .ops-table,
    .ops-table tbody,
    .ops-table tr,
    .ops-table td {
        display: block;
        width: 100%;
    }

    .ops-table tr {
        padding: .75rem 0;
    }

    .ops-table td {
        padding: .3rem 0;
        border-bottom: 0;
    }
}
</style>
@endsection

@section('content')
<div class="dash-grid">

  {{-- Row 0 : Hero band --}}
  <div class="dash-hero">
    <div>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        {{-- workspace tabs --}}
        <div class="dash-tabs">
          @foreach($workspaceTabs as $tab)
            <a href="{{ $tab['href'] }}" class="dash-tab {{ $tab['key'] === $workspace ? 'active' : '' }}">{{ $tab['label'] }}</a>
          @endforeach
        </div>
        {{-- period tabs --}}
        <div class="dash-period" style="margin-left:12px;">
          @foreach($periodTabs as $pt)
            <a href="{{ $pt['href'] }}" class="dash-period-btn {{ $pt['value'] === $period ? 'active' : '' }}">{{ $pt['value'] }}j</a>
          @endforeach
        </div>
      </div>
      <div class="dash-hero-title">{{ $workspaceHeading }}</div>
      <div class="dash-hero-sub">{{ $workspaceIntro }}</div>
      <div class="dash-hero-stats">
        @foreach($heroStats as $stat)
          <div>
            <div class="dash-hero-stat-val">{{ $stat['value'] }}</div>
            <div class="dash-hero-stat-lbl">{{ $stat['label'] }}</div>
          </div>
        @endforeach
      </div>
    </div>
    <div style="text-align:right;flex-shrink:0;">
      <span class="dash-hero-state {{ $globalState === 'stable' ? 'stable' : ($globalState === 'sous tension' ? 'warning' : 'critical') }}">
        {{ $globalStateLabel }}
      </span>
      {{-- Sparkline SVG --}}
      <div style="margin-top:12px;width:160px;height:56px;">
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" style="width:100%;height:100%;" aria-hidden="true">
          <defs>
            <linearGradient id="heroGrad" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="rgba(255,255,255,.3)"/>
              <stop offset="100%" stop-color="rgba(255,255,255,.05)"/>
            </linearGradient>
          </defs>
          <polyline fill="none" stroke="rgba(255,255,255,.85)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="{{ $chartPoints ?: '0,100 100,100' }}"/>
          <polyline fill="url(#heroGrad)" stroke="none" points="0,100 {{ $chartPoints ?: '50,100' }} 100,100"/>
        </svg>
      </div>
    </div>
  </div>

  {{-- Row 1 : 4 KPI cards --}}
  <div class="dash-row">
    @php
      $kpiColors = ['c-blue','c-green','c-orange','c-red'];
      $kpiIcons  = ['fas fa-shopping-bag','fas fa-coins','fas fa-clock','fas fa-exclamation-circle'];
    @endphp
    @foreach($visibleKpis as $i => $kpi)
    <div class="dash-kpi {{ $kpiColors[$i % 4] }}">
      <div class="dash-kpi-icon"><i class="{{ $kpi['icon'] ?? $kpiIcons[$i % 4] }}"></i></div>
      <div class="dash-kpi-val">{{ $kpi['value'] ?? '0' }}</div>
      <div class="dash-kpi-lbl">{{ $kpi['label'] ?? 'Indicateur' }}</div>
      <div class="dash-kpi-meta">{{ $kpi['meta'] ?? '' }}</div>
    </div>
    @endforeach
  </div>

  {{-- Row 2 : Chart + Breakdown donut + Alerts --}}
  <div style="display:grid;grid-template-columns:1fr 300px 280px;gap:16px;">

    {{-- Chart area --}}
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">{{ $workspaceMeta['chart_title'] ?? 'Tendance' }}</div>
          <div class="dash-card-sub">Dernier : {{ number_format($chartLatest,0,',',' ') }}{{ ($revenueChart['mode']??'count')==='currency' ? ' FCFA' : '' }} · Moy. : {{ number_format($chartAverage,0,',',' ') }}{{ ($revenueChart['mode']??'count')==='currency' ? ' FCFA' : '' }}</div>
        </div>
      </div>
      <div class="dash-card-body" style="padding-top:8px;">
        {{-- Bar chart columns --}}
        <div style="display:flex;align-items:flex-end;gap:4px;height:120px;padding-bottom:20px;position:relative;">
          @php $color = $revenueChart['color'] ?? '#3b82f6'; @endphp
          @forelse($chartColumns->take(14) as $ci => $height)
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end;">
              <div style="width:100%;border-radius:4px 4px 0 0;background:{{ $color }};opacity:.85;transition:height .3s;height:{{ max($height,4) }}%;" title="{{ $chartLabels->slice(-14)->values()->get($ci) }}"></div>
            </div>
          @empty
            <div style="color:#9ca3af;font-size:.78rem;align-self:center;">Aucune donnée</div>
          @endforelse
        </div>
        {{-- Labels --}}
        <div style="display:flex;gap:4px;margin-top:-16px;">
          @foreach($chartLabels->slice(-14)->values() as $lbl)
            <div style="flex:1;font-size:.55rem;color:#9ca3af;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $lbl }}</div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Donut breakdown --}}
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">Répartition</div>
          <div class="dash-card-sub">{{ number_format(data_get($serviceBreakdown,'total',0),0,',',' ') }} opérations</div>
        </div>
      </div>
      <div class="dash-card-body" style="display:flex;flex-direction:column;align-items:center;gap:12px;">
        @php
          $donutItems = $breakdownItems->take(4);
          $donutTotal = max($donutItems->sum('value'), 1);
          $donutColors = ['#3b82f6','#10b981','#f97316','#8b5cf6'];
          $donutR = 54; $donutC = 70; $donutCirc = 2 * pi() * $donutR;
          $donutOffset = 0;
          $donutSegments = [];
          foreach ($donutItems as $di => $item) {
              $pct = (float)($item['value'] ?? 0) / $donutTotal;
              $len = $pct * $donutCirc;
              $donutSegments[] = ['offset' => $donutOffset, 'len' => $len, 'color' => $donutColors[$di % 4], 'label' => $item['label'] ?? '', 'value' => $item['value'] ?? 0];
              $donutOffset += $len;
          }
        @endphp
        <div class="dash-donut">
          <svg viewBox="0 0 140 140" width="140" height="140" style="transform:rotate(-90deg);">
            <circle cx="70" cy="70" r="{{ $donutR }}" fill="none" stroke="#f1f5f9" stroke-width="14"/>
            @foreach($donutSegments as $seg)
              <circle cx="70" cy="70" r="{{ $donutR }}" fill="none"
                stroke="{{ $seg['color'] }}" stroke-width="14"
                stroke-dasharray="{{ round($seg['len'],2) }} {{ round($donutCirc - $seg['len'],2) }}"
                stroke-dashoffset="{{ -round($seg['offset'],2) }}"
                stroke-linecap="round"/>
            @endforeach
          </svg>
          <div class="dash-donut-center">
            <div class="dash-donut-val">{{ number_format($donutTotal,0,',',' ') }}</div>
            <div class="dash-donut-lbl">Total</div>
          </div>
        </div>
        <div style="width:100%;display:flex;flex-direction:column;gap:6px;">
          @foreach($donutSegments as $di => $seg)
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="width:10px;height:10px;border-radius:3px;background:{{ $seg['color'] }};flex-shrink:0;"></span>
              <span style="font-size:.72rem;color:#374151;flex:1;">{{ $seg['label'] }}</span>
              <span style="font-size:.75rem;font-weight:800;color:#111827;">{{ number_format($seg['value'],0,',',' ') }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Alerts --}}
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">{{ $workspaceMeta['alerts_title'] ?? 'Alertes' }}</div>
        </div>
      </div>
      <div class="dash-card-body">
        @forelse($visibleAlerts as $alert)
          <div class="dash-alert {{ $alert['level'] === 'critical' ? 'critical' : (($alert['count'] ?? 0) > 0 ? 'warning' : 'ok') }}">
            <div class="dash-alert-icon">
              <i class="fas {{ $alert['level'] === 'critical' ? 'fa-fire' : 'fa-exclamation-triangle' }}"></i>
            </div>
            <div style="flex:1;min-width:0;">
              <div class="dash-alert-title">{{ $alert['title'] }}</div>
              <div class="dash-alert-msg">{{ $alert['message'] }}</div>
              @if(!empty($alert['action_url']))
                <a href="{{ $alert['action_url'] }}" style="font-size:.7rem;color:#3b82f6;font-weight:700;text-decoration:none;margin-top:4px;display:inline-block;">{{ $alert['action_label'] ?? 'Voir' }} →</a>
              @endif
            </div>
            <div class="dash-alert-count">{{ $alert['count'] ?? 0 }}</div>
          </div>
        @empty
          <div style="text-align:center;padding:20px;color:#9ca3af;font-size:.78rem;">Aucune alerte active</div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- Row 3 : Table principale + Side panel --}}
  <div class="dash-row-main">

    {{-- Table --}}
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">{{ $workspaceMeta['table_title'] ?? 'Données récentes' }}</div>
        </div>
        @if(!empty($workspaceMeta['table_cta_url']))
          <a href="{{ $workspaceMeta['table_cta_url'] }}" style="font-size:.75rem;font-weight:700;color:#3b82f6;text-decoration:none;">Voir tout →</a>
        @endif
      </div>
      <div class="dash-card-body" style="padding-top:8px;overflow-x:auto;">
        <table class="dash-table">
          <thead>
            <tr>
              @foreach($tableHeaders as $h)
                <th>{{ $h }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @forelse($visibleTableRows as $row)
              <tr>
                <td style="font-weight:800;color:#111827;font-family:monospace;">{{ $row['reference'] }}</td>
                <td>
                  <span class="dash-badge {{ $row['type'] === 'Livraison' ? 'blue' : 'purple' }}">{{ $row['type'] }}</span>
                </td>
                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $row['zone'] }}</td>
                <td>{{ $row['owner'] }}</td>
                <td style="color:#9ca3af;">{{ $row['delay'] }}</td>
                <td>
                  @php
                    $sl = strtolower($row['status_label'] ?? '');
                    $sc = str_contains($sl,'livr') ? 'green' : (str_contains($sl,'annul') ? 'red' : (str_contains($sl,'attente') ? 'orange' : (str_contains($sl,'cours') ? 'blue' : 'gray')));
                  @endphp
                  <span class="dash-badge {{ $sc }}">{{ $row['status_label'] }}</span>
                </td>
                <td><a href="{{ $row['action_url'] }}" style="font-size:.72rem;font-weight:700;color:#3b82f6;text-decoration:none;">{{ $row['action_label'] }}</a></td>
              </tr>
            @empty
              <tr><td colspan="{{ $tableHeaders->count() }}" style="text-align:center;color:#9ca3af;padding:20px;">Aucune donnée</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Side panel : Activity + Op Metrics --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

      {{-- Activity feed --}}
      <div class="dash-card">
        <div class="dash-card-head"><div class="dash-card-title">Activité live</div></div>
        <div class="dash-card-body">
          <div class="dash-activity">
            @forelse($visibleActivities as $activity)
              <div class="dash-activity-item">
                <span class="dash-activity-dot {{ $activity['kind'] ?? 'green' }}"></span>
                <span class="dash-activity-text">{{ $activity['text'] }}</span>
                <span class="dash-activity-time">{{ $activity['time'] }}</span>
              </div>
            @empty
              <div style="color:#9ca3af;font-size:.78rem;text-align:center;padding:10px 0;">Aucune activité récente</div>
            @endforelse
          </div>
        </div>
      </div>

      {{-- Op metrics --}}
      <div class="dash-card">
        <div class="dash-card-head"><div class="dash-card-title">{{ $workspaceMeta['metrics_title'] ?? 'Indicateurs' }}</div></div>
        <div class="dash-card-body">
          @foreach($visibleOperationalMetrics as $m)
            <div class="dash-opmetric">
              <span class="dash-opmetric-lbl">{{ $m['label'] }}</span>
              <span class="dash-opmetric-val">{{ $m['value'] }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- Row 4 : Top restaurants + Actions à prendre + Summary --}}
  <div class="dash-row-3">

    {{-- Top restaurants --}}
    <div class="dash-card">
      <div class="dash-card-head">
        <div class="dash-card-title">{{ $workspaceMeta['secondary_title'] ?? 'Performance' }}</div>
      </div>
      <div class="dash-card-body">
        @forelse($visibleTopRestaurants as $rest)
          <div style="margin-bottom:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
              <span style="font-size:.82rem;font-weight:700;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px;">{{ $rest['name'] ?? ($rest->name ?? 'Restaurant') }}</span>
              <span style="font-size:.78rem;font-weight:900;color:#111827;">{{ number_format($rest['revenue']??($rest->revenue??0),0,',',' ') }} FCFA</span>
            </div>
            <div class="dash-top-bar">
              <div class="dash-top-bar-fill" style="width:{{ round((($rest['revenue']??($rest->revenue??0))/$topRestaurantRevenueMax)*100) }}%;"></div>
            </div>
            <div style="font-size:.65rem;color:#9ca3af;margin-top:2px;">{{ $rest['orders_count'] ?? ($rest->orders_count ?? 0) }} commandes</div>
          </div>
        @empty
          <div style="color:#9ca3af;font-size:.78rem;text-align:center;padding:10px 0;">Aucune donnée</div>
        @endforelse
      </div>
    </div>

    {{-- Actions à prendre --}}
    <div class="dash-card">
      <div class="dash-card-head"><div class="dash-card-title">Actions à prendre</div></div>
      <div class="dash-card-body">
        @foreach($visibleDecisions as $d)
          <div class="dash-decision">
            <div class="dash-decision-rank">{{ $d['rank'] }}</div>
            <div class="dash-decision-body">
              <div class="dash-decision-title">{{ $d['title'] }}</div>
              <div class="dash-decision-meta">{{ $d['meta'] }}</div>
            </div>
            @if(!empty($d['action_url']))
              <a href="{{ $d['action_url'] }}" class="dash-decision-action">{{ $d['action_label'] ?? 'Voir' }} →</a>
            @endif
          </div>
        @endforeach
      </div>
    </div>

    {{-- Structure opérationnelle --}}
    <div class="dash-card">
      <div class="dash-card-head"><div class="dash-card-title">Structure</div></div>
      <div class="dash-card-body">
        @foreach([['label'=>'Restaurants actifs','value'=>$summaryCards['restaurants']??0],['label'=>'Livreurs','value'=>$summaryCards['drivers']??0],['label'=>'Clients','value'=>$summaryCards['clients']??0],['label'=>'Livraisons actives','value'=>$summaryCards['activeDeliveries']??0]] as $sc)
          <div class="dash-opmetric">
            <span class="dash-opmetric-lbl">{{ $sc['label'] }}</span>
            <span class="dash-opmetric-val">{{ number_format($sc['value'],0,',',' ') }}</span>
          </div>
        @endforeach
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:14px;">
          @foreach($primaryModuleLinks as $link)
            @if(app('router')->has($link['route']))
              <a href="{{ route($link['route'], $link['params']) }}" style="flex:1;min-width:80px;text-align:center;background:#f1f5f9;border:1px solid #e5e7eb;border-radius:8px;padding:8px 6px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none;">
                {{ $link['label'] }}
              </a>
            @endif
          @endforeach
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
