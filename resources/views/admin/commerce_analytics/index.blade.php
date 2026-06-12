@extends('layouts.admin-modern')
@section('title', 'Analytics commerce | Admin')
@section('page_title', 'Analytique commerce')
@section('nav_active', 'commerce-analytics')
@section('style')
<style>
.ana-page { padding:24px; display:grid; gap:20px; }
.ana-header { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:18px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; }
.ana-header h3 { margin:0; font-size:1.1rem; font-weight:700; color:#111827; }
.ana-header small { font-size:12px; color:#9ca3af; display:block; margin-top:2px; }
.ana-pills { display:flex; flex-wrap:wrap; gap:8px; }
.ana-pill { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; }
.ana-pill--dark    { background:#1f2937; color:#f9fafb; }
.ana-pill--success { background:#d1fae5; color:#065f46; }
.ana-pill--warning { background:#fef3c7; color:#92400e; }
.ana-pill--danger  { background:#fee2e2; color:#991b1b; }
.ana-pill--info    { background:#dbeafe; color:#1e40af; }
.ana-kpi-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px; }
.ana-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px; }
.ana-kpi__label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; }
.ana-kpi__value { font-size:1.5rem; font-weight:900; color:#111827; margin-top:6px; line-height:1; }
.ana-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.ana-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.ana-card__header { padding:12px 18px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; gap:8px; }
.ana-card__title { font-size:13px; font-weight:700; color:#111827; margin:0; }
.ana-card__body { padding:18px; }
.ana-sla-row { display:flex; justify-content:space-between; align-items:center; padding:7px 0; font-size:13px; border-bottom:1px solid #f9fafb; }
.ana-sla-row:last-child { border-bottom:none; }
.ana-sla-row span { color:#374151; }
.ana-sla-row strong { color:#111827; }
.ana-int-row { display:flex; justify-content:space-between; padding:7px 0; font-size:13px; border-bottom:1px solid #f3f4f6; }
.ana-int-row:last-child { border-bottom:none; }
.ana-risk-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
.ana-risk-box { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
.ana-risk-box__label { font-size:10px; font-weight:800; text-transform:uppercase; color:#9ca3af; }
.ana-risk-box__value { font-size:1.4rem; font-weight:900; color:#111827; margin-top:4px; }
.ana-table-wrap { overflow-x:auto; }
.ana-table { width:100%; border-collapse:collapse; font-size:13px; }
.ana-table thead th { padding:8px 12px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; }
.ana-table tbody td { padding:8px 12px; color:#374151; border-bottom:1px solid #f3f4f6; }
.ana-table tbody tr:last-child td { border-bottom:none; }
.ana-btn-link { display:inline-flex; align-items:center; padding:5px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:12px; font-weight:600; color:#374151; text-decoration:none; background:#fff; }
.ana-btn-link:hover { background:#f9fafb; color:#111827; text-decoration:none; }
@media (max-width:900px) { .ana-row-2 { grid-template-columns:1fr; } .ana-risk-grid { grid-template-columns:repeat(2,1fr); } }
</style>
@endsection

@section('content')
<div class="ana-page">
    <div class="ana-header">
        <div>
            <h3>Analytics commerce</h3>
            <small>Fenêtre {{ $overview['window_days'] }} jours</small>
        </div>
        <div class="ana-pills">
            <span class="ana-pill ana-pill--dark">Commandes {{ $overview['orders']['count'] }}</span>
            <span class="ana-pill ana-pill--success">Payées {{ $overview['payments']['paid'] }}</span>
            <span class="ana-pill ana-pill--warning">Tickets {{ $overview['tickets']['open'] }}</span>
            <span class="ana-pill ana-pill--danger">Risque {{ $overview['risk']['high'] + $overview['risk']['critical'] }}</span>
        </div>
    </div>

    <div class="ana-kpi-grid">
        @foreach([
            'orders' => 'Commandes',
            'deliveries' => 'Livraisons',
            'payments' => 'Paiements',
            'tickets' => 'Tickets',
            'signals' => 'Signaux',
            'risk' => 'Risque',
            'ledger' => 'Ledger',
        ] as $key => $label)
        <div class="ana-kpi">
            <div class="ana-kpi__label">{{ $label }}</div>
            <div class="ana-kpi__value">{{ $overview[$key]['count'] ?? $overview[$key]['paid'] ?? 0 }}</div>
        </div>
        @endforeach
        <div class="ana-kpi">
            <div class="ana-kpi__label">Panier moyen</div>
            <div class="ana-kpi__value">{{ number_format((float) ($overview['orders']['average_value'] ?? 0), 0, ',', ' ') }}</div>
        </div>
        <div class="ana-kpi">
            <div class="ana-kpi__label">Clients uniques</div>
            <div class="ana-kpi__value">{{ (int) ($overview['orders']['unique_customers'] ?? 0) }}</div>
        </div>
    </div>

    <div class="ana-row-2">
        <div class="ana-card">
            <div class="ana-card__header"><h3 class="ana-card__title">SLA</h3></div>
            <div class="ana-card__body">
                <div class="ana-sla-row"><span>Acceptation restaurant</span><strong>{{ $overview['sla']['restaurant_accept_minutes'] }} min</strong></div>
                <div class="ana-sla-row"><span>Assignation livreur</span><strong>{{ $overview['sla']['delivery_assign_minutes'] }} min</strong></div>
                <div class="ana-sla-row"><span>Livraison complète</span><strong>{{ $overview['sla']['delivery_complete_minutes'] }} min</strong></div>
                <hr style="margin:12px 0;border:none;border-top:1px solid #f3f4f6;">
                <div class="ana-sla-row"><span>Commandes en retard d'acceptation</span><span class="ana-pill ana-pill--warning">{{ $overview['sla']['restaurant_accept_overdue'] }}</span></div>
                <div class="ana-sla-row"><span>Livraisons en attente de dispatch</span><span class="ana-pill ana-pill--warning">{{ $overview['sla']['delivery_assign_overdue'] }}</span></div>
                <div class="ana-sla-row"><span>Livraisons en retard</span><span class="ana-pill ana-pill--danger">{{ $overview['sla']['delivery_complete_overdue'] }}</span></div>
            </div>
        </div>
        <div class="ana-card">
            <div class="ana-card__header"><h3 class="ana-card__title">Intégrations</h3></div>
            <div class="ana-card__body">
                @foreach($integrationsReport as $name => $report)
                <div class="ana-int-row">
                    <span style="text-transform:capitalize;">{{ $name }}</span>
                    <span class="ana-pill {{ !empty($report['ok']) ? 'ana-pill--success' : 'ana-pill--warning' }}">{{ $report['message'] ?? ($report['ok'] ? 'ok' : 'degraded') }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="ana-row-2">
        <div class="ana-card">
            <div class="ana-card__header">
                <h3 class="ana-card__title">Risque & anti-fraude</h3>
                <span class="ana-pill ana-pill--danger">{{ $overview['risk']['count'] }} évaluations</span>
            </div>
            <div class="ana-card__body">
                <div class="ana-risk-grid">
                    @foreach(['low' => 'Faible', 'medium' => 'Moyen', 'high' => 'Élevé', 'critical' => 'Critique'] as $riskKey => $riskLabel)
                    <div class="ana-risk-box">
                        <div class="ana-risk-box__label">{{ $riskLabel }}</div>
                        <div class="ana-risk-box__value">{{ $overview['risk'][$riskKey] ?? 0 }}</div>
                    </div>
                    @endforeach
                </div>
                <div class="ana-table-wrap">
                    <table class="ana-table">
                        <thead><tr><th>Commande</th><th>Niveau</th><th>Score</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse($overview['risk']['recent'] as $risk)
                            <tr>
                                <td>{{ $risk->order_id ?? 'N/A' }}</td>
                                <td><span class="ana-pill {{ in_array($risk->level, ['critical', 'high'], true) ? 'ana-pill--danger' : 'ana-pill--warning' }}">{{ $risk->level }}</span></td>
                                <td>{{ number_format((float) $risk->score, 2, ',', ' ') }}</td>
                                <td>{{ $risk->action ?? 'monitor' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" style="color:#9ca3af;">Aucun signal de risque récent.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="ana-card">
            <div class="ana-card__header">
                <h3 class="ana-card__title">Tickets récents</h3>
                <a href="{{ route('admin.support-tickets.index') }}" class="ana-btn-link">Voir tout</a>
            </div>
            <div class="ana-card__body">
                <div class="ana-table-wrap">
                    <table class="ana-table">
                        <thead><tr><th>Module</th><th>Catégorie</th><th>Titre</th><th>Statut</th><th>Priorité</th></tr></thead>
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
                            <tr><td colspan="5" style="color:#9ca3af;">Aucun ticket récent.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="ana-row-2">
        <div class="ana-card">
            <div class="ana-card__header"><h3 class="ana-card__title">Top restaurants</h3></div>
            <div class="ana-card__body">
                <div class="ana-table-wrap">
                    <table class="ana-table">
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
        <div class="ana-card">
            <div class="ana-card__header"><h3 class="ana-card__title">Top produits</h3></div>
            <div class="ana-card__body">
                <div class="ana-table-wrap">
                    <table class="ana-table">
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

    <div class="ana-row-2">
        <div class="ana-card">
            <div class="ana-card__header"><h3 class="ana-card__title">Catégories support</h3></div>
            <div class="ana-card__body">
                <div class="ana-table-wrap">
                    <table class="ana-table">
                        <thead><tr><th>Catégorie</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach($overview['top_support_categories'] as $row)
                            <tr><td>{{ $row->category }}</td><td>{{ $row->total }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="ana-card">
            <div class="ana-card__header"><h3 class="ana-card__title">Signaux</h3></div>
            <div class="ana-card__body">
                <div class="ana-table-wrap">
                    <table class="ana-table">
                        <thead><tr><th>Type</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach($overview['top_signal_types'] as $row)
                            <tr><td>{{ $row->signal_type }}</td><td>{{ $row->total }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
