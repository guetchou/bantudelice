@extends('frontend.layouts.colis')
@section('title', 'Détail du colis | Mema')
@section('description', 'Suivez l\'état de votre colis en temps réel avec Mema.')

@php
    $paymentExperience = $paymentExperience ?? null;
    $shipmentQrTarget = route('colis.track_public', ['tracking_number' => $shipment->tracking_number]);
    $shipmentCancelUrl = route('colis.cancel', ['id' => $shipment->id]);
@endphp

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

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
@endsection

@section('content')
<div class="container my-5" style="margin-top: 100px !important;">
    <div class="row">
        <div class="col-md-12 mb-4">
            <a href="{{ url('/mes-colis') }}" style="display:inline-flex;align-items:center;background:transparent;color:#009543;font-weight:600;border:none;cursor:pointer;text-decoration:none;padding:0;"><i class="fa fa-arrow-left"></i> Retour à mes envois</a>
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
                                <button type="button" id="driverPhoneBtn" style="display:inline-flex;align-items:center;background:#009543;color:#fff;font-weight:600;font-size:.8rem;padding:.4rem 1rem;border-radius:999px;border:none;cursor:pointer;" disabled aria-disabled="true">
                                    <i class="fa fa-phone"></i> Appeler
                                </button>
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

                <div class="card border-0 bg-light mb-4">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:12px;">
                            <div>
                                <small class="text-muted d-block">Statut du paiement</small>
                                <strong id="shipmentPaymentStatusLabel">{{ $paymentExperience['status'] ?? strtoupper($shipment->payment_status ?? 'pending') }}</strong>
                                <small id="shipmentPaymentMessage" class="d-block text-muted mt-2">{{ $paymentExperience['customer_message'] ?? 'Confirmation de paiement en attente.' }}</small>
                                <small id="shipmentPaymentSupportAction" class="d-block text-muted">{{ $paymentExperience['support_action'] ?? '' }}</small>
                                <small id="shipmentPaymentFailureReason" class="d-block text-danger">{{ !empty($paymentExperience['failure_reason']) ? 'Code provider: ' . $paymentExperience['failure_reason'] : '' }}</small>
                            </div>
                            <div>
                                <small class="text-muted d-block">Montant</small>
                                <strong>{{ number_format($shipment->total_price, 0, ',', ' ') }} {{ $shipment->currency }}</strong>
                            </div>
                        </div>
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
                    <hr>
                    <div class="text-center">
                        <small class="text-muted d-block mb-2">QR de suivi colis</small>
                        <div style="display:inline-flex; align-items:center; justify-content:center; width:160px; min-height:160px; max-width:100%; background:#fff; border-radius:14px; padding:8px; border:1px solid #e5e7eb;">
                            {!! QrCode::format('svg')->size(148)->margin(1)->generate($shipmentQrTarget) !!}
                        </div>
                        <div class="mt-2"><code>{{ $shipment->tracking_number }}</code></div>
                        <a href="{{ $shipmentQrTarget }}" style="display:inline-flex;align-items:center;background:transparent;color:#009543;font-weight:600;font-size:.82rem;padding:.2rem .5rem;border:none;cursor:pointer;text-decoration:none;">Ouvrir le suivi public</a>
                    </div>
                </div>
            </div>

            <button type="button" style="width:100%;display:inline-flex;align-items:center;justify-content:center;background:#fff;color:#64748b;font-weight:600;padding:.8rem 1.5rem;border-radius:14px;border:1.5px solid #cbd5e1;cursor:pointer;margin-bottom:.75rem;" onclick="window.print()">
                Imprimer le reçu
            </button>

            @if($shipment->status->value == 'created')
            <form action="{{ $shipmentCancelUrl }}" method="POST">
                @csrf
                <button type="submit" style="width:100%;display:inline-flex;align-items:center;justify-content:center;background:transparent;color:#dc2626;font-weight:700;padding:.8rem 1.5rem;border-radius:14px;border:2px solid #fca5a5;cursor:pointer;" onclick="return confirm('Êtes-vous sûr de vouloir annuler cet envoi ?')">
                    Annuler l'envoi
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ google_maps_api_key() }}&libraries=places"></script>
<script>
    let map, pickupMarker, dropoffMarker, driverMarker;
    const trackingNumber = '{{ $shipment->tracking_number }}';
    const INITIAL_PAYMENT_EXPERIENCE = @json($paymentExperience);

    function renderPaymentExperience(experience, fallbackStatus) {
        const paymentLabel = document.getElementById('shipmentPaymentStatusLabel');
        const paymentMessage = document.getElementById('shipmentPaymentMessage');
        const supportAction = document.getElementById('shipmentPaymentSupportAction');
        const failureReason = document.getElementById('shipmentPaymentFailureReason');

        if (paymentLabel) {
            paymentLabel.textContent = experience?.status || String(fallbackStatus || 'pending').toUpperCase();
        }

        if (paymentMessage) {
            paymentMessage.textContent = experience?.customer_message || 'Confirmation de paiement en attente.';
        }

        if (supportAction) {
            supportAction.textContent = experience?.support_action || '';
            supportAction.style.display = supportAction.textContent ? 'block' : 'none';
        }

        if (failureReason) {
            failureReason.textContent = experience?.failure_reason ? `Code provider: ${experience.failure_reason}` : '';
            failureReason.style.display = failureReason.textContent ? 'block' : 'none';
        }
    }

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

    function showShipmentPollingError(message) {
        const banner = document.getElementById('currentStatusBanner');
        if (banner) {
            banner.textContent = message || 'Statut actuel : mise a jour indisponible';
            banner.className = 'status-banner status-canceled';
        }

        renderPaymentExperience({
            status: 'ATTENTION',
            customer_message: message || 'Impossible de verifier le suivi Mema pour le moment.',
        }, 'pending');
    }

    function shipmentTrackingUrl() {
        const url = new URL(`/api/v1/colis/track/${trackingNumber}`, window.location.origin);
        url.searchParams.set('_ts', Date.now().toString());
        return url.toString();
    }

    async function pollTracking() {
        try {
            const response = await fetch(shipmentTrackingUrl(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                showShipmentPollingError(data?.message || 'Le serveur Mema n’a pas confirme un statut 200 pour ce colis.');
                return;
            }

            if (data.tracking_number && data.status && data.status_label) {
                // Mise à jour de la bannière de statut
                const banner = document.getElementById('currentStatusBanner');
                banner.textContent = 'Statut actuel : ' + data.status_label;
                const statusValue = typeof data.status === 'string' ? data.status : (data.status?.value || 'created');
                banner.className = 'status-banner status-' + statusValue;
                
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
                    const phoneBtn = document.getElementById('driverPhoneBtn');
                    phoneBtn.disabled = false;
                    phoneBtn.setAttribute('aria-disabled', 'false');
                    phoneBtn.onclick = () => {
                        window.location.href = 'tel:' + data.courier.phone;
                    };
                } else {
                    driverCard.style.display = 'none';
                }

                renderPaymentExperience(data.payment_experience || null, data.payment_status);
            } else {
                showShipmentPollingError(data?.message || 'Aucune confirmation exploitable n’a ete retournee par le suivi Mema.');
            }
        } catch (error) {
            console.error('Erreur poll tracking:', error);
            showShipmentPollingError('Impossible de verifier le suivi Mema pour le moment.');
        }
    }

    window.addEventListener('load', () => {
        renderPaymentExperience(INITIAL_PAYMENT_EXPERIENCE, '{{ $shipment->payment_status ?? 'pending' }}');
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

<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.5.0/dist/web/pusher.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    var key = @json(config('broadcasting.connections.pusher.key', ''));
    if (!key || typeof Pusher === 'undefined') return;

    var shipmentId = @json((int) $shipment->id);
    var lastRecordedAt = 0;
    var pusher = new Pusher(key, {
        wsHost: window.location.hostname,
        wsPort: 443,
        wssPort: 443,
        forceTLS: true,
        disableStats: true,
        enabledTransports: ['wss'],
        authEndpoint: '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } }
    });

    function acceptLocation(location) {
        if (!location || location.lat === null || location.lng === null) return false;
        var recordedAt = location.recorded_at ? Date.parse(location.recorded_at) : Date.now();
        if (!Number.isFinite(recordedAt) || recordedAt < lastRecordedAt) return false;
        lastRecordedAt = recordedAt;
        return true;
    }

    var presence = pusher.subscribe('private-colis.shipment.' + shipmentId + '.presence');
    presence.bind('colis.shipment.presence.updated', function (data) {
        if (!data || !acceptLocation(data.location)) return;
        updateDriverOnMap({ lat: Number(data.location.lat), lng: Number(data.location.lng) });
        var container = document.getElementById('trackingMapContainer');
        if (container) container.style.display = 'block';
    });

    var status = pusher.subscribe('private-colis.shipment.' + shipmentId + '.status');
    status.bind('colis.shipment.status.updated', function (data) {
        if (!data || !data.status) return;
        var banner = document.getElementById('currentStatusBanner');
        if (banner) {
            banner.textContent = 'Statut actuel : ' + (data.status_label || data.status);
            banner.className = 'status-banner status-' + data.status;
        }
        pollTracking();
    });
})();
</script>
@endsection
