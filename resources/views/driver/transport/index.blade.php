@extends('layouts.app')
@section('title', 'Tableau de bord Chauffeur | BantuDelice')
@section('transport_driver_nav', 'active')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Espace Chauffeur Transport</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <!-- Active Booking -->
        @if($activeBooking)
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-route mr-2"></i>Course en cours</h3>
                <div class="card-tools">
                    <span class="badge badge-primary">{{ $activeBooking->status->label() }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Client: <b>{{ $activeBooking->user->name }}</b></h5>
                        <p><i class="fas fa-phone"></i> {{ $activeBooking->user->phone }}</p>
                        <hr>
                        <p><b>Départ:</b> {{ $activeBooking->pickup_address }}</p>
                        <p><b>Arrivée:</b> {{ $activeBooking->dropoff_address }}</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <div style="font-size: 2rem; font-weight: 800; color: #FF6B35; margin-bottom: 1rem;">
                            {{ number_format($activeBooking->total_price ?? $activeBooking->estimated_price, 0, ',', ' ') }} FCFA
                        </div>
                        
                        <div class="btn-group-vertical w-100">
                            @if($activeBooking->status->value === 'assigned')
                                <button onclick="updateStatus('driver_arriving')" class="btn btn-warning btn-lg">Je suis en route</button>
                            @elseif($activeBooking->status->value === 'driver_arriving')
                                <button onclick="updateStatus('in_progress')" class="btn btn-primary btn-lg">Course démarrée</button>
                            @elseif($activeBooking->status->value === 'in_progress')
                                <button onclick="updateStatus('completed')" class="btn btn-success btn-lg">Terminer la course</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Nearby Requests -->
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Demandes à proximité</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-valign-middle">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>De</th>
                            <th>À</th>
                            <th>Prix Est.</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($nearbyRequests as $request)
                        <tr>
                            <td><span class="badge badge-info">{{ $request->type->label() }}</span></td>
                            <td><small>{{ $request->pickup_address }}</small></td>
                            <td><small>{{ $request->dropoff_address }}</small></td>
                            <td>{{ number_format($request->estimated_price, 0) }} F</td>
                            <td>
                                <button onclick="acceptBooking('{{ $request->uuid }}')" class="btn btn-sm btn-success">Accepter</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Aucune demande en attente.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

@endsection

@section('script')
<script>
    function acceptBooking(uuid) {
        if(!confirm('Accepter cette course ?')) return;
        
        fetch(`/api/v1/transport/driver/bookings/${uuid}/accept`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                @if(auth()->user()->api_token)
                'Authorization': 'Bearer {{ auth()->user()->api_token }}'
                @endif
            }
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) {
                alert(data.error || 'Erreur lors de l\'acceptation');
            } else {
                window.location.reload();
            }
        })
        .catch(err => alert('Erreur technique'));
    }

    function updateStatus(status) {
        if(!confirm('Confirmer le changement de statut ?')) return;
        
        fetch(`/api/v1/transport/driver/bookings/{{ $activeBooking->uuid ?? '' }}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Authorization': 'Bearer {{ auth()->user()->api_token }}'
            },
            body: JSON.stringify({ status: status })
        }).then(res => res.json()).then(data => {
            window.location.reload();
        });
    }

    // GPS Tracking
    if ("geolocation" in navigator && @json($activeBooking ? true : false)) {
        setInterval(() => {
            navigator.geolocation.getCurrentPosition(pos => {
                fetch(`/api/v1/transport/driver/bookings/{{ $activeBooking->uuid ?? '' }}/location`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Authorization': 'Bearer {{ auth()->user()->api_token }}'
                    },
                    body: JSON.stringify({
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        speed: pos.coords.speed
                    })
                });
            });
        }, 10000);
    }
</script>
@endsection

