@extends('layouts.admin-modern')

@section('title', 'Metriques et observabilite')
@section('page_title', 'Métriques & Observabilité')
@section('nav_active', 'metrics')

@section('style')
<style>
.mtr-page { padding:24px; display:grid; gap:20px; }
.mtr-alert { padding:12px 16px; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; font-size:13px; color:#92400e; }
.mtr-alert h5 { margin:0 0 8px; font-size:13px; font-weight:700; }
.mtr-alert ul { margin:0; padding-left:18px; }
.mtr-pill { display:inline-flex; align-items:center; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:700; margin-left:6px; }
.mtr-pill--danger  { background:#fee2e2; color:#991b1b; }
.mtr-pill--warning { background:#fef3c7; color:#92400e; }
.mtr-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
.mtr-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px; }
.mtr-kpi h6 { margin:0 0 8px; font-size:12px; font-weight:600; color:#9ca3af; }
.mtr-kpi h2 { margin:0 0 4px; font-size:1.5rem; font-weight:900; color:#111827; }
.mtr-kpi small { font-size:12px; color:#9ca3af; }
.mtr-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.mtr-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.mtr-card__header { padding:12px 18px; border-bottom:1px solid #f3f4f6; }
.mtr-card__title { font-size:13px; font-weight:700; color:#111827; margin:0; }
.mtr-card__body { padding:18px; }
.mtr-table { width:100%; border-collapse:collapse; font-size:13px; }
.mtr-table tr td { padding:8px 0; color:#374151; border-bottom:1px solid #f3f4f6; }
.mtr-table tr:last-child td { border-bottom:none; }
.mtr-table tr td:last-child { text-align:right; }
.mtr-code { background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:12px; font-size:13px; font-family:monospace; margin-bottom:8px; }
.mtr-code:last-child { margin-bottom:0; }
@media (max-width:900px) { .mtr-kpi-grid { grid-template-columns:repeat(2,1fr); } .mtr-row-2 { grid-template-columns:1fr; } }
@media (max-width:576px) { .mtr-kpi-grid { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="mtr-page">
    @include('admin.partials.control_hub_nav')

    @if(!empty($realtimeMetrics['alerts']))
    <div class="mtr-alert">
        <h5><i class="fas fa-exclamation-triangle"></i> Alertes actives</h5>
        <ul>
            @foreach($realtimeMetrics['alerts'] as $alert)
            <li>
                <strong>{{ ucfirst($alert['type']) }}:</strong> {{ $alert['message'] }}
                @if(isset($alert['severity']))
                    <span class="mtr-pill {{ $alert['severity'] === 'high' ? 'mtr-pill--danger' : 'mtr-pill--warning' }}">{{ $alert['severity'] }}</span>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="mtr-kpi-grid">
        <div class="mtr-kpi">
            <h6>Commandes Aujourd'hui</h6>
            <h2>{{ $realtimeMetrics['orders']['today'] ?? 0 }}</h2>
            <small>Hier: {{ $realtimeMetrics['orders']['yesterday'] ?? 0 }}</small>
        </div>
        <div class="mtr-kpi">
            <h6>Revenus Aujourd'hui</h6>
            <h2>{{ number_format($realtimeMetrics['revenue']['today'] ?? 0, 0, ',', ' ') }} FCFA</h2>
            <small>Hier: {{ number_format($realtimeMetrics['revenue']['yesterday'] ?? 0, 0, ',', ' ') }} FCFA</small>
        </div>
        <div class="mtr-kpi">
            <h6>Temps Moyen Livraison</h6>
            <h2>{{ $realtimeMetrics['deliveries']['avg_delivery_time'] ? number_format($realtimeMetrics['deliveries']['avg_delivery_time'], 0) . ' min' : 'N/A' }}</h2>
            <small>Livraisons complétées: {{ $realtimeMetrics['deliveries']['completed_today'] ?? 0 }}</small>
        </div>
        <div class="mtr-kpi">
            <h6>Taux Succès Paiement</h6>
            <h2>{{ $realtimeMetrics['payments']['success_rate'] ? number_format($realtimeMetrics['payments']['success_rate'], 1) . '%' : 'N/A' }}</h2>
            <small>Paiements aujourd'hui: {{ $realtimeMetrics['payments']['paid_today'] ?? 0 }}</small>
        </div>
    </div>

    <div class="mtr-row-2">
        <div class="mtr-card">
            <div class="mtr-card__header"><h3 class="mtr-card__title">Commandes</h3></div>
            <div class="mtr-card__body">
                <table class="mtr-table">
                    <tr><td>En attente</td><td><strong>{{ $realtimeMetrics['orders']['pending'] ?? 0 }}</strong></td></tr>
                    <tr><td>En cours</td><td><strong>{{ $realtimeMetrics['orders']['in_progress'] ?? 0 }}</strong></td></tr>
                    <tr><td>Complétées aujourd'hui</td><td><strong>{{ $realtimeMetrics['orders']['completed_today'] ?? 0 }}</strong></td></tr>
                    <tr><td>7 derniers jours</td><td><strong>{{ $realtimeMetrics['orders']['last_7_days'] ?? 0 }}</strong></td></tr>
                    <tr><td>30 derniers jours</td><td><strong>{{ $realtimeMetrics['orders']['last_30_days'] ?? 0 }}</strong></td></tr>
                </table>
            </div>
        </div>
        <div class="mtr-card">
            <div class="mtr-card__header"><h3 class="mtr-card__title">Livraisons</h3></div>
            <div class="mtr-card__body">
                <table class="mtr-table">
                    <tr><td>En attente d'assignation</td><td><strong>{{ $realtimeMetrics['deliveries']['pending'] ?? 0 }}</strong></td></tr>
                    <tr><td>Assignées</td><td><strong>{{ $realtimeMetrics['deliveries']['assigned'] ?? 0 }}</strong></td></tr>
                    <tr><td>En cours</td><td><strong>{{ $realtimeMetrics['deliveries']['in_progress'] ?? 0 }}</strong></td></tr>
                    <tr><td>Complétées aujourd'hui</td><td><strong>{{ $realtimeMetrics['deliveries']['completed_today'] ?? 0 }}</strong></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="mtr-card">
        <div class="mtr-card__header"><h3 class="mtr-card__title">Revenus</h3></div>
        <div class="mtr-card__body">
            <table class="mtr-table">
                <tr><td>Aujourd'hui</td><td><strong>{{ number_format($realtimeMetrics['revenue']['today'] ?? 0, 0, ',', ' ') }} FCFA</strong></td></tr>
                <tr><td>Hier</td><td>{{ number_format($realtimeMetrics['revenue']['yesterday'] ?? 0, 0, ',', ' ') }} FCFA</td></tr>
                <tr><td>7 derniers jours</td><td><strong>{{ number_format($realtimeMetrics['revenue']['last_7_days'] ?? 0, 0, ',', ' ') }} FCFA</strong></td></tr>
                <tr><td>30 derniers jours</td><td><strong>{{ number_format($realtimeMetrics['revenue']['last_30_days'] ?? 0, 0, ',', ' ') }} FCFA</strong></td></tr>
            </table>
        </div>
    </div>

    <div class="mtr-row-2">
        <div class="mtr-card">
            <div class="mtr-card__header"><h3 class="mtr-card__title">Utilisateurs</h3></div>
            <div class="mtr-card__body">
                <table class="mtr-table">
                    <tr><td>Total</td><td><strong>{{ $realtimeMetrics['users']['total'] ?? 0 }}</strong></td></tr>
                    <tr><td>Nouveaux aujourd'hui</td><td><strong>{{ $realtimeMetrics['users']['new_today'] ?? 0 }}</strong></td></tr>
                    <tr><td>Actifs (30j)</td><td><strong>{{ $realtimeMetrics['users']['active_last_30_days'] ?? 0 }}</strong></td></tr>
                </table>
            </div>
        </div>
        <div class="mtr-card">
            <div class="mtr-card__header"><h3 class="mtr-card__title">Restaurants</h3></div>
            <div class="mtr-card__body">
                <table class="mtr-table">
                    <tr><td>Total</td><td><strong>{{ $realtimeMetrics['restaurants']['total'] ?? 0 }}</strong></td></tr>
                    <tr><td>Actifs aujourd'hui</td><td><strong>{{ $realtimeMetrics['restaurants']['active_today'] ?? 0 }}</strong></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="mtr-card">
        <div class="mtr-card__header"><h3 class="mtr-card__title">API Endpoints</h3></div>
        <div class="mtr-card__body">
            <div class="mtr-code"><code>GET {{ route('admin.metrics.realtime') }}</code> — Métriques temps réel (JSON, session admin)</div>
            <div class="mtr-code"><code>GET {{ route('admin.metrics.historical', ['days' => 30]) }}</code> — Métriques historiques (JSON, session admin)</div>
        </div>
    </div>
</div>

@section('script')
<script>
setInterval(function() {
    fetch(@json(route('admin.metrics.realtime')), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) console.log('Métriques mises à jour', data.data);
    })
    .catch(err => console.error('Erreur refresh métriques:', err));
}, 60000);
</script>
@endsection
@endsection
