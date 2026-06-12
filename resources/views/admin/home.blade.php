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
    $dashUserWorkspaces = auth()->user()->adminWorkspaces();
    $workspaceTabs = collect([
        ['key' => 'bantudelice', 'label' => 'BantuDelice', 'href' => route('admin.dashboard', ['workspace' => 'bantudelice', 'period' => $period])],
        ['key' => 'kende',       'label' => 'Kende',       'href' => route('admin.dashboard', ['workspace' => 'kende',       'period' => $period])],
        ['key' => 'mema',        'label' => 'Mema',        'href' => route('admin.dashboard', ['workspace' => 'mema',        'period' => $period])],
    ])->filter(fn($tab) => in_array($tab['key'], $dashUserWorkspaces))->values()->all();
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
        ['label' => 'Commandes',    'route' => 'admin.all_orders',            'params' => ['workspace' => $workspace],         'meta' => 'Flux food'],
        ['label' => 'Restaurants',  'route' => 'restaurant.index',            'params' => ['workspace' => $workspace],         'meta' => 'Partenaires'],
        $workspaceMediaLink,
        ['label' => 'Paiements',    'route' => 'admin.payments.dashboard',    'params' => ['workspace' => $workspace],         'meta' => 'Finance'],
        ['label' => 'Transport',    'route' => 'admin.transport.dashboard',   'params' => ['workspace' => 'kende'],            'meta' => 'Courses',    'ws_required' => 'kende'],
        ['label' => 'Colis',        'route' => 'admin.colis.index',           'params' => ['workspace' => 'mema'],             'meta' => 'Expeditions','ws_required' => 'mema'],
        ['label' => 'Support',      'route' => 'admin.support-tickets.index', 'params' => ['workspace' => $workspace],         'meta' => 'Tickets'],
        ['label' => 'Accueil',      'route' => 'admin.home-content.edit',     'params' => ['workspace' => $workspace],         'meta' => 'CMS'],
        ['label' => 'Observabilite','route' => 'admin.metrics',               'params' => ['workspace' => $workspace],         'meta' => 'KPIs'],
    ])->filter(function ($link) use ($dashUserWorkspaces) {
        if (!app('router')->has($link['route'])) return false;
        if (isset($link['ws_required']) && !in_array($link['ws_required'], $dashUserWorkspaces)) return false;
        return true;
    })->values();
    $visibleModuleLinks = $moduleLinks->take(9);
    $primaryModuleLinks = $visibleModuleLinks->take(4);
    $secondaryModuleLinks = $visibleModuleLinks->slice(4)->values();
    $heroActionLinks = $moduleLinks->take(3);
@endphp

@section('style')
<style>
/* ── DASHBOARD V2 ─────────────────────────────────────── */
.dash-grid { display:grid; gap:20px; max-width:1440px; margin:0 auto; }
.dash-row  { display:grid; gap:16px; grid-template-columns:repeat(4,1fr); }
.dash-row-3{ display:grid; gap:16px; grid-template-columns:repeat(3,1fr); }
.dash-row-2{ display:grid; gap:16px; grid-template-columns:2fr 1fr; }
.dash-row-main { display:grid; gap:16px; grid-template-columns:1fr 340px; }

/* En-tête de page (remplace le hero band) */
.dash-page-header {
  display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;
}
.dash-page-title { font-size:1.5rem; font-weight:800; color:#111827; margin:0; line-height:1.2; }
.dash-page-sub   { color:#9ca3af; font-size:.85rem; margin:4px 0 0; }

/* KPI card — layout horizontal icône-gauche (style maquette Sedap) */
.dash-kpi {
  background:#fff;
  border-radius:16px;
  padding:20px;
  display:flex;
  align-items:center;
  gap:16px;
  border:1px solid #e5e7eb;
  box-shadow:0 1px 4px rgba(0,0,0,.04);
}
.dash-kpi-icon {
  width:52px; height:52px;
  border-radius:14px;
  display:flex; align-items:center; justify-content:center;
  font-size:22px; color:#fff; flex-shrink:0;
}
.dash-kpi.c-blue   .dash-kpi-icon { background:#2563eb; }
.dash-kpi.c-green  .dash-kpi-icon { background:#009543; }
.dash-kpi.c-orange .dash-kpi-icon { background:#f97316; }
.dash-kpi.c-red    .dash-kpi-icon { background:#ef4444; }
.dash-kpi-body { flex:1; min-width:0; overflow:hidden; }
.dash-kpi-val  { font-size:1.6rem; font-weight:900; color:#111827; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dash-kpi-lbl  { font-size:.68rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dash-kpi-meta { font-size:.68rem; color:#9ca3af; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

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

/* State badge */
.dash-hero-state {
  padding:6px 14px; border-radius:99px; font-size:.72rem; font-weight:800; letter-spacing:.05em;
}
.dash-hero-state.stable  { background:#dcfce7; color:#15803d; }
.dash-hero-state.warning { background:#fef3c7; color:#92400e; }
.dash-hero-state.critical{ background:#fee2e2; color:#b91c1c; }

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
.dash-activity-text { font-size:.78rem; color:#374151; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dash-activity-time { font-size:.68rem; color:#9ca3af; white-space:nowrap; }

/* Op metrics */
.dash-opmetric { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f1f5f9; }
.dash-opmetric:last-child { border-bottom:none; }
.dash-opmetric-lbl { font-size:.78rem; color:#6b7280; font-weight:600; }
.dash-opmetric-val { font-size:.88rem; font-weight:800; color:#111827; }

/* Decision list */
.dash-decision { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:10px; background:#f8fafc; border:1px solid #e5e7eb; margin-bottom:6px; }
.dash-decision-rank { font-size:1rem; font-weight:900; color:#111827; width:44px; flex-shrink:0; text-align:center; }
.dash-decision-body { flex:1; min-width:0; overflow:hidden; }
.dash-decision-title { font-size:.78rem; font-weight:800; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dash-decision-meta  { font-size:.66rem; color:#9ca3af; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dash-decision-action { font-size:.7rem; font-weight:700; color:#009543; text-decoration:none; white-space:nowrap; flex-shrink:0; }

/* Bar sparkline (top restaurants) */
.dash-top-bar { height:6px; border-radius:99px; background:#e5e7eb; overflow:hidden; margin-top:4px; }
.dash-top-bar-fill { height:100%; border-radius:99px; background:#009543; }

/* KPI delta */
.dash-kpi-delta { font-size:.7rem; font-weight:700; margin-top:3px; display:inline-flex; align-items:center; gap:3px; }
.dash-kpi-delta.up      { color:#009543; }
.dash-kpi-delta.down    { color:#ef4444; }
.dash-kpi-delta.neutral { color:#9ca3af; }

/* KPI zero state */
.dash-kpi.is-zero .dash-kpi-val { color:#9ca3af; }
.dash-kpi.is-zero .dash-kpi-icon { opacity:.45; }

/* Alert grid responsive */
.dash-alert-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
@media (max-width:768px) { .dash-alert-grid { grid-template-columns:1fr; } }

/* Decision rank zero */
.dash-decision-rank-ok { color:#16a34a; font-size:1.1rem; }

/* Period filter */
.dash-filter-select {
  padding:5px 10px; border:1px solid #d1d5db; border-radius:8px;
  font-size:.78rem; color:#374151; background:#f9fafb;
  cursor:pointer; font-family:inherit;
}
.dash-filter-select:focus { outline:none; border-color:#009543; box-shadow:0 0 0 3px rgba(0,149,67,.1); }
.dash-filter-apply {
  padding:6px 14px; border-radius:8px; background:#009543; border:none;
  color:#fff; font-size:.72rem; font-weight:700; cursor:pointer; transition:background .15s;
  display:inline-flex; align-items:center; gap:5px; font-family:inherit;
}
.dash-filter-apply:hover { background:#007836; }

/* Alert severity pill */
.dash-alert-sev {
  display:inline-flex; align-items:center; gap:4px;
  padding:2px 8px; border-radius:999px; font-size:.63rem; font-weight:800;
  text-transform:uppercase; letter-spacing:.06em; flex-shrink:0;
}
.dash-alert-sev.critical { background:#fee2e2; color:#b91c1c; }
.dash-alert-sev.warning  { background:#fef3c7; color:#92400e; }
.dash-alert-sev.ok       { background:#dcfce7; color:#15803d; }

/* Table toolbar */
.dash-table-toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding-bottom:12px; }
.dash-search { flex:1; min-width:140px; max-width:240px; padding:6px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:.78rem; color:#374151; font-family:inherit; }
.dash-search:focus { outline:none; border-color:#009543; box-shadow:0 0 0 3px rgba(0,149,67,.1); }
.dash-table th[data-col] { cursor:pointer; user-select:none; }
.dash-table th[data-col]:hover { color:#374151; }
.dash-table th.sort-asc::after  { content:' ↑'; opacity:.6; }
.dash-table th.sort-desc::after { content:' ↓'; opacity:.6; }
.dash-tr-clickable { cursor:pointer; }
.dash-tr-clickable:hover td { background:#f0fdf4 !important; }

/* Pagination */
.dash-pagination { display:flex; align-items:center; gap:4px; padding-top:10px; justify-content:flex-end; flex-wrap:wrap; }
.dash-page-btn { padding:4px 10px; border-radius:6px; border:1px solid #e5e7eb; background:#fff; font-size:.72rem; font-weight:700; color:#6b7280; cursor:pointer; transition:all .15s; font-family:inherit; }
.dash-page-btn:hover:not(:disabled), .dash-page-btn.active { background:#009543; color:#fff; border-color:#009543; }
.dash-page-btn:disabled { opacity:.35; cursor:default; }

/* Toast */
.dash-toast { position:fixed; bottom:24px; right:24px; background:#111827; color:#fff; padding:12px 16px; border-radius:12px; font-size:.78rem; font-weight:600; box-shadow:0 4px 24px rgba(0,0,0,.22); z-index:9999; display:flex; align-items:center; gap:10px; transition:opacity .3s,transform .3s; max-width:360px; }
.dash-toast.hidden { opacity:0; transform:translateY(10px); pointer-events:none; }
.dash-toast-close { cursor:pointer; color:rgba(255,255,255,.5); font-size:16px; line-height:1; }

/* Drawer */
.dash-drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,.28); z-index:1000; opacity:0; pointer-events:none; transition:opacity .25s; }
.dash-drawer-overlay.open { opacity:1; pointer-events:all; }
.dash-drawer { position:fixed; top:0; right:0; height:100%; width:380px; max-width:95vw; background:#fff; z-index:1001; box-shadow:-6px 0 40px rgba(0,0,0,.10); transform:translateX(100%); transition:transform .25s cubic-bezier(.4,0,.2,1); overflow-y:auto; }
.dash-drawer.open { transform:translateX(0); }
.dash-drawer-head { padding:20px 20px 16px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; background:#fff; z-index:1; }
.dash-drawer-title { font-size:.95rem; font-weight:800; color:#111827; }
.dash-drawer-close { cursor:pointer; color:#9ca3af; font-size:18px; line-height:1; padding:4px; }
.dash-drawer-body { padding:20px; }
.dash-drawer-field { margin-bottom:16px; }
.dash-drawer-label { font-size:.63rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.07em; margin-bottom:4px; }
.dash-drawer-value { font-size:.88rem; color:#111827; font-weight:600; }

/* Empty state */
.dash-empty { text-align:center; padding:28px 16px; }
.dash-empty-icon { font-size:1.8rem; color:#d1d5db; margin-bottom:8px; }
.dash-empty-text { font-size:.82rem; font-weight:700; color:#9ca3af; }
.dash-empty-sub  { font-size:.72rem; color:#d1d5db; margin-top:3px; }

/* Chart + donut section */
.dash-row-chart { display:grid; gap:16px; grid-template-columns:1fr 280px; }

/* Responsive */
@media (max-width:1200px) {
  .dash-row { grid-template-columns:repeat(2,1fr); }
  .dash-row-main { grid-template-columns:1fr; }
  .dash-row-chart { grid-template-columns:1fr 240px; }
}
@media (max-width:900px) {
  .dash-row-chart { grid-template-columns:1fr; }
}
@media (max-width:768px) {
  .dash-row, .dash-row-3, .dash-row-2, .dash-row-main, .dash-row-chart { grid-template-columns:1fr; }
  .dash-drawer { width:100%; }
}
</style>
@endsection

@section('content')
<div class="dash-grid">

  {{-- En-tête de page — filtre période + état (le titre est dans la topbar) --}}
  <div class="dash-page-header">
    <p class="dash-page-sub" style="margin:0;">{{ $workspaceIntro }}</p>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      @php
        $totalAlerts = $alertsCollection->sum('count');
        $stateClass  = match(true) {
            str_contains(strtolower($globalState), 'critique') || str_contains(strtolower($globalState), 'critical') => 'critical',
            str_contains(strtolower($globalState), 'tension')  || str_contains(strtolower($globalState), 'warning')  => 'warning',
            default => 'stable',
        };
        $stateDisplay = ($stateClass !== 'stable' && $totalAlerts > 0)
            ? $totalAlerts . ' incident' . ($totalAlerts > 1 ? 's' : '') . ' ouvert' . ($totalAlerts > 1 ? 's' : '')
            : $globalStateLabel;
      @endphp
      <span class="dash-hero-state {{ $stateClass }}">{{ $stateDisplay }}</span>
      <form method="GET" action="{{ route('admin.dashboard') }}" style="display:flex;align-items:center;gap:6px;">
        <input type="hidden" name="workspace" value="{{ $workspace }}">
        <select name="period" class="dash-filter-select" onchange="this.form.submit()" style="font-size:.75rem;padding:5px 10px;">
          @foreach($periodTabs as $pt)
            <option value="{{ $pt['value'] }}" {{ $pt['value'] === $period ? 'selected' : '' }}>{{ $pt['value'] }} jours</option>
          @endforeach
        </select>
      </form>
      <div style="display:flex;align-items:center;gap:4px;color:#9ca3af;font-size:.7rem;white-space:nowrap;">
        <i class="fas fa-sync-alt" style="font-size:.58rem;"></i>
        <span id="dashRefreshCount">60s</span>
        <button id="dashRefreshPause" title="Pause auto-refresh"
          style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:1px 4px;font-size:.58rem;line-height:1;">
          <i class="fas fa-pause"></i>
        </button>
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
    @php
      $kpiVal   = (string)($kpi['value'] ?? '0');
      $isZero   = preg_match('/^0(\s|$|[^,\d])/u', $kpiVal) === 1;
      $kpiLabel = $kpi['label'] ?? 'Indicateur';
      // Clarify the incidents sum label
      if (str_contains(mb_strtolower($kpiLabel), 'incident')) {
          $kpiLabel = 'Points d\'attention';
      }
    @endphp
    <div class="dash-kpi {{ $kpiColors[$i % 4] }}{{ $isZero ? ' is-zero' : '' }}">
      <div class="dash-kpi-icon"><i class="{{ $kpi['icon'] ?? $kpiIcons[$i % 4] }}"></i></div>
      <div class="dash-kpi-body">
        <div class="dash-kpi-val">{{ $kpiVal }}</div>
        <div class="dash-kpi-lbl">{{ $kpiLabel }}</div>
        @if(!empty($kpi['meta']))<div class="dash-kpi-meta">{{ $kpi['meta'] }}</div>@endif
        @if(!empty($kpi['delta']))
          @php
            $dDir = str_starts_with((string)$kpi['delta'], '+') ? 'up' : (str_starts_with((string)$kpi['delta'], '-') ? 'down' : 'neutral');
            $dArr = $dDir === 'up' ? '↑' : ($dDir === 'down' ? '↓' : '→');
          @endphp
          <div class="dash-kpi-delta {{ $dDir }}"><span>{{ $dArr }}</span> {{ $kpi['delta'] }}</div>
        @endif
      </div>
    </div>
    @endforeach
  </div>

  {{-- Row 2 : Alertes opérationnelles (priorité max — avant les graphiques) --}}
  <div class="dash-alert-grid">
    @forelse($visibleAlerts as $alert)
      @php $lvl = $alert['level'] === 'critical' ? 'critical' : (($alert['count'] ?? 0) > 0 ? 'warning' : 'ok'); @endphp
      <div class="dash-alert {{ $lvl }}" style="margin-bottom:0;">
        <div class="dash-alert-icon">
          <i class="fas {{ $lvl === 'critical' ? 'fa-fire' : ($lvl === 'ok' ? 'fa-check' : 'fa-exclamation-triangle') }}"></i>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:2px;">
            <div class="dash-alert-title">{{ $alert['title'] }}</div>
            <span class="dash-alert-sev {{ $lvl }}">{{ $lvl === 'critical' ? 'Critique' : ($lvl === 'ok' ? 'OK' : 'Attention') }}</span>
          </div>
          <div class="dash-alert-msg">{{ $alert['message'] }}</div>
          @if(!empty($alert['action_url']))
            <a href="{{ $alert['action_url'] }}" style="font-size:.72rem;background:#009543;color:#fff;font-weight:700;text-decoration:none;padding:5px 12px;border-radius:8px;display:inline-flex;align-items:center;gap:5px;margin-top:6px;line-height:1.2;">
              {{ $alert['action_label'] ?? 'Traiter' }} <i class="fas fa-arrow-right" style="font-size:.6rem;"></i>
            </a>
          @endif
        </div>
        <div class="dash-alert-count">{{ $alert['count'] ?? 0 }}</div>
      </div>
    @empty
      <div class="dash-card" style="grid-column:1/-1;text-align:center;padding:20px;color:#9ca3af;font-size:.78rem;">Aucune alerte active</div>
    @endforelse
  </div>

  {{-- Row 3 : Chart tendance + Breakdown donut --}}
  <div class="dash-row-chart">

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
        @php
          $chartColor = $revenueChart['color'] ?? '#009543';
          $chartSlice = $chartColumns->take(14);
          $chartHasData = $chartValues->take(14)->max() > 0;
        @endphp
        <div style="display:flex;align-items:flex-end;gap:3px;height:130px;padding-bottom:20px;position:relative;">
          @if(!$chartHasData)
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;width:100%;height:100%;">
              <i class="fas fa-chart-bar" style="font-size:1.6rem;color:#e5e7eb;"></i>
              <span style="color:#9ca3af;font-size:.78rem;font-weight:600;">Aucune donnée sur la période</span>
            </div>
          @else
            @foreach($chartSlice as $ci => $height)
              <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end;">
                <div style="width:100%;border-radius:4px 4px 0 0;background:{{ $chartColor }};opacity:.9;transition:height .3s;height:{{ max($height,4) }}%;box-shadow:0 -2px 6px rgba(0,149,67,.2);" title="{{ $chartLabels->slice(-14)->values()->get($ci) }}"></div>
              </div>
            @endforeach
          @endif
        </div>
        {{-- Labels axes --}}
        <div style="display:flex;gap:3px;margin-top:-16px;border-top:1px solid #f1f5f9;padding-top:4px;">
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
          $donutColors = ['#009543','#2563eb','#f97316','#8b5cf6'];
          $donutR = 54; $donutCirc = 2 * pi() * $donutR;
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
            <div class="dash-donut-lbl">opér.</div>
          </div>
        </div>
        <div style="width:100%;display:flex;flex-direction:column;gap:6px;">
          @foreach($donutSegments as $seg)
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="width:10px;height:10px;border-radius:3px;background:{{ $seg['color'] }};flex-shrink:0;"></span>
              <span style="font-size:.72rem;color:#374151;flex:1;">{{ $seg['label'] }}</span>
              <span style="font-size:.75rem;font-weight:800;color:#111827;">{{ number_format($seg['value'],0,',',' ') }}</span>
            </div>
          @endforeach
        </div>
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
          <a href="{{ $workspaceMeta['table_cta_url'] }}" style="font-size:.75rem;font-weight:700;color:#009543;text-decoration:none;">Voir tout →</a>
        @endif
      </div>
      <div class="dash-card-body" style="padding-top:8px;">
        {{-- Toolbar : recherche + filtre statut --}}
        <div class="dash-table-toolbar">
          <input id="dashSearch" type="text" class="dash-search" placeholder="Rechercher…" autocomplete="off">
          @php $tableStatuses = $visibleTableRows->pluck('status_label')->unique()->filter()->values(); @endphp
          <select id="dashStatusFilter" class="dash-filter-select" style="font-size:.75rem;">
            <option value="">Tous les statuts</option>
            @foreach($tableStatuses as $st)
              <option value="{{ $st }}">{{ $st }}</option>
            @endforeach
          </select>
          <span id="dashRowCount" style="font-size:.72rem;color:#9ca3af;margin-left:auto;white-space:nowrap;"></span>
        </div>
        {{-- Table --}}
        <div style="overflow-x:auto;">
          <table class="dash-table" id="dashMainTable">
            <thead>
              <tr>
                @foreach($tableHeaders as $h)
                  <th @if(!$loop->last) data-col="{{ $loop->index }}" @endif>{{ $h }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @forelse($visibleTableRows as $row)
                <tr class="dash-tr-clickable"
                    data-ref="{{ $row['reference'] }}"
                    data-type="{{ $row['type'] }}"
                    data-zone="{{ $row['zone'] }}"
                    data-owner="{{ $row['owner'] }}"
                    data-delay="{{ $row['delay'] }}"
                    data-status="{{ $row['status_label'] }}"
                    data-action="{{ $row['action_url'] }}"
                    data-action-label="{{ $row['action_label'] ?? 'Voir' }}">
                  <td style="font-weight:800;color:#111827;font-family:monospace;">{{ $row['reference'] }}</td>
                  <td>
                    <span class="dash-badge {{ $row['type'] === 'Livraison' ? 'blue' : 'purple' }}">{{ $row['type'] }}</span>
                  </td>
                  <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $row['zone'] }}</td>
                  <td style="max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $row['owner'] }}</td>
                  <td style="color:#9ca3af;white-space:nowrap;">{{ $row['delay'] }}</td>
                  <td>
                    @php
                      $sl = mb_strtolower($row['status_label'] ?? '');
                      $sc = match(true) {
                          str_contains($sl,'livré') || str_contains($sl,'livrée') => 'green',
                          str_contains($sl,'livraison') || str_contains($sl,'assigné') || str_contains($sl,'récupér') => 'blue',
                          str_contains($sl,'en cours') || str_contains($sl,'prépar') || str_contains($sl,'accepté') => 'orange',
                          str_contains($sl,'attente') || str_contains($sl,'pending') || str_contains($sl,'prête') => 'orange',
                          str_contains($sl,'annul') || str_contains($sl,'incident') || str_contains($sl,'litige') => 'red',
                          default => 'gray'
                      };
                    @endphp
                    <span class="dash-badge {{ $sc }}">{{ $row['status_label'] }}</span>
                  </td>
                  <td onclick="event.stopPropagation()">
                    <a href="{{ $row['action_url'] }}" style="font-size:.72rem;font-weight:700;color:#009543;text-decoration:none;">{{ $row['action_label'] }}</a>
                  </td>
                </tr>
              @empty
                <tr class="dash-empty-row">
                  <td colspan="{{ $tableHeaders->count() }}">
                    <div class="dash-empty">
                      <div class="dash-empty-icon"><i class="fas fa-inbox"></i></div>
                      <div class="dash-empty-text">Aucune donnée</div>
                      <div class="dash-empty-sub">Aucune opération sur cette période</div>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        {{-- Pagination --}}
        <div id="dashPager" class="dash-pagination"></div>
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
                <span class="dash-activity-text" title="{{ $activity['text'] }}">{{ $activity['text'] }}</span>
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
          @foreach($visibleOperationalMetrics->reject(fn($m) => mb_strtolower($m['label'] ?? '') === 'volume vs hier') as $m)
            <div class="dash-opmetric">
              <span class="dash-opmetric-lbl">{{ $m['label'] }}</span>
              <span class="dash-opmetric-val" style="{{ str_starts_with((string)($m['value']??''), '0') ? 'color:#9ca3af;' : '' }}">{{ $m['value'] }}</span>
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
          @php
            $dRankRaw = trim(preg_replace('/[\xc2\xa0\s]+/', '', (string)($d['rank'] ?? '0')));
            $dIsZero  = $dRankRaw === '0';
            $dIsHigh  = !$dIsZero && (int)$dRankRaw >= 3;
          @endphp
          <div class="dash-decision" style="{{ $dIsHigh ? 'border-color:#fca5a5;background:#fff5f5;' : '' }}">
            @if($dIsZero)
              <div class="dash-decision-rank dash-decision-rank-ok" title="Aucune action requise">✓</div>
            @else
              <div class="dash-decision-rank" style="{{ $dIsHigh ? 'color:#dc2626;' : '' }}">{{ $d['rank'] }}</div>
            @endif
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
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:5px;margin-top:12px;">
          @foreach($primaryModuleLinks as $link)
            @if(app('router')->has($link['route']))
              <a href="{{ route($link['route'], $link['params']) }}" style="text-align:center;background:#f1f5f9;border:1px solid #e5e7eb;border-radius:7px;padding:7px 4px;font-size:.66rem;font-weight:700;color:#374151;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                {{ $link['label'] }}
              </a>
            @endif
          @endforeach
        </div>
      </div>
    </div>
  </div>

</div>{{-- /.dash-grid --}}

{{-- Drawer overlay --}}
<div id="dashDrawerOverlay" class="dash-drawer-overlay"></div>
<aside id="dashDrawer" class="dash-drawer" role="dialog" aria-modal="true" aria-label="Détail opération">
  <div class="dash-drawer-head">
    <span class="dash-drawer-title">Détail opération</span>
    <button id="dashDrawerClose" class="dash-drawer-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
  </div>
  <div class="dash-drawer-body">
    <div class="dash-drawer-field">
      <div class="dash-drawer-label">Référence</div>
      <div class="dash-drawer-value" id="dRef">—</div>
    </div>
    <div class="dash-drawer-field">
      <div class="dash-drawer-label">Type</div>
      <div class="dash-drawer-value" id="dType">—</div>
    </div>
    <div class="dash-drawer-field">
      <div class="dash-drawer-label">Zone</div>
      <div class="dash-drawer-value" id="dZone">—</div>
    </div>
    <div class="dash-drawer-field">
      <div class="dash-drawer-label">Responsable</div>
      <div class="dash-drawer-value" id="dOwner">—</div>
    </div>
    <div class="dash-drawer-field">
      <div class="dash-drawer-label">Délai</div>
      <div class="dash-drawer-value" id="dDelay">—</div>
    </div>
    <div class="dash-drawer-field">
      <div class="dash-drawer-label">Statut</div>
      <div class="dash-drawer-value" id="dStatus">—</div>
    </div>
    <div style="margin-top:24px;">
      <a id="dLink" href="#"
         style="display:inline-flex;align-items:center;gap:8px;background:#009543;color:#fff;font-size:.78rem;font-weight:700;text-decoration:none;padding:10px 20px;border-radius:10px;">
        <span id="dLinkLabel">Voir le détail</span>
        <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
      </a>
    </div>
  </div>
</aside>

{{-- Toast session flash --}}
@if(session('success') || session('status'))
  <div id="dashToast" class="dash-toast">
    <i class="fas fa-check-circle" style="color:#4ade80;font-size:16px;flex-shrink:0;"></i>
    <span>{{ session('success') ?? session('status') }}</span>
    <button id="dashToastClose" class="dash-toast-close" style="background:none;border:none;padding:0;line-height:1;">×</button>
  </div>
@endif

@endsection

@section('scripts')
<script>
(function () {
  'use strict';

  /* ── TABLE : search / filter / sort / pagination ─────── */
  var table   = document.getElementById('dashMainTable');
  var tbody   = table ? table.querySelector('tbody') : null;
  var search  = document.getElementById('dashSearch');
  var filter  = document.getElementById('dashStatusFilter');
  var pager   = document.getElementById('dashPager');
  var countEl = document.getElementById('dashRowCount');
  var PAGE    = 10;
  var sortCol = -1, sortAsc = true, page = 1;

  function allRows() {
    return tbody ? Array.from(tbody.querySelectorAll('tr[data-ref]')) : [];
  }

  function applyAll() {
    var term = search ? search.value.toLowerCase() : '';
    var st   = filter ? filter.value : '';

    var visible = allRows().filter(function (tr) {
      var text   = tr.textContent.toLowerCase();
      var status = (tr.dataset.status || '').trim();
      return (!term || text.includes(term)) && (!st || status === st);
    });

    if (sortCol >= 0) {
      visible.sort(function (a, b) {
        var va = a.cells[sortCol] ? a.cells[sortCol].textContent.trim() : '';
        var vb = b.cells[sortCol] ? b.cells[sortCol].textContent.trim() : '';
        var cmp = va.localeCompare(vb, 'fr', { sensitivity: 'base' });
        return sortAsc ? cmp : -cmp;
      });
      visible.forEach(function (tr) { tbody.appendChild(tr); });
    }

    var total = visible.length;
    var pages = Math.max(1, Math.ceil(total / PAGE));
    if (page > pages) page = pages;
    var start = (page - 1) * PAGE;

    allRows().forEach(function (tr) { tr.style.display = 'none'; });
    visible.slice(start, start + PAGE).forEach(function (tr) { tr.style.display = ''; });

    var emptyRow = tbody ? tbody.querySelector('tr.dash-empty-row') : null;
    if (emptyRow) emptyRow.style.display = (visible.length === 0) ? '' : 'none';

    if (countEl) countEl.textContent = total + ' résultat' + (total !== 1 ? 's' : '');

    renderPager(pages);
  }

  function renderPager(pages) {
    if (!pager) return;
    pager.innerHTML = '';
    if (pages <= 1) return;

    function mkBtn(label, disabled, active, cb) {
      var b = document.createElement('button');
      b.className = 'dash-page-btn' + (active ? ' active' : '');
      b.textContent = label;
      b.disabled = !!disabled;
      b.addEventListener('click', cb);
      return b;
    }

    pager.appendChild(mkBtn('←', page === 1, false, function () { page--; applyAll(); }));
    for (var i = 1; i <= pages; i++) {
      (function (i) {
        pager.appendChild(mkBtn(i, false, i === page, function () { page = i; applyAll(); }));
      })(i);
    }
    pager.appendChild(mkBtn('→', page === pages, false, function () { page++; applyAll(); }));
  }

  if (table) {
    table.querySelectorAll('th[data-col]').forEach(function (th) {
      th.addEventListener('click', function () {
        var col = parseInt(th.dataset.col, 10);
        if (sortCol === col) { sortAsc = !sortAsc; } else { sortCol = col; sortAsc = true; }
        table.querySelectorAll('th[data-col]').forEach(function (h) { h.classList.remove('sort-asc', 'sort-desc'); });
        th.classList.add(sortAsc ? 'sort-asc' : 'sort-desc');
        page = 1;
        applyAll();
      });
    });
  }

  if (search) search.addEventListener('input', function () { page = 1; applyAll(); });
  if (filter) filter.addEventListener('change', function () { page = 1; applyAll(); });
  if (table)  applyAll();

  /* ── DRAWER ────────────────────────────────────────────── */
  var overlay  = document.getElementById('dashDrawerOverlay');
  var drawer   = document.getElementById('dashDrawer');
  var closeBtn = document.getElementById('dashDrawerClose');

  function setText(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val;
  }

  function openDrawer(tr) {
    var d = tr.dataset;
    setText('dRef',    d.ref    || '—');
    setText('dType',   d.type   || '—');
    setText('dZone',   d.zone   || '—');
    setText('dOwner',  d.owner  || '—');
    setText('dDelay',  d.delay  || '—');
    setText('dStatus', d.status || '—');
    var link      = document.getElementById('dLink');
    var linkLabel = document.getElementById('dLinkLabel');
    if (link)      link.href             = d.action      || '#';
    if (linkLabel) linkLabel.textContent = d.actionLabel || 'Voir le détail';
    if (overlay) overlay.classList.add('open');
    if (drawer)  drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    if (overlay) overlay.classList.remove('open');
    if (drawer)  drawer.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (tbody) {
    tbody.addEventListener('click', function (e) {
      if (e.target.closest('a, button')) return;
      var tr = e.target.closest('tr[data-ref]');
      if (tr) openDrawer(tr);
    });
  }

  if (overlay)  overlay.addEventListener('click', closeDrawer);
  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });

  /* ── TOAST ─────────────────────────────────────────────── */
  var toast      = document.getElementById('dashToast');
  var toastClose = document.getElementById('dashToastClose');
  if (toast) {
    setTimeout(function () { toast.classList.add('hidden'); }, 4500);
    if (toastClose) toastClose.addEventListener('click', function () { toast.classList.add('hidden'); });
  }
})();

/* ── AUTO-REFRESH ────────────────────────────────────────── */
(function () {
  var DELAY    = 60;
  var left     = DELAY;
  var paused   = false;
  var countEl  = document.getElementById('dashRefreshCount');
  var pauseBtn = document.getElementById('dashRefreshPause');

  setInterval(function () {
    if (paused) return;
    left--;
    if (countEl) countEl.textContent = left + 's';
    if (left <= 0) { location.reload(); }
  }, 1000);

  if (pauseBtn) {
    pauseBtn.addEventListener('click', function () {
      paused = !paused;
      left   = DELAY;
      if (countEl) countEl.textContent = left + 's';
      pauseBtn.title = paused ? 'Reprendre' : 'Pause auto-refresh';
      var ic = pauseBtn.querySelector('i');
      if (ic) ic.className = paused ? 'fas fa-play' : 'fas fa-pause';
    });
  }
})();
</script>
@endsection
