@extends('frontend.layouts.app-modern')
@section('title', 'Détail du colis | BantuDelice')

@section('style')
<style>
    .shipment-detail-container { background: #fff; padding: 30px; border-radius: 8px; margin-bottom: 50px; }
    .status-banner { padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: center; font-weight: bold; }
    .status-created { background: #e3f2fd; color: #0d47a1; }
    .status-delivered { background: #e8f5e9; color: #1b5e20; }
    .status-canceled { background: #ffebee; color: #b71c1c; }
    .timeline-container { position: relative; padding-left: 30px; border-left: 2px solid #f4f4f4; margin-left: 15px; }
    .timeline-item { position: relative; margin-bottom: 30px; }
    .timeline-item::before { content: ""; position: absolute; left: -37px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #4A67B2; border: 2px solid #fff; }
    .timeline-date { font-size: 12px; color: #999; }
</style>
@endsection

@section('content')
<div class="container my-5" style="margin-top: 100px !important;">
    <div class="row">
        <div class="col-md-12 mb-4">
            <a href="{{ url('/mes-colis') }}" class="btn btn-link"><i class="fa fa-arrow-left"></i> Retour à mes envois</a>
        </div>
        
        <div class="col-md-8">
            <div class="shipment-detail-container shadow-sm">
                <div id="currentStatusBanner" class="status-banner status-{{ $shipment->status->value }}">
                    Statut actuel : {{ $shipment->status->label() }}
                </div>

                <!-- Carte de suivi en direct -->
                <div id="trackingMapContainer" style="display: none; margin-bottom: 2rem;">
                    <h5 class="mb-3"><i class="fa fa-map-marker-alt text-danger"></i> Suivi en direct du livreur</h5>
                    <div id="trackingMap" style="height: 350px; border-radius: 8px; overflow: hidden; border: 1px solid #eee;"></div>
                    
                    <div id="driverCard" class="mt-3 p-3 bg-light rounded" style="display: none;">
                        <div class="d-flex align-items-center">
                            <div class="bg-white rounded-circle p-2 shadow-sm mr-3">
                                <i class="fa fa-motorcycle fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0" id="driverName">--</h6>
                                <small class="text-muted">Votre livreur est en route</small>
                            </div>
                            <div class="ml-auto">
                                <a href="#" id="driverPhoneBtn" class="btn btn-success btn-sm rounded-pill px-3">
                                    <i class="fa fa-phone"></i> Appeler
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5><i class="fa fa-map-marker text-danger"></i> Expéditeur (Ramassage)</h5>
                        <p class="mt-2">
                            <strong>{{ $shipment->pickupAddress()->full_name }}</strong><br>
                            {{ $shipment->pickupAddress()->phone }}<br>
                            {{ $shipment->pickupAddress()->address_line }}, {{ $shipment->pickupAddress()->district }}<br>
                            {{ $shipment->pickupAddress()->city }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fa fa-truck text-success"></i> Destinataire (Livraison)</h5>
                        <p class="mt-2">
                            <strong>{{ $shipment->dropoffAddress()->full_name }}</strong><br>
                            {{ $shipment->dropoffAddress()->phone }}<br>
                            {{ $shipment->dropoffAddress()->address_line }}, {{ $shipment->dropoffAddress()->district }}<br>
                            {{ $shipment->dropoffAddress()->city }}
                        </p>
                    </div>
                </div>

                <h4 class="mt-5 mb-4">Historique du colis</h4>
                <div class="timeline-container">
                    @foreach($shipment->events->sortByDesc('created_at') as $event)
                    <div class="timeline-item">
                        <div class="timeline-date">{{ $event->created_at->format('d/m/Y H:i') }}</div>
                        <strong>{{ $event->status->label() }}</strong>
                        <p class="text-muted">{{ $event->notes }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light"><strong>Détails de facturation</strong></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>N° Tracking :</span>
                        <code>{{ $shipment->tracking_number }}</code>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Poids :</span>
                        <span>{{ $shipment->weight_kg }} kg</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Service :</span>
                        <span>{{ ucfirst($shipment->service_level) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>TOTAL :</strong>
                        <strong class="text-success">{{ number_format($shipment->total_price, 0, ',', ' ') }} FCFA</strong>
                    </div>
                </div>
            </div>

            @if($shipment->status->value == 'created')
            <form action="{{ url('/colis/shipments/'.$shipment->id.'/cancel') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-block" onclick="return confirm('Êtes-vous sûr de vouloir annuler cet envoi ?')">
                    Annuler l'envoi
                </button>
            </form>
        @endsection

@section('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ google_maps_api_key() }}&libraries=places"></script>
<script>
    let map, pickupMarker, dropoffMarker, driverMarker;
    const trackingNumber = '{{ $shipment->tracking_number }}';

    function initMap(pickup, dropoff, driver) {
        if (!pickup || !dropoff) return;

        document.getElementById('trackingMapContainer').style.display = 'block';

        const mapOptions = {
            zoom: 12,
            center: { lat: parseFloat(pickup.lat), lng: parseFloat(pickup.lng) },
            mapTypeId: 'roadmap',
            styles: [
                { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }
            ]
        };

        map = new google.maps.Map(document.getElementById('trackingMap'), mapOptions);

        pickupMarker = new google.maps.Marker({
            position: { lat: parseFloat(pickup.lat), lng: parseFloat(pickup.lng) },
            map: map,
            title: 'Point de ramassage',
            icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
        });

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
                title: 'Livreur',
                icon: 'https://maps.google.com/mapfiles/ms/icons/motorcycle.png',
                animation: google.maps.Animation.BOUNCE
            });
        }
    }

    async function pollTracking() {
        try {
            const response = await fetch(`/api/v1/colis/track/${trackingNumber}`);
            const data = await response.json();

            if (data.status) {
                // Mise à jour de la bannière de statut
                const banner = document.getElementById('currentStatusBanner');
                banner.textContent = 'Statut actuel : ' + data.status_label;
                banner.className = 'status-banner status-' + data.status.value;
                
                // Mise à jour de la carte
                if (data.locations.pickup && data.locations.dropoff) {
                    if (!map) {
                        initMap(data.locations.pickup, data.locations.dropoff, data.locations.current_driver);
                    } else if (data.locations.current_driver) {
                        updateDriverOnMap(data.locations.current_driver);
                    }
                }

                // Infos livreur
                const driverCard = document.getElementById('driverCard');
                if (data.courier && data.locations.current_driver) {
                    driverCard.style.display = 'block';
                    document.getElementById('driverName').textContent = data.courier.name;
                    document.getElementById('driverPhoneBtn').href = 'tel:' + data.courier.phone;
                } else {
                    driverCard.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Erreur poll tracking:', error);
        }
    }

    window.addEventListener('load', () => {
        // Chargement initial
        const pickupLat = '{{ $shipment->pickupAddress()->lat }}';
        const dropoffLat = '{{ $shipment->dropoffAddress()->lat }}';
        
        if (pickupLat && dropoffLat) {
            initMap(
                { lat: pickupLat, lng: '{{ $shipment->pickupAddress()->lng }}' },
                { lat: dropoffLat, lng: '{{ $shipment->dropoffAddress()->lng }}' },
                null
            );
        }
        
        pollTracking();
        setInterval(pollTracking, 10000);
    });
</script>
@endsection

