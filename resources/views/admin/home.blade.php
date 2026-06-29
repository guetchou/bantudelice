@extends('layouts.admin-modern')

@section('title', ($workspaceMeta['page_title'] ?? 'Pilotage') . ' | Admin')
@section('nav_active', ($workspace ?? 'bantudelice') === 'kende' ? 'kende-dashboard' : (($workspace ?? 'bantudelice') === 'mema' ? 'mema-dashboard' : 'dashboard'))
@section('page_title', $workspaceMeta['page_title'] ?? 'Tableau de bord')

@php
    $workspace = $workspace ?? 'bantudelice';
    $period = (int) ($period ?? 30);
    $workspaceIntro = $workspaceMeta['intro'] ?? 'Vue opérationnelle des dossiers qui nécessitent une décision.';
    $globalStateLabel = $workspaceMeta['global_state'] ?? 'Stable';

    $visibleKpis = collect($kpis ?? [])->take(4)->values();
    $alertsCollection = collect($alerts ?? []);
    $decisionsCollection = collect($secondaryList ?? []);
    $tableHeaders = collect(data_get($primaryTable ?? [], 'headers', []));
    $tableRows = collect(data_get($primaryTable ?? [], 'rows', []))->take(8)->values();

    $actionQueue = $alertsCollection
        ->filter(fn ($alert) => (int) ($alert['count'] ?? 0) > 0)
        ->map(function ($alert) {
            $count = (int) ($alert['count'] ?? 0);
            $level = (string) ($alert['level'] ?? 'warning');

            return [
                'priority' => $level === 'critical' || $count >= 5 ? 'critical' : 'warning',
                'title' => $alert['title'] ?? 'Action requise',
                'description' => $alert['message'] ?? '',
                'count' => $count,
                'action_label' => $alert['action_label'] ?? 'Traiter',
                'action_url' => $alert['action_url'] ?? null,
            ];
        })
        ->concat(
            $decisionsCollection
                ->filter(function ($decision) {
                    $rank = preg_replace('/[^0-9]/', '', (string) ($decision['rank'] ?? '0'));
                    return (int) $rank > 0;
                })
                ->map(function ($decision) {
                    $rank = (int) preg_replace('/[^0-9]/', '', (string) ($decision['rank'] ?? '0'));

                    return [
                        'priority' => $rank >= 5 ? 'critical' : 'warning',
                        'title' => $decision['title'] ?? 'Décision attendue',
                        'description' => $decision['meta'] ?? '',
                        'count' => $rank,
                        'action_label' => $decision['action_label'] ?? 'Ouvrir',
                        'action_url' => $decision['action_url'] ?? null,
                    ];
                })
        )
        ->unique('title')
        ->sortByDesc(fn ($item) => $item['priority'] === 'critical' ? 1000 + $item['count'] : $item['count'])
        ->take(8)
        ->values();

    $chartLabels = collect($revenueChart['labels'] ?? [])->slice(-14)->values();
    $chartValues = collect($revenueChart['values'] ?? [])->slice(-14)->map(fn ($value) => (float) $value)->values();
    $chartMax = max((float) $chartValues->max(), 1);
    $chartMode = $revenueChart['mode'] ?? 'count';
    $chartColor = $revenueChart['color'] ?? '#009543';
    $chartLatest = (float) ($chartValues->last() ?? 0);
    $chartTotal = (float) $chartValues->sum();
@endphp

@section('style')
<style>
.admin-focus {
    display: flex;
    flex-direction: column;
    gap: 18px;
    max-width: 1440px;
    margin: 0 auto;
}

.admin-focus__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
}

.admin-focus__intro {
    max-width: 760px;
    color: #6b7280;
    font-size: .84rem;
    line-height: 1.55;
}

.admin-focus__controls {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.admin-focus__state,
.admin-focus__period {
    min-height: 34px;
    border-radius: 9px;
    border: 1px solid #dbe3ea;
    background: #fff;
    font: 700 .73rem 'Poppins', sans-serif;
}

.admin-focus__state {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0 12px;
    color: #166534;
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.admin-focus__state::before {
    content: '';
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
}

.admin-focus__period {
    padding: 0 10px;
    color: #374151;
    cursor: pointer;
}

.admin-focus__kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.admin-focus__kpi,
.admin-focus__panel {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
}

.admin-focus__kpi {
    padding: 17px;
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
}

.admin-focus__kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ecfdf5;
    color: #00833b;
    font-size: .92rem;
}

.admin-focus__kpi-value {
    color: #111827;
    font-size: 1.35rem;
    line-height: 1.1;
    font-weight: 900;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-focus__kpi-label {
    margin-top: 4px;
    color: #374151;
    font-size: .7rem;
    font-weight: 800;
}

.admin-focus__kpi-meta {
    margin-top: 2px;
    color: #9ca3af;
    font-size: .64rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-focus__main {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(340px, .7fr);
    gap: 14px;
}

.admin-focus__panel-head {
    padding: 15px 17px;
    border-bottom: 1px solid #eef2f7;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.admin-focus__panel-title {
    color: #111827;
    font-size: .84rem;
    font-weight: 850;
}

.admin-focus__panel-sub {
    margin-top: 2px;
    color: #9ca3af;
    font-size: .66rem;
}

.admin-focus__panel-body {
    padding: 14px 17px 17px;
}

.admin-focus__queue {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.admin-focus__queue-item {
    display: grid;
    grid-template-columns: 8px 38px minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    padding: 11px 12px;
    border: 1px solid #edf1f5;
    border-radius: 11px;
    background: #fbfcfd;
}

.admin-focus__queue-severity {
    width: 8px;
    height: 36px;
    border-radius: 999px;
    background: #f59e0b;
}

.admin-focus__queue-item.is-critical .admin-focus__queue-severity {
    background: #ef4444;
}

.admin-focus__queue-count {
    min-width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff7ed;
    color: #c2410c;
    font-size: .8rem;
    font-weight: 900;
}

.admin-focus__queue-item.is-critical .admin-focus__queue-count {
    background: #fef2f2;
    color: #dc2626;
}

.admin-focus__queue-title {
    color: #111827;
    font-size: .76rem;
    font-weight: 800;
}

.admin-focus__queue-desc {
    margin-top: 2px;
    color: #6b7280;
    font-size: .66rem;
    line-height: 1.45;
}

.admin-focus__queue-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 10px;
    border-radius: 8px;
    background: #009543;
    color: #fff;
    text-decoration: none;
    font-size: .68rem;
    font-weight: 800;
    white-space: nowrap;
}

.admin-focus__empty {
    padding: 34px 18px;
    text-align: center;
    color: #94a3b8;
    font-size: .76rem;
}

.admin-focus__chart-summary {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 14px;
}

.admin-focus__chart-stat {
    padding: 10px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #edf2f7;
}

.admin-focus__chart-stat-label {
    color: #9ca3af;
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.admin-focus__chart-stat-value {
    margin-top: 3px;
    color: #111827;
    font-size: .92rem;
    font-weight: 900;
}

.admin-focus__bars {
    height: 170px;
    display: flex;
    align-items: flex-end;
    gap: 5px;
    padding-top: 10px;
}

.admin-focus__bar-wrap {
    min-width: 0;
    height: 100%;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: center;
    gap: 6px;
}

.admin-focus__bar {
    width: 100%;
    min-height: 3px;
    border-radius: 5px 5px 2px 2px;
    opacity: .9;
}

.admin-focus__bar-label {
    width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-align: center;
    color: #9ca3af;
    font-size: .52rem;
}

.admin-focus__table-wrap {
    overflow-x: auto;
}

.admin-focus__table {
    width: 100%;
    border-collapse: collapse;
}

.admin-focus__table th {
    padding: 9px 11px;
    border-bottom: 1px solid #e5e7eb;
    color: #94a3b8;
    font-size: .62rem;
    font-weight: 800;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: .05em;
    white-space: nowrap;
}

.admin-focus__table td {
    padding: 11px;
    border-bottom: 1px solid #f1f5f9;
    color: #374151;
    font-size: .72rem;
    vertical-align: middle;
    white-space: nowrap;
}

.admin-focus__table tr:last-child td {
    border-bottom: 0;
}

.admin-focus__reference {
    color: #111827;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-weight: 800;
}

.admin-focus__badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    font-size: .62rem;
    font-weight: 800;
}

.admin-focus__table-link {
    color: #00833b;
    text-decoration: none;
    font-weight: 800;
}

@media (max-width: 1180px) {
    .admin-focus__kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .admin-focus__main { grid-template-columns: 1fr; }
}

@media (max-width: 680px) {
    .admin-focus__kpis { grid-template-columns: 1fr; }
    .admin-focus__queue-item { grid-template-columns: 7px 34px minmax(0, 1fr); }
    .admin-focus__queue-link { grid-column: 2 / -1; justify-content: center; }
    .admin-focus__header { align-items: stretch; }
    .admin-focus__controls { width: 100%; }
    .admin-focus__period { flex: 1; }
}
</style>
@endsection

@section('content')
<div class="admin-focus">
    <header class="admin-focus__header">
        <p class="admin-focus__intro">{{ $workspaceIntro }}</p>

        <div class="admin-focus__controls">
            <span class="admin-focus__state">{{ $globalStateLabel }}</span>
            <form method="GET" action="{{ route('admin.dashboard') }}">
                <input type="hidden" name="workspace" value="{{ $workspace }}">
                <select name="period" class="admin-focus__period" onchange="this.form.submit()" aria-label="Période d'analyse">
                    @foreach(($periodOptions ?? [7, 30, 90]) as $option)
                        <option value="{{ $option }}" {{ (int) $option === $period ? 'selected' : '' }}>
                            {{ $option }} jours
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </header>

    <section class="admin-focus__kpis" aria-label="Indicateurs principaux">
        @foreach($visibleKpis as $kpi)
            <article class="admin-focus__kpi">
                <div class="admin-focus__kpi-icon" aria-hidden="true">
                    <i class="{{ $kpi['icon'] ?? 'fas fa-chart-line' }}"></i>
                </div>
                <div>
                    <div class="admin-focus__kpi-value">{{ $kpi['value'] ?? 0 }}</div>
                    <div class="admin-focus__kpi-label">{{ $kpi['label'] ?? 'Indicateur' }}</div>
                    @if(!empty($kpi['meta']))
                        <div class="admin-focus__kpi-meta">{{ $kpi['meta'] }}</div>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <section class="admin-focus__main">
        <article class="admin-focus__panel">
            <div class="admin-focus__panel-head">
                <div>
                    <div class="admin-focus__panel-title">Actions requises</div>
                    <div class="admin-focus__panel-sub">Une seule file de travail, classée par priorité.</div>
                </div>
                <span class="admin-focus__badge">{{ $actionQueue->count() }} dossier{{ $actionQueue->count() > 1 ? 's' : '' }}</span>
            </div>

            <div class="admin-focus__panel-body">
                @forelse($actionQueue as $item)
                    <div class="admin-focus__queue-item {{ $item['priority'] === 'critical' ? 'is-critical' : '' }}">
                        <span class="admin-focus__queue-severity" aria-hidden="true"></span>
                        <span class="admin-focus__queue-count">{{ $item['count'] }}</span>
                        <div>
                            <div class="admin-focus__queue-title">{{ $item['title'] }}</div>
                            <div class="admin-focus__queue-desc">{{ $item['description'] }}</div>
                        </div>
                        @if(!empty($item['action_url']))
                            <a href="{{ $item['action_url'] }}" class="admin-focus__queue-link">
                                {{ $item['action_label'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="admin-focus__empty">
                        <i class="fas fa-check-circle" style="color:#22c55e;font-size:1.5rem;margin-bottom:8px;"></i>
                        <div>Aucune action prioritaire en attente.</div>
                    </div>
                @endforelse
            </div>
        </article>

        <article class="admin-focus__panel">
            <div class="admin-focus__panel-head">
                <div>
                    <div class="admin-focus__panel-title">{{ $workspaceMeta['chart_title'] ?? 'Tendance' }}</div>
                    <div class="admin-focus__panel-sub">Lecture synthétique des 14 derniers points.</div>
                </div>
            </div>

            <div class="admin-focus__panel-body">
                <div class="admin-focus__chart-summary">
                    <div class="admin-focus__chart-stat">
                        <div class="admin-focus__chart-stat-label">Dernier point</div>
                        <div class="admin-focus__chart-stat-value">
                            {{ number_format($chartLatest, 0, ',', ' ') }}{{ $chartMode === 'currency' ? ' FCFA' : '' }}
                        </div>
                    </div>
                    <div class="admin-focus__chart-stat">
                        <div class="admin-focus__chart-stat-label">Cumul affiché</div>
                        <div class="admin-focus__chart-stat-value">
                            {{ number_format($chartTotal, 0, ',', ' ') }}{{ $chartMode === 'currency' ? ' FCFA' : '' }}
                        </div>
                    </div>
                </div>

                @if($chartValues->max() > 0)
                    <div class="admin-focus__bars" role="img" aria-label="Graphique de tendance">
                        @foreach($chartValues as $index => $value)
                            <div class="admin-focus__bar-wrap" title="{{ $chartLabels->get($index, '') }} : {{ number_format($value, 0, ',', ' ') }}">
                                <div class="admin-focus__bar" style="height:{{ max(3, round(($value / $chartMax) * 100)) }}%;background:{{ $chartColor }};"></div>
                                <div class="admin-focus__bar-label">{{ $chartLabels->get($index, '') }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="admin-focus__empty">
                        <i class="fas fa-chart-bar" style="font-size:1.4rem;margin-bottom:8px;"></i>
                        <div>Aucune donnée sur cette période.</div>
                    </div>
                @endif
            </div>
        </article>
    </section>

    <section class="admin-focus__panel">
        <div class="admin-focus__panel-head">
            <div>
                <div class="admin-focus__panel-title">{{ $workspaceMeta['table_title'] ?? 'Opérations récentes' }}</div>
                <div class="admin-focus__panel-sub">Les opérations récentes restent accessibles sans dupliquer les alertes.</div>
            </div>
            @if(!empty($workspaceMeta['table_cta_url']))
                <a href="{{ $workspaceMeta['table_cta_url'] }}" class="admin-focus__table-link">Voir tout →</a>
            @endif
        </div>

        <div class="admin-focus__table-wrap">
            <table class="admin-focus__table">
                <thead>
                    <tr>
                        @foreach($tableHeaders as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($tableRows as $row)
                        <tr>
                            <td class="admin-focus__reference">{{ $row['reference'] ?? '—' }}</td>
                            <td><span class="admin-focus__badge">{{ $row['type'] ?? '—' }}</span></td>
                            <td>{{ $row['zone'] ?? '—' }}</td>
                            <td>{{ $row['owner'] ?? '—' }}</td>
                            <td>{{ $row['delay'] ?? '—' }}</td>
                            <td><span class="admin-focus__badge">{{ $row['status_label'] ?? '—' }}</span></td>
                            <td>
                                @if(!empty($row['action_url']))
                                    <a href="{{ $row['action_url'] }}" class="admin-focus__table-link">
                                        {{ $row['action_label'] ?? 'Ouvrir' }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max($tableHeaders->count(), 1) }}">
                                <div class="admin-focus__empty">Aucune opération récente sur cette période.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
