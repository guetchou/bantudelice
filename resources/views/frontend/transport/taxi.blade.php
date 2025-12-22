@extends('frontend.layouts.app-modern')
@section('title', 'Commander un Taxi | BantuDelice')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%); padding: 100px 0 40px; text-align: center; color: white;">
    <div class="container">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
            <i class="fas fa-taxi"></i> Commander un Taxi
        </h1>
        <p style="font-size: 1.125rem; opacity: 0.9;">Votre chauffeur arrive en quelques minutes.</p>
    </div>
</section>

<!-- Taxi Booking Content -->
<section class="section" style="background: #F9FAFB; padding: 3rem 0;">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 450px; gap: 2rem; align-items: start;">
            
            <!-- Map Column -->
            <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); overflow: hidden; height: 600px; position: relative;">
                <div id="taxiMap" style="height: 100%; width: 100%;"></div>
                
                <!-- Floating Address Selector -->
                <div style="position: absolute; top: 20px; left: 20px; right: 20px; background: white; padding: 1.5rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 1;">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="position: relative;">
                            <i class="fas fa-circle" style="position: absolute; left: 15px; top: 15px; color: #10B981; font-size: 12px;"></i>
                            <input type="text" id="pickupInput" placeholder="Point de départ" 
                                   style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #E5E7EB; border-radius: 12px; font-size: 0.9375rem;">
                        </div>
                        <div style="position: relative;">
                            <i class="fas fa-map-marker-alt" style="position: absolute; left: 15px; top: 15px; color: #EF4444; font-size: 14px;"></i>
                            <input type="text" id="dropoffInput" placeholder="Où allez-vous ?" 
                                   style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #E5E7EB; border-radius: 12px; font-size: 0.9375rem;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Panel -->
            <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 2rem;">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-receipt" style="color: #FF6B35;"></i>
                    Détails de la course
                </h3>

                <!-- Estimates -->
                <div id="estimateSection" style="display: none; background: #f8f9fa; border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="color: #6B7280;">Distance estimée</span>
                        <span id="estDistance" style="font-weight: 600;">-- km</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="color: #6B7280;">Durée estimée</span>
                        <span id="estDuration" style="font-weight: 600;">-- min</span>
                    </div>
                    <div style="border-top: 1px solid #E5E7EB; padding-top: 0.75rem; margin-top: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="font-weight: 700; font-size: 1.125rem;">Prix estimé</span>
                        <span id="estPrice" style="font-weight: 800; font-size: 1.25rem; color: #FF6B35;">-- FCFA</span>
                    </div>
                </div>

                <!-- Payment Method -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #374151;">Mode de paiement</label>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid #FF6B35; background: rgba(255,107,53,0.05); border-radius: 12px; cursor: pointer;">
                            <input type="radio" name="payment_method" value="cash" checked style="margin-right: 1rem; width: 18px; height: 18px;">
                            <span>💵 Espèces</span>
                        </label>
                        <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid #E5E7EB; border-radius: 12px; cursor: pointer;">
                            <input type="radio" name="payment_method" value="momo" style="margin-right: 1rem; width: 18px; height: 18px;">
                            <span>📱 Mobile Money</span>
                        </label>
                    </div>
                </div>

                <!-- Hidden inputs for coordinates -->
                <input type="hidden" id="p_lat">
                <input type="hidden" id="p_lng">
                <input type="hidden" id="d_lat">
                <input type="hidden" id="d_lng">

                <!-- Submit Button -->
                <button id="confirmBtn" class="btn btn-primary btn-lg" style="width: 100%; height: 60px; font-size: 1.125rem; font-weight: 700;" disabled>
                    <i class="fas fa-check-circle"></i> Confirmer la commande
                </button>
                
                <p style="text-align: center; color: #9CA3AF; font-size: 0.8125rem; margin-top: 1rem;">
                    En confirmant, vous acceptez nos conditions générales de transport.
                </p>
            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCkXFIvxvN0M1Chg644bLwAnXEQUG_RKUI&libraries=places,directions"></script>
<script>
    let map, directionsService, directionsRenderer, pickupAutocomplete, dropoffAutocomplete;
    let pickupMarker, dropoffMarker;

    function initTaxiMap() {
        const brazzaville = { lat: -4.2767, lng: 15.2832 };
        
        map = new google.maps.Map(document.getElementById('taxiMap'), {
            center: brazzaville,
            zoom: 13,
            disableDefaultUI: true,
            zoomControl: true,
            styles: [{"featureType": "poi", "stylers": [{"visibility": "off"}]}]
        });

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: true,
            polylineOptions: {
                strokeColor: '#FF6B35',
                strokeWeight: 5,
                strokeOpacity: 0.8
            }
        });

        // Autocomplete setup
        const options = {
            componentRestrictions: { country: ['cg', 'cd'] },
            fields: ['geometry', 'formatted_address']
        };

        pickupAutocomplete = new google.maps.places.Autocomplete(document.getElementById('pickupInput'), options);
        dropoffAutocomplete = new google.maps.places.Autocomplete(document.getElementById('dropoffInput'), options);

        pickupAutocomplete.addListener('place_changed', () => onPlaceChanged('pickup'));
        dropoffAutocomplete.addListener('place_changed', () => onPlaceChanged('dropoff'));

        // Geolocation
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((pos) => {
                const myPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                map.setCenter(myPos);
                setMarker('pickup', myPos);
                
                // Inverse geocoding for current position
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: myPos }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        document.getElementById('pickupInput').value = results[0].formatted_address;
                    }
                });
            });
        }
    }

    function onPlaceChanged(type) {
        const place = (type === 'pickup' ? pickupAutocomplete : dropoffAutocomplete).getPlace();
        if (!place.geometry) return;

        const pos = {
            lat: place.geometry.location.lat(),
            lng: place.geometry.location.lng()
        };

        setMarker(type, pos);
        calculateRoute();
    }

    function setMarker(type, pos) {
        if (type === 'pickup') {
            if (pickupMarker) pickupMarker.setMap(null);
            pickupMarker = new google.maps.Marker({
                position: pos,
                map: map,
                icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
            });
            document.getElementById('p_lat').value = pos.lat;
            document.getElementById('p_lng').value = pos.lng;
        } else {
            if (dropoffMarker) dropoffMarker.setMap(null);
            dropoffMarker = new google.maps.Marker({
                position: pos,
                map: map,
                icon: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png'
            });
            document.getElementById('d_lat').value = pos.lat;
            document.getElementById('d_lng').value = pos.lng;
        }
    }

    function calculateRoute() {
        const pLat = document.getElementById('p_lat').value;
        const pLng = document.getElementById('p_lng').value;
        const dLat = document.getElementById('d_lat').value;
        const dLng = document.getElementById('d_lng').value;

        if (!pLat || !dLat) return;

        directionsService.route({
            origin: { lat: parseFloat(pLat), lng: parseFloat(pLng) },
            destination: { lat: parseFloat(dLat), lng: parseFloat(dLng) },
            travelMode: google.maps.TravelMode.DRIVING
        }, (response, status) => {
            if (status === 'OK') {
                directionsRenderer.setDirections(response);
                const route = response.routes[0].legs[0];
                
                // Show estimates
                updateEstimates(route.distance.value / 1000, route.duration.value / 60);
            }
        });
    }

    async function updateEstimates(distance, duration) {
        try {
            const response = await fetch('/api/v1/transport/estimate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    type: 'taxi',
                    distance: distance,
                    duration: duration
                })
            });
            const data = await response.json();
            
            document.getElementById('estimateSection').style.display = 'block';
            document.getElementById('estDistance').textContent = distance.toFixed(1) + ' km';
            document.getElementById('estDuration').textContent = Math.ceil(duration) + ' min';
            document.getElementById('estPrice').textContent = data.estimated_price.toLocaleString('fr-FR') + ' FCFA';
            
            document.getElementById('confirmBtn').disabled = false;
        } catch (error) {
            console.error('Estimation error:', error);
        }
    }

    // Confirm Booking
    document.getElementById('confirmBtn').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recherche de chauffeur...';

        const payload = {
            type: 'taxi',
            pickup_address: document.getElementById('pickupInput').value,
            pickup_lat: document.getElementById('p_lat').value,
            pickup_lng: document.getElementById('p_lng').value,
            dropoff_address: document.getElementById('dropoffInput').value,
            dropoff_lat: document.getElementById('d_lat').value,
            dropoff_lng: document.getElementById('d_lng').value,
            payment_method: document.querySelector('input[name="payment_method"]:checked').value
        };

        try {
            const response = await fetch('/api/v1/transport/bookings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            
            if (data.booking) {
                window.location.href = `/transport/booking/${data.booking.uuid}`;
            } else {
                alert('Erreur lors de la réservation. Veuillez réessayer.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirmer la commande';
            }
        } catch (error) {
            console.error('Booking error:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirmer la commande';
        }
    });

    window.onload = initTaxiMap;
</script>
@endsection

