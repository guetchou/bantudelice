@extends('frontend.layouts.transport')
@section('title', 'Bus | Kende')
@section('description', 'Reservez votre place de bus avec Kende.')

@php
    $pricingData = [
        'base_fare' => (float) ($pricing->base_fare ?? 5000),
        'price_per_km' => (float) ($pricing->price_per_km ?? 30),
        'minimum_fare' => (float) ($pricing->minimum_fare ?? 7000),
    ];
@endphp

@section('styles')
<style>
    .kd-b-shell{padding:28px 0 44px;background:linear-gradient(180deg,#fff 0%,#f5f5f7 56%,#eef0f2 100%)}
    .kd-b-wrap{width:min(1280px,calc(100% - 32px));margin:0 auto}
    .kd-b-hero{display:grid;grid-template-columns:minmax(0,.95fr) minmax(360px,.82fr);gap:20px;align-items:start;margin-bottom:22px}
    .kd-b-card{background:rgba(255,255,255,.96);border:1px solid rgba(17,17,19,.06);border-radius:30px;box-shadow:0 24px 60px rgba(17,17,19,.08)}
    .kd-b-copy,.kd-b-form,.kd-b-lines{padding:24px}
    .kd-b-badge{display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 12px;border-radius:999px;background:rgba(0,155,58,.10);color:#009B3A;font-size:.76rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .kd-b-badge span{width:8px;height:8px;border-radius:50%;background:#009B3A}
    .kd-b-copy h1{margin:18px 0 10px;font-family:'Outfit',sans-serif;font-size:clamp(2.4rem,4vw,4.4rem);line-height:.95;letter-spacing:-.06em}
    .kd-b-copy h1 em{color:#FF6B00;font-style:normal}
    .kd-b-copy p{margin:0;color:#6B6B74;line-height:1.75;max-width:42ch}
    .kd-b-layout{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(340px,.95fr);gap:20px}
    .kd-b-lines-grid{display:grid;gap:12px}
    .kd-b-line{padding:18px 18px 16px;border:1px solid rgba(17,17,19,.06);border-radius:22px;background:#fff}
    .kd-b-line-head{display:flex;justify-content:space-between;gap:14px;align-items:start}
    .kd-b-line-head strong{font-size:1.02rem}
    .kd-b-line-head span{font-family:'Outfit',sans-serif;font-size:1.4rem;font-weight:900;letter-spacing:-.05em;color:#009B3A}
    .kd-b-line p{margin:6px 0 0;color:#6B6B74}
    .kd-b-field{display:grid;gap:8px}
    .kd-b-field label{font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#6B6B74}
    .kd-b-input,.kd-b-select,.kd-b-textarea{width:100%;min-height:56px;border:1px solid #E4E6EA;border-radius:18px;background:#fff;padding:0 16px;font-size:.96rem;font-weight:600;color:#111113}
    .kd-b-textarea{padding-top:14px;min-height:100px;resize:vertical}
    .kd-b-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .kd-b-price{padding:16px 18px;border-radius:20px;background:#F0F1F3}
    .kd-b-price small{display:block;color:#009B3A;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:8px}
    .kd-b-price strong{font-family:'Outfit',sans-serif;font-size:1.8rem;letter-spacing:-.05em}
    .kd-b-btn{min-height:56px;border:none;border-radius:18px;background:#FF6B00;color:#fff;font-weight:800;padding:0 18px;box-shadow:0 16px 30px rgba(255,107,0,.20)}
    .kd-b-message{display:none;border-radius:18px;padding:14px 16px;font-size:.92rem}
    @media (max-width: 1040px){
        .kd-b-hero,.kd-b-layout,.kd-b-row{grid-template-columns:1fr}
    }
</style>
@endsection

@section('content')
<section class="kd-b-shell">
    <div class="kd-b-wrap">
        <div class="kd-b-hero">
            <div class="kd-b-card kd-b-copy">
                <div class="kd-b-badge"><span></span>Transport interurbain</div>
                <h1>Reservez le bus <em>sans detour.</em></h1>
                <p>Kende affiche les lignes utiles, les horaires et le tarif estime. Vous choisissez votre depart, l'arrivee et la date, puis la reservation part dans le meme backend transport.</p>
            </div>
            <div class="kd-b-card kd-b-form">
                <h2 style="margin:0 0 14px;font-size:1.2rem;font-weight:900">Reserver une place</h2>
                <form id="busBookingForm" style="display:grid;gap:12px">
                    <div class="kd-b-field">
                        <label for="busPickupAddress">Depart</label>
                        <input id="busPickupAddress" name="pickup_address" class="kd-b-input" type="text" value="Brazzaville" required>
                    </div>
                    <div class="kd-b-field">
                        <label for="busDropoffAddress">Arrivee</label>
                        <input id="busDropoffAddress" name="dropoff_address" class="kd-b-input" type="text" value="Pointe-Noire" required>
                    </div>
                    <div class="kd-b-row">
                        <div class="kd-b-field">
                            <label for="busScheduledAt">Date de depart</label>
                            <input id="busScheduledAt" name="scheduled_at" class="kd-b-input" type="datetime-local" required>
                        </div>
                        <div class="kd-b-field">
                            <label for="busPaymentMethod">Paiement</label>
                            <select id="busPaymentMethod" name="payment_method" class="kd-b-select">
                                <option value="cash">Especes</option>
                                <option value="momo">Mobile Money</option>
                            </select>
                        </div>
                    </div>
                    <div class="kd-b-field">
                        <label for="busNotes">Notes voyage</label>
                        <textarea id="busNotes" name="notes" class="kd-b-textarea" placeholder="Ex: 2 places, bagages, ligne souhaitee"></textarea>
                    </div>
                    <div class="kd-b-price">
                        <small>Tarif estime</small>
                        <strong id="busPriceLabel">{{ number_format($pricingData['minimum_fare'], 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <button type="submit" class="kd-b-btn">Reserver bus</button>
                    <div id="busBookingMessage" class="kd-b-message"></div>
                </form>
            </div>
        </div>

        <div class="kd-b-layout">
            <div class="kd-b-card kd-b-lines">
                <h2 style="margin:0 0 14px;font-size:1.2rem;font-weight:900">Lignes suggerees</h2>
                <div class="kd-b-lines-grid">
                    @foreach($busLines as $line)
                        <article class="kd-b-line">
                            <div class="kd-b-line-head">
                                <div>
                                    <strong>{{ $line['name'] }}</strong>
                                    <p>{{ $line['frequency'] }} · Depart {{ $line['departure'] }} · Arrivee {{ $line['arrival'] }}</p>
                                </div>
                                <span>{{ number_format($line['price'], 0, ',', ' ') }} FCFA</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
            <div class="kd-b-card kd-b-form">
                <h2 style="margin:0 0 12px;font-size:1.1rem;font-weight:900">Lecture rapide</h2>
                <div style="display:grid;gap:12px;color:#6B6B74;line-height:1.7">
                    <p style="margin:0">Le formulaire bus reste volontairement simple : ville de depart, ville d'arrivee, date et paiement.</p>
                    <p style="margin:0">Le moteur de reservation reste le backend transport existant. Le design change, pas le socle de traitement.</p>
                    <p style="margin:0">Le prix final peut bouger selon la ligne, la compagnie et la disponibilite, mais la demande part deja avec une estimation propre.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    (function () {
        const form = document.getElementById('busBookingForm');
        const message = document.getElementById('busBookingMessage');

        function showMessage(text, isError = false) {
            message.style.display = 'block';
            message.textContent = text;
            message.style.background = isError ? '#fee2e2' : '#dcfce7';
            message.style.color = isError ? '#991b1b' : '#007836';
            message.style.border = '1px solid ' + (isError ? '#fecaca' : '#bbf7d0');
        }

        form?.addEventListener('submit', async function (event) {
            event.preventDefault();

            const payload = {
                type: 'bus',
                pickup_address: document.getElementById('busPickupAddress').value,
                dropoff_address: document.getElementById('busDropoffAddress').value,
                pickup_lat: -4.2634,
                pickup_lng: 15.2429,
                dropoff_lat: -4.7761,
                dropoff_lng: 11.8635,
                scheduled_at: document.getElementById('busScheduledAt').value,
                estimated_distance: 510,
                estimated_duration: 420,
                estimated_price: {{ (int) round($pricingData['minimum_fare']) }},
                total_price: {{ (int) round($pricingData['minimum_fare']) }},
                payment_method: document.getElementById('busPaymentMethod').value,
                notes: document.getElementById('busNotes').value
            };

            try {
                const response = await fetch('{{ route('transport.xhr.bookings.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data?.booking?.uuid) throw new Error(data.message || 'Impossible de creer la reservation bus.');
                showMessage('Reservation bus creee. Redirection vers le suivi...');
                window.location.href = '{{ url('transport/booking') }}/' + data.booking.uuid;
            } catch (error) {
                showMessage(error.message || 'Erreur de reservation bus.', true);
            }
        });
    })();
</script>
@endsection
