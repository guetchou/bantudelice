@extends('layouts.app')
@section('title', 'Points Relais | BantuDelice')
@section('colis_nav', 'active')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Points Relais (Partenaires)</h1>
            </div>
            <div class="col-sm-6">
                <a href="{{ route('admin.relay-points.create') }}" class="btn btn-primary float-sm-right">
                    <i class="fas fa-plus"></i> Ajouter un point relais
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Ville / Quartier</th>
                            <th>Contact</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($relayPoints as $rp)
                        <tr>
                            <td>{{ $rp->name }}</td>
                            <td>{{ $rp->city }} ({{ $rp->district }})</td>
                            <td>{{ $rp->contact_phone }}</td>
                            <td>
                                <span class="badge badge-{{ $rp->is_active ? 'success' : 'danger' }}">
                                    {{ $rp->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('admin.relay-points.toggle', $rp->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-{{ $rp->is_active ? 'warning' : 'success' }}">
                                        {{ $rp->is_active ? 'Désactiver' : 'Activer' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

