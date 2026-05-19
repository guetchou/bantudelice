@extends('layouts.admin-modern')
@section('title', 'Gestion des Colis | Mema')
@section('page_title', 'Colis')
@section('nav_active', 'colis')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="bd-ops-shell">
            <section class="bd-ops-hero">
                <div>
                    <p class="bd-ops-hero__eyebrow">Mema</p>
                    <h1>Expéditions, affectations et remises confirmées</h1>
                    <p>La vue d’entrée doit remonter les colis à lancer, les flux en transit, les coursiers non assignés et les points de friction logistiques.</p>
                </div>
                <div class="bd-ops-hero__actions">
                    <a href="{{ route('admin.colis.finance') }}" class="btn btn-light">Ouvrir la finance</a>
                </div>
            </section>

            <section class="bd-ops-stat-grid">
                <article class="bd-ops-stat-card is-orange">
                    <span>Total envois</span>
                    <strong>{{ $stats['total'] }}</strong>
                    <small>Volume cumule</small>
                </article>
                <article class="bd-ops-stat-card is-lemon">
                    <span>En attente</span>
                    <strong>{{ $stats['pending'] }}</strong>
                    <small>A lancer ou assigner</small>
                </article>
                <article class="bd-ops-stat-card is-dark">
                    <span>En transit</span>
                    <strong>{{ $stats['in_transit'] }}</strong>
                    <small>Flux actif</small>
                </article>
                <article class="bd-ops-stat-card is-soft">
                    <span>Livres aujourd'hui</span>
                    <strong>{{ $stats['delivered_today'] }}</strong>
                    <small>Clotures du jour</small>
                </article>
            </section>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card bd-ops-filter-card">
            <div class="card-body">
                <form action="{{ route('admin.colis.index') }}" method="GET">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <input type="text" name="search" class="form-control" placeholder="N° de suivi ou nom du client" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="status" class="form-control">
                                <option value="">Tous les statuts</option>
                                @foreach(\App\Domain\Colis\Enums\ShipmentStatus::cases() as $status)
                                    <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="courier_id" class="form-control">
                                <option value="">Tous les coursiers</option>
                                @foreach($couriers as $courier)
                                    <option value="{{ $courier->id }}" {{ request('courier_id') == $courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="Date de début">
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="submit" class="btn bd-ops-primary-btn"><i class="fas fa-search mr-1"></i>Filtrer</button>
                            <a href="{{ route('admin.colis.export-csv', request()->query()) }}" class="btn btn-success"><i class="fas fa-file-csv"></i> Export CSV</a>
                            <a href="{{ route('admin.colis.index') }}" class="btn btn-default">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card bd-ops-table-card">
            <div class="card-header border-0">
                <div class="bd-ops-table-card__header">
                    <div>
                        <h3>Liste des expéditions</h3>
                        <p>Tracking, client, coursier, poids, montant et statut réunis dans une table orientée action.</p>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>N° de suivi</th>
                            <th>Client</th>
                            <th>Coursier</th>
                            <th>Poids</th>
                            <th>Prix total</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shipments as $shipment)
                        <tr>
                            <td><code>{{ $shipment->tracking_number }}</code></td>
                            <td>{{ $shipment->customer->name ?? 'N/A' }}</td>
                            <td>
                                @if($shipment->courier)
                                    <span class="bd-ops-status is-soft">{{ $shipment->courier->name }}</span>
                                @else
                                    <span class="bd-ops-status is-danger">Non assigne</span>
                                @endif
                            </td>
                            <td>{{ $shipment->weight_kg }} kg</td>
                            <td>{{ number_format($shipment->total_price, 0, ',', ' ') }} FCFA</td>
                            <td>
                                <span class="bd-ops-status {{ $shipment->status->value == 'delivered' ? 'is-success' : ($shipment->status->value == 'canceled' ? 'is-danger' : ($shipment->status->value == 'pending' ? 'is-lemon' : 'is-orange')) }}">
                                    {{ $shipment->status->label() }}
                                </span>
                            </td>
                            <td>{{ $shipment->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.colis.show', $shipment->id) }}" class="btn btn-sm btn-outline-primary">
                                    Voir
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Aucun envoi ne correspond aux filtres actuels.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                {{ $shipments->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</section>

<style>
    .bd-ops-shell { display:grid; gap:20px; }
    .bd-ops-hero {
        display:grid; grid-template-columns:minmax(0,1.55fr) minmax(220px,.45fr); align-items:end; gap:20px;
        padding:24px 26px; border-radius:24px;
        background:
            radial-gradient(circle at top right, rgba(245,158,11,.16), transparent 28%),
            linear-gradient(135deg, #26160a 0%, #5b3417 54%, #7c4a1b 100%);
        color:#fff; box-shadow:0 22px 42px rgba(91,52,23,.18);
    }
    .bd-ops-hero__eyebrow { margin:0 0 8px; font-size:.72rem; text-transform:uppercase; letter-spacing:.18em; font-weight:800; color:rgba(253,224,71,.95); }
    .bd-ops-hero h1 { margin:0; color:#fff; font-size:clamp(1.8rem,3vw,2.55rem); font-weight:900; line-height:1.04; max-width:760px; }
    .bd-ops-hero p { margin:12px 0 0; max-width:720px; color:rgba(255,248,235,.84); line-height:1.7; }
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
