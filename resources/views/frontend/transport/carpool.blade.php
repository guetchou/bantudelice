@extends('frontend.layouts.app-modern')
@section('title', 'Covoiturage | BantuDelice')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); padding: 100px 0 40px; text-align: center; color: white;">
    <div class="container">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
            <i class="fas fa-users"></i> Covoiturage
        </h1>
        <p style="font-size: 1.125rem; opacity: 0.9;">Partagez vos trajets et voyagez à moindre frais.</p>
    </div>
</section>

<!-- Search Section -->
<section style="background: white; padding: 2rem 0; border-bottom: 1px solid #E5E7EB;">
    <div class="container">
        <form style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Départ</label>
                <input type="text" placeholder="Brazzaville" style="width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Arrivée</label>
                <input type="text" placeholder="Pointe-Noire" style="width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Date</label>
                <input type="date" style="width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 8px;">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 45px; padding: 0 2rem;">Rechercher</button>
        </form>
    </div>
</section>

<!-- Carpool List -->
<section class="section" style="background: #F9FAFB; padding: 3rem 0;">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto;">
            <h3 style="margin-bottom: 2rem;">Trajets disponibles</h3>
            
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                @forelse($rides as $ride)
                <div style="background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; display: flex;">
                    <!-- Driver Info -->
                    <div style="width: 200px; background: #f8f9fa; padding: 2rem; text-align: center; border-right: 1px solid #E5E7EB;">
                        <div style="width: 70px; height: 70px; background: #10B981; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 700; margin: 0 auto 1rem;">
                            {{ substr($ride->driver->name, 0, 2) }}
                        </div>
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">{{ $ride->driver->name }}</h4>
                        <p style="color: #6B7280; font-size: 0.8125rem;">
                            <i class="fas fa-star text-warning"></i> 4.8 (24 avis)
                        </p>
                    </div>

                    <!-- Ride Info -->
                    <div style="flex: 1; padding: 2rem; display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="display: flex; gap: 1.5rem; position: relative; padding-left: 20px;">
                                <div style="position: absolute; left: 0; top: 8px; bottom: 8px; width: 2px; background: #E5E7EB; display: flex; flex-direction: column; justify-content: space-between; align-items: center;">
                                    <div style="width: 8px; height: 8px; background: #10B981; border-radius: 50%;"></div>
                                    <div style="width: 8px; height: 8px; background: #EF4444; border-radius: 50%;"></div>
                                </div>
                                <div>
                                    <div style="margin-bottom: 1.5rem;">
                                        <p style="font-size: 0.8125rem; color: #6B7280; margin-bottom: 0.25rem;">DÉPART</p>
                                        <p style="font-weight: 700;">{{ $ride->departure_time->format('H:i') }} • {{ $ride->origin_address }}</p>
                                    </div>
                                    <div>
                                        <p style="font-size: 0.8125rem; color: #6B7280; margin-bottom: 0.25rem;">ARRIVÉE</p>
                                        <p style="font-weight: 700;">{{ $ride->destination_address }}</p>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 1.5rem; font-weight: 800; color: #10B981; margin: 0;">{{ number_format($ride->price_per_seat, 0, ',', ' ') }} F</p>
                                <p style="font-size: 0.8125rem; color: #6B7280;">par place</p>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 1rem; color: #6B7280; font-size: 0.875rem;">
                                <span><i class="fas fa-couch"></i> {{ $ride->available_seats }} places libres</span>
                                <span><i class="fas fa-car"></i> {{ $ride->vehicle->make }} {{ $ride->vehicle->model }}</span>
                            </div>
                            <button class="btn btn-primary" onclick="bookRide('{{ $ride->uuid }}')">Réserver</button>
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align: center; padding: 4rem; background: white; border-radius: 20px;">
                    <i class="fas fa-car-side" style="font-size: 4rem; color: #E5E7EB; margin-bottom: 1.5rem;"></i>
                    <h4>Aucun trajet disponible</h4>
                    <p style="color: #6B7280;">Revenez plus tard ou publiez votre propre trajet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</section>

<script>
    function bookRide(rideId) {
        if (!confirm('Voulez-vous réserver une place pour ce trajet ?')) return;
        
        // Appeler l'API de réservation
        fetch('/api/transport/bookings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                type: 'carpool',
                ride_id: rideId,
                // On pourrait ajouter le nombre de places ici
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.booking) {
                window.location.href = `/transport/booking/${data.booking.uuid}`;
            }
        });
    }
</script>
@endsection

