@extends('frontend.layouts.app-modern')
@section('title', 'Mes envois de colis | BantuDelice')

@section('content')
<div class="container my-5" style="margin-top: 100px !important;">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Mes envois de colis</h2>
                <a href="{{ url('/colis/nouveau') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvel envoi
                </a>
            </div>
        </div>
        
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>N° Tracking</th>
                                <th>Destinataire</th>
                                <th>Poids</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shipments as $shipment)
                            <tr>
                                <td><code>{{ $shipment->tracking_number }}</code></td>
                                <td>{{ $shipment->dropoffAddress()->full_name ?? 'N/A' }}</td>
                                <td>{{ $shipment->weight_kg }} kg</td>
                                <td>
                                    <span class="badge badge-primary">
                                        {{ $shipment->status->label() }}
                                    </span>
                                </td>
                                <td>{{ $shipment->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ url('/mes-colis/'.$shipment->id) }}" class="btn btn-sm btn-outline-info">
                                        Détails
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    Vous n'avez pas encore effectué d'envoi de colis.<br>
                                    <a href="{{ url('/colis/nouveau') }}" class="mt-2 d-inline-block">Commencer maintenant</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

