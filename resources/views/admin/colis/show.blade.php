@extends('layouts.app')
@section('title', 'Détail Colis | BantuDelice')
@section('colis_nav', 'active')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Détail du Colis : {{ $shipment->tracking_number }}</h1>
            </div>
            <div class="col-sm-6">
                <div class="float-right">
                    <a href="{{ route('admin.colis.print', $shipment->id) }}" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Imprimer l'étiquette</a>
                </div>
                <ol class="breadcrumb float-sm-right mr-3">
                    <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.colis.index')}}">Colis</a></li>
                    <li class="breadcrumb-item active">Détail</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <h3 class="profile-username text-center">{{ $shipment->tracking_number }}</h3>
                        <p class="text-muted text-center">{{ $shipment->status->label() }}</p>
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Client</b> <a class="float-right">{{ $shipment->customer->name ?? 'N/A' }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Poids</b> <a class="float-right">{{ $shipment->weight_kg }} kg</a>
                            </li>
                            <li class="list-group-item">
                                <b>Prix Total</b> <a class="float-right">{{ number_format($shipment->total_price, 0, ',', ' ') }} FCFA</a>
                            </li>
                            <li class="list-group-item">
                                <b>Paiement</b> <a class="float-right badge badge-info">{{ strtoupper($shipment->payment_status) }}</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Adresses</h3>
                    </div>
                    <div class="card-body">
                        <strong><i class="fas fa-map-marker-alt mr-1"></i> Ramassage (Pickup)</strong>
                        <p class="text-muted">
                            @php $p = $shipment->pickupAddress(); @endphp
                            {{ $p->full_name }} ({{ $p->phone }})<br>
                            {{ $p->address_line }}, {{ $p->district }}, {{ $p->city }}<br>
                            <small>Repère : {{ $p->landmark ?? 'N/A' }}</small>
                        </p>
                        <hr>
                        <strong><i class="fas fa-map-marker-alt mr-1"></i> Livraison (Dropoff)</strong>
                        <p class="text-muted">
                            @php $d = $shipment->dropoffAddress(); @endphp
                            {{ $d->full_name }} ({{ $d->phone }})<br>
                            {{ $d->address_line }}, {{ $d->district }}, {{ $d->city }}<br>
                            <small>Repère : {{ $d->landmark ?? 'N/A' }}</small>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a class="nav-link active" href="#timeline" data-toggle="tab">Timeline (Suivi)</a></li>
                            <li class="nav-item"><a class="nav-link" href="#details" data-toggle="tab">Plus de détails</a></li>
                            <li class="nav-item"><a class="nav-link" href="#incidents" data-toggle="tab">Incidents & Litiges <span class="badge badge-danger">{{ $shipment->incidents->count() }}</span></a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="active tab-pane" id="timeline">
                                <!-- ... timeline content ... -->
                            </div>
                            <div class="tab-pane" id="details">
                                <!-- ... details content ... -->
                            </div>
                            <!-- Onglet Incidents -->
                            <div class="tab-pane" id="incidents">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-danger float-right" data-toggle="modal" data-target="#modal-report-incident">
                                            <i class="fas fa-exclamation-triangle"></i> Signaler un incident
                                        </button>
                                    </div>
                                </div>

                                <div class="timeline timeline-inverse">
                                    @forelse($shipment->incidents->sortByDesc('created_at') as $incident)
                                        <div class="time-label">
                                            <span class="bg-danger">{{ $incident->created_at->format('d M. Y') }}</span>
                                        </div>
                                        <div>
                                            <i class="fas fa-exclamation bg-red"></i>
                                            <div class="timeline-item">
                                                <span class="time"><i class="far fa-clock"></i> {{ $incident->created_at->format('H:i') }}</span>
                                                <h3 class="timeline-header">Type : <b>{{ strtoupper($incident->type) }}</b> - Signalé par {{ $incident->reporter->name ?? 'Système' }}</h3>
                                                <div class="timeline-body">
                                                    {{ $incident->description }}
                                                </div>
                                                @if($incident->resolution_notes)
                                                    <div class="timeline-footer bg-light p-2">
                                                        <strong>Résolution :</strong> {{ $incident->resolution_notes }}
                                                        <br><small>Résolu le : {{ $incident->resolved_at->format('d/m/Y H:i') }}</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-center text-muted py-3">Aucun incident signalé pour ce colis.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Signaler Incident -->
<div class="modal fade" id="modal-report-incident">
    <div class="modal-dialog">
        <form action="{{ route('admin.colis.report-incident', $shipment->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h4 class="modal-title">Signaler un incident / litige</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Type d'incident</label>
                        <select name="type" class="form-control" required>
                            <option value="damage">Colis endommagé</option>
                            <option value="loss">Colis perdu / volé</option>
                            <option value="delay">Retard important</option>
                            <option value="customer_complain">Plainte client</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description du problème</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Détaillez le problème rencontré..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Action immédiate (Changement de statut)</label>
                        <select name="status" class="form-control">
                            <option value="">Ne pas changer le statut</option>
                            <option value="damaged">Marquer comme ENDOMMAGÉ</option>
                            <option value="lost">Marquer comme PERDU</option>
                            <option value="returned">Lancer le RETOUR à l'expéditeur</option>
                        </select>
                        <small class="text-muted">Cela mettra à jour le tracking du client.</small>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-danger">Enregistrer le signalement</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

