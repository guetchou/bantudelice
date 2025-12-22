@extends('layouts.app')
@section('title', 'Gestion des Colis | BantuDelice')
@section('colis_nav', 'active')
@section('colis_nav_open', 'menu-open')
@section('colis_nav_all', 'active')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Gestion des Colis</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                    <li class="breadcrumb-item active">Colis</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Statistiques -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total'] }}</h3>
                        <p>Total Colis</p>
                    </div>
                    <div class="icon"><i class="fas fa-boxes"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['pending'] }}</h3>
                        <p>En attente</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $stats['in_transit'] }}</h3>
                        <p>En cours</p>
                    </div>
                    <div class="icon"><i class="fas fa-truck"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['delivered_today'] }}</h3>
                        <p>Livrés (Aujourd'hui)</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>

        <!-- Filtres Avancés -->
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtres et Recherche</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.colis.index') }}" method="GET">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <input type="text" name="search" class="form-control" placeholder="N° Tracking ou Client" value="{{ request('search') }}">
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
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
                            <a href="{{ route('admin.colis.export-csv', request()->query()) }}" class="btn btn-success"><i class="fas fa-file-csv"></i> Export CSV</a>
                            <a href="{{ route('admin.colis.index') }}" class="btn btn-default">Effacer</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Liste des envois de colis</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>N° Tracking</th>
                            <th>Client</th>
                            <th>Coursier</th>
                            <th>
                                Poids 
                                <i class="fas fa-info-circle text-muted" data-toggle="tooltip" title="Poids réel en kg servant de base au calcul"></i>
                            </th>
                            <th>
                                Prix Total
                                <i class="fas fa-info-circle text-muted" data-toggle="tooltip" title="Montant facturé incluant les options (COD, Assurance, Express)"></i>
                            </th>
                            <th>
                                Statut
                                <i class="fas fa-info-circle text-muted" data-toggle="tooltip" title="Étape actuelle de la livraison"></i>
                            </th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shipments as $shipment)
                        <tr>
                            <td><code>{{ $shipment->tracking_number }}</code></td>
                            <td>{{ $shipment->customer->name ?? 'N/A' }}</td>
                            <td>
                                @if($shipment->courier)
                                    <span class="badge badge-info">{{ $shipment->courier->name }}</span>
                                @else
                                    <span class="badge badge-secondary">Non assigné</span>
                                @endif
                            </td>
                            <td>{{ $shipment->weight_kg }} kg</td>
                            <td>{{ number_format($shipment->total_price, 0, ',', ' ') }} FCFA</td>
                            <td>
                                <span class="badge badge-{{ $shipment->status->value == 'delivered' ? 'success' : ($shipment->status->value == 'canceled' ? 'danger' : 'primary') }}">
                                    {{ $shipment->status->label() }}
                                </span>
                            </td>
                            <td>{{ $shipment->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.colis.show', $shipment->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Aucun colis trouvé.</td>
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
@endsection

