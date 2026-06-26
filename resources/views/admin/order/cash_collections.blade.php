@extends('layouts.admin-modern')
@section('title', 'Encaissements cash')
@section('page_title', 'Encaissements cash')
@section('nav_active', 'orders')

@section('style')
<style>
.cash-wrap{display:flex;flex-direction:column;gap:16px;padding:20px}.cash-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.cash-kpi,.cash-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px}.cash-kpi{padding:14px}.cash-kpi__label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#6b7280}.cash-kpi__value{font-size:23px;font-weight:800;color:#111827;margin-top:5px}.cash-card{overflow:hidden}.cash-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #eef0f3;flex-wrap:wrap}.cash-title{font-size:15px;font-weight:800;color:#111827;margin:0}.cash-muted{font-size:12px;color:#6b7280}.cash-filters{display:grid;grid-template-columns:1.2fr 1fr 1fr 1fr 1fr auto;gap:9px;padding:14px 18px;background:#f9fafb}.cash-filters input,.cash-filters select{width:100%;border:1px solid #d1d5db;border-radius:7px;padding:8px 10px;font-size:12px;background:#fff}.cash-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:7px;padding:8px 12px;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;white-space:nowrap}.cash-btn--primary{border:1px solid #1e3a5f;background:#1e3a5f;color:#fff}.cash-btn--light{border:1px solid #d1d5db;background:#fff;color:#374151}.cash-table-wrap{overflow-x:auto}.cash-table{width:100%;border-collapse:collapse;font-size:12px}.cash-table th{padding:9px 12px;background:#f9fafb;color:#6b7280;text-transform:uppercase;font-size:10px;letter-spacing:.05em;text-align:left;white-space:nowrap}.cash-table td{padding:11px 12px;border-top:1px solid #f1f3f5;color:#374151;vertical-align:middle}.cash-pill{display:inline-flex;padding:3px 8px;border-radius:999px;font-weight:700;font-size:10px}.cash-pill--pending{background:#fff7ed;color:#9a3412}.cash-pill--collected{background:#f0fdf4;color:#166534}.cash-pill--failed{background:#fef2f2;color:#991b1b}.cash-pill--disputed{background:#fefce8;color:#854d0e}.cash-resolve{display:flex;align-items:center;gap:5px}.cash-resolve select{border:1px solid #d1d5db;border-radius:6px;padding:5px;font-size:11px}.cash-resolve button{border:1px solid #1e3a5f;background:#fff;color:#1e3a5f;border-radius:6px;padding:5px 8px;font-size:11px;font-weight:700}.cash-alert{padding:11px 14px;border-radius:8px;font-size:13px}.cash-alert--success{background:#f0fdf4;color:#166534}.cash-alert--danger{background:#fef2f2;color:#991b1b}.cash-pagination{padding:14px 18px;border-top:1px solid #f1f3f5}@media(max-width:1100px){.cash-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.cash-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:640px){.cash-grid,.cash-filters{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="cash-wrap">
    @if(session()->has('alert'))
        <div class="cash-alert cash-alert--{{ session()->get('alert.type') }}">{{ session()->get('alert.message') }}</div>
    @endif

    <div class="cash-grid">
        <div class="cash-kpi"><div class="cash-kpi__label">En attente</div><div class="cash-kpi__value">{{ (int) ($summary['pending_collection'] ?? 0) }}</div></div>
        <div class="cash-kpi"><div class="cash-kpi__label">Collectées</div><div class="cash-kpi__value">{{ (int) ($summary['collected'] ?? 0) }}</div></div>
        <div class="cash-kpi"><div class="cash-kpi__label">Échecs</div><div class="cash-kpi__value">{{ (int) ($summary['collection_failed'] ?? 0) }}</div></div>
        <div class="cash-kpi"><div class="cash-kpi__label">Litiges</div><div class="cash-kpi__value">{{ (int) ($summary['disputed'] ?? 0) }}</div></div>
        <div class="cash-kpi"><div class="cash-kpi__label">Montant filtré</div><div class="cash-kpi__value">{{ number_format($totalAmount, 0, ',', ' ') }} <small style="font-size:11px">FCFA</small></div></div>
    </div>

    <div class="cash-card">
        <div class="cash-head">
            <div><p class="cash-title">Suivi des encaissements en espèces</p><span class="cash-muted">Une ligne par commande, sans duplication des produits.</span></div>
            <a class="cash-btn cash-btn--light" href="{{ route('admin.cash_collections.export', request()->query()) }}">Exporter CSV</a>
        </div>

        <form method="GET" action="{{ route('admin.cash_collections.index') }}" class="cash-filters">
            <input type="search" name="order_no" value="{{ request('order_no') }}" placeholder="N° commande">
            <select name="status">
                <option value="">Tous les statuts</option>
                <option value="pending_collection" @selected(request('status') === 'pending_collection')>En attente</option>
                <option value="collected" @selected(request('status') === 'collected')>Collectée</option>
                <option value="collection_failed" @selected(request('status') === 'collection_failed')>Échec</option>
                <option value="disputed" @selected(request('status') === 'disputed')>Litige</option>
            </select>
            <select name="restaurant_id">
                <option value="">Tous les restaurants</option>
                @foreach($restaurants as $restaurant)
                    <option value="{{ $restaurant->id }}" @selected((string) request('restaurant_id') === (string) $restaurant->id)>{{ $restaurant->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Date de début">
            <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Date de fin">
            <button class="cash-btn cash-btn--primary" type="submit">Filtrer</button>
        </form>

        <div class="cash-table-wrap">
            <table class="cash-table">
                <thead><tr><th>Commande</th><th>Date</th><th>Restaurant</th><th>Livreur</th><th>Montant</th><th>Statut</th><th>Collecte</th><th>Référence</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    @php
                        $pill = match($order->cash_collection_status) {
                            'collected' => 'collected',
                            'collection_failed' => 'failed',
                            'disputed' => 'disputed',
                            default => 'pending',
                        };
                        $label = match($order->cash_collection_status) {
                            'collected' => 'Collectée',
                            'collection_failed' => 'Échec collecte',
                            'disputed' => 'Litige',
                            default => 'En attente',
                        };
                    @endphp
                    <tr>
                        <td><a href="{{ route('admin.show_order', $order->order_no) }}">{{ $order->order_no }}</a></td>
                        <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $order->restaurant->name ?? '—' }}</td>
                        <td>{{ $order->driver->name ?? 'Non affecté' }}</td>
                        <td>{{ number_format((float) ($order->total ?? 0), 0, ',', ' ') }} FCFA</td>
                        <td><span class="cash-pill cash-pill--{{ $pill }}">{{ $label }}</span></td>
                        <td>
                            @if($order->cash_collected_at)
                                {{ $order->cash_collected_at->format('d/m/Y H:i') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $order->cash_collection_reference ?: '—' }}</td>
                        <td>
                            @if(in_array($order->cash_collection_status, ['disputed', 'collection_failed'], true))
                                <form method="POST" action="{{ route('admin.cash_disputes.resolve', $order->order_no) }}" class="cash-resolve" onsubmit="return confirm('Confirmer cette résolution ?');">
                                    @csrf
                                    <select name="resolution" required>
                                        <option value="confirmed_collected">Espèces reçues</option>
                                        <option value="confirmed_not_collected">Non reçues</option>
                                    </select>
                                    <button type="submit">Résoudre</button>
                                </form>
                            @else
                                <span class="cash-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center;padding:28px;color:#9ca3af">Aucun encaissement ne correspond aux filtres.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="cash-pagination">{{ $orders->links() }}</div>
    </div>
</div>
@endsection
