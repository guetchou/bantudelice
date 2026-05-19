@extends('layouts.admin-modern')
@section('title', 'Analytics commerce | Admin')
@section('page_title', 'Analytique commerce')
@section('nav_active', 'commerce-analytics')
@section('style')
<style>
.bd-analytics-card .card-body,
.bd-analytics-card .card-header {
    padding-left: 14px !important;
    padding-right: 14px !important;
}
.bd-analytics-kpi .card-body {
    padding: 12px 14px !important;
}
.bd-analytics-card .table td {
    vertical-align: top !important;
    line-height: 1.45;
}
</style>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4 bd-analytics-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="mb-1">Analytics commerce</h3>
                            <small class="text-muted">Fenêtre {{ $overview['window_days'] }} jours</small>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge badge-dark">Commandes {{ $overview['orders']['count'] }}</span>
                            <span class="badge badge-success">Payées {{ $overview['payments']['paid'] }}</span>
                            <span class="badge badge-warning">Tickets {{ $overview['tickets']['open'] }}</span>
                            <span class="badge badge-danger">Risque {{ $overview['risk']['high'] + $overview['risk']['critical'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                @foreach([
                    'orders' => 'Commandes',
                    'deliveries' => 'Livraisons',
                    'payments' => 'Paiements',
                    'tickets' => 'Tickets',
                    'signals' => 'Signaux',
                    'risk' => 'Risque',
                    'ledger' => 'Ledger',
                ] as $key => $label)
                    <div class="col-md-4 col-lg-2 mb-3">
                        <div class="card h-100 shadow-sm border-0 bd-analytics-card bd-analytics-kpi">
                            <div class="card-body">
                                <div class="text-muted text-uppercase small">{{ $label }}</div>
                                <div class="h4 mb-0">{{ $overview[$key]['count'] ?? $overview[$key]['paid'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="col-md-4 col-lg-2 mb-3">
                    <div class="card h-100 shadow-sm border-0 bd-analytics-card bd-analytics-kpi">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small">Panier moyen</div>
                            <div class="h4 mb-0">{{ number_format((float) ($overview['orders']['average_value'] ?? 0), 0, ',', ' ') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 mb-3">
                    <div class="card h-100 shadow-sm border-0 bd-analytics-card bd-analytics-kpi">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small">Clients uniques</div>
                            <div class="h4 mb-0">{{ (int) ($overview['orders']['unique_customers'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100 bd-analytics-card">
                        <div class="card-header bg-white"><strong>SLA</strong></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Acceptation restaurant</span>
                                <strong>{{ $overview['sla']['restaurant_accept_minutes'] }} min</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Assignation livreur</span>
                                <strong>{{ $overview['sla']['delivery_assign_minutes'] }} min</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Livraison complète</span>
                                <strong>{{ $overview['sla']['delivery_complete_minutes'] }} min</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Commandes en retard d'acceptation</span>
                                <span class="badge badge-warning">{{ $overview['sla']['restaurant_accept_overdue'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Livraisons en attente de dispatch</span>
                                <span class="badge badge-warning">{{ $overview['sla']['delivery_assign_overdue'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Livraisons en retard</span>
                                <span class="badge badge-danger">{{ $overview['sla']['delivery_complete_overdue'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100 bd-analytics-card">
                        <div class="card-header bg-white"><strong>Intégrations</strong></div>
                        <div class="card-body">
                            @foreach($integrationsReport as $name => $report)
                                <div class="d-flex justify-content-between border-bottom py-2">
                                    <span class="text-capitalize">{{ $name }}</span>
                                    <span class="badge badge-{{ !empty($report['ok']) ? 'success' : 'warning' }}">{{ $report['message'] ?? ($report['ok'] ? 'ok' : 'degraded') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100 bd-analytics-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <strong>Risque & anti-fraude</strong>
                            <span class="badge badge-danger">{{ $overview['risk']['count'] }} évaluations</span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach([
                                    'low' => 'Faible',
                                    'medium' => 'Moyen',
                                    'high' => 'Élevé',
                                    'critical' => 'Critique',
                                ] as $riskKey => $riskLabel)
                                    <div class="col-6 col-lg-3 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted text-uppercase small">{{ $riskLabel }}</div>
                                            <div class="h4 mb-0">{{ $overview['risk'][$riskKey] ?? 0 }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Commande</th>
                                            <th>Niveau</th>
                                            <th>Score</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($overview['risk']['recent'] as $risk)
                                            <tr>
                                                <td>{{ $risk->order_id ?? 'N/A' }}</td>
                                                <td><span class="badge badge-{{ in_array($risk->level, ['critical', 'high'], true) ? 'danger' : 'warning' }}">{{ $risk->level }}</span></td>
                                                <td>{{ number_format((float) $risk->score, 2, ',', ' ') }}</td>
                                                <td>{{ $risk->action ?? 'monitor' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-muted">Aucun signal de risque récent.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100 bd-analytics-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <strong>Tickets récents</strong>
                            <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-sm btn-outline-secondary">Voir tout</a>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Catégorie</th>
                                        <th>Titre</th>
                                        <th>Statut</th>
                                        <th>Priorité</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($overview['recent_support_tickets'] as $ticket)
                                        <tr>
                                            <td>{{ $ticket->module }}</td>
                                            <td>{{ $ticket->category }}</td>
                                            <td>{{ $ticket->title }}</td>
                                            <td>{{ $ticket->status }}</td>
                                            <td>{{ $ticket->priority }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted">Aucun ticket récent.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100 bd-analytics-card">
                        <div class="card-header bg-white"><strong>Top restaurants</strong></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Restaurant</th><th>Commandes</th><th>CA</th></tr></thead>
                                <tbody>
                                    @foreach($overview['top_restaurants'] as $row)
                                        <tr>
                                            <td>#{{ $row->restaurant_id }}</td>
                                            <td>{{ $row->total }}</td>
                                            <td>{{ number_format((float) ($row->gross_total ?? 0), 0, ',', ' ') }} FCFA</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100 bd-analytics-card">
                        <div class="card-header bg-white"><strong>Top produits</strong></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Produit</th><th>Unités</th></tr></thead>
                                <tbody>
                                    @foreach($overview['top_products'] as $row)
                                        <tr>
                                            <td>#{{ $row->product_id }}</td>
                                            <td>{{ $row->units }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100 bd-analytics-card">
                        <div class="card-header bg-white"><strong>Catégories support</strong></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Catégorie</th><th>Total</th></tr></thead>
                                <tbody>
                                    @foreach($overview['top_support_categories'] as $row)
                                        <tr>
                                            <td>{{ $row->category }}</td>
                                            <td>{{ $row->total }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white"><strong>Signaux</strong></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Type</th><th>Total</th></tr></thead>
                                <tbody>
                                    @foreach($overview['top_signal_types'] as $row)
                                        <tr>
                                            <td>{{ $row->signal_type }}</td>
                                            <td>{{ $row->total }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
