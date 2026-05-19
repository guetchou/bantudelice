@extends('frontend.layouts.colis')
@section('title', 'Mes envois | Mema')
@section('description', 'Suivez et gérez tous vos envois Mema depuis votre espace personnel.')

@section('styles')
{{-- Google Fonts chargées par le layout --}}
@endsection

@section('content')
<div class="container my-5" style="margin-top: 100px !important;">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Mes envois de colis</h2>
                <a href="{{ url('/colis/nouveau') }}" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;">
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
                                    <a href="{{ url('/mes-colis/'.$shipment->id) }}" style="display:inline-flex;align-items:center;background:#fff;color:#0ea5e9;font-weight:600;font-size:.8rem;padding:.35rem .75rem;border-radius:999px;border:1.5px solid #bae6fd;cursor:pointer;text-decoration:none;">
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
