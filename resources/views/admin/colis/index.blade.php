@extends('layouts.admin-modern')
@section('title', 'Gestion des Colis | Mema')
@section('page_title', 'Colis')
@section('nav_active', 'colis')

@section('content')
<div style="padding:24px;">
    <div class="bd-ops-shell">
        <div class="adm-page-bar">
            <div class="adm-page-bar__left">
                <nav class="adm-page-bar__breadcrumb">
                    <span>Mema</span><span class="sep">/</span><span>Expeditions</span>
                </nav>
                <h1 class="adm-page-bar__title">Colis &amp; Expeditions</h1>
            </div>
            <div class="adm-page-bar__right">
                <a href="{{ route('admin.colis.finance') }}" class="ops-primary-btn">Finance</a>
            </div>
        </div>

        <section class="bd-ops-stat-grid">
            <article class="bd-ops-stat-card is-orange"><span>Total envois</span><strong>{{ $stats['total'] }}</strong><small>Volume cumule</small></article>
            <article class="bd-ops-stat-card is-lemon"><span>En attente</span><strong>{{ $stats['pending'] }}</strong><small>A lancer ou assigner</small></article>
            <article class="bd-ops-stat-card is-dark"><span>En transit</span><strong>{{ $stats['in_transit'] }}</strong><small>Flux actif</small></article>
            <article class="bd-ops-stat-card is-soft"><span>Livres aujourd'hui</span><strong>{{ $stats['delivered_today'] }}</strong><small>Clotures du jour</small></article>
        </section>
    </div>

    {{-- Filters --}}
    <div class="bd-ops-filter-card" style="margin-top:20px;padding:16px 20px;">
        <form action="{{ route('admin.colis.index') }}" method="GET">
            <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                <input type="text" name="search" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;min-width:200px;" placeholder="N° de suivi ou nom du client" value="{{ request('search') }}">
                <select name="status" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">
                    <option value="">Tous les statuts</option>
                    @foreach(\App\Domain\Colis\Enums\ShipmentStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <select name="courier_id" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">
                    <option value="">Tous les coursiers</option>
                    @foreach($couriers as $courier)
                        <option value="{{ $courier->id }}" {{ request('courier_id') == $courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;" value="{{ request('date_from') }}" title="Date de début">
                <button type="submit" class="btn bd-ops-primary-btn"><i class="fas fa-search mr-1"></i>Filtrer</button>
                <a href="{{ route('admin.colis.export-csv', request()->query()) }}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;background:#16a34a;color:#fff;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;"><i class="fas fa-file-csv"></i> Export CSV</a>
                <a href="{{ route('admin.colis.index') }}" style="display:inline-flex;align-items:center;padding:8px 14px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;color:#374151;background:#fff;text-decoration:none;">Réinitialiser</a>
            </div>
        </form>
    </div>

    <div class="bd-ops-table-card" style="margin-top:16px;">
        <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
            <div class="bd-ops-table-card__header">
                <div>
                    <h3>Liste des expéditions</h3>
                    <p>Tracking, client, coursier, poids, montant et statut réunis dans une table orientée action.</p>
                </div>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;white-space:nowrap;">
                <thead>
                    <tr>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">N° de suivi</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Client</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Coursier</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Poids</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Prix total</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Statut</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Date</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shipments as $shipment)
                    <tr>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;"><code>{{ $shipment->tracking_number }}</code></td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $shipment->customer->name ?? 'N/A' }}</td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                            @if($shipment->courier)
                                <span class="bd-ops-status is-soft">{{ $shipment->courier->name }}</span>
                            @else
                                <span class="bd-ops-status is-danger">Non assigne</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $shipment->weight_kg }} kg</td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ number_format($shipment->total_price, 0, ',', ' ') }} FCFA</td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                            <span class="bd-ops-status {{ $shipment->status->value == 'delivered' ? 'is-success' : ($shipment->status->value == 'canceled' ? 'is-danger' : ($shipment->status->value == 'pending' ? 'is-lemon' : 'is-orange')) }}">
                                {{ $shipment->status->label() }}
                            </span>
                        </td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $shipment->created_at->format('d/m/Y H:i') }}</td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;text-align:right;">
                            <a href="{{ route('admin.colis.show', $shipment->id) }}" style="display:inline-flex;align-items:center;padding:4px 10px;border:1px solid #1e3a5f;color:#1e3a5f;border-radius:5px;font-size:12px;font-weight:600;text-decoration:none;">Voir</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center;color:#9ca3af;padding:32px;">Aucun envoi ne correspond aux filtres actuels.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #f3f4f6;">
            {{ $shipments->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<style>
    .bd-ops-shell { display:grid; gap:20px; }
    .bd-ops-hero { display:none; }
    .bd-ops-stat-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
    .bd-ops-stat-card { padding:18px; border-radius:20px; background:rgba(255,255,255,.92); border:1px solid rgba(15,23,42,.08); box-shadow:0 14px 28px rgba(15,23,42,.05); }
    .bd-ops-stat-card span { display:block; color:#64748b; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .bd-ops-stat-card strong { display:block; margin-top:10px; font-size:1.82rem; line-height:1; font-weight:900; color:#111827; }
    .bd-ops-stat-card small { display:block; margin-top:8px; color:#94a3b8; }
    .bd-ops-stat-card.is-orange strong { color:#c2410c; }
    .bd-ops-stat-card.is-lemon strong { color:#65a30d; }
    .bd-ops-stat-card.is-soft strong { color:#b45309; }
    .bd-ops-filter-card,
    .bd-ops-table-card { border:1px solid rgba(15,23,42,.08) !important; border-radius:20px !important; box-shadow:0 14px 32px rgba(15,23,42,.05) !important; overflow:hidden; background:rgba(255,255,255,.88) !important; }
    .bd-ops-primary-btn {
        border:0; min-height:44px; border-radius:14px; color:#fff;
        background:linear-gradient(135deg, #7c4a1b 0%, #b45309 58%, #f59e0b 100%);
        box-shadow:0 12px 24px rgba(180,83,9,.2);
    }
    .bd-ops-table-card__header { display:flex; align-items:flex-end; justify-content:space-between; gap:18px; }
    .bd-ops-table-card__header h3 { margin:0; color:#111827; font-size:1.12rem; font-weight:900; }
    .bd-ops-table-card__header p { margin:8px 0 0; color:#64748b; line-height:1.62; }
    .bd-ops-status { display:inline-flex; align-items:center; min-height:32px; padding:0 12px; border-radius:999px; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
    .bd-ops-status.is-orange { background:#ffedd5; color:#c2410c; }
    .bd-ops-status.is-lemon { background:#fef9c3; color:#4d7c0f; }
    .bd-ops-status.is-success { background:#dcfce7; color:#007836; }
    .bd-ops-status.is-danger { background:#fee2e2; color:#b91c1c; }
    .bd-ops-status.is-soft { background:#f3f4f6; color:#374151; }
    @media (max-width: 992px) {
        .bd-ops-hero,
        .bd-ops-table-card__header { flex-direction:column; align-items:flex-start; }
        .bd-ops-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width: 576px) {
        .bd-ops-stat-grid { grid-template-columns:1fr; }
        .bd-ops-hero { padding:24px; }
    }
</style>
@endsection
