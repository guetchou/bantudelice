@extends('frontend.layouts.app-modern')
@section('title', 'Suivi de réservation #' . $booking->booking_no . ' | Kende')

@php
    $statusLabels = [
        'requested' => 'Demande envoyee',
        'assigned' => 'Chauffeur assigne',
        'driver_arriving' => 'Chauffeur en approche',
        'picked_up' => 'Prise en charge effectuee',
        'in_progress' => 'Course en cours',
        'completed' => 'Course terminee',
        'paid' => 'Paiement confirme',
        'closed' => 'Course cloturee',
        'cancelled' => 'Course annulee',
    ];

    $timeline = [
        ['key' => 'requested', 'label' => 'Demande envoyee', 'icon' => 'paper-plane'],
        ['key' => 'assigned', 'label' => 'Chauffeur assigne', 'icon' => 'user-check'],
        ['key' => 'driver_arriving', 'label' => 'Chauffeur en approche', 'icon' => 'car-side'],
        ['key' => 'picked_up', 'label' => 'Prise en charge', 'icon' => 'handshake'],
        ['key' => 'in_progress', 'label' => 'Course en cours', 'icon' => 'route'],
        ['key' => 'completed', 'label' => 'Course terminee', 'icon' => 'flag-checkered'],
    ];
@endphp

@php
    $paymentExperience = $paymentExperience ?? null;
@endphp

@section('styles')
<style>
    .trip-shell {
        background:
            radial-gradient(circle at top left, rgba(255,90,31,0.12), transparent 28%),
            linear-gradient(180deg, #fff8f3 0%, #f8fafc 45%, #ffffff 100%);
        padding: 96px 0 56px;
    }

    .trip-hero {
        margin-bottom: 24px;
        padding: 30px 32px;
        border-radius: 34px;
        color: #ffffff;
        background: linear-gradient(135deg, #ff5a1f 0%, #f59e0b 60%, #facc15 100%);
        box-shadow: 0 26px 70px rgba(245, 158, 11, 0.24);
    }

    .trip-hero__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.22);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .trip-hero h1 {
        margin: 16px 0 8px;
        font-size: clamp(2rem, 4vw, 3.2rem);
        line-height: 0.95;
        letter-spacing: -0.04em;
        font-weight: 900;
    }

    .trip-hero p {
        margin: 0;
        max-width: 760px;
        color: rgba(255,255,255,0.9);
        line-height: 1.75;
        font-size: 1rem;
    }

    .trip-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(340px, 0.8fr);
        gap: 24px;
        align-items: start;
    }

    .trip-card {
        background: rgba(255,255,255,0.98);
        border-radius: 32px;
        border: 1px solid rgba(15,23,42,0.06);
        box-shadow: 0 24px 70px rgba(15,23,42,0.08);
    }

    .trip-map-card {
        overflow: hidden;
    }

    #bookingMap {
        width: 100%;
        height: 440px;
    }

    .trip-map-meta {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        padding: 20px;
        border-top: 1px solid rgba(15,23,42,0.06);
        background: #ffffff;
    }

    .trip-map-stat {
        padding: 14px 16px;
        border-radius: 20px;
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,0.05);
    }

    .trip-map-stat small {
        display: block;
        color: #64748b;
        margin-bottom: 6px;
        font-size: 0.78rem;
    }

    .trip-map-stat strong {
        display: block;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 900;
    }

    .trip-timeline-card,
    .trip-side-card {
        padding: 26px;
    }

    .trip-card__title {
        margin: 0 0 16px;
        color: #0f172a;
        font-size: 1.22rem;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .trip-timeline {
        display: grid;
        gap: 18px;
    }

    .trip-step {
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr);
        gap: 14px;
        align-items: start;
    }

    .trip-step__icon {
        width: 48px;
        height: 48px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e5e7eb;
        color: #64748b;
        font-size: 1rem;
    }

    .trip-step.is-done .trip-step__icon,
    .trip-step.is-active .trip-step__icon {
        background: #009543;
        color: #ffffff;
    }

    .trip-step.is-active .trip-step__copy {
        border-color: rgba(16,185,129,0.24);
        background: #ecfdf5;
    }

    .trip-step__copy {
        padding: 14px 16px;
        border-radius: 20px;
        border: 1px solid rgba(15,23,42,0.06);
        background: #ffffff;
    }

    .trip-step__copy strong {
        display: block;
        color: #0f172a;
        font-size: 0.96rem;
        font-weight: 800;
    }

    .trip-step__copy small {
        display: block;
        margin-top: 5px;
        color: #64748b;
        line-height: 1.5;
    }

    .trip-side-card {
        position: sticky;
        top: 110px;
        display: grid;
        gap: 16px;
    }

    .trip-driver-card,
    .trip-fare-card,
    .trip-route-card {
        padding: 18px;
        border-radius: 24px;
        background: #ffffff;
        border: 1px solid rgba(15,23,42,0.06);
    }

    .trip-driver-head {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .trip-driver-avatar {
        width: 62px;
        height: 62px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0f766e 0%, #0284c7 100%);
        color: #ffffff;
        font-size: 1.2rem;
        font-weight: 900;
        flex-shrink: 0;
    }

    .trip-driver-head strong {
        display: block;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 900;
    }

    .trip-driver-head small,
    .trip-driver-meta small,
    .trip-route-card small {
        color: #64748b;
    }

    .trip-driver-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
        padding: 0 12px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,0.08);
        font-weight: 800;
        font-size: 0.78rem;
        color: #0f172a;
    }

    .trip-driver-chip.is-online {
        color: #047857;
        background: #ecfdf5;
        border-color: rgba(16,185,129,0.22);
    }

    .trip-driver-chip.is-waiting {
        color: #92400e;
        background: #fff7ed;
        border-color: rgba(245,158,11,0.22);
    }

    .trip-driver-meta {
        margin-top: 14px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .trip-driver-meta article {
        padding: 12px 14px;
        border-radius: 18px;
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,0.05);
    }

    .trip-driver-meta strong {
        display: block;
        margin-top: 4px;
        color: #0f172a;
        font-size: 0.96rem;
        font-weight: 900;
    }

    .trip-call-btn {
        width: 100%;
        min-height: 50px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(135deg, #059669 0%, #009543 100%);
        color: #ffffff;
        font-weight: 900;
        text-decoration: none !important;
        margin-top: 14px;
    }

    .trip-fare-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
        color: #64748b;
    }

    .trip-fare-row strong {
        color: #0f172a;
    }

    .trip-fare-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding-top: 14px;
        margin-top: 14px;
        border-top: 1px solid rgba(15,23,42,0.08);
    }

    .trip-fare-total strong:last-child {
        color: #ff5a1f;
        font-size: 1.5rem;
        font-weight: 900;
        letter-spacing: -0.04em;
    }

    .trip-route-point {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .trip-route-point:last-child {
        margin-bottom: 0;
    }

    .trip-route-pin {
        width: 14px;
        height: 14px;
        border-radius: 999px;
        margin-top: 4px;
        flex-shrink: 0;
    }

    .trip-route-pin.is-pickup {
        background: #009543;
        box-shadow: 0 0 0 6px rgba(16,185,129,0.14);
    }

    .trip-route-pin.is-dropoff {
        background: #ef4444;
        box-shadow: 0 0 0 6px rgba(239,68,68,0.12);
    }

    .trip-route-point strong {
        display: block;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .trip-route-point span {
        display: block;
        margin-top: 4px;
        color: #0f172a;
        font-weight: 700;
        line-height: 1.5;
    }

    .trip-pay-btn {
        width: 100%;
        min-height: 52px;
        border: 0;
        border-radius: 18px;
        background: #111827;
        color: #ffffff;
        font-weight: 900;
        margin-top: 14px;
    }

    .trip-pay-phone {
        width: 100%;
        min-height: 52px;
        margin-top: 14px;
        padding: 0 16px;
        border-radius: 16px;
        border: 1px solid rgba(15,23,42,0.1);
        background: #ffffff;
        color: #0f172a;
        font-weight: 700;
    }

    @media (max-width: 1180px) {
        .trip-layout {
            grid-template-columns: 1fr;
        }

        .trip-side-card {
            position: static;
            top: auto;
        }
    }

    @media (max-width: 768px) {
        .trip-shell {
            padding-top: 88px;
        }

        .trip-hero,
        .trip-timeline-card,
        .trip-side-card {
            padding: 22px;
        }

        .trip-map-meta,
        .trip-driver-meta {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
@endsection

@section('content')
<section class="trip-shell">
    <div class="container">
        <section class="trip-hero">
            <span class="trip-hero__eyebrow"><i class="fas fa-route"></i> Suivi en direct</span>
            <h1>Votre course taxi, de la demande au chauffeur.</h1>
            <p>Reservation <strong>#{{ $booking->booking_no }}</strong> · Statut actuel: <strong id="heroStatusLabel">{{ $statusLabels[$booking->status->value] ?? ucfirst($booking->status->value) }}</strong>.</p>
        </section>

        <div class="trip-layout">
            <div style="display:grid; gap:24px;">
                <section class="trip-card trip-map-card">
                    <div id="bookingMap"></div>
                    <div class="trip-map-meta">
                        <article class="trip-map-stat">
                            <small>Etat chauffeur</small>
                            <strong id="driverAvailability">{{ $booking->driver ? ($booking->driver->status ?? 'inconnu') : 'En attente' }}</strong>
                        </article>
                        <article class="trip-map-stat">
                            <small>ETA</small>
                            <strong id="driverEta">-- min</strong>
                        </article>
                        <article class="trip-map-stat">
                            <small>Distance restante</small>
                            <strong id="driverDistance">-- km</strong>
                        </article>
                        <article class="trip-map-stat">
                            <small>Derniere mise a jour</small>
                            <strong id="driverLastUpdate">--</strong>
                        </article>
                    </div>
                </section>

                <section class="trip-card trip-timeline-card">
                    <h2 class="trip-card__title">Progression de la course</h2>
                    <div class="trip-timeline" id="tripTimeline">
                        @php
                            $currentStatusIndex = array_search($booking->status->value, array_column($timeline, 'key'), true);
                        @endphp
                        @foreach($timeline as $index => $step)
                            @php
                                $isDone = $currentStatusIndex !== false && $index <= $currentStatusIndex;
                                $isActive = $booking->status->value === $step['key'];
                            @endphp
                            <article class="trip-step{{ $isDone ? ' is-done' : '' }}{{ $isActive ? ' is-active' : '' }}" data-status-step="{{ $step['key'] }}">
                                <span class="trip-step__icon"><i class="fas fa-{{ $step['icon'] }}"></i></span>
                                <div class="trip-step__copy">
                                    <strong>{{ $step['label'] }}</strong>
                                    <small>{{ $isActive ? 'Etape actuelle' : 'En attente de validation workflow' }}</small>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="trip-card trip-side-card">
                <section class="trip-driver-card">
                    @if($booking->driver)
                        <div class="trip-driver-head">
                            <span class="trip-driver-avatar">{{ strtoupper(substr($booking->driver->name, 0, 2)) }}</span>
                            <div>
                                <strong id="driverName">{{ $booking->driver->name }}</strong>
                                <small>Votre chauffeur Kende</small>
                            </div>
                        </div>
                        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
                            <span class="trip-driver-chip {{ ($booking->driver->status ?? null) === 'online' ? 'is-online' : 'is-waiting' }}" id="driverStatusChip">
                                <i class="fas fa-signal"></i> {{ $booking->driver->status ?? 'offline' }}
                            </span>
                            <span class="trip-driver-chip" id="bookingStatusChip">
                                <i class="fas fa-info-circle"></i> {{ $statusLabels[$booking->status->value] ?? ucfirst($booking->status->value) }}
                            </span>
                        </div>
                        <div class="trip-driver-meta">
                            <article>
                                <small>Vehicule</small>
                                <strong id="driverVehicle">{{ $booking->vehicle ? trim(($booking->vehicle->make ?? '') . ' ' . ($booking->vehicle->model ?? '')) : 'Non renseigne' }}</strong>
                            </article>
                            <article>
                                <small>Immatriculation</small>
                                <strong id="driverPlate">{{ $booking->vehicle->plate_number ?? 'A venir' }}</strong>
                            </article>
                        </div>
                        @if($booking->driver->phone)
                            <a href="tel:{{ $booking->driver->phone }}" class="trip-call-btn">
                                <i class="fas fa-phone"></i> Appeler le chauffeur
                            </a>
                        @endif
                    @else
                        <div class="trip-driver-head">
                            <span class="trip-driver-avatar" style="background:linear-gradient(135deg,#64748b 0%,#94a3b8 100%);">..</span>
                            <div>
                                <strong>Recherche de chauffeur</strong>
                                <small>Nous cherchons le chauffeur le plus proche.</small>
                            </div>
                        </div>
                        <div style="margin-top:14px;">
                            <span class="trip-driver-chip is-waiting" id="driverStatusChip"><i class="fas fa-spinner"></i> En attente d attribution</span>
                        </div>
                    @endif
                </section>

                <section class="trip-route-card">
                    <h2 class="trip-card__title" style="font-size:1.05rem; margin-bottom:14px;">Trajet</h2>
                    <div class="trip-route-point">
                        <span class="trip-route-pin is-pickup"></span>
                        <div>
                            <strong>Depart</strong>
                            <span>{{ $booking->pickup_address }}</span>
                        </div>
                    </div>
                    <div class="trip-route-point">
                        <span class="trip-route-pin is-dropoff"></span>
                        <div>
                            <strong>Arrivee</strong>
                            <span>{{ $booking->dropoff_address }}</span>
                        </div>
                    </div>
                </section>

                <section class="trip-fare-card">
                    <h2 class="trip-card__title" style="font-size:1.05rem; margin-bottom:14px;">Paiement</h2>
                    <div class="trip-fare-row">
                        <span>Prix estime</span>
                        <strong>{{ number_format($booking->estimated_price, 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div class="trip-fare-row">
                        <span>Methode</span>
                        <strong>{{ $booking->payment_method === 'cash' ? 'Especes' : 'Mobile Money' }}</strong>
                    </div>
                    <div class="trip-fare-row">
                        <span>Statut paiement</span>
                        <strong id="paymentStatusLabel">{{ $paymentExperience['status'] ?? strtoupper($booking->payment_status ?? 'pending') }}</strong>
                    </div>
                    <div class="trip-fare-note">
                        <small id="paymentCustomerMessage">{{ $paymentExperience['customer_message'] ?? 'Confirmation de paiement en attente.' }}</small>
                        <small id="paymentSupportAction" style="display:block; margin-top:8px;">{{ $paymentExperience['support_action'] ?? '' }}</small>
                        <small id="paymentFailureReason" style="display:block; margin-top:8px; color:#dc2626;">{{ !empty($paymentExperience['failure_reason']) ? 'Code provider: ' . $paymentExperience['failure_reason'] : '' }}</small>
                    </div>
                    <div class="trip-fare-total">
                        <strong>Total</strong>
                        <strong>{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} FCFA</strong>
                    </div>

                    @if($booking->payment_status === 'pending' && $booking->payment_method !== 'cash')
                        <input
                            type="tel"
                            id="transportMomoPhone"
                            class="trip-pay-phone"
                            placeholder="Numero MoMo a debiter"
                            value="{{ auth()->check() ? preg_replace('/\s+/', '', (string) auth()->user()->phone) : '' }}"
                        >
                        <button id="transportPayNowBtn" class="trip-pay-btn" onclick="payNow(event)">Payer maintenant</button>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let map;
    let pickupMarker;
    let dropoffMarker;
    let driverMarker;
    let routeLayer;
    let tripPollTimer = null;
    let paymentPollTimer = null;

    const MAPBOX_TOKEN = @json(mapbox_public_token());
    const BOOKING_UUID = @json($booking->uuid);
    const STATUS_LABELS = @json($statusLabels);
    const INITIAL_BOOKING = @json($booking->loadMissing(['driver', 'vehicle', 'trackingPoints'])->toArray());
    const INITIAL_PAYMENT_EXPERIENCE = @json($paymentExperience);

    function renderPaymentExperience(experience, fallbackStatus) {
        const paymentStatusLabel = document.getElementById('paymentStatusLabel');
        const paymentCustomerMessage = document.getElementById('paymentCustomerMessage');
        const paymentSupportAction = document.getElementById('paymentSupportAction');
        const paymentFailureReason = document.getElementById('paymentFailureReason');
        const payBtn = document.getElementById('transportPayNowBtn');
        const phoneField = document.getElementById('transportMomoPhone');

        if (paymentStatusLabel) {
            paymentStatusLabel.textContent = experience?.status || String(fallbackStatus || 'pending').toUpperCase();
        }

        if (paymentCustomerMessage) {
            paymentCustomerMessage.textContent = experience?.customer_message || 'Confirmation de paiement en attente.';
        }

        if (paymentSupportAction) {
            paymentSupportAction.textContent = experience?.support_action || '';
            paymentSupportAction.style.display = paymentSupportAction.textContent ? 'block' : 'none';
        }

        if (paymentFailureReason) {
            paymentFailureReason.textContent = experience?.failure_reason ? `Code provider: ${experience.failure_reason}` : '';
            paymentFailureReason.style.display = paymentFailureReason.textContent ? 'block' : 'none';
        }

        if (experience?.status === 'PAID' || (experience?.status === 'FAILED' && experience?.retry_allowed === false)) {
            if (payBtn) payBtn.remove();
            if (phoneField) phoneField.remove();
        }
    }

    function initBookingMap() {
        const box = document.getElementById('bookingMap');
        if (!box) return;

        if (!MAPBOX_TOKEN) {
            box.innerHTML = '<div style="padding:1.5rem;color:#64748b;">Carte indisponible. Ajoutez MAPBOX_PUBLIC_TOKEN.</div>';
            return;
        }

        const pickup = [parseFloat(INITIAL_BOOKING.pickup_lat), parseFloat(INITIAL_BOOKING.pickup_lng)];
        const dropoff = [parseFloat(INITIAL_BOOKING.dropoff_lat), parseFloat(INITIAL_BOOKING.dropoff_lng)];

        map = L.map('bookingMap', { zoomControl: true }).setView(pickup, 13);

        L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' + MAPBOX_TOKEN, {
            tileSize: 512,
            zoomOffset: -1,
            attribution: '&copy; OpenStreetMap contributors &copy; Mapbox',
            maxZoom: 19
        }).addTo(map);

        pickupMarker = L.marker(pickup).addTo(map);
        dropoffMarker = L.marker(dropoff).addTo(map);

        drawRoute(pickup, dropoff);
        hydrateLiveTrip(INITIAL_BOOKING);
        startTripPolling();
    }

    async function drawRoute(pickup, dropoff) {
        try {
            const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${pickup[1]},${pickup[0]};${dropoff[1]},${dropoff[0]}?geometries=geojson&overview=full&language=fr&access_token=${MAPBOX_TOKEN}`;
            const response = await fetch(url);
            const data = await response.json();
            if (data.routes && data.routes[0] && data.routes[0].geometry) {
                if (routeLayer) map.removeLayer(routeLayer);
                routeLayer = L.geoJSON(data.routes[0].geometry, {
                    style: { color: '#ff5a1f', weight: 5, opacity: 0.9 }
                }).addTo(map);
                map.fitBounds(routeLayer.getBounds(), { padding: [28, 28] });
                return;
            }
        } catch (e) {
            console.warn('Route draw fallback', e);
        }

        const line = {
            type: 'Feature',
            geometry: {
                type: 'LineString',
                coordinates: [[pickup[1], pickup[0]], [dropoff[1], dropoff[0]]]
            }
        };

        if (routeLayer) map.removeLayer(routeLayer);
        routeLayer = L.geoJSON(line, {
            style: { color: '#ff5a1f', weight: 4, dashArray: '10 8' }
        }).addTo(map);
        map.fitBounds(routeLayer.getBounds(), { padding: [28, 28] });
    }

    function hydrateLiveTrip(booking) {
        const live = booking.live_trip || {};
        const latest = live.latest_tracking_point || null;

        if (booking.driver) {
            document.getElementById('driverAvailability').textContent = normalizeDriverAvailability(live.driver_availability || booking.driver.status || 'offline');
            const statusChip = document.getElementById('driverStatusChip');
            if (statusChip) {
                statusChip.classList.toggle('is-online', (live.driver_availability || booking.driver.status) === 'online');
                statusChip.classList.toggle('is-waiting', (live.driver_availability || booking.driver.status) !== 'online');
                statusChip.innerHTML = `<i class="fas fa-signal"></i> ${normalizeDriverAvailability(live.driver_availability || booking.driver.status || 'offline')}`;
            }
        } else {
            document.getElementById('driverAvailability').textContent = 'En attente';
        }

        document.getElementById('driverEta').textContent = live.eta_minutes ? `${live.eta_minutes} min` : '-- min';
        document.getElementById('driverDistance').textContent = live.remaining_distance_km ? `${Number(live.remaining_distance_km).toFixed(1)} km` : '-- km';
        document.getElementById('driverLastUpdate').textContent = latest && latest.recorded_at ? humanizeDate(latest.recorded_at) : '--';
        document.getElementById('heroStatusLabel').textContent = STATUS_LABELS[booking.status] || booking.status;
        renderPaymentExperience(booking.payment_experience || INITIAL_PAYMENT_EXPERIENCE, booking.payment_status);

        const bookingStatusChip = document.getElementById('bookingStatusChip');
        if (bookingStatusChip) {
            bookingStatusChip.innerHTML = `<i class="fas fa-info-circle"></i> ${STATUS_LABELS[booking.status] || booking.status}`;
        }

        updateTimeline(booking.status);

        if (latest && latest.lat && latest.lng && map) {
            const position = [parseFloat(latest.lat), parseFloat(latest.lng)];
            if (!driverMarker) {
                driverMarker = L.marker(position).addTo(map);
            } else {
                driverMarker.setLatLng(position);
            }
        }
    }

    function normalizeDriverAvailability(value) {
        if (!value) return 'Inconnu';
        if (value === 'online') return 'Disponible';
        if (value === 'offline') return 'Hors ligne';
        return value;
    }

    function updateTimeline(status) {
        const order = ['requested', 'assigned', 'driver_arriving', 'picked_up', 'in_progress', 'completed', 'paid'];
        const currentIndex = order.indexOf(status);
        document.querySelectorAll('[data-status-step]').forEach((node, index) => {
            node.classList.toggle('is-done', currentIndex >= index && currentIndex !== -1);
            node.classList.toggle('is-active', node.dataset.statusStep === status);
            const label = node.querySelector('small');
            if (!label) return;
            if (node.dataset.statusStep === status) {
                label.textContent = 'Etape actuelle';
            } else if (currentIndex >= index && currentIndex !== -1) {
                label.textContent = 'Etape validee';
            } else {
                label.textContent = 'En attente de validation workflow';
            }
        });
    }

    function humanizeDate(value) {
        try {
            return new Date(value).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return '--';
        }
    }

    function startTripPolling() {
        stopTripPolling();
        pollTrip();
    }

    function stopTripPolling() {
        if (tripPollTimer) {
            clearTimeout(tripPollTimer);
            tripPollTimer = null;
        }
    }

    function pollTrip() {
        fetch(`{{ url('transport/xhr/bookings') }}/${BOOKING_UUID}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            credentials: 'same-origin'
        })
        .then((res) => res.json())
        .then((data) => {
            hydrateLiveTrip(data);
            if (!['completed', 'paid', 'closed', 'cancelled'].includes(data.status)) {
                tripPollTimer = setTimeout(pollTrip, 8000);
            }
        })
        .catch(() => {
            tripPollTimer = setTimeout(pollTrip, 12000);
        });
    }

    function stopPaymentPolling() {
        if (paymentPollTimer) {
            clearTimeout(paymentPollTimer);
            paymentPollTimer = null;
        }
    }

    function pollTransportPaymentStatus(paymentId, attempt = 0) {
        fetch(`/api/payments/${paymentId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            const payment = data.payment || {};
            const experience = data.payment_experience || null;
            const status = payment.status;

            if (status === 'PAID') {
                stopPaymentPolling();
                if (typeof showToast === 'function') {
                    showToast('Paiement confirme. Mise a jour de la reservation...', 'success');
                }
                renderPaymentExperience(experience, 'PAID');
                setTimeout(() => window.location.reload(), 1200);
                return;
            }

            if (status === 'FAILED' || status === 'CANCELLED') {
                stopPaymentPolling();
                renderPaymentExperience(experience, status);
                if (typeof showToast === 'function') {
                    showToast(experience?.customer_message || 'Le paiement a ete annule ou refuse.', 'error');
                }
                resetPayButton();
                return;
            }

            if (attempt >= 35) {
                stopPaymentPolling();
                if (typeof showToast === 'function') {
                    showToast('Verification trop longue. Rechargez la page.', 'error');
                }
                resetPayButton();
                return;
            }

            paymentPollTimer = setTimeout(() => pollTransportPaymentStatus(paymentId, attempt + 1), 5000);
        })
        .catch(() => {
            if (attempt >= 35) {
                stopPaymentPolling();
                resetPayButton();
                return;
            }
            paymentPollTimer = setTimeout(() => pollTransportPaymentStatus(paymentId, attempt + 1), 5000);
        });
    }

    function resetPayButton() {
        const btn = document.getElementById('transportPayNowBtn');
        if (!btn) return;
        btn.disabled = false;
        btn.textContent = 'Payer maintenant';
    }

    function payNow(event) {
        const btn = event.currentTarget;
        const phoneField = document.getElementById('transportMomoPhone');
        const phone = phoneField ? phoneField.value.trim() : '';

        if (!phone) {
            alert('Renseignez le numero Mobile Money a debiter.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initialisation...';

        fetch(`{{ url('transport/xhr/bookings') }}/${BOOKING_UUID}/pay`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ provider: 'momo', phone: phone })
        })
        .then(res => res.json())
        .then(data => {
            renderPaymentExperience(data.payment_experience || null, data.payment?.status);
            if (data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }

            if (data.payment_id || data.payment?.id) {
                const paymentId = data.payment_id || data.payment.id;
                if (typeof showToast === 'function') {
                    showToast('Paiement initie. Confirmez sur votre telephone.', 'success');
                }
                pollTransportPaymentStatus(paymentId);
                return;
            }

            resetPayButton();
        })
        .catch(() => resetPayButton());
    }

    window.addEventListener('load', initBookingMap, { once: true });
</script>
@endsection
