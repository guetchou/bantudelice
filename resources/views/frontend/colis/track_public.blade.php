@extends('frontend.layouts.app-modern')

@section('title', 'Suivi de colis | BantuDelice')

@section('content')
<div class="breadcrumb-agile">
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
            <li class="breadcrumb-item active" aria-current="page">Suivi de colis</li>
        </ol>
    </div>
</div>

<div class="tracking-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Suivre mon colis</h2>
                        
                        <form action="{{ route('colis.track_public') }}" method="GET" class="mb-5">
                            <div class="input-group input-group-lg">
                                <input type="text" name="tracking_number" class="form-control border-primary" 
                                       placeholder="Entrez votre numéro de suivi (ex: BD-CG-XXXXX)" 
                                       value="{{ $trackingNumber ?? '' }}" required>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fa fa-search"></i> RECHERCHER
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if($trackingNumber && !$shipment)
                            <div class="alert alert-danger text-center">
                                <i class="fa fa-exclamation-triangle"></i> Désolé, aucun colis n'a été trouvé avec le numéro <strong>{{ $trackingNumber }}</strong>.
                            </div>
                        @endif

                        @if($shipment)
                            <div class="shipment-result mt-4">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                                    <div>
                                        <h5 class="text-muted mb-1">Numéro de suivi</h5>
                                        <h3 class="text-primary font-weight-bold">{{ $shipment->tracking_number }}</h3>
                                    </div>
                                    <div class="text-right">
                                        <h5 class="text-muted mb-1">Statut actuel</h5>
                                        <span id="currentStatusBadge" class="badge badge-pill badge-primary p-2 px-3" style="font-size: 1rem;">
                                            {{ ucfirst($shipment->status->label()) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Carte de suivi en direct -->
                                <div id="trackingMapContainer" style="display: none; margin-bottom: 2rem;">
                                    <h4 class="mb-3"><i class="fa fa-map-marker-alt text-primary"></i> Suivi en direct</h4>
                                    <div id="trackingMap" style="height: 400px; border-radius: 12px; overflow: hidden; border: 1px solid #ddd;"></div>
                                    <div id="driverInfo" class="mt-3 p-3 bg-light rounded border" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                                    <i class="fa fa-motorcycle"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 font-weight-bold" id="driverName">--</h6>
                                                <p class="mb-0 text-muted small">Votre livreur est en route</p>
                                            </div>
                                            <div class="ml-auto">
                                                <a href="#" id="driverPhoneLink" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                                                    <i class="fa fa-phone"></i> Appeler
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="timeline-wrapper">
                                    <h4 class="mb-4">Historique de l'expédition</h4>
                                    <div id="shipmentTimeline" class="timeline">
                                        @foreach($shipment->events as $event)
                                            <div class="timeline-item pb-4 border-left pl-4 position-relative" style="border-width: 2px !important; border-color: #eee !important;">
                                                <div class="timeline-marker position-absolute" style="left: -9px; top: 0; width: 16px; height: 16px; border-radius: 50%; background: {{ $loop->first ? '#ff0000' : '#ccc' }}; border: 3px solid #fff; box-shadow: 0 0 0 2px {{ $loop->first ? '#ff0000' : '#ccc' }};"></div>
                                                <div class="timeline-content">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="font-weight-bold mb-1">{{ ucfirst($event->status->label()) }}</h6>
                                                        <small class="text-muted">{{ $event->created_at->format('d/m/Y H:i') }}</small>
                                                    </div>
                                                    <p class="text-muted small mb-0">{{ $event->notes }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-5 p-4 bg-light rounded border">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-muted small text-uppercase">Expéditeur</h6>
                                            <p class="mb-0 font-weight-bold">{{ $shipment->pickupAddress()->city }} ({{ $shipment->pickupAddress()->district }})</p>
                                        </div>
                                        <div class="col-md-6 text-md-right mt-3 mt-md-0">
                                            <h6 class="text-muted small text-uppercase">Destinataire</h6>
                                            <p class="mb-0 font-weight-bold">{{ $shipment->dropoffAddress()->city }} ({{ $shipment->dropoffAddress()->district }})</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline-item:last-child {
        border-left: none !important;
    }
</style>
@section('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ google_maps_api_key() }}&libraries=places"></script>
<script>
    let map, pickupMarker, dropoffMarker, driverMarker;
    const trackingNumber = '{{ $trackingNumber ?? '' }}';

    function initMap(pickup, dropoff, driver) {
        if (!pickup || !dropoff) return;

        document.getElementById('trackingMapContainer').style.display = 'block';

        const mapOptions = {
            zoom: 12,
            center: { lat: parseFloat(pickup.lat), lng: parseFloat(pickup.lng) },
            mapTypeId: google.maps.MapId ? 'roadmap' : 'roadmap',
            disableDefaultUI: false,
        };

        map = new google.maps.Map(document.getElementById('trackingMap'), mapOptions);

        // Marker Ramassage
        pickupMarker = new google.maps.Marker({
            position: { lat: parseFloat(pickup.lat), lng: parseFloat(pickup.lng) },
            map: map,
            title: 'Point de ramassage',
            icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
        });

        // Marker Livraison
        dropoffMarker = new google.maps.Marker({
            position: { lat: parseFloat(dropoff.lat), lng: parseFloat(dropoff.lng) },
            map: map,
            title: 'Point de livraison',
            icon: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png'
        });

        const bounds = new google.maps.LatLngBounds();
        bounds.extend(pickupMarker.getPosition());
        bounds.extend(dropoffMarker.getPosition());

        if (driver) {
            updateDriverOnMap(driver);
            bounds.extend(new google.maps.LatLng(driver.lat, driver.lng));
        }

        map.fitBounds(bounds);
    }

    function updateDriverOnMap(driver) {
        if (!driver) return;

        if (driverMarker) {
            driverMarker.setPosition({ lat: parseFloat(driver.lat), lng: parseFloat(driver.lng) });
        } else {
            driverMarker = new google.maps.Marker({
                position: { lat: parseFloat(driver.lat), lng: parseFloat(driver.lng) },
                map: map,
                title: 'Livreur en mouvement',
                icon: 'https://maps.google.com/mapfiles/ms/icons/motorcycle.png'
            });
        }
    }

    async function pollTracking() {
        if (!trackingNumber) return;

        try {
            const response = await fetch(`/api/v1/colis/track/${trackingNumber}`);
            const data = await response.json();

            if (data.status) {
                // Mise à jour du badge
                const badge = document.getElementById('currentStatusBadge');
                badge.textContent = data.status_label;
                
                // Mise à jour de la carte
                if (data.locations.pickup && data.locations.dropoff) {
                    if (!map) {
                        initMap(data.locations.pickup, data.locations.dropoff, data.locations.current_driver);
                    } else if (data.locations.current_driver) {
                        updateDriverOnMap(data.locations.current_driver);
                    }
                }

                // Mise à jour des infos livreur
                const driverDiv = document.getElementById('driverInfo');
                if (data.courier && data.locations.current_driver) {
                    driverDiv.style.display = 'block';
                    document.getElementById('driverName').textContent = data.courier.name;
                    document.getElementById('driverPhoneLink').href = 'tel:' + data.courier.phone;
                } else {
                    driverDiv.style.display = 'none';
                }

                // Note: La mise à jour de la timeline pourrait être faite ici aussi si besoin
            }
        } catch (error) {
            console.error('Erreur de tracking:', error);
        }
    }

    @if($shipment)
        // Initialisation immédiate avec les données PHP
        window.addEventListener('load', () => {
            const initialData = {
                pickup: { lat: '{{ $shipment->pickupAddress()->lat }}', lng: '{{ $shipment->pickupAddress()->lng }}' },
                dropoff: { lat: '{{ $shipment->dropoffAddress()->lat }}', lng: '{{ $shipment->dropoffAddress()->lng }}' },
                driver: null // Sera chargé via le polling
            };
            
            if (initialData.pickup.lat && initialData.dropoff.lat) {
                initMap(initialData.pickup, initialData.dropoff, null);
            }
            
            pollTracking();
            setInterval(pollTracking, 10000); // Toutes les 10 secondes
        });
    @endif
</script>
@endsection
@endsection

