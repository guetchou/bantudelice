@extends('frontend.layouts.app-modern')
@section('title', 'Suivi de réservation #' . $booking->booking_no . ' | BantuDelice')

@section('content')
<!-- Header -->
<section style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%); padding: 120px 0 60px; text-align: center; color: white;">
    <div class="container">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
            <i class="fas fa-route"></i> Suivi de votre course
        </h1>
        <p style="font-size: 1.125rem; opacity: 0.9;">Réservation #{{ $booking->booking_no }}</p>
    </div>
</section>

<section class="section" style="background: #F9FAFB; padding: 3rem 0;">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem;">
            
            <!-- Map & Timeline -->
            <div>
                <!-- Map -->
                <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); overflow: hidden; height: 450px; margin-bottom: 2rem;">
                    <div id="bookingMap" style="height: 100%; width: 100%;"></div>
                </div>

                <!-- Timeline -->
                <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 2.5rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 2rem;">Statut de la réservation</h3>
                    
                    <div style="position: relative; padding-left: 3rem;">
                        <div style="position: absolute; left: 20px; top: 0; bottom: 0; width: 3px; background: #E5E7EB;">
                            <div id="statusProgress" style="position: absolute; top: 0; left: 0; width: 100%; background: #10B981; transition: height 0.5s; height: 0%;"></div>
                        </div>

                        @php
                            $steps = [
                                'requested' => ['label' => 'Demande envoyée', 'icon' => 'paper-plane'],
                                'assigned' => ['label' => 'Chauffeur assigné', 'icon' => 'user-check'],
                                'driver_arriving' => ['label' => 'Chauffeur en route', 'icon' => 'car'],
                                'in_progress' => ['label' => 'Course en cours', 'icon' => 'route'],
                                'completed' => ['label' => 'Course terminée', 'icon' => 'flag-checkered'],
                            ];
                            $currentStatusIndex = array_search($booking->status->value, array_keys($steps));
                        @endphp

                        @foreach($steps as $key => $step)
                            @php 
                                $stepIndex = array_search($key, array_keys($steps));
                                $isCompleted = $stepIndex <= $currentStatusIndex;
                                $isActive = $key === $booking->status->value;
                            @endphp
                            <div style="position: relative; margin-bottom: 2.5rem;">
                                <div style="position: absolute; left: -3rem; top: 0; width: 44px; height: 44px; background: {{ $isCompleted ? '#10B981' : '#E5E7EB' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; z-index: 2;">
                                    <i class="fas fa-{{ $step['icon'] }}"></i>
                                </div>
                                <div>
                                    <h4 style="font-size: 1.125rem; font-weight: 700; color: {{ $isCompleted ? '#1F2937' : '#9CA3AF' }};">{{ $step['label'] }}</h4>
                                    @if($isActive)
                                        <p style="color: #6B7280; font-size: 0.875rem; margin: 0;">Étape actuelle</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div style="position: sticky; top: 100px;">
                <!-- Driver Card -->
                @if($booking->driver)
                <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: #05944F; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 700;">
                            {{ substr($booking->driver->name, 0, 2) }}
                        </div>
                        <div>
                            <h4 style="font-weight: 700; margin: 0;">{{ $booking->driver->name }}</h4>
                            <p style="color: #6B7280; font-size: 0.875rem; margin: 0;">Votre chauffeur</p>
                        </div>
                    </div>
                    @if($booking->vehicle)
                    <div style="background: #f8f9fa; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
                        <p style="font-weight: 600; margin-bottom: 0.25rem;">{{ $booking->vehicle->make }} {{ $booking->vehicle->model }}</p>
                        <p style="color: #6B7280; font-size: 0.8125rem; margin: 0;">{{ $booking->vehicle->plate_number }} • {{ $booking->vehicle->color }}</p>
                    </div>
                    @endif
                    <a href="tel:{{ $booking->driver->phone }}" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-phone"></i> Appeler le chauffeur
                    </a>
                </div>
                @else
                <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 2rem; text-align: center; margin-bottom: 1.5rem;">
                    <div style="width: 60px; height: 60px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #9CA3AF; margin: 0 auto 1rem;">
                        <i class="fas fa-user-clock fa-2x"></i>
                    </div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">Recherche de chauffeur</h4>
                    <p style="color: #6B7280; font-size: 0.875rem;">Nous cherchons le chauffeur le plus proche de vous.</p>
                </div>
                @endif

                <!-- Price Card -->
                <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Détails du paiement</h4>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: #6B7280;">Prix estimé</span>
                        <span style="font-weight: 600;">{{ number_format($booking->estimated_price, 0, ',', ' ') }} F</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="color: #6B7280;">Méthode</span>
                        <span style="font-weight: 600;">{{ $booking->payment_method === 'cash' ? 'Espèces' : 'Mobile Money' }}</span>
                    </div>
                    <div style="border-top: 1px solid #E5E7EB; padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700;">Total</span>
                            <span style="font-weight: 800; font-size: 1.5rem; color: #FF6B35;">{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} FCFA</span>
                        </div>
                    </div>
                    
                    @if($booking->payment_status === 'pending' && $booking->payment_method !== 'cash')
                        <button class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;" onclick="payNow()">
                            Payer maintenant
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCkXFIvxvN0M1Chg644bLwAnXEQUG_RKUI&libraries=places,directions"></script>
<script>
    let map, pickupMarker, dropoffMarker, driverMarker, directionsRenderer;

    function initBookingMap() {
        const pickup = { lat: parseFloat({{ $booking->pickup_lat }}), lng: parseFloat({{ $booking->pickup_lng }}) };
        const dropoff = { lat: parseFloat({{ $booking->dropoff_lat }}), lng: parseFloat({{ $booking->dropoff_lng }}) };

        map = new google.maps.Map(document.getElementById('bookingMap'), {
            center: pickup,
            zoom: 13,
            disableDefaultUI: true,
            zoomControl: true
        });

        directionsRenderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: true
        });

        pickupMarker = new google.maps.Marker({
            position: pickup,
            map: map,
            icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
        });

        dropoffMarker = new google.maps.Marker({
            position: dropoff,
            map: map,
            icon: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png'
        });

        const directionsService = new google.maps.DirectionsService();
        directionsService.route({
            origin: pickup,
            destination: dropoff,
            travelMode: google.maps.TravelMode.DRIVING
        }, (res, status) => {
            if (status === 'OK') {
                directionsRenderer.setDirections(res);
            }
        });

        // Simuler le tracking si chauffeur assigné
        @if($booking->driver && $booking->status->value !== 'completed')
            updateDriverLocation();
            setInterval(updateDriverLocation, 5000);
        @endif
    }

    function updateDriverLocation() {
        fetch(`/api/v1/transport/bookings/{{ $booking->uuid }}`)
            .then(res => res.json())
            .then(data => {
                if (data.status !== '{{ $booking->status->value }}') {
                    window.location.reload();
                    return;
                }
                
                // Fetch latest tracking point
                fetch(`/api/v1/transport/bookings/{{ $booking->uuid }}/track`)
                    .then(res => res.json())
                    .then(track => {
                        if (track.lat && track.lng) {
                            const pos = { lat: parseFloat(track.lat), lng: parseFloat(track.lng) };
                            if (!driverMarker) {
                                driverMarker = new google.maps.Marker({
                                    position: pos,
                                    map: map,
                                    icon: {
                                        url: 'https://cdn-icons-png.flaticon.com/512/3448/3448327.png',
                                        scaledSize: new google.maps.Size(40, 40)
                                    },
                                    title: 'Votre chauffeur'
                                });
                            } else {
                                driverMarker.setPosition(pos);
                            }
                        }
                    });
            });
    }

    function payNow() {
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initialisation...';

        fetch(`/api/v1/transport/bookings/{{ $booking->uuid }}/pay`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ provider: 'momo' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else if (data.status === 'success') {
                showToast('Paiement réussi !', 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showToast(data.message || 'Erreur lors du paiement', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Payer maintenant';
            }
        })
        .catch(err => {
            showToast('Erreur technique', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Payer maintenant';
        });
    }

    window.onload = initBookingMap;
</script>
@endsection

