@extends('layouts.app')
@section('title', 'Gestion des Véhicules')
@section('transport_nav', 'active')
@section('transport_vehicles_nav', 'active')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Véhicules Transport</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.transport.dashboard') }}">Transport</a></li>
                        <li class="breadcrumb-item active">Véhicules</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Liste des véhicules</h3>
                    <div class="card-tools">
                        <button class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Ajouter un véhicule</button>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Véhicule</th>
                                <th>Immatriculation</th>
                                <th>Propriétaire</th>
                                <th>Type</th>
                                <th>Places</th>
                                <th>Disponibilité</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicles as $vehicle)
                                <tr>
                                    <td>
                                        <b>{{ $vehicle->make }} {{ $vehicle->model }}</b><br>
                                        <small>{{ $vehicle->color }} ({{ $vehicle->year }})</small>
                                    </td>
                                    <td>{{ $vehicle->plate_number }}</td>
                                    <td>{{ $vehicle->owner->name ?? 'Admin' }}</td>
                                    <td><span class="badge badge-info">{{ $vehicle->type }}</span></td>
                                    <td>{{ $vehicle->seats }}</td>
                                    <td>
                                        @if($vehicle->is_available)
                                            <span class="badge badge-success">Oui</span>
                                        @else
                                            <span class="badge badge-danger">Non</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($vehicle->status === 'active')
                                            <span class="badge badge-success">Actif / Approuvé</span>
                                        @elseif($vehicle->status === 'pending')
                                            <span class="badge badge-warning">En attente</span>
                                        @elseif($vehicle->status === 'rejected')
                                            <span class="badge badge-danger">Rejeté</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $vehicle->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-info" title="Modifier"><i class="fas fa-edit"></i></button>
                                            
                                            @if($vehicle->status === 'pending')
                                                <form action="{{ route('admin.transport.vehicles.approve', $vehicle->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approuver"><i class="fas fa-check"></i></button>
                                                </form>
                                                <button class="btn btn-sm btn-danger btn-reject" data-id="{{ $vehicle->id }}" title="Rejeter"><i class="fas fa-times"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">
                        {{ $vehicles->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Reject -->
    <div class="modal fade" id="modal-reject">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-reject" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h4 class="modal-title">Rejeter le véhicule</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Raison du rejet</label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Ex: Documents illisibles, véhicule non conforme..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-danger">Confirmer le rejet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.btn-reject').click(function() {
            var id = $(this).data('id');
            var url = "{{ route('admin.transport.vehicles.reject', ':id') }}";
            url = url.replace(':id', id);
            $('#form-reject').attr('action', url);
            $('#modal-reject').modal('show');
        });
    });
</script>
@endsection

