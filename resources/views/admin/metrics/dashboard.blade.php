@extends('layouts.app')

@section('title', 'Métriques & Observabilité')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">📊 Métriques & Observabilité</h1>
            <p class="text-muted">KPIs temps réel et historique</p>
        </div>
    </div>

    <!-- Alertes -->
    @if(!empty($realtimeMetrics['alerts']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> Alertes actives</h5>
                <ul class="mb-0">
                    @foreach($realtimeMetrics['alerts'] as $alert)
                    <li>
                        <strong>{{ ucfirst($alert['type']) }}:</strong> {{ $alert['message'] }}
                        @if(isset($alert['severity']))
                            <span class="badge badge-{{ $alert['severity'] === 'high' ? 'danger' : 'warning' }}">{{ $alert['severity'] }}</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- KPIs Principaux -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Commandes Aujourd'hui</h6>
                    <h2 class="mb-0">{{ $realtimeMetrics['orders']['today'] ?? 0 }}</h2>
                    <small class="text-muted">
                        Hier: {{ $realtimeMetrics['orders']['yesterday'] ?? 0 }}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Revenus Aujourd'hui</h6>
                    <h2 class="mb-0">{{ number_format($realtimeMetrics['revenue']['today'] ?? 0, 0, ',', ' ') }} FCFA</h2>
                    <small class="text-muted">
                        Hier: {{ number_format($realtimeMetrics['revenue']['yesterday'] ?? 0, 0, ',', ' ') }} FCFA
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Temps Moyen Livraison</h6>
                    <h2 class="mb-0">
                        {{ $realtimeMetrics['deliveries']['avg_delivery_time'] ? number_format($realtimeMetrics['deliveries']['avg_delivery_time'], 0) . ' min' : 'N/A' }}
                    </h2>
                    <small class="text-muted">
                        Livraisons complétées: {{ $realtimeMetrics['deliveries']['completed_today'] ?? 0 }}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Taux Succès Paiement</h6>
                    <h2 class="mb-0">
                        {{ $realtimeMetrics['payments']['success_rate'] ? number_format($realtimeMetrics['payments']['success_rate'], 1) . '%' : 'N/A' }}
                    </h2>
                    <small class="text-muted">
                        Paiements aujourd'hui: {{ $realtimeMetrics['payments']['paid_today'] ?? 0 }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Détails -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Commandes</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>En attente</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['orders']['pending'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>En cours</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['orders']['in_progress'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>Complétées aujourd'hui</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['orders']['completed_today'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>7 derniers jours</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['orders']['last_7_days'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>30 derniers jours</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['orders']['last_30_days'] ?? 0 }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Livraisons</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>En attente d'assignation</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['deliveries']['pending'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>Assignées</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['deliveries']['assigned'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>En cours</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['deliveries']['in_progress'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>Complétées aujourd'hui</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['deliveries']['completed_today'] ?? 0 }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenus -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Revenus</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <td>Aujourd'hui</td>
                            <td class="text-right"><strong>{{ number_format($realtimeMetrics['revenue']['today'] ?? 0, 0, ',', ' ') }} FCFA</strong></td>
                        </tr>
                        <tr>
                            <td>Hier</td>
                            <td class="text-right">{{ number_format($realtimeMetrics['revenue']['yesterday'] ?? 0, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        <tr>
                            <td>7 derniers jours</td>
                            <td class="text-right"><strong>{{ number_format($realtimeMetrics['revenue']['last_7_days'] ?? 0, 0, ',', ' ') }} FCFA</strong></td>
                        </tr>
                        <tr>
                            <td>30 derniers jours</td>
                            <td class="text-right"><strong>{{ number_format($realtimeMetrics['revenue']['last_30_days'] ?? 0, 0, ',', ' ') }} FCFA</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Utilisateurs & Restaurants -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Utilisateurs</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Total</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['users']['total'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>Nouveaux aujourd'hui</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['users']['new_today'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>Actifs (30j)</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['users']['active_last_30_days'] ?? 0 }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Restaurants</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Total</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['restaurants']['total'] ?? 0 }}</strong></td>
                        </tr>
                        <tr>
                            <td>Actifs aujourd'hui</td>
                            <td class="text-right"><strong>{{ $realtimeMetrics['restaurants']['active_today'] ?? 0 }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- API Endpoints -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>API Endpoints</h5>
                </div>
                <div class="card-body">
                    <p><code>GET /api/admin/metrics/realtime</code> - Métriques temps réel (JSON)</p>
                    <p><code>GET /api/admin/metrics/historical?days=30</code> - Métriques historiques (JSON)</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh toutes les 60 secondes
setInterval(function() {
    fetch('/api/admin/metrics/realtime', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            // Mettre à jour les KPIs (simplifié, à améliorer avec Vue.js/React si besoin)
            console.log('Métriques mises à jour', data.data);
        }
    })
    .catch(err => console.error('Erreur refresh métriques:', err));
}, 60000); // 60 secondes
</script>
@endsection

