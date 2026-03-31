@extends('frontend.layouts.app-modern')

@section('title', 'Suivi de colis | BantuDelice')
@section('description', 'Suivre un colis BantuDelice via la page publique de tracking, avec un parcours colis distinct du food delivery.')

@php
    $paymentExperience = $paymentExperience ?? null;
@endphp

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
@endsection

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
                                       placeholder="Entrez votre numéro de suivi (ex: BD-COLIS-20260319-0001)"
                                       value="{{ $trackingNumber ?? '' }}" required>
                                <div class="input-group-append">
                                    <button style="display:inline-flex;align-items:center;justify-content:center;background:#16a34a;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;" type="submit"><i class="fa fa-search"></i> Rechercher</button>
                                </div>
                            </div>
                        </form>

                        @if($trackingNumber && !$shipment)
                            <div class="alert alert-danger text-center">
                                <i class="fa fa-exclamation-triangle"></i> Aucun colis n'a été retrouvé avec le numéro <strong>{{ $trackingNumber }}</strong>. Vérifiez le numéro saisi puis réessayez.
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

                                <div class="d-flex justify-content-between align-items-center bg-light rounded border px-3 py-3 mb-4">
                                    <div>
                                        <small class="text-muted d-block">Statut du paiement</small>
                                        <strong id="currentPaymentBadge">{{ $paymentExperience['status'] ?? strtoupper($shipment->payment_status ?? 'pending') }}</strong>
                                        <small id="currentPaymentMessage" class="d-block text-muted mt-2">{{ $paymentExperience['customer_message'] ?? 'Confirmation de paiement en attente.' }}</small>
                                        <small id="currentPaymentFailureReason" class="d-block text-danger">{{ !empty($paymentExperience['failure_reason']) ? 'Code provider: ' . $paymentExperience['failure_reason'] : '' }}</small>
                                    </div>
                                    <div class="text-right">
                                        <small class="text-muted d-block">Montant</small>
                                        <strong>{{ number_format($shipment->total_price, 0, ',', ' ') }} {{ $shipment->currency }}</strong>
                                    </div>
                                </div>

                                <div id="trackingMapContainer" style="display: none; margin-bottom: 2rem;">
                                    <h4 class="mb-3"><i class="fa fa-map-marker-alt text-primary"></i> Suivi en direct</h4>
                                    <div id="trackingMap" style="height: 400px; border-radius: 12px; overflow: hidden; border: 1px solid #ddd;"></div>
                                    <div id="mapUnavailable" class="alert alert-light border mt-3 mb-0" style="display: none;">
                                        La carte n'est pas disponible pour le moment. Ajoutez <strong>MAPBOX_PUBLIC_TOKEN</strong> dans la configuration pour activer l'affichage cartographique.
                                    </div>
                                    <div id="driverInfo" class="mt-3 p-3 bg-light rounded border" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                                    <i class="fa fa-motorcycle"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 font-weight-bold" id="driverName">--</h6>
                                                <p class="mb-0 text-muted small">Le coursier est actuellement en route</p>
                                            </div>
                                            <div class="ml-auto">
                                                <button type="button" id="driverPhoneLink" style="display:inline-flex;align-items:center;background:transparent;color:#16a34a;font-weight:600;font-size:.8rem;padding:.4rem 1rem;border-radius:999px;border:1.5px solid #bbf7d0;cursor:pointer;text-decoration:none;" disabled aria-disabled="true">
                                                    <i class="fa fa-phone"></i> Appeler
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="timeline-wrapper">
                                    <h4 class="mb-4">Historique de l'expédition</h4>
                                    <p class="text-muted mb-4">Chaque mise à jour confirme une étape du traitement ou de l'acheminement de votre colis.</p>
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
    .tracking-map-driver-icon,
    .tracking-map-pickup-icon,
    .tracking-map-dropoff-icon {
        border-radius: 999px;
        width: 18px;
        height: 18px;
        border: 3px solid #fff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
    }
    .tracking-map-pickup-icon { background: #2563eb; }
    .tracking-map-dropoff-icon { background: #ef4444; }
    .tracking-map-driver-icon { background: #0f172a; width: 16px; height: 16px; }
</style>

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let map, pickupMarker, dropoffMarker, driverMarker;
    const trackingNumber = '{{ $trackingNumber ?? '' }}';
    const MAPBOX_TOKEN = @json(mapbox_public_token());
    const INITIAL_PAYMENT_EXPERIENCE = @json($paymentExperience);

    function renderPaymentExperience(experience, fallbackStatus) {
        const paymentBadge = document.getElementById('currentPaymentBadge');
        const paymentMessage = document.getElementById('currentPaymentMessage');
        const failureReason = document.getElementById('currentPaymentFailureReason');

        if (paymentBadge) {
            paymentBadge.textContent = experience?.status || String(fallbackStatus || 'pending').toUpperCase();
        }

        if (paymentMessage) {
            paymentMessage.textContent = experience?.customer_message || 'Confirmation de paiement en attente.';
        }

        if (failureReason) {
            failureReason.textContent = experience?.failure_reason ? `Code provider: ${experience.failure_reason}` : '';
            failureReason.style.display = failureReason.textContent ? 'block' : 'none';
        }
    }

    function pointIcon(className) {
        return L.divIcon({
            className: '',
            html: `<div class="${className}"></div>`,
            iconSize: [18, 18],
            iconAnchor: [9, 9]
        });
    }

    function showMapUnavailable() {
        const container = document.getElementById('trackingMapContainer');
        const notice = document.getElementById('mapUnavailable');
        if (container) container.style.display = 'block';
        if (notice) notice.style.display = 'block';
    }

    function initMap(pickup, dropoff, driver) {
        if (!pickup || !dropoff) return;

        document.getElementById('trackingMapContainer').style.display = 'block';

        if (!MAPBOX_TOKEN) {
            showMapUnavailable();
            return;
        }

        if (!map) {
            map = L.map('trackingMap', { zoomControl: true, scrollWheelZoom: false });
            L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' + MAPBOX_TOKEN, {
                tileSize: 512,
                zoomOffset: -1,
                attribution: '&copy; OpenStreetMap contributors &copy; Mapbox',
                maxZoom: 19
            }).addTo(map);
        }

        const pickupLatLng = [parseFloat(pickup.lat), parseFloat(pickup.lng)];
        const dropoffLatLng = [parseFloat(dropoff.lat), parseFloat(dropoff.lng)];

        if (pickupMarker) {
            pickupMarker.setLatLng(pickupLatLng);
        } else {
            pickupMarker = L.marker(pickupLatLng, { icon: pointIcon('tracking-map-pickup-icon') }).addTo(map).bindTooltip('Point de ramassage');
        }

        if (dropoffMarker) {
            dropoffMarker.setLatLng(dropoffLatLng);
        } else {
            dropoffMarker = L.marker(dropoffLatLng, { icon: pointIcon('tracking-map-dropoff-icon') }).addTo(map).bindTooltip('Point de livraison');
        }

        const bounds = L.latLngBounds([pickupLatLng, dropoffLatLng]);

        if (driver && driver.lat && driver.lng) {
            updateDriverOnMap(driver);
            bounds.extend([parseFloat(driver.lat), parseFloat(driver.lng)]);
        }

        map.fitBounds(bounds.pad(0.2));
    }

    function updateDriverOnMap(driver) {
        if (!driver || !driver.lat || !driver.lng || !map) return;

        const driverLatLng = [parseFloat(driver.lat), parseFloat(driver.lng)];

        if (driverMarker) {
            driverMarker.setLatLng(driverLatLng);
        } else {
            driverMarker = L.marker(driverLatLng, { icon: pointIcon('tracking-map-driver-icon') }).addTo(map).bindTooltip('Coursier en mouvement');
        }
    }

    async function pollTracking() {
        if (!trackingNumber) return;

        try {
            const response = await fetch(`/api/v1/colis/track/${trackingNumber}`);
            const data = await response.json();

            if (data.status) {
                const badge = document.getElementById('currentStatusBadge');
                if (badge) {
                    badge.textContent = data.status_label;
                }
                renderPaymentExperience(data.payment_experience || null, data.payment_status);

                if (data.locations && data.locations.pickup && data.locations.dropoff) {
                    initMap(data.locations.pickup, data.locations.dropoff, data.locations.current_driver);
                    if (data.locations.current_driver) {
                        updateDriverOnMap(data.locations.current_driver);
                    }
                }

                const driverDiv = document.getElementById('driverInfo');
                if (driverDiv && data.courier && data.locations && data.locations.current_driver) {
                    driverDiv.style.display = 'block';
                    document.getElementById('driverName').textContent = data.courier.name;
                    const driverPhoneLink = document.getElementById('driverPhoneLink');
                    driverPhoneLink.disabled = false;
                    driverPhoneLink.setAttribute('aria-disabled', 'false');
                    driverPhoneLink.onclick = () => {
                        window.location.href = 'tel:' + data.courier.phone;
                    };
                } else if (driverDiv) {
                    driverDiv.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Erreur de tracking:', error);
        }
    }

    @if($shipment)
        window.addEventListener('load', () => {
            renderPaymentExperience(INITIAL_PAYMENT_EXPERIENCE, '{{ $shipment->payment_status ?? 'pending' }}');
            const initialData = {
                pickup: { lat: '{{ $shipment->pickupAddress()->lat }}', lng: '{{ $shipment->pickupAddress()->lng }}' },
                dropoff: { lat: '{{ $shipment->dropoffAddress()->lat }}', lng: '{{ $shipment->dropoffAddress()->lng }}' },
                driver: null
            };

            if (initialData.pickup.lat && initialData.dropoff.lat) {
                initMap(initialData.pickup, initialData.dropoff, initialData.driver);
            }

            pollTracking();
            setInterval(pollTracking, 10000);
        });
    @endif
</script>
@endsection
@endsection
