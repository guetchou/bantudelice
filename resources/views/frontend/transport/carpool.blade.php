@extends('frontend.layouts.transport')
@section('title', 'Covoiturage | Kende')
@section('description', 'Trouvez un trajet partage avec Kende.')

@php
    $transportBookingStoreUrl = route('transport.xhr.bookings.store');
@endphp

@section('styles')
<style>
    .kd-cp-shell{padding:28px 0 44px;background:linear-gradient(180deg,#fff 0%,#f5f5f7 56%,#eef0f2 100%)}
    .kd-cp-wrap{width:min(1280px,calc(100% - 32px));margin:0 auto}
    .kd-cp-hero{display:grid;grid-template-columns:minmax(0,.92fr) minmax(360px,.88fr);gap:20px;align-items:start;margin-bottom:22px}
    .kd-cp-card{background:rgba(255,255,255,.96);border:1px solid rgba(17,17,19,.06);border-radius:30px;box-shadow:0 24px 60px rgba(17,17,19,.08)}
    .kd-cp-copy{padding:28px}
    .kd-cp-badge{display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 12px;border-radius:999px;background:rgba(0,155,58,.10);color:#009B3A;font-size:.76rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .kd-cp-badge span{width:8px;height:8px;border-radius:50%;background:#009B3A}
    .kd-cp-copy h1{margin:18px 0 10px;font-family:'Outfit',sans-serif;font-size:clamp(2.4rem,4vw,4.4rem);line-height:.95;letter-spacing:-.06em}
    .kd-cp-copy h1 em{color:#FF6B00;font-style:normal}
    .kd-cp-copy p{margin:0;color:#6B6B74;line-height:1.75;max-width:42ch}
    .kd-cp-filters{padding:24px;display:grid;gap:14px}
    .kd-cp-grid{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px}
    .kd-cp-field label{display:block;margin-bottom:8px;font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#6B6B74}
    .kd-cp-input{width:100%;min-height:56px;border:1px solid #E4E6EA;border-radius:18px;background:#fff;padding:0 16px;font-size:.96rem;font-weight:600;color:#111113}
    .kd-cp-btn{min-height:56px;border:none;border-radius:18px;background:#FF6B00;color:#fff;font-weight:800;padding:0 18px;box-shadow:0 16px 30px rgba(255,107,0,.20)}
    .kd-cp-feedback{display:none;color:#6B6B74;font-size:.92rem}
    .kd-cp-list{display:grid;gap:16px}
    .kd-cp-ride{display:grid;grid-template-columns:220px minmax(0,1fr) auto;gap:18px;padding:20px}
    .kd-cp-driver{border-right:1px solid rgba(17,17,19,.06);padding-right:18px;display:grid;align-content:center;justify-items:center;text-align:center}
    .kd-cp-avatar{width:72px;height:72px;border-radius:50%;display:grid;place-items:center;background:#FF6B00;color:#fff;font-weight:900;font-size:1.3rem;margin-bottom:12px}
    .kd-cp-driver strong{font-size:1rem}
    .kd-cp-driver small{color:#6B6B74}
    .kd-cp-route{display:grid;gap:14px}
    .kd-cp-step{display:grid;grid-template-columns:16px minmax(0,1fr);gap:12px}
    .kd-cp-line{display:grid;justify-items:center}
    .kd-cp-dot{width:10px;height:10px;border-radius:50%}
    .kd-cp-dot--start{background:#009B3A}
    .kd-cp-dot--end{background:#DC241F}
    .kd-cp-bar{width:2px;min-height:44px;background:#E4E6EA}
    .kd-cp-meta-k{display:block;font-size:.74rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#6B6B74;margin-bottom:4px}
    .kd-cp-meta-v{display:block;font-size:1rem;font-weight:800;color:#111113}
    .kd-cp-meta-sub{display:block;margin-top:4px;color:#6B6B74;font-size:.9rem}
    .kd-cp-side{display:grid;align-content:space-between;justify-items:end;gap:18px}
    .kd-cp-price{font-family:'Outfit',sans-serif;font-size:1.9rem;font-weight:900;letter-spacing:-.05em;color:#FF6B00}
    .kd-cp-side small{color:#6B6B74}
    .kd-cp-tags{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
    .kd-cp-tag{display:inline-flex;align-items:center;min-height:36px;padding:0 12px;border-radius:999px;background:#F0F1F3;color:#111113;font-size:.84rem;font-weight:700}
    .kd-cp-empty{padding:52px 28px;text-align:center}
    .kd-cp-empty h3{margin:0 0 8px;font-size:1.2rem}
    .kd-cp-empty p{margin:0;color:#6B6B74}
    @media (max-width: 1040px){
        .kd-cp-hero,.kd-cp-grid,.kd-cp-ride{grid-template-columns:1fr}
        .kd-cp-driver{border-right:none;border-bottom:1px solid rgba(17,17,19,.06);padding-right:0;padding-bottom:16px}
        .kd-cp-side,.kd-cp-tags{justify-items:start;justify-content:flex-start}
    }
</style>
@endsection

@section('content')
<section class="kd-cp-shell">
    <div class="kd-cp-wrap">
        <div class="kd-cp-hero">
            <div class="kd-cp-card kd-cp-copy">
                <div class="kd-cp-badge"><span></span>Trajets partages</div>
                <h1>Partagez la route <em>utilement.</em></h1>
                <p>Kende affiche les trajets disponibles, le prix par place, l'heure de depart et le nombre de places encore libres. Vous choisissez, puis vous confirmez sans ecran parasite.</p>
            </div>
            <div class="kd-cp-card kd-cp-filters">
                <form id="carpoolFilterForm" class="kd-cp-grid">
                    <div class="kd-cp-field">
                        <label for="carpoolOriginFilter">Depart</label>
                        <input id="carpoolOriginFilter" class="kd-cp-input" type="text" placeholder="Brazzaville">
                    </div>
                    <div class="kd-cp-field">
                        <label for="carpoolDestinationFilter">Arrivee</label>
                        <input id="carpoolDestinationFilter" class="kd-cp-input" type="text" placeholder="Pointe-Noire">
                    </div>
                    <div class="kd-cp-field">
                        <label for="carpoolDateFilter">Date</label>
                        <input id="carpoolDateFilter" class="kd-cp-input" type="date">
                    </div>
                    <button type="submit" class="kd-cp-btn">Rechercher</button>
                </form>
                <p id="carpoolFilterFeedback" class="kd-cp-feedback"></p>
            </div>
        </div>

        <div class="kd-cp-list">
            @forelse($rides as $ride)
                <article
                    class="kd-cp-card kd-cp-ride carpool-ride-card"
                    data-origin="{{ strtolower($ride->origin_address) }}"
                    data-destination="{{ strtolower($ride->destination_address) }}"
                    data-date="{{ optional($ride->departure_time)->format('Y-m-d') }}"
                >
                    <div class="kd-cp-driver">
                        <div class="kd-cp-avatar">{{ strtoupper(substr($ride->driver->name ?? 'KD', 0, 2)) }}</div>
                        <strong>{{ $ride->driver->name }}</strong>
                        <small>Chauffeur Kende</small>
                    </div>
                    <div class="kd-cp-route">
                        <div class="kd-cp-step">
                            <div class="kd-cp-line">
                                <span class="kd-cp-dot kd-cp-dot--start"></span>
                                <span class="kd-cp-bar"></span>
                            </div>
                            <div>
                                <span class="kd-cp-meta-k">Depart</span>
                                <span class="kd-cp-meta-v">{{ optional($ride->departure_time)->format('H:i') }} · {{ $ride->origin_address }}</span>
                                <span class="kd-cp-meta-sub">{{ optional($ride->departure_time)->format('d/m/Y') }}</span>
                            </div>
                        </div>
                        <div class="kd-cp-step">
                            <div class="kd-cp-line">
                                <span class="kd-cp-dot kd-cp-dot--end"></span>
                            </div>
                            <div>
                                <span class="kd-cp-meta-k">Arrivee</span>
                                <span class="kd-cp-meta-v">{{ $ride->destination_address }}</span>
                                <span class="kd-cp-meta-sub">{{ $ride->vehicle->make ?? '' }} {{ $ride->vehicle->model ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="kd-cp-side">
                        <div>
                            <div class="kd-cp-price">{{ number_format($ride->price_per_seat, 0, ',', ' ') }} FCFA</div>
                            <small>par place</small>
                        </div>
                        <div class="kd-cp-tags">
                            <span class="kd-cp-tag">{{ $ride->available_seats }} places libres</span>
                            <span class="kd-cp-tag">{{ $ride->vehicle->make ?? '' }} {{ $ride->vehicle->model ?? '' }}</span>
                        </div>
                        <button
                            type="button"
                            class="kd-cp-btn"
                            onclick="bookRide(this)"
                            data-ride-id="{{ $ride->uuid }}"
                            data-vehicle-id="{{ $ride->vehicle_id }}"
                            data-origin-address="{{ $ride->origin_address }}"
                            data-origin-lat="{{ $ride->origin_lat }}"
                            data-origin-lng="{{ $ride->origin_lng }}"
                            data-destination-address="{{ $ride->destination_address }}"
                            data-destination-lat="{{ $ride->destination_lat }}"
                            data-destination-lng="{{ $ride->destination_lng }}"
                            data-departure-time="{{ optional($ride->departure_time)->toIso8601String() }}"
                            data-price="{{ (float) $ride->price_per_seat }}"
                        >Reserver une place</button>
                    </div>
                </article>
            @empty
                <div class="kd-cp-card kd-cp-empty">
                    <h3>Aucun trajet disponible</h3>
                    <p>Revenez plus tard ou publiez un trajet lorsque le module conducteur sera alimente.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function applyCarpoolFilters() {
        const origin = (document.getElementById('carpoolOriginFilter')?.value || '').trim().toLowerCase();
        const destination = (document.getElementById('carpoolDestinationFilter')?.value || '').trim().toLowerCase();
        const date = (document.getElementById('carpoolDateFilter')?.value || '').trim();
        const cards = Array.from(document.querySelectorAll('.carpool-ride-card'));
        const feedback = document.getElementById('carpoolFilterFeedback');
        let visibleCount = 0;

        cards.forEach((card) => {
            const matchesOrigin = !origin || (card.dataset.origin || '').includes(origin);
            const matchesDestination = !destination || (card.dataset.destination || '').includes(destination);
            const matchesDate = !date || (card.dataset.date || '') === date;
            const isVisible = matchesOrigin && matchesDestination && matchesDate;
            card.style.display = isVisible ? 'grid' : 'none';
            if (isVisible) visibleCount += 1;
        });

        if (!feedback) return;

        if (!origin && !destination && !date) {
            feedback.style.display = 'none';
            feedback.textContent = '';
            return;
        }

        feedback.style.display = 'block';
        feedback.textContent = visibleCount > 0
            ? visibleCount + ' trajet(s) correspondent a votre recherche.'
            : 'Aucun trajet ne correspond a ces criteres.';
    }

    async function bookRide(button) {
        if (!button || !confirm('Voulez-vous reserver une place pour ce trajet ?')) return;

        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Reservation...';

        try {
            const response = await fetch(@json($transportBookingStoreUrl), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    type: 'carpool',
                    ride_id: button.dataset.rideId,
                    vehicle_id: button.dataset.vehicleId || null,
                    pickup_address: button.dataset.originAddress,
                    pickup_lat: Number(button.dataset.originLat || 0),
                    pickup_lng: Number(button.dataset.originLng || 0),
                    dropoff_address: button.dataset.destinationAddress,
                    dropoff_lat: Number(button.dataset.destinationLat || 0),
                    dropoff_lng: Number(button.dataset.destinationLng || 0),
                    scheduled_at: button.dataset.departureTime || null,
                    estimated_distance: 1,
                    estimated_duration: 1,
                    estimated_price: Number(button.dataset.price || 0),
                    total_price: Number(button.dataset.price || 0),
                    payment_method: 'cash'
                })
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data?.booking?.uuid) throw new Error(data.message || 'Impossible de creer la reservation.');
            window.location.href = '{{ url('transport/booking') }}/' + data.booking.uuid;
        } catch (error) {
            alert(error.message || 'Erreur de reservation covoiturage.');
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    document.getElementById('carpoolFilterForm')?.addEventListener('submit', function (event) {
        event.preventDefault();
        applyCarpoolFilters();
    });
</script>
@endsection
