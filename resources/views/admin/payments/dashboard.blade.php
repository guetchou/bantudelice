@extends('layouts.admin-modern')

@section('title', 'Cockpit Paiements')
@section('page_title', 'Cockpit Paiements')
@section('nav_active', 'payments')

@section('style')
<style>
.payment-dashboard {
    display: grid;
    gap: .9rem;
}
.payment-context-band { display: none; }
.payment-hero { display: none; }
.payment-filter-group {
    display: inline-flex;
    gap: .45rem;
    flex-wrap: wrap;
}
.payment-pill {
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    border-radius: 999px;
    padding: .38rem .7rem;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .02em;
    border: 1px solid #d1d5db;
    background: #f3f4f6;
    color: #374151;
    cursor: pointer;
}
.payment-pill:hover { background: #e5e7eb; color: #111827; }
.payment-pill.active {
    background: rgba(0,149,67,.1);
    color: #009543;
    border-color: rgba(0,149,67,.3);
}
.payment-pill--ghost {
    text-transform: none;
    letter-spacing: 0;
    font-weight: 600;
}
.payment-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
}
.payment-kpi-card {
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    padding: .9rem;
    background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
    border: 1px solid rgba(255,255,255,.06);
}
.payment-kpi-card::after {
    content: '';
    position: absolute;
    inset: auto -35px -35px auto;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,.16), transparent 68%);
}
.payment-kpi-label {
    font-size: .72rem;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: .12em;
    font-weight: 800;
}
.payment-kpi-value {
    margin-top: .45rem;
    font-family: var(--f-d);
    font-size: 1.55rem;
    line-height: 1;
    color: var(--text);
}
.payment-kpi-meta {
    margin-top: .3rem;
    font-size: .74rem;
    color: var(--text-2);
    line-height: 1.45;
}
.payment-main-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(320px, .9fr);
    gap: .9rem;
}
.payment-status-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .6rem;
}
.payment-status-card {
    padding: .75rem .8rem;
    border-radius: 14px;
    background: rgba(255,255,255,.035);
    border: 1px solid rgba(255,255,255,.06);
}
.payment-status-name {
    font-size: .72rem;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: .1em;
    font-weight: 800;
}
.payment-status-value {
    margin-top: .4rem;
    font-family: var(--f-d);
    font-size: 1.28rem;
    color: var(--text);
    line-height: 1;
}
.payment-card {
    border-radius: 16px;
    padding: .95rem;
    background: linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.025));
    border: 1px solid rgba(255,255,255,.06);
}
.payment-card-header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    margin-bottom: .75rem;
}
.payment-card-title {
    font-size: .9rem;
    font-weight: 800;
    color: var(--text);
}
.payment-card-subtitle {
    margin-top: .2rem;
    font-size: .72rem;
    color: var(--text-3);
    line-height: 1.45;
}
.payment-alert-list,
.payment-stream,
.payment-provider-list {
    display: grid;
    gap: .6rem;
}
.payment-alert {
    display: grid;
    gap: .3rem;
    padding: .75rem .8rem;
    border-radius: 14px;
    border: 1px solid transparent;
}
.payment-alert--warning { background: rgba(245, 158, 11, .11); border-color: rgba(245, 158, 11, .25); }
.payment-alert--danger { background: rgba(239, 68, 68, .1); border-color: rgba(239, 68, 68, .2); }
.payment-alert--success { background: rgba(34, 197, 94, .1); border-color: rgba(34, 197, 94, .18); }
.payment-alert--info { background: rgba(59, 130, 246, .1); border-color: rgba(59, 130, 246, .2); }
.payment-alert-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    color: var(--text);
    font-weight: 700;
}
.payment-alert-value {
    font-size: 1.1rem;
}
.payment-alert-message {
    color: var(--text-2);
    font-size: .76rem;
    line-height: 1.45;
}
.payment-provider-row {
    display: grid;
    gap: .45rem;
}
.payment-provider-top {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: baseline;
}
.payment-provider-name {
    font-weight: 700;
    color: var(--text);
}
.payment-provider-meta {
    font-size: .76rem;
    color: var(--text-3);
}
.payment-progress {
    height: 8px;
    border-radius: 999px;
    background: rgba(255,255,255,.06);
    overflow: hidden;
}
.payment-progress > span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #1db860, #6ee7b7);
}
.payment-stream-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: .65rem;
    align-items: center;
    padding: .7rem .8rem;
    border-radius: 14px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.05);
}
.payment-stream-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    box-shadow: 0 0 0 6px rgba(255,255,255,.03);
}
.payment-stream-dot--success,
.status-badge--success,
.status-badge--paid { background: #22c55e; color: #baf7cd; }
.payment-stream-dot--pending,
.status-badge--pending,
.status-badge--initiated { background: #f59e0b; color: #fde68a; }
.payment-stream-dot--processing,
.status-badge--processing { background: #38bdf8; color: #bae6fd; }
.payment-stream-dot--failed,
.status-badge--failed { background: #ef4444; color: #fecaca; }
.payment-stream-dot--cancelled,
.status-badge--cancelled { background: #a78bfa; color: #ddd6fe; }
.payment-stream-dot--expired,
.status-badge--expired { background: #94a3b8; color: #e2e8f0; }
.payment-stream-dot--refunded,
.status-badge--refunded { background: #06b6d4; color: #cffafe; }
.payment-stream-main {
    min-width: 0;
}
.payment-stream-phone {
    font-weight: 700;
    color: var(--text);
}
.payment-stream-ref {
    margin-top: .2rem;
    font-size: .72rem;
    color: var(--text-3);
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
    overflow-wrap: anywhere;
    line-height: 1.4;
}
.payment-stream-side {
    text-align: right;
}
.payment-stream-amount {
    font-weight: 800;
    color: var(--text);
}
.payment-stream-time {
    margin-top: .2rem;
    font-size: .72rem;
    color: var(--text-3);
}
.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 92px;
    padding: .35rem .6rem;
    border-radius: 999px;
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    background: rgba(255,255,255,.08);
}
.payment-table-wrap {
    overflow-x: auto;
}
.payment-table {
    width: 100%;
    border-collapse: collapse;
}
.payment-table th,
.payment-table td {
    padding: .6rem .55rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
    vertical-align: top;
}
.payment-table th {
    font-size: .7rem;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: .12em;
}
.payment-table td {
    color: var(--text-2);
    font-size: .78rem;
    line-height: 1.45;
    word-break: break-word;
}
.payment-table strong {
    color: var(--text);
}
.payment-table small {
    display: block;
    margin-top: .2rem;
    color: var(--text-3);
    line-height: 1.4;
}
.payment-action-btn {
    border: 1px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.04);
    color: var(--text);
    border-radius: 999px;
    padding: .45rem .75rem;
    font-size: .72rem;
    font-weight: 700;
}
.payment-filter-select {
    min-width: 170px;
}
.payment-muted-row {
    color: var(--text-3);
    text-align: center;
}
@media (max-width: 1100px) {
    .payment-kpi-grid,
    .payment-main-grid,
    .payment-status-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection

@section('content')
<div class="payment-dashboard" style="padding:24px;">
    @include('admin.partials.control_hub_nav')
    @php
        $financeMenus = ['Dashboard', 'Transactions', 'Retraits', 'Rapprochements', 'Remboursements', 'Rapports'];
        $financeQueues = ['Anomalies', 'Retraits', 'Rapprochements'];
        $financeAlerts = collect($cards['alerts'] ?? []);
        $financeTables = collect($tables['recent_transactions']['rows'] ?? []);
    @endphp

    <div class="adm-page-bar">
        <div class="adm-page-bar__left">
            <nav class="adm-page-bar__breadcrumb">
                <span>Finance</span><span class="sep">/</span><span>Paiements</span>
            </nav>
            <h1 class="adm-page-bar__title">Cockpit Paiements</h1>
        </div>
        <div class="adm-page-bar__right">
            <span class="adm-page-bar__badge adm-page-bar__badge--warn">{{ number_format($kpis['failed'] ?? 0, 0, ',', ' ') }} anomalies</span>
            <span class="adm-page-bar__badge adm-page-bar__badge--warn">{{ number_format($kpis['pending'] ?? 0, 0, ',', ' ') }} en attente</span>
        </div>
    </div>
    <div class="ops-kpi-grid" style="margin-bottom:1rem;">
        <div class="ops-kpi">
            <div class="ops-kpi__label">Montants traites</div>
            <div class="ops-kpi__value">{{ number_format($kpis['turnover'] ?? 0, 0, ',', ' ') }} FCFA</div>
            <div class="ops-kpi__sub">Jour courant</div>
        </div>
        <div class="ops-kpi">
            <div class="ops-kpi__label">Anomalies paiement</div>
            <div class="ops-kpi__value">{{ number_format($kpis['failed'] ?? 0, 0, ',', ' ') }}</div>
            <div class="ops-kpi__sub">A rapprocher</div>
        </div>
        <div class="ops-kpi">
            <div class="ops-kpi__label">Retraits en attente</div>
            <div class="ops-kpi__value">{{ number_format($cards['pending_statuses']['pending'] ?? 0, 0, ',', ' ') }}</div>
            <div class="ops-kpi__sub">Flux sous controle</div>
        </div>
        <div class="ops-kpi">
            <div class="ops-kpi__label">Encaissement du jour</div>
            <div class="ops-kpi__value">{{ number_format($kpis['turnover'] ?? 0, 0, ',', ' ') }} FCFA</div>
            <div class="ops-kpi__sub">Base de calcul marge</div>
        </div>
    </div>
    <div class="ops-panel ops-alerts" style="margin-bottom:1rem;">
        @forelse($financeAlerts->take(3) as $alert)
            <div class="ops-alert">
                <div class="ops-alert__top">
                    <h3>{{ $alert['title'] ?? 'Alerte finance' }}</h3>
                    <span class="ops-pill {{ ($alert['severity'] ?? '') === 'critical' ? 'ops-pill--danger' : 'ops-pill--warn' }}">{{ $alert['value'] ?? 'A surveiller' }}</span>
                </div>
                <p>{{ $alert['description'] ?? 'Controle requis sur le flux financier.' }}</p>
            </div>
        @empty
            <div class="ops-alert">
                <div class="ops-alert__top">
                    <h3>Aucune alerte critique</h3>
                    <span class="ops-pill ops-pill--ok">Stable</span>
                </div>
                <p>Le cockpit finance ne remonte pas de derive majeure pour la periode selectionnee.</p>
            </div>
        @endforelse
    </div>

    <section class="ops-grid ops-grid--2">
        <div class="ops-card">
            <div class="ops-card__header">
                <div><h2>Incidents a traiter</h2><p>File immediate.</p></div>
                <span class="ops-pill ops-pill--danger">{{ $financeAlerts->take(3)->count() }} ouverts</span>
            </div>
            <div class="ops-queue">
                @forelse($financeAlerts->take(3) as $alert)
                    <div class="ops-queue-item">
                        <div class="ops-queue-dot {{ ($alert['severity'] ?? '') === 'critical' ? 'ops-queue-dot--danger' : 'ops-queue-dot--warn' }}"></div>
                        <div>
                            <h3>{{ $alert['title'] ?? 'Incident finance' }}</h3>
                            <p>{{ $alert['description'] ?? 'Vérification transactionnelle requise.' }}</p>
                        </div>
                        <a href="#finance-table">Verifier</a>
                    </div>
                @empty
                    <div class="ops-queue-item">
                        <div class="ops-queue-dot ops-queue-dot--ok"></div>
                        <div>
                            <h3>Pas d'incident prioritaire</h3>
                            <p>Le périmètre finance reste stable sur la période affichée.</p>
                        </div>
                        <a href="#finance-table">Suivre</a>
                    </div>
                @endforelse
            </div>
        </div>
        <div class="ops-card">
            <div class="ops-card__header">
                <div><h2>Suivi court</h2><p>Indicateurs secondaires.</p></div>
            </div>
            <div class="ops-stats-grid">
                <div class="ops-stat"><strong>{{ number_format($kpis['success_rate'] ?? 0, 1, ',', ' ') }}%</strong><span>Taux de succes</span></div>
                <div class="ops-stat"><strong>{{ number_format($kpis['transactions'] ?? 0, 0, ',', ' ') }}</strong><span>Transactions observees</span></div>
                <div class="ops-stat"><strong>{{ number_format($cards['pending_statuses']['processing'] ?? 0, 0, ',', ' ') }}</strong><span>En traitement</span></div>
                <div class="ops-stat"><strong>{{ number_format($cards['pending_statuses']['failed'] ?? 0, 0, ',', ' ') }}</strong><span>Echecs recents</span></div>
            </div>
            <div class="ops-trend">
                <svg viewBox="0 0 100 40" preserveAspectRatio="none">
                    <polyline fill="none" stroke="#009543" stroke-width="2.5" points="0,25 14,22 28,23 42,18 57,16 71,19 85,14 100,11"></polyline>
                </svg>
            </div>
        </div>
    </section>

    <section class="ops-grid ops-grid--2">
        <div class="ops-card">
            <div class="ops-card__header"><div><h2>Actions requises</h2><p>Validations et arbitrages.</p></div></div>
            <table class="table">
                <thead><tr><th>Sujet</th><th>File</th><th>Statut</th></tr></thead>
                <tbody>
                    <tr><td><strong>Anomalies du jour</strong><span>Vérifier les paiements en échec et les doublons éventuels</span></td><td>Transactions</td><td><span class="ops-pill ops-pill--warn">Traiter</span></td></tr>
                    <tr><td><strong>Rapprochement providers</strong><span>Comparer les statuts applicatifs et opérateur</span></td><td>Rapprochements</td><td><span class="ops-pill ops-pill--warn">Verifier</span></td></tr>
                    <tr><td><strong>Cloture journaliere</strong><span>Préparer les totaux confirmés pour le reporting</span></td><td>Rapports</td><td><span class="ops-pill ops-pill--ok">Planifier</span></td></tr>
                </tbody>
            </table>
        </div>
        <div class="ops-card" id="finance-table">
            <div class="ops-card__header"><div><h2>Tableau principal</h2><p>Exceptions et statuts utiles.</p></div></div>
            <table class="table">
                <thead><tr><th>Reference</th><th>Type</th><th>Canal</th><th>Montant</th><th>Statut</th></tr></thead>
                <tbody>
                    @forelse($financeTables->take(3) as $row)
                        <tr>
                            <td><strong>{{ $row['reference'] ?? 'N/A' }}</strong><span>{{ $row['secondary'] ?? ($row['phone'] ?? '') }}</span></td>
                            <td>{{ $row['provider'] ?? ($row['type'] ?? 'Paiement') }}</td>
                            <td>{{ $row['channel'] ?? ($row['provider_label'] ?? 'Mobile money') }}</td>
                            <td>{{ $row['amount'] ?? '0 FCFA' }}</td>
                            <td><span class="ops-pill {{ str_contains(strtolower($row['status'] ?? ''), 'fail') ? 'ops-pill--danger' : (str_contains(strtolower($row['status'] ?? ''), 'pending') ? 'ops-pill--warn' : 'ops-pill--ok') }}">{{ $row['status_label'] ?? ($row['status'] ?? 'Statut') }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><span>Aucune transaction exploitable pour la période sélectionnée.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="ops-filter-card" style="margin-bottom:1rem;">
        <div class="ops-filter-row">
            <span class="ops-label">Periode</span>
            <div class="payment-filter-group" id="hoursFilterGroup">
                @foreach([6, 12, 24] as $period)
                    <a href="{{ route('admin.payments.dashboard', ['hours' => $period, 'provider' => $filters['provider'], 'status' => $filters['status']]) }}" data-hours="{{ $period }}" class="payment-pill {{ (int) $hours === $period ? 'active' : '' }}">
                        {{ $period }}h
                    </a>
                @endforeach
            </div>
            <select id="providerFilter" class="ops-filter-input">
                @foreach($filterOptions['providers'] as $providerOption)
                    <option value="{{ $providerOption['value'] }}" {{ $filters['provider'] === $providerOption['value'] ? 'selected' : '' }}>
                        {{ $providerOption['label'] }}
                    </option>
                @endforeach
            </select>
            <select id="statusFilter" class="ops-filter-input">
                @foreach($filterOptions['statuses'] as $statusOption)
                    <option value="{{ $statusOption['value'] }}" {{ $filters['status'] === $statusOption['value'] ? 'selected' : '' }}>
                        {{ $statusOption['label'] }}
                    </option>
                @endforeach
            </select>
            <span class="ops-label" id="generatedAtLabel">
                {{ $generatedAt->format('d/m/Y H:i:s') }}
            </span>
            <button type="button" class="ops-primary-btn" id="refreshDashboardBtn">Rafraichir</button>
        </div>
    </div>

    <section class="payment-kpi-grid">
        <article class="payment-kpi-card">
            <div class="payment-kpi-label">Encaissement confirmé du jour</div>
            <div class="payment-kpi-value" id="kpiTurnover">{{ number_format($kpis['turnover'], 0, ',', ' ') }}</div>
            <div class="payment-kpi-meta">FCFA sur paiements déjà confirmés</div>
        </article>
        <article class="payment-kpi-card">
            <div class="payment-kpi-label">Transactions observées</div>
            <div class="payment-kpi-value" id="kpiTransactions">{{ number_format($kpis['transactions'], 0, ',', ' ') }}</div>
            <div class="payment-kpi-meta">Toutes tentatives de paiement confondues</div>
        </article>
        <article class="payment-kpi-card">
            <div class="payment-kpi-label">Taux de succès du jour</div>
            <div class="payment-kpi-value" id="kpiSuccessRate">{{ number_format($kpis['success_rate'], 1, ',', ' ') }}%</div>
            <div class="payment-kpi-meta">Paiements réussis rapportés au volume observé</div>
        </article>
        <article class="payment-kpi-card">
            <div class="payment-kpi-label">Paiements en attente</div>
            <div class="payment-kpi-value" id="kpiPending">{{ number_format($kpis['pending'], 0, ',', ' ') }}</div>
            <div class="payment-kpi-meta">Initialisation + attente + traitement</div>
        </article>
    </section>

    <section class="payment-main-grid">
        <article class="payment-card">
            <div class="payment-card-header">
                <div>
                    <div class="payment-card-title">Activité des paiements</div>
                    <div class="payment-card-subtitle">Montants initiés sur les {{ $hours }} dernières heures pour le pilotage finance</div>
                </div>
            </div>
            <div style="height:280px;">
                <canvas id="paymentsLineChart"></canvas>
            </div>
        </article>

        <article class="payment-card">
            <div class="payment-card-header">
                <div>
                    <div class="payment-card-title">Alertes critiques</div>
                    <div class="payment-card-subtitle">Points à surveiller immédiatement</div>
                </div>
            </div>
            <div class="payment-alert-list" id="paymentAlertsList">
                @foreach($alerts as $alert)
                    <div class="payment-alert payment-alert--{{ $alert['tone'] }}">
                        <div class="payment-alert-top">
                            <span>{{ $alert['label'] }}</span>
                            <span class="payment-alert-value">{{ $alert['value'] }}</span>
                        </div>
                        <div class="payment-alert-message">{{ $alert['message'] }}</div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="payment-status-grid">
        @foreach([
            'initiated' => 'Initialisation',
            'pending' => 'En attente',
            'processing' => 'Traitement',
            'paid' => 'Payé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            'expired' => 'Expiré',
            'refunded' => 'Remboursé',
        ] as $statusKey => $statusLabel)
            <article class="payment-status-card">
                <div class="payment-status-name">{{ $statusLabel }}</div>
                <div class="payment-status-value" data-status-key="{{ $statusKey }}">{{ $statusBreakdown[$statusKey] ?? 0 }}</div>
            </article>
        @endforeach
    </section>

    <section class="payment-main-grid">
        <article class="payment-card">
            <div class="payment-card-header">
                <div>
                    <div class="payment-card-title">Flux temps réel</div>
                    <div class="payment-card-subtitle">Dernières transactions mises à jour côté finance</div>
                </div>
            </div>
            <div class="payment-stream" id="paymentLiveStream">
                @forelse($livePayments as $payment)
                    <div class="payment-stream-item">
                        <span class="payment-stream-dot payment-stream-dot--{{ $payment['status'] }}"></span>
                        <div class="payment-stream-main">
                            <div class="payment-stream-phone">{{ $payment['phone'] }}</div>
                            <div class="payment-stream-ref">{{ $payment['provider'] }} · {{ $payment['reference'] }}</div>
                        </div>
                        <div class="payment-stream-side">
                            <div class="payment-stream-amount">{{ number_format($payment['amount'], 0, ',', ' ') }} FCFA</div>
                            <div class="payment-stream-time">{{ $payment['updated_at_human'] }}</div>
                        </div>
                    </div>
                @empty
                    <div style="color:#94a3b8;font-size:.83rem;padding:12px 0;">Aucune transaction récente.</div>
                @endforelse
            </div>
        </article>

        <article class="payment-card">
            <div class="payment-card-header">
                <div>
                    <div class="payment-card-title">Répartition opérateurs</div>
                    <div class="payment-card-subtitle">Volume et performance du jour</div>
                </div>
            </div>
            <div class="payment-provider-list" id="paymentProviderList">
                @forelse($providerBreakdown as $provider)
                    <div class="payment-provider-row">
                        <div class="payment-provider-top">
                            <div>
                                <div class="payment-provider-name">{{ $provider['provider'] }}</div>
                                <div class="payment-provider-meta">{{ $provider['count'] }} transaction(s) · succès {{ number_format($provider['success_rate'], 1, ',', ' ') }}%</div>
                            </div>
                            <strong>{{ number_format($provider['amount'], 0, ',', ' ') }} FCFA</strong>
                        </div>
                        <div class="payment-progress"><span style="width: {{ max(6, $provider['share_percent']) }}%"></span></div>
                    </div>
                @empty
                    <div style="color:#94a3b8;font-size:.83rem;padding:12px 0;">Aucune donnée opérateur.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="payment-card">
        <div class="payment-card-header">
            <div>
                    <div class="payment-card-title">Journal des transactions</div>
                    <div class="payment-card-subtitle">Dernières lignes paiements avec statut normalisé et action de vérification</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <a href="{{ route('admin.payments.export-csv', array_filter(['provider' => request('provider'), 'status' => request('status')])) }}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border:1px solid #16a34a;border-radius:7px;color:#16a34a;font-size:.78rem;font-weight:700;text-decoration:none;"
                   title="Export CSV pour comptabilité">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
                <a href="{{ route('admin.payments.export-csv', ['provider' => 'momo', 'status' => 'paid']) }}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border:1px solid #16a34a;border-radius:7px;background:#16a34a;color:#fff;font-size:.78rem;font-weight:700;text-decoration:none;"
                   title="Export paiements MoMo confirmés">
                    <i class="fas fa-download"></i> MoMo payés
                </a>
            </div>
        </div>
        <div class="payment-table-wrap">
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Téléphone</th>
                        <th>Montant</th>
                        <th>Opérateur</th>
                        <th>Statut</th>
                        <th>Référence</th>
                        <th>Dernière activité</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="paymentTableBody">
                    @forelse($tablePayments as $payment)
                        <tr>
                            <td><strong>{{ $payment['id'] }}</strong></td>
                            <td>
                                <strong>{{ $payment['phone'] }}</strong>
                                @if($payment['reason'])
                                    <small>{{ $payment['reason'] ?: 'Aucun motif detaille' }}</small>
                                @endif
                            </td>
                            <td>{{ number_format($payment['amount'], 0, ',', ' ') }} FCFA</td>
                            <td>{{ $payment['provider'] }}</td>
                            <td><span class="status-badge status-badge--{{ $payment['status'] }}">{{ $payment['status_label'] }}</span></td>
                            <td>{{ $payment['reference'] ?: 'N/A' }}</td>
                            <td>{{ $payment['updated_at_human'] }}</td>
                            <td><button type="button" class="payment-action-btn" data-payment-id="{{ (int) str_replace('TX', '', $payment['id']) }}">Vérifier</button></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="payment-muted-row">Aucune transaction à afficher.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@section('script')
<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.font.family = "'Manrope', sans-serif";
Chart.defaults.font.size = 11;

const dashboardState = {
    hours: @json($hours),
    filters: @json($filters),
    endpoints: {
        data: @json(route('admin.payments.dashboard.data')),
        reconcileBase: @json(url('/admin/payments')),
    },
    csrfToken: @json(csrf_token()),
};

const compactFormatter = new Intl.NumberFormat('fr-FR', { notation: 'compact', compactDisplay: 'short', minimumFractionDigits: 0, maximumFractionDigits: 0 });
const numberFormatter = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
let paymentsChart = new Chart(document.getElementById('paymentsLineChart'), {
    type: 'line',
    data: {
        labels: @json($hourlySeries['labels']),
        datasets: [
            {
                label: 'Montant',
                data: @json($hourlySeries['amounts']),
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.16)',
                fill: true,
                borderWidth: 3,
                pointRadius: 0,
                pointHoverRadius: 4,
                tension: 0.35,
                yAxisID: 'y'
            },
            {
                label: 'Transactions',
                data: @json($hourlySeries['counts']),
                borderColor: '#38bdf8',
                backgroundColor: 'rgba(56, 189, 248, 0.10)',
                fill: false,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 4,
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                labels: {
                    boxWidth: 10,
                    color: '#cbd5e1'
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.dataset.yAxisID === 'y') {
                            return context.dataset.label + ': ' + numberFormatter.format(Math.round(context.parsed.y || 0)) + ' FCFA';
                        }
                        return context.dataset.label + ': ' + numberFormatter.format(Math.round(context.parsed.y || 0));
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,.04)', drawBorder: false },
                ticks: { color: '#64748b' }
            },
            y: {
                position: 'left',
                grid: { color: 'rgba(255,255,255,.05)', drawBorder: false },
                ticks: {
                    color: '#64748b',
                    callback: function(value) {
                        return compactFormatter.format(Math.round(value || 0));
                    }
                }
            },
            y1: {
                position: 'right',
                grid: { drawOnChartArea: false, drawBorder: false },
                ticks: {
                    color: '#64748b',
                    precision: 0
                }
            }
        }
    }
});

function renderAlerts(alerts) {
    const host = document.getElementById('paymentAlertsList');
    host.innerHTML = alerts.map(alert => `
        <div class="payment-alert payment-alert--${alert.tone}">
            <div class="payment-alert-top">
                <span>${escapeHtml(alert.label)}</span>
                <span class="payment-alert-value">${escapeHtml(String(alert.value))}</span>
            </div>
            <div class="payment-alert-message">${escapeHtml(alert.message)}</div>
        </div>
    `).join('');
}

function renderStatusBreakdown(statusBreakdown) {
    document.querySelectorAll('[data-status-key]').forEach(node => {
        node.textContent = numberFormatter.format(statusBreakdown[node.dataset.statusKey] || 0);
    });
}

function renderLivePayments(payments) {
    const host = document.getElementById('paymentLiveStream');
    if (!payments.length) {
        host.innerHTML = '<div style="color:#94a3b8;font-size:.83rem;padding:12px 0;">Aucune transaction récente.</div>';
        return;
    }

    host.innerHTML = payments.map(payment => `
        <div class="payment-stream-item">
            <span class="payment-stream-dot payment-stream-dot--${escapeHtml(payment.status)}"></span>
            <div class="payment-stream-main">
                <div class="payment-stream-phone">${escapeHtml(payment.phone)}</div>
                <div class="payment-stream-ref">${escapeHtml(payment.provider)} · ${escapeHtml(payment.reference)}</div>
            </div>
            <div class="payment-stream-side">
                <div class="payment-stream-amount">${numberFormatter.format(Math.round(payment.amount || 0))} FCFA</div>
                <div class="payment-stream-time">${escapeHtml(payment.updated_at_human)}</div>
            </div>
        </div>
    `).join('');
}

function renderProviderBreakdown(providers) {
    const host = document.getElementById('paymentProviderList');
    if (!providers.length) {
        host.innerHTML = '<div style="color:#94a3b8;font-size:.83rem;padding:12px 0;">Aucune donnée opérateur.</div>';
        return;
    }

    host.innerHTML = providers.map(provider => `
        <div class="payment-provider-row">
            <div class="payment-provider-top">
                <div>
                    <div class="payment-provider-name">${escapeHtml(provider.provider)}</div>
                    <div class="payment-provider-meta">${numberFormatter.format(provider.count)} transaction(s) · succès ${numberFormatter.format(provider.success_rate)}%</div>
                </div>
                <strong>${numberFormatter.format(Math.round(provider.amount || 0))} FCFA</strong>
            </div>
            <div class="payment-progress"><span style="width:${Math.max(6, provider.share_percent)}%"></span></div>
        </div>
    `).join('');
}

function renderTable(payments) {
    const host = document.getElementById('paymentTableBody');
    if (!payments.length) {
        host.innerHTML = '<tr><td colspan="8" class="payment-muted-row">Aucune transaction à afficher.</td></tr>';
        return;
    }

    host.innerHTML = payments.map(payment => `
        <tr>
            <td><strong>${escapeHtml(payment.id)}</strong></td>
            <td>
                <strong>${escapeHtml(payment.phone)}</strong>
                ${payment.reason ? `<small>${escapeHtml(payment.reason)}</small>` : ''}
            </td>
            <td>${numberFormatter.format(Math.round(payment.amount || 0))} FCFA</td>
            <td>${escapeHtml(payment.provider)}</td>
            <td><span class="status-badge status-badge--${escapeHtml(payment.status)}">${escapeHtml(payment.status_label)}</span></td>
            <td>${escapeHtml(payment.reference)}</td>
            <td>${escapeHtml(payment.updated_at_human)}</td>
            <td><button type="button" class="payment-action-btn" data-payment-id="${String(payment.id).replace('TX', '')}">Vérifier</button></td>
        </tr>
    `).join('');
}

function renderKpis(kpis) {
    document.getElementById('kpiTurnover').textContent = numberFormatter.format(Math.round(kpis.turnover || 0));
    document.getElementById('kpiTransactions').textContent = numberFormatter.format(Math.round(kpis.transactions || 0));
    document.getElementById('kpiSuccessRate').textContent = `${numberFormatter.format(kpis.success_rate)}%`;
    document.getElementById('kpiPending').textContent = numberFormatter.format(Math.round(kpis.pending || 0));
}

function updateChart(hourlySeries) {
    paymentsChart.data.labels = hourlySeries.labels;
    paymentsChart.data.datasets[0].data = hourlySeries.amounts;
    paymentsChart.data.datasets[1].data = hourlySeries.counts;
    paymentsChart.update();
}

function updateHistoryState() {
    const url = new URL(window.location.href);
    url.searchParams.set('hours', dashboardState.hours);
    url.searchParams.set('provider', dashboardState.filters.provider);
    url.searchParams.set('status', dashboardState.filters.status);
    window.history.replaceState({}, '', url.toString());

    document.querySelectorAll('#hoursFilterGroup [data-hours]').forEach(link => {
        link.classList.toggle('active', Number(link.dataset.hours) === Number(dashboardState.hours));
        const nextUrl = new URL(link.href);
        nextUrl.searchParams.set('provider', dashboardState.filters.provider);
        nextUrl.searchParams.set('status', dashboardState.filters.status);
        link.href = nextUrl.toString();
    });
}

function renderDashboard(payload) {
    renderKpis(payload.kpis);
    renderAlerts(payload.alerts);
    renderStatusBreakdown(payload.statusBreakdown);
    renderLivePayments(payload.livePayments);
    renderProviderBreakdown(payload.providerBreakdown);
    renderTable(payload.tablePayments);
    updateChart(payload.hourlySeries);
    document.getElementById('generatedAtLabel').textContent = `Dernière génération: ${new Date(payload.generatedAt.date || payload.generatedAt).toLocaleString('fr-FR')}`;
}

async function fetchDashboardData(showBusy = false) {
    if (showBusy) {
        document.getElementById('refreshDashboardBtn').textContent = 'Chargement…';
    }

    const url = new URL(dashboardState.endpoints.data, window.location.origin);
    url.searchParams.set('hours', dashboardState.hours);
    url.searchParams.set('provider', dashboardState.filters.provider);
    url.searchParams.set('status', dashboardState.filters.status);

    try {
        const response = await fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        });

        const payload = await response.json();
        if (!payload.status) {
            throw new Error('Réponse dashboard invalide');
        }

        renderDashboard(payload.data);
        updateHistoryState();
    } catch (error) {
        console.error('Dashboard payments refresh failed', error);
    } finally {
        if (showBusy) {
            document.getElementById('refreshDashboardBtn').textContent = 'Rafraîchir';
        }
    }
}

async function reconcilePayment(paymentId, button) {
    const original = button.textContent;
    button.disabled = true;
    button.textContent = 'Vérification…';

    try {
        const response = await fetch(`${dashboardState.endpoints.reconcileBase}/${paymentId}/reconcile`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': dashboardState.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        });

        const payload = await response.json();
        if (!payload.status) {
            throw new Error(payload.message || 'Réconciliation impossible');
        }

        await fetchDashboardData(true);
    } catch (error) {
        console.error('Payment reconcile failed', error);
        button.textContent = 'Échec';
        setTimeout(() => {
            button.disabled = false;
            button.textContent = original;
        }, 1600);
        return;
    }

    button.disabled = false;
    button.textContent = original;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.getElementById('providerFilter').addEventListener('change', (event) => {
    dashboardState.filters.provider = event.target.value;
    fetchDashboardData(true);
});

document.getElementById('statusFilter').addEventListener('change', (event) => {
    dashboardState.filters.status = event.target.value;
    fetchDashboardData(true);
});

document.getElementById('refreshDashboardBtn').addEventListener('click', () => {
    fetchDashboardData(true);
});

document.querySelectorAll('#hoursFilterGroup [data-hours]').forEach(link => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        dashboardState.hours = Number(link.dataset.hours);
        fetchDashboardData(true);
    });
});

document.getElementById('paymentTableBody').addEventListener('click', (event) => {
    const button = event.target.closest('[data-payment-id]');
    if (!button) {
        return;
    }

    reconcilePayment(button.dataset.paymentId, button);
});

setInterval(() => fetchDashboardData(false), 30000);
</script>
@endsection
