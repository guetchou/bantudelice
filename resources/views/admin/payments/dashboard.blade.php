@extends('layouts.admin-modern')

@section('title', 'Opérations de paiement')
@section('page_title', 'Opérations de paiement')
@section('nav_active', 'payments')

@php
    $chartLabels = collect($hourlySeries['labels'] ?? [])->values();
    $chartAmounts = collect($hourlySeries['amounts'] ?? [])->map(fn ($value) => (float) $value)->values();
    $chartCounts = collect($hourlySeries['counts'] ?? [])->map(fn ($value) => (int) $value)->values();
    $chartMax = max(1, (float) $chartAmounts->max());
    $chartCount = max(1, $chartAmounts->count());
    $chartPoints = $chartAmounts->map(function ($value, $index) use ($chartMax, $chartCount) {
        $x = $chartCount <= 1 ? 0 : round(($index / ($chartCount - 1)) * 100, 2);
        $y = round(92 - (($value / $chartMax) * 76), 2);
        return $x . ',' . $y;
    })->implode(' ');
    $chartArea = $chartPoints !== '' ? '0,100 ' . $chartPoints . ' 100,100' : '';
    $statusSummary = [
        ['key' => 'paid', 'label' => 'Confirmés', 'value' => ($statusBreakdown['paid'] ?? 0) + ($statusBreakdown['success'] ?? 0), 'tone' => 'success'],
        ['key' => 'pending', 'label' => 'Non résolus', 'value' => ($statusBreakdown['initiated'] ?? 0) + ($statusBreakdown['pending'] ?? 0) + ($statusBreakdown['processing'] ?? 0), 'tone' => 'warning'],
        ['key' => 'failed', 'label' => 'Échecs', 'value' => $statusBreakdown['failed'] ?? 0, 'tone' => 'danger'],
        ['key' => 'unknown', 'label' => 'Inconnus / inversés', 'value' => ($statusBreakdown['unknown'] ?? 0) + ($statusBreakdown['reversed'] ?? 0), 'tone' => 'critical'],
    ];
@endphp

@section('style')
<style>
.payops {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 1600px;
    margin: 0 auto;
}

.payops-panel,
.payops-kpi,
.payops-filterbar,
.payops-health {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
}

.payops-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    flex-wrap: wrap;
}

.payops-breadcrumb {
    display: flex;
    gap: 6px;
    align-items: center;
    color: #94a3b8;
    font-size: .68rem;
    font-weight: 700;
}

.payops-title {
    margin-top: 5px;
    color: #0f172a;
    font-size: 1.35rem;
    line-height: 1.2;
    font-weight: 900;
}

.payops-subtitle {
    max-width: 740px;
    margin-top: 6px;
    color: #64748b;
    font-size: .76rem;
    line-height: 1.55;
}

.payops-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.payops-btn {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px;
    border: 1px solid #dbe3ea;
    border-radius: 9px;
    background: #fff;
    color: #334155;
    text-decoration: none;
    font: 750 .7rem 'Poppins', sans-serif;
    cursor: pointer;
}

.payops-btn:hover { border-color: #94a3b8; color: #0f172a; }
.payops-btn--primary { background: #009543; border-color: #009543; color: #fff; }
.payops-btn--primary:hover { background: #007f39; border-color: #007f39; color: #fff; }
.payops-btn:disabled { opacity: .55; cursor: wait; }

.payops-health {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-left-width: 4px;
}

.payops-health--success { border-left-color: #16a34a; }
.payops-health--warning { border-left-color: #f59e0b; }
.payops-health--danger { border-left-color: #dc2626; }
.payops-health--neutral { border-left-color: #94a3b8; }

.payops-health__icon {
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: #f8fafc;
    color: #475569;
}

.payops-health__title { color: #0f172a; font-size: .76rem; font-weight: 850; }
.payops-health__message { margin-top: 2px; color: #64748b; font-size: .68rem; }
.payops-health__time { margin-left: auto; color: #94a3b8; font-size: .65rem; white-space: nowrap; }

.payops-filterbar {
    padding: 12px;
    display: flex;
    gap: 10px;
    align-items: end;
    flex-wrap: wrap;
}

.payops-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.payops-field label {
    color: #64748b;
    font-size: .61rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
}

.payops-select {
    min-width: 170px;
    height: 36px;
    padding: 0 30px 0 10px;
    border: 1px solid #dbe3ea;
    border-radius: 9px;
    background: #fff;
    color: #334155;
    font: 650 .7rem 'Poppins', sans-serif;
}

.payops-periods {
    display: inline-flex;
    padding: 3px;
    border: 1px solid #dbe3ea;
    border-radius: 9px;
    background: #f8fafc;
}

.payops-period {
    min-width: 48px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 6px;
    color: #64748b;
    text-decoration: none;
    font-size: .67rem;
    font-weight: 800;
}

.payops-period.is-active { background: #fff; color: #009543; box-shadow: 0 1px 3px rgba(15, 23, 42, .1); }
.payops-filterbar__spacer { flex: 1; }

.payops-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.payops-kpi {
    padding: 16px;
    min-width: 0;
}

.payops-kpi__top {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: center;
}

.payops-kpi__label { color: #64748b; font-size: .66rem; font-weight: 800; }
.payops-kpi__icon { color: #94a3b8; font-size: .82rem; }
.payops-kpi__value { margin-top: 10px; color: #0f172a; font-size: 1.42rem; font-weight: 900; line-height: 1; }
.payops-kpi__meta { margin-top: 6px; color: #94a3b8; font-size: .63rem; line-height: 1.45; }

.payops-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(320px, .75fr);
    gap: 14px;
}

.payops-panel { overflow: hidden; }

.payops-panel__head {
    min-height: 58px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 16px;
    border-bottom: 1px solid #edf2f7;
}

.payops-panel__title { color: #0f172a; font-size: .8rem; font-weight: 850; }
.payops-panel__sub { margin-top: 3px; color: #94a3b8; font-size: .64rem; }
.payops-panel__body { padding: 16px; }

.payops-chart-summary {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.payops-chart-summary strong { color: #0f172a; font-size: .86rem; }
.payops-chart-summary span { display: block; margin-top: 2px; color: #94a3b8; font-size: .6rem; }

.payops-chart {
    width: 100%;
    height: 220px;
    border-radius: 10px;
    background: linear-gradient(180deg, #fbfdfc, #f8fafc);
    border: 1px solid #edf2f7;
}

.payops-chart-grid { stroke: #e8edf2; stroke-width: .6; }
.payops-chart-line { fill: none; stroke: #009543; stroke-width: 2.4; vector-effect: non-scaling-stroke; }
.payops-chart-area { fill: rgba(0, 149, 67, .08); }

.payops-chart-labels {
    display: grid;
    grid-template-columns: repeat(var(--chart-columns), minmax(0, 1fr));
    gap: 2px;
    margin-top: 7px;
}

.payops-chart-label {
    overflow: hidden;
    text-align: center;
    color: #94a3b8;
    font-size: .52rem;
    white-space: nowrap;
}

.payops-provider-list { display: flex; flex-direction: column; }

.payops-provider {
    padding: 13px 0;
    border-bottom: 1px solid #edf2f7;
}

.payops-provider:first-child { padding-top: 0; }
.payops-provider:last-child { border-bottom: 0; padding-bottom: 0; }

.payops-provider__top {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: flex-start;
}

.payops-provider__name { color: #0f172a; font-size: .73rem; font-weight: 800; }
.payops-provider__meta { margin-top: 3px; color: #94a3b8; font-size: .61rem; }
.payops-provider__amount { color: #0f172a; font-size: .7rem; font-weight: 850; white-space: nowrap; }

.payops-progress { height: 5px; margin-top: 9px; overflow: hidden; border-radius: 999px; background: #edf2f7; }
.payops-progress span { display: block; height: 100%; border-radius: inherit; background: #009543; }

.payops-status-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #edf2f7;
}

.payops-status {
    padding: 11px 14px;
    border-right: 1px solid #edf2f7;
}

.payops-status:last-child { border-right: 0; }
.payops-status__label { color: #94a3b8; font-size: .59rem; font-weight: 800; }
.payops-status__value { margin-top: 4px; color: #0f172a; font-size: .9rem; font-weight: 900; }
.payops-status--danger .payops-status__value,
.payops-status--critical .payops-status__value { color: #dc2626; }
.payops-status--warning .payops-status__value { color: #d97706; }
.payops-status--success .payops-status__value { color: #15803d; }

.payops-alerts {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.payops-alert {
    display: grid;
    grid-template-columns: 8px minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    padding: 10px 11px;
    border: 1px solid #edf2f7;
    border-radius: 10px;
    background: #fbfcfd;
}

.payops-alert__rail { width: 8px; height: 34px; border-radius: 999px; background: #16a34a; }
.payops-alert--warning .payops-alert__rail { background: #f59e0b; }
.payops-alert--danger .payops-alert__rail { background: #dc2626; }
.payops-alert__label { color: #0f172a; font-size: .69rem; font-weight: 800; }
.payops-alert__message { margin-top: 2px; color: #64748b; font-size: .61rem; line-height: 1.45; }
.payops-alert__value { min-width: 28px; text-align: right; color: #0f172a; font-size: .88rem; font-weight: 900; }

.payops-table-wrap { overflow-x: auto; }
.payops-table { width: 100%; border-collapse: collapse; }

.payops-table th {
    padding: 9px 12px;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
    color: #64748b;
    font-size: .58rem;
    font-weight: 850;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}

.payops-table td {
    padding: 11px 12px;
    border-bottom: 1px solid #edf2f7;
    color: #475569;
    font-size: .68rem;
    vertical-align: middle;
}

.payops-table tr:last-child td { border-bottom: 0; }
.payops-table tbody tr:hover { background: #fbfdfc; }
.payops-table strong { color: #0f172a; font-weight: 800; }
.payops-table small { display: block; margin-top: 3px; color: #94a3b8; font-size: .58rem; line-height: 1.35; }

.payops-priority {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .62rem;
    font-weight: 800;
    white-space: nowrap;
}

.payops-priority::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #f59e0b; }
.payops-priority--critical { color: #b91c1c; }
.payops-priority--critical::before { background: #dc2626; }
.payops-priority--warning { color: #b45309; }

.payops-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 76px;
    padding: 4px 8px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    font-size: .59rem;
    font-weight: 850;
    white-space: nowrap;
}

.payops-badge--paid,
.payops-badge--success { background: #dcfce7; color: #166534; }
.payops-badge--pending,
.payops-badge--initiated,
.payops-badge--processing { background: #fef3c7; color: #92400e; }
.payops-badge--failed,
.payops-badge--unknown,
.payops-badge--reversed,
.payops-badge--disputed { background: #fee2e2; color: #991b1b; }
.payops-badge--cancelled,
.payops-badge--expired,
.payops-badge--refunded { background: #e2e8f0; color: #475569; }

.payops-search {
    width: min(100%, 240px);
    height: 34px;
    padding: 0 10px 0 32px;
    border: 1px solid #dbe3ea;
    border-radius: 8px;
    background: #fff;
    color: #334155;
    font: 650 .66rem 'Poppins', sans-serif;
}

.payops-search-wrap { position: relative; }
.payops-search-wrap i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .68rem; }

.payops-empty { padding: 28px 16px; text-align: center; color: #94a3b8; font-size: .69rem; }
.payops-empty i { display: block; margin-bottom: 7px; font-size: 1.2rem; }
.payops-toast { position: fixed; right: 18px; bottom: 18px; z-index: 500; display: none; max-width: 360px; padding: 11px 14px; border-radius: 10px; background: #0f172a; color: #fff; font-size: .68rem; box-shadow: 0 12px 30px rgba(15,23,42,.25); }
.payops-toast.is-visible { display: block; }
.payops-toast.is-error { background: #991b1b; }

@media (max-width: 1160px) {
    .payops-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .payops-grid { grid-template-columns: 1fr; }
}

@media (max-width: 760px) {
    .payops-kpis,
    .payops-status-strip { grid-template-columns: 1fr 1fr; }
    .payops-status:nth-child(2) { border-right: 0; }
    .payops-status:nth-child(-n+2) { border-bottom: 1px solid #edf2f7; }
    .payops-header { align-items: stretch; }
    .payops-actions { width: 100%; }
    .payops-actions .payops-btn { flex: 1; }
    .payops-field { width: 100%; }
    .payops-select { width: 100%; }
    .payops-filterbar__spacer { display: none; }
    .payops-filterbar .payops-btn { flex: 1; }
}

@media (max-width: 480px) {
    .payops-kpis { grid-template-columns: 1fr; }
    .payops-health { align-items: flex-start; }
    .payops-health__time { display: none; }
}
</style>
@endsection

@section('content')
<div class="payops">
    <header class="payops-header">
        <div>
            <nav class="payops-breadcrumb" aria-label="Fil d’Ariane">
                <span>Finance</span><i class="fas fa-chevron-right" aria-hidden="true"></i><span>Paiements</span>
            </nav>
            <h1 class="payops-title">Centre d’opérations de paiement</h1>
            <p class="payops-subtitle">Surveiller les encaissements confirmés, isoler les exceptions et rapprocher les opérations sans multiplier les écrans.</p>
        </div>
        <div class="payops-actions">
            <a class="payops-btn" href="{{ route('admin.payments.export-csv', array_filter(['provider' => $filters['provider'] !== 'all' ? $filters['provider'] : null, 'status' => $filters['status'] !== 'all' ? $filters['status'] : null])) }}">
                <i class="fas fa-file-export"></i> Exporter CSV
            </a>
            <a class="payops-btn payops-btn--primary" href="{{ request()->fullUrl() }}">
                <i class="fas fa-rotate"></i> Actualiser
            </a>
        </div>
    </header>

    <section class="payops-health payops-health--{{ $health['tone'] ?? 'neutral' }}" aria-label="État du flux financier">
        <div class="payops-health__icon"><i class="fas fa-shield-halved"></i></div>
        <div>
            <div class="payops-health__title">{{ $health['label'] ?? 'État inconnu' }}</div>
            <div class="payops-health__message">{{ $health['message'] ?? 'Aucune information disponible.' }}</div>
        </div>
        <div class="payops-health__time">Mis à jour à {{ $generatedAt->format('H:i:s') }}</div>
    </section>

    <form class="payops-filterbar" method="GET" action="{{ route('admin.payments.dashboard') }}">
        <div class="payops-field">
            <label>Période</label>
            <div class="payops-periods">
                @foreach([6, 12, 24] as $period)
                    <a class="payops-period {{ (int) $hours === $period ? 'is-active' : '' }}" href="{{ route('admin.payments.dashboard', ['hours' => $period, 'provider' => $filters['provider'], 'status' => $filters['status']]) }}">{{ $period }} h</a>
                @endforeach
            </div>
        </div>
        <input type="hidden" name="hours" value="{{ $hours }}">
        <div class="payops-field">
            <label for="providerFilter">Canal</label>
            <select id="providerFilter" name="provider" class="payops-select">
                @foreach($filterOptions['providers'] as $option)
                    <option value="{{ $option['value'] }}" {{ $filters['provider'] === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="payops-field">
            <label for="statusFilter">Statut</label>
            <select id="statusFilter" name="status" class="payops-select">
                @foreach($filterOptions['statuses'] as $option)
                    <option value="{{ $option['value'] }}" {{ $filters['status'] === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="payops-filterbar__spacer"></div>
        <a class="payops-btn" href="{{ route('admin.payments.dashboard') }}">Réinitialiser</a>
        <button class="payops-btn payops-btn--primary" type="submit">Appliquer</button>
    </form>

    <section class="payops-kpis" aria-label="Indicateurs financiers principaux">
        <article class="payops-kpi">
            <div class="payops-kpi__top"><span class="payops-kpi__label">Encaissement confirmé</span><i class="fas fa-coins payops-kpi__icon"></i></div>
            <div class="payops-kpi__value">{{ number_format($kpis['turnover'] ?? 0, 0, ',', ' ') }} FCFA</div>
            <div class="payops-kpi__meta">Uniquement les paiements confirmés aujourd’hui.</div>
        </article>
        <article class="payops-kpi">
            <div class="payops-kpi__top"><span class="payops-kpi__label">Taux de réussite</span><i class="fas fa-chart-line payops-kpi__icon"></i></div>
            <div class="payops-kpi__value">{{ number_format($kpis['success_rate'] ?? 0, 1, ',', ' ') }} %</div>
            <div class="payops-kpi__meta">{{ number_format($kpis['transactions'] ?? 0, 0, ',', ' ') }} tentative(s) observée(s).</div>
        </article>
        <article class="payops-kpi">
            <div class="payops-kpi__top"><span class="payops-kpi__label">Non résolus</span><i class="fas fa-hourglass-half payops-kpi__icon"></i></div>
            <div class="payops-kpi__value">{{ number_format($kpis['pending'] ?? 0, 0, ',', ' ') }}</div>
            <div class="payops-kpi__meta">Initialisation, attente, traitement ou statut inconnu.</div>
        </article>
        <article class="payops-kpi">
            <div class="payops-kpi__top"><span class="payops-kpi__label">Exceptions à traiter</span><i class="fas fa-triangle-exclamation payops-kpi__icon"></i></div>
            <div class="payops-kpi__value">{{ number_format($kpis['exceptions'] ?? 0, 0, ',', ' ') }}</div>
            <div class="payops-kpi__meta">Échecs, inversions et attentes anormalement longues.</div>
        </article>
    </section>

    <section class="payops-grid">
        <article class="payops-panel">
            <div class="payops-panel__head">
                <div>
                    <div class="payops-panel__title">Encaissements confirmés</div>
                    <div class="payops-panel__sub">Montants confirmés sur les {{ $hours }} dernières heures.</div>
                </div>
            </div>
            <div class="payops-panel__body">
                <div class="payops-chart-summary">
                    <div><strong>{{ number_format($chartAmounts->sum(), 0, ',', ' ') }} FCFA</strong><span>Montant confirmé sur la fenêtre</span></div>
                    <div><strong>{{ number_format($chartCounts->sum(), 0, ',', ' ') }}</strong><span>Tentatives sur la fenêtre</span></div>
                    <div><strong>{{ number_format($chartMax, 0, ',', ' ') }} FCFA</strong><span>Pic horaire</span></div>
                </div>
                @if($chartAmounts->count() > 0)
                    <svg class="payops-chart" viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="Évolution des encaissements confirmés">
                        <line class="payops-chart-grid" x1="0" y1="25" x2="100" y2="25"></line>
                        <line class="payops-chart-grid" x1="0" y1="50" x2="100" y2="50"></line>
                        <line class="payops-chart-grid" x1="0" y1="75" x2="100" y2="75"></line>
                        <polygon class="payops-chart-area" points="{{ $chartArea }}"></polygon>
                        <polyline class="payops-chart-line" points="{{ $chartPoints }}"></polyline>
                    </svg>
                    <div class="payops-chart-labels" style="--chart-columns:{{ max(1, $chartLabels->count()) }}">
                        @foreach($chartLabels as $label)<span class="payops-chart-label">{{ $label }}</span>@endforeach
                    </div>
                @else
                    <div class="payops-empty"><i class="fas fa-chart-area"></i>Aucune donnée sur cette période.</div>
                @endif
            </div>
        </article>

        <article class="payops-panel">
            <div class="payops-panel__head">
                <div>
                    <div class="payops-panel__title">Santé des canaux</div>
                    <div class="payops-panel__sub">Volume confirmé, réussite et exceptions.</div>
                </div>
            </div>
            <div class="payops-panel__body">
                <div class="payops-provider-list">
                    @forelse($providerBreakdown as $provider)
                        <div class="payops-provider">
                            <div class="payops-provider__top">
                                <div>
                                    <div class="payops-provider__name">{{ $provider['provider'] }}</div>
                                    <div class="payops-provider__meta">{{ $provider['count'] }} transaction(s) · {{ number_format($provider['success_rate'], 1, ',', ' ') }} % réussies · {{ $provider['exceptions'] }} exception(s)</div>
                                </div>
                                <div class="payops-provider__amount">{{ number_format($provider['amount'], 0, ',', ' ') }} FCFA</div>
                            </div>
                            <div class="payops-progress"><span style="width:{{ min(100, max(3, $provider['share_percent'])) }}%"></span></div>
                        </div>
                    @empty
                        <div class="payops-empty"><i class="fas fa-signal"></i>Aucune activité opérateur.</div>
                    @endforelse
                </div>
            </div>
        </article>
    </section>

    <section class="payops-panel">
        <div class="payops-status-strip">
            @foreach($statusSummary as $status)
                <div class="payops-status payops-status--{{ $status['tone'] }}">
                    <div class="payops-status__label">{{ $status['label'] }}</div>
                    <div class="payops-status__value">{{ number_format($status['value'], 0, ',', ' ') }}</div>
                </div>
            @endforeach
        </div>
        <div class="payops-panel__head">
            <div>
                <div class="payops-panel__title">File de rapprochement</div>
                <div class="payops-panel__sub">Uniquement les paiements qui exigent une décision ou une vérification.</div>
            </div>
            <span class="payops-badge payops-badge--{{ ($kpis['exceptions'] ?? 0) > 0 ? 'failed' : 'paid' }}">{{ $workQueue->count() }} dossier(s)</span>
        </div>
        <div class="payops-table-wrap">
            <table class="payops-table">
                <thead>
                    <tr>
                        <th>Priorité</th>
                        <th>Transaction</th>
                        <th>Canal</th>
                        <th>Montant</th>
                        <th>Âge</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workQueue as $payment)
                        <tr>
                            <td><span class="payops-priority payops-priority--{{ $payment['severity'] }}">{{ $payment['severity'] === 'critical' ? 'Critique' : 'À vérifier' }}</span></td>
                            <td><strong>{{ $payment['id'] }}</strong><small>{{ $payment['order_reference'] }} · {{ $payment['phone'] }}</small></td>
                            <td><strong>{{ $payment['provider'] }}</strong><small>{{ $payment['reference'] }}</small></td>
                            <td><strong>{{ number_format($payment['amount'], 0, ',', ' ') }} FCFA</strong></td>
                            <td>{{ $payment['age_label'] }}</td>
                            <td><span class="payops-badge payops-badge--{{ $payment['status'] }}">{{ $payment['status_label'] }}</span></td>
                            <td>
                                @if($payment['can_reconcile'])
                                    <button type="button" class="payops-btn payops-reconcile" data-reconcile-url="{{ route('admin.payments.reconcile', ['payment' => $payment['raw_id']]) }}">Rapprocher</button>
                                @else
                                    <span class="payops-badge">Lecture seule</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="payops-empty"><i class="fas fa-circle-check"></i>Aucune exception prioritaire.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="payops-grid">
        <article class="payops-panel">
            <div class="payops-panel__head">
                <div>
                    <div class="payops-panel__title">Alertes opérationnelles</div>
                    <div class="payops-panel__sub">Synthèse sans duplication de la file de rapprochement.</div>
                </div>
            </div>
            <div class="payops-panel__body">
                <div class="payops-alerts">
                    @foreach($alerts as $alert)
                        <div class="payops-alert payops-alert--{{ $alert['tone'] }}">
                            <span class="payops-alert__rail"></span>
                            <div><div class="payops-alert__label">{{ $alert['label'] }}</div><div class="payops-alert__message">{{ $alert['message'] }}</div></div>
                            <div class="payops-alert__value">{{ $alert['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="payops-panel">
            <div class="payops-panel__head">
                <div>
                    <div class="payops-panel__title">Règles de lecture</div>
                    <div class="payops-panel__sub">Ce que les chiffres signifient réellement.</div>
                </div>
            </div>
            <div class="payops-panel__body">
                <div class="payops-alerts">
                    <div class="payops-alert"><span class="payops-alert__rail"></span><div><div class="payops-alert__label">Confirmé ≠ initié</div><div class="payops-alert__message">Le chiffre d’affaires affiché exclut les paiements encore en attente.</div></div></div>
                    <div class="payops-alert payops-alert--warning"><span class="payops-alert__rail"></span><div><div class="payops-alert__label">Statut inconnu</div><div class="payops-alert__message">Aucun nouveau débit ne doit être relancé avant rapprochement.</div></div></div>
                    <div class="payops-alert payops-alert--danger"><span class="payops-alert__rail"></span><div><div class="payops-alert__label">Inversion financière</div><div class="payops-alert__message">Une opération inversée reste visible et doit être traitée par contre-écriture.</div></div></div>
                </div>
            </div>
        </article>
    </section>

    <section class="payops-panel">
        <div class="payops-panel__head">
            <div>
                <div class="payops-panel__title">Journal des transactions</div>
                <div class="payops-panel__sub">Trente dernières opérations correspondant aux filtres.</div>
            </div>
            <div class="payops-search-wrap">
                <i class="fas fa-search"></i>
                <input id="paymentSearch" class="payops-search" type="search" placeholder="Rechercher une référence…" autocomplete="off">
            </div>
        </div>
        <div class="payops-table-wrap">
            <table class="payops-table" id="paymentJournalTable">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Payeur / commande</th>
                        <th>Canal / référence</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Activité</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tablePayments as $payment)
                        <tr data-search="{{ strtolower($payment['id'] . ' ' . $payment['phone'] . ' ' . $payment['provider'] . ' ' . $payment['reference'] . ' ' . $payment['order_reference']) }}">
                            <td><strong>{{ $payment['id'] }}</strong><small>{{ $payment['order_reference'] }}</small></td>
                            <td><strong>{{ $payment['phone'] }}</strong>@if($payment['reason'])<small>{{ $payment['reason'] }}</small>@endif</td>
                            <td><strong>{{ $payment['provider'] }}</strong><small>{{ $payment['reference'] }}</small></td>
                            <td><strong>{{ number_format($payment['amount'], 0, ',', ' ') }} FCFA</strong></td>
                            <td><span class="payops-badge payops-badge--{{ $payment['status'] }}">{{ $payment['status_label'] }}</span></td>
                            <td>{{ $payment['updated_at_human'] }}<small>{{ $payment['age_label'] }}</small></td>
                            <td>
                                @if($payment['can_reconcile'])
                                    <button type="button" class="payops-btn payops-reconcile" data-reconcile-url="{{ route('admin.payments.reconcile', ['payment' => $payment['raw_id']]) }}">Vérifier</button>
                                @else
                                    <span class="payops-badge">Clôturé</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="payops-empty"><i class="fas fa-receipt"></i>Aucune transaction à afficher.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="payopsToast" class="payops-toast" role="status" aria-live="polite"></div>
@endsection

@section('script')
<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const toast = document.getElementById('payopsToast');
    const search = document.getElementById('paymentSearch');
    const tableRows = Array.from(document.querySelectorAll('#paymentJournalTable tbody tr[data-search]'));

    function showToast(message, isError) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.toggle('is-error', Boolean(isError));
        toast.classList.add('is-visible');
        window.setTimeout(() => toast.classList.remove('is-visible'), 3500);
    }

    async function reconcile(button) {
        const url = button.dataset.reconcileUrl;
        if (!url) return;

        const original = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vérification';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.status !== true) {
                throw new Error(payload.message || 'Le rapprochement a échoué.');
            }
            showToast(payload.message || 'Rapprochement terminé.', false);
            window.setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast(error.message || 'Erreur pendant le rapprochement.', true);
            button.disabled = false;
            button.innerHTML = original;
        }
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.payops-reconcile');
        if (button) reconcile(button);
    });

    search?.addEventListener('input', function () {
        const query = search.value.trim().toLocaleLowerCase('fr');
        tableRows.forEach(row => {
            row.hidden = query !== '' && !row.dataset.search.includes(query);
        });
    });
})();
</script>
@endsection
