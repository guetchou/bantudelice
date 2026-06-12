@extends('frontend.layouts.transport')
@section('title', 'Location | Kende')
@section('description', 'Louez un vehicule avec Kende.')

@php
    $transportBookingStoreUrl = route('transport.xhr.bookings.store');
@endphp

@section('styles')
<style>
    .kd-r-shell{padding:28px 0 44px;background:linear-gradient(180deg,#fff 0%,#f5f5f7 56%,#eef0f2 100%)}
    .kd-r-wrap{width:min(1280px,calc(100% - 32px));margin:0 auto}
    .kd-r-hero{display:grid;grid-template-columns:minmax(0,.92fr) 360px;gap:20px;align-items:start;margin-bottom:22px}
    .kd-r-card{background:rgba(255,255,255,.96);border:1px solid rgba(17,17,19,.06);border-radius:30px;box-shadow:0 24px 60px rgba(17,17,19,.08)}
    .kd-r-copy,.kd-r-filter,.kd-r-form{padding:24px}
    .kd-r-badge{display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 12px;border-radius:999px;background:rgba(255,107,0,.10);color:#FF6B00;font-size:.76rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .kd-r-badge span{width:8px;height:8px;border-radius:50%;background:#FF6B00}
    .kd-r-copy h1{margin:18px 0 10px;font-family:'Outfit',sans-serif;font-size:clamp(2.4rem,4vw,4.4rem);line-height:.95;letter-spacing:-.06em}
    .kd-r-copy h1 em{color:#FF6B00;font-style:normal}
    .kd-r-copy p{margin:0;color:#6B6B74;line-height:1.75;max-width:44ch}
    .kd-r-layout{display:grid;grid-template-columns:360px minmax(0,1fr);gap:20px;align-items:start}
    .kd-r-field{display:grid;gap:8px}
    .kd-r-field label{font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#6B6B74}
    .kd-r-input,.kd-r-select{width:100%;min-height:56px;border:1px solid #E4E6EA;border-radius:18px;background:#fff;padding:0 16px;font-size:.96rem;font-weight:600;color:#111113}
    .kd-r-range{width:100%}
    .kd-r-btn{min-height:56px;border:none;border-radius:18px;background:#FF6B00;color:#fff;font-weight:800;padding:0 18px;box-shadow:0 16px 30px rgba(255,107,0,.20)}
    .kd-r-feedback{display:none;color:#6B6B74;font-size:.92rem}
    .kd-r-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
    .kd-r-vehicle{overflow:hidden}
    .kd-r-media{height:210px;background:#eef0f2;position:relative}
    .kd-r-media img{width:100%;height:100%;object-fit:cover}
    .kd-r-rate{position:absolute;right:14px;top:14px;display:inline-flex;align-items:center;min-height:36px;padding:0 12px;border-radius:999px;background:#fff;color:#FF6B00;font-size:.86rem;font-weight:800;box-shadow:0 10px 24px rgba(17,17,19,.10)}
    .kd-r-body{padding:18px}
    .kd-r-body h3{margin:0 0 8px;font-size:1.1rem;font-weight:800}
    .kd-r-meta{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;color:#6B6B74;font-size:.84rem}
    .kd-r-desc{margin:0 0 16px;color:#6B6B74;line-height:1.65}
    .kd-r-select-btn{width:100%;min-height:50px;border-radius:16px;border:1px solid rgba(255,107,0,.24);background:rgba(255,107,0,.08);color:#FF6B00;font-weight:800}
    .kd-r-form-head{display:flex;justify-content:space-between;gap:14px;align-items:start;margin-bottom:14px}
    .kd-r-form-head strong{font-size:1rem}
    .kd-r-form-head span{color:#FF6B00;font-weight:800}
    .kd-r-form-grid{display:grid;gap:12px}
    .kd-r-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .kd-r-note{padding:14px 16px;border-radius:18px;background:#F0F1F3;color:#6B6B74;font-size:.92rem;line-height:1.6}
    @media (max-width: 1040px){
        .kd-r-hero,.kd-r-layout,.kd-r-grid,.kd-r-row{grid-template-columns:1fr}
    }
</style>
@endsection

@section('content')
<section class="kd-r-shell">
    <div class="kd-r-wrap">
        <div class="kd-r-hero">
            <div class="kd-r-card kd-r-copy">
                <div class="kd-r-badge"><span></span>Vehicules disponibles</div>
                <h1>Louez le vehicule <em>adapté.</em></h1>
                <p>Kende garde un seul parcours utile : choisir un vehicule, preciser le retrait, fixer la restitution et envoyer la demande de location avec le bon mode de paiement.</p>
            </div>
            <div class="kd-r-card kd-r-filter">
                <div class="kd-r-field">
                    <label for="rentalTypeFilter">Type de vehicule</label>
                    <select id="rentalTypeFilter" class="kd-r-select">
                        <option value="">Tous les types</option>
                        <option value="berline">Berline</option>
                        <option value="suv">SUV / 4x4</option>
                        <option value="utilitaire">Utilitaire</option>
                    </select>
                </div>
                <div class="kd-r-field" style="margin-top:14px">
                    <label for="rentalBudgetFilter">Budget max / jour</label>
                    <input id="rentalBudgetFilter" class="kd-r-range" type="range" min="10000" max="100000" step="5000" value="100000">
                    <div style="display:flex;justify-content:space-between;color:#6B6B74;font-size:.84rem;"><span>10k FCFA</span><span id="rentalBudgetValue">100k FCFA</span></div>
                </div>
                <button id="rentalApplyFilters" type="button" class="kd-r-btn" style="margin-top:16px;width:100%">Filtrer</button>
                <p id="rentalFilterFeedback" class="kd-r-feedback" style="margin-top:12px"></p>
            </div>
        </div>

        <div class="kd-r-layout">
            <div class="kd-r-card kd-r-form">
                <div class="kd-r-form-head">
                    <div>
                        <strong>Demande de location</strong>
                        <div style="margin-top:4px;color:#6B6B74;font-size:.92rem">Selectionnez un vehicule puis renseignez votre besoin.</div>
                    </div>
                    <span id="selectedRentalVehicleLabel">Aucun vehicule</span>
                </div>
                <form id="rentalBookingForm" class="kd-r-form-grid">
                    <input type="hidden" id="rentalVehicleId" value="">
                    <div class="kd-r-field">
                        <label for="rentalPickupAddress">Retrait</label>
                        <input id="rentalPickupAddress" class="kd-r-input" type="text" value="Brazzaville centre" required>
                    </div>
                    <div class="kd-r-field">
                        <label for="rentalDropoffAddress">Restitution</label>
                        <input id="rentalDropoffAddress" class="kd-r-input" type="text" value="Brazzaville centre" required>
                    </div>
                    <div class="kd-r-row">
                        <div class="kd-r-field">
                            <label for="rentalScheduledAt">Date de depart</label>
                            <input id="rentalScheduledAt" class="kd-r-input" type="datetime-local" required>
                        </div>
                        <div class="kd-r-field">
                            <label for="rentalPaymentMethod">Paiement</label>
                            <select id="rentalPaymentMethod" class="kd-r-select">
                                <option value="cash">Especes</option>
                                <option value="momo">Mobile Money</option>
                            </select>
                        </div>
                    </div>
                    <div class="kd-r-note">Le vehicule selectionne remplit seulement le contexte de la reservation. Le socle backend reste celui des reservations transport.</div>
                    <button id="rentalBookingSubmit" type="submit" class="kd-r-btn">Envoyer la demande</button>
                    <div id="rentalBookingFeedback" class="kd-r-feedback"></div>
                </form>
            </div>

            <div class="kd-r-grid">
                @forelse($vehicles as $vehicle)
                    @php
                        $vehicleProfile = strtolower(trim(($vehicle->type ?? '') . ' ' . ($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '') . ' ' . ($vehicle->description ?? '')));
                    @endphp
                    <article class="kd-r-card kd-r-vehicle vehicle-card" data-profile="{{ $vehicleProfile }}" data-rate="{{ (float) $vehicle->daily_rate }}">
                        <div class="kd-r-media">
                            @if($vehicle->image)
                                <img src="{{ asset('images/vehicles/' . $vehicle->image) }}" alt="{{ trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')) }}">
                            @endif
                            <div class="kd-r-rate">{{ number_format($vehicle->daily_rate, 0, ',', ' ') }} FCFA/jour</div>
                        </div>
                        <div class="kd-r-body">
                            <h3>{{ $vehicle->make }} {{ $vehicle->model }}</h3>
                            <div class="kd-r-meta">
                                <span>{{ $vehicle->seats }} places</span>
                                <span>{{ $vehicle->type ?? 'vehicule' }}</span>
                            </div>
                            <p class="kd-r-desc">{{ \Illuminate\Support\Str::limit($vehicle->description, 96) }}</p>
                            <button
                                type="button"
                                class="kd-r-select-btn"
                                onclick="rentVehicle(this)"
                                data-vehicle-id="{{ $vehicle->id }}"
                                data-vehicle-label="{{ trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')) }}"
                                data-rate="{{ (float) $vehicle->daily_rate }}"
                            >Choisir ce vehicule</button>
                        </div>
                    </article>
                @empty
                    <div class="kd-r-card kd-r-form">
                        <strong>Aucun vehicule disponible</strong>
                        <p style="margin:8px 0 0;color:#6B6B74">Le catalogue reviendra quand les vehicules seront publies.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function updateRentalBudgetLabel() {
        const budgetInput = document.getElementById('rentalBudgetFilter');
        const label = document.getElementById('rentalBudgetValue');
        if (budgetInput && label) label.textContent = Math.round(Number(budgetInput.value || 0) / 1000) + 'k FCFA';
    }

    function applyRentalFilters() {
        const typeFilter = (document.getElementById('rentalTypeFilter')?.value || '').trim().toLowerCase();
        const maxBudget = Number(document.getElementById('rentalBudgetFilter')?.value || 100000);
        const cards = Array.from(document.querySelectorAll('.vehicle-card'));
        const feedback = document.getElementById('rentalFilterFeedback');
        let visibleCount = 0;

        cards.forEach((card) => {
            const profile = card.dataset.profile || '';
            const rate = Number(card.dataset.rate || 0);
            const matchesType = !typeFilter || profile.includes(typeFilter);
            const matchesBudget = !maxBudget || rate <= maxBudget;
            const isVisible = matchesType && matchesBudget;
            card.style.display = isVisible ? 'block' : 'none';
            if (isVisible) visibleCount += 1;
        });

        if (!feedback) return;
        feedback.style.display = 'block';
        feedback.textContent = visibleCount > 0
            ? visibleCount + ' vehicule(s) correspondent aux filtres.'
            : 'Aucun vehicule ne correspond a ces filtres.';
    }

    function showRentalBookingFeedback(text, isError = false) {
        const feedback = document.getElementById('rentalBookingFeedback');
        if (!feedback) return;
        feedback.style.display = 'block';
        feedback.textContent = text;
        feedback.style.color = isError ? '#991b1b' : '#007836';
    }

    function rentVehicle(button) {
        if (!button) return;
        document.getElementById('rentalVehicleId').value = button.dataset.vehicleId || '';
        document.getElementById('selectedRentalVehicleLabel').textContent = (button.dataset.vehicleLabel || 'Vehicule') + ' · ' + (button.dataset.rate || '0') + ' FCFA/jour';
        document.getElementById('rentalBookingForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.getElementById('rentalBudgetFilter')?.addEventListener('input', updateRentalBudgetLabel);
    document.getElementById('rentalApplyFilters')?.addEventListener('click', applyRentalFilters);

    document.getElementById('rentalBookingForm')?.addEventListener('submit', async function (event) {
        event.preventDefault();

        const vehicleId = document.getElementById('rentalVehicleId')?.value || '';
        if (!vehicleId) {
            showRentalBookingFeedback('Selectionnez d abord un vehicule.', true);
            return;
        }

        const submitButton = document.getElementById('rentalBookingSubmit');
        submitButton.disabled = true;
        submitButton.textContent = 'Envoi...';

        try {
            const response = await fetch(@json($transportBookingStoreUrl), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    type: 'rental',
                    vehicle_id: vehicleId,
                    pickup_address: document.getElementById('rentalPickupAddress')?.value || 'Brazzaville centre',
                    pickup_lat: -4.2634,
                    pickup_lng: 15.2429,
                    dropoff_address: document.getElementById('rentalDropoffAddress')?.value || 'Brazzaville centre',
                    dropoff_lat: -4.2634,
                    dropoff_lng: 15.2429,
                    scheduled_at: document.getElementById('rentalScheduledAt')?.value || null,
                    estimated_distance: 1,
                    estimated_duration: 1440,
                    payment_method: document.getElementById('rentalPaymentMethod')?.value || 'cash'
                })
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data?.booking?.uuid) throw new Error(data.message || 'Impossible de creer la demande.');

            showRentalBookingFeedback('Demande envoyee. Redirection vers la reservation...');
            window.location.href = '{{ url('transport/booking') }}/' + data.booking.uuid;
        } catch (error) {
            showRentalBookingFeedback(error.message || 'Erreur de demande de location.', true);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Envoyer la demande';
        }
    });

    updateRentalBudgetLabel();
    if (document.querySelector('.vehicle-card')) applyRentalFilters();
</script>
@endsection
