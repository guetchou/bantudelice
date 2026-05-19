@extends('frontend.layouts.transport')
@section('title', 'Mes reservations | Kende')
@section('description', 'Consultez vos reservations Kende.')

@section('styles')
<style>
    .kd-mb-shell{padding:28px 0 44px;background:linear-gradient(180deg,#fff 0%,#f5f5f7 56%,#eef0f2 100%)}
    .kd-mb-wrap{width:min(1280px,calc(100% - 32px));margin:0 auto}
    .kd-mb-head{display:grid;grid-template-columns:minmax(0,.95fr) 340px;gap:20px;align-items:start;margin-bottom:22px}
    .kd-mb-card{background:rgba(255,255,255,.96);border:1px solid rgba(17,17,19,.06);border-radius:30px;box-shadow:0 24px 60px rgba(17,17,19,.08)}
    .kd-mb-copy,.kd-mb-stats,.kd-mb-list{padding:24px}
    .kd-mb-badge{display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 12px;border-radius:999px;background:rgba(255,107,0,.10);color:#FF6B00;font-size:.76rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .kd-mb-badge span{width:8px;height:8px;border-radius:50%;background:#FF6B00}
    .kd-mb-copy h1{margin:18px 0 10px;font-family:'Outfit',sans-serif;font-size:clamp(2.2rem,4vw,4rem);line-height:.95;letter-spacing:-.06em}
    .kd-mb-copy p{margin:0;color:#6B6B74;line-height:1.75;max-width:42ch}
    .kd-mb-stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .kd-mb-stat{padding:16px;border-radius:20px;background:#F0F1F3}
    .kd-mb-stat small{display:block;color:#6B6B74;font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
    .kd-mb-stat strong{font-family:'Outfit',sans-serif;font-size:1.8rem;letter-spacing:-.05em}
    .kd-mb-list{display:grid;gap:14px}
    .kd-mb-row{display:grid;grid-template-columns:180px minmax(0,1fr) 150px 170px 160px;gap:16px;align-items:center;padding:18px 0;border-top:1px solid rgba(17,17,19,.06)}
    .kd-mb-row:first-child{border-top:none;padding-top:0}
    .kd-mb-no{font-weight:800;color:#FF6B00}
    .kd-mb-type,.kd-mb-status{display:inline-flex;align-items:center;min-height:38px;padding:0 12px;border-radius:999px;font-size:.84rem;font-weight:800}
    .kd-mb-type{background:#F0F1F3;color:#111113}
    .kd-mb-status{background:#eef0f2;color:#111113}
    .kd-mb-route{display:grid;gap:8px}
    .kd-mb-point small{display:block;color:#6B6B74;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px}
    .kd-mb-point strong{display:block;font-size:.92rem;line-height:1.5}
    .kd-mb-date,.kd-mb-price{font-size:.92rem;color:#111113;font-weight:700}
    .kd-mb-date small{display:block;color:#6B6B74;font-weight:600;margin-top:4px}
    .kd-mb-link{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 16px;border-radius:16px;background:rgba(255,107,0,.08);color:#FF6B00;text-decoration:none;font-weight:800}
    .kd-mb-empty{padding:48px 24px;text-align:center}
    .kd-mb-empty p{margin:8px 0 18px;color:#6B6B74}
    .kd-mb-btn{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 18px;border-radius:16px;background:#FF6B00;color:#fff;text-decoration:none;font-weight:800}
    @media (max-width: 1080px){
        .kd-mb-head,.kd-mb-stat-grid,.kd-mb-row{grid-template-columns:1fr}
    }
</style>
@endsection

@section('content')
@php
    $completedCount = $bookings->whereIn('status.value', ['completed', 'paid', 'closed'])->count();
    $activeCount = $bookings->whereNotIn('status.value', ['completed', 'paid', 'closed', 'cancelled'])->count();
@endphp
<section class="kd-mb-shell">
    <div class="kd-mb-wrap">
        <div class="kd-mb-head">
            <div class="kd-mb-card kd-mb-copy">
                <div class="kd-mb-badge"><span></span>Historique</div>
                <h1>Mes reservations</h1>
                <p>Retrouvez chaque reservation Kende avec son trajet, son statut, son prix et l'acces direct au detail de suivi.</p>
            </div>
            <div class="kd-mb-card kd-mb-stats">
                <div class="kd-mb-stat-grid">
                    <div class="kd-mb-stat">
                        <small>Reservations</small>
                        <strong>{{ $bookings->count() }}</strong>
                    </div>
                    <div class="kd-mb-stat">
                        <small>Actives</small>
                        <strong>{{ $activeCount }}</strong>
                    </div>
                    <div class="kd-mb-stat" style="grid-column:1 / -1">
                        <small>Terminees</small>
                        <strong>{{ $completedCount }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="kd-mb-card kd-mb-list">
            @forelse($bookings as $booking)
                @php
                    $statusColors = [
                        'requested' => '#F59E0B',
                        'assigned' => '#2563EB',
                        'driver_arriving' => '#2563EB',
                        'picked_up' => '#7C3AED',
                        'in_progress' => '#7C3AED',
                        'completed' => '#009B3A',
                        'paid' => '#009B3A',
                        'closed' => '#009B3A',
                        'cancelled' => '#DC241F',
                    ];
                    $color = $statusColors[$booking->status->value] ?? '#6B7280';
                @endphp
                <article class="kd-mb-row">
                    <div>
                        <div class="kd-mb-no">#{{ $booking->booking_no }}</div>
                        <div style="margin-top:8px"><span class="kd-mb-type">{{ $booking->type->label() }}</span></div>
                    </div>
                    <div class="kd-mb-route">
                        <div class="kd-mb-point">
                            <small>Depart</small>
                            <strong>{{ \Illuminate\Support\Str::limit($booking->pickup_address, 52) }}</strong>
                        </div>
                        <div class="kd-mb-point">
                            <small>Arrivee</small>
                            <strong>{{ \Illuminate\Support\Str::limit($booking->dropoff_address, 52) }}</strong>
                        </div>
                    </div>
                    <div class="kd-mb-date">
                        {{ $booking->created_at->format('d/m/Y') }}
                        <small>{{ $booking->created_at->format('H:i') }}</small>
                    </div>
                    <div>
                        <div class="kd-mb-price">{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} FCFA</div>
                        <div style="margin-top:8px">
                            <span class="kd-mb-status" style="background: {{ $color }}15; color: {{ $color }};">{{ $booking->status->label() }}</span>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('transport.booking.show', $booking->uuid) }}" class="kd-mb-link">Voir le detail</a>
                    </div>
                </article>
            @empty
                <div class="kd-mb-empty">
                    <h3>Aucune reservation</h3>
                    <p>Vous n'avez pas encore de reservation transport.</p>
                    <a href="{{ route('transport.index') }}" class="kd-mb-btn">Reserver maintenant</a>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
