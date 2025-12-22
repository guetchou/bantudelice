@extends('frontend.layouts.app-modern')
@section('title', 'Mes réservations transport | BantuDelice')

@section('content')
<!-- Header -->
<section style="background: linear-gradient(135deg, #1F2937 0%, #111827 100%); padding: 100px 0 40px; text-align: center; color: white;">
    <div class="container">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
            <i class="fas fa-list-ul"></i> Mes Réservations
        </h1>
        <p style="font-size: 1.125rem; opacity: 0.9;">Historique de vos déplacements avec BantuDelice.</p>
    </div>
</section>

<section class="section" style="background: #F9FAFB; padding: 3rem 0; min-height: 60vh;">
    <div class="container">
        <div style="max-width: 1000px; margin: 0 auto;">
            
            <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); overflow: hidden;">
                <table class="table" style="margin: 0;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 1.5rem; border: none;">Réservation</th>
                            <th style="padding: 1.5rem; border: none;">Type</th>
                            <th style="padding: 1.5rem; border: none;">Date</th>
                            <th style="padding: 1.5rem; border: none;">Trajet</th>
                            <th style="padding: 1.5rem; border: none;">Prix</th>
                            <th style="padding: 1.5rem; border: none;">Statut</th>
                            <th style="padding: 1.5rem; border: none;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 1.5rem; vertical-align: middle;">
                                <span style="font-weight: 700; color: #FF6B35;">#{{ $booking->booking_no }}</span>
                            </td>
                            <td style="padding: 1.5rem; vertical-align: middle;">
                                <span class="badge" style="background: #E5E7EB; color: #374151; padding: 6px 12px; border-radius: 50px;">
                                    {{ $booking->type->label() }}
                                </span>
                            </td>
                            <td style="padding: 1.5rem; vertical-align: middle;">
                                <div style="font-size: 0.875rem;">
                                    {{ $booking->created_at->format('d/m/Y') }}<br>
                                    <span style="color: #6B7280;">{{ $booking->created_at->format('H:i') }}</span>
                                </div>
                            </td>
                            <td style="padding: 1.5rem; vertical-align: middle;">
                                <div style="max-width: 250px; font-size: 0.875rem;">
                                    <i class="fas fa-circle text-success" style="font-size: 8px;"></i> {{ \Illuminate\Support\Str::limit($booking->pickup_address, 30) }}<br>
                                    <i class="fas fa-map-marker-alt text-danger" style="font-size: 10px;"></i> {{ \Illuminate\Support\Str::limit($booking->dropoff_address, 30) }}
                                </div>
                            </td>
                            <td style="padding: 1.5rem; vertical-align: middle;">
                                <span style="font-weight: 700;">{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} F</span>
                            </td>
                            <td style="padding: 1.5rem; vertical-align: middle;">
                                @php
                                    $statusColors = [
                                        'requested' => '#FBBF24',
                                        'assigned' => '#3B82F6',
                                        'in_progress' => '#8B5CF6',
                                        'completed' => '#10B981',
                                        'cancelled' => '#EF4444',
                                    ];
                                    $color = $statusColors[$booking->status->value] ?? '#6B7280';
                                @endphp
                                <span class="badge" style="background: {{ $color }}20; color: {{ $color }}; padding: 6px 12px; border-radius: 50px;">
                                    {{ $booking->status->label() }}
                                </span>
                            </td>
                            <td style="padding: 1.5rem; vertical-align: middle; text-align: right;">
                                <a href="{{ route('transport.booking.show', $booking->uuid) }}" class="btn btn-outline-primary btn-sm">
                                    Détails
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="padding: 4rem; text-align: center; color: #6B7280;">
                                <i class="fas fa-car-side fa-3x" style="margin-bottom: 1rem; opacity: 0.2;"></i>
                                <p>Vous n'avez pas encore de réservations transport.</p>
                                <a href="{{ route('transport.index') }}" class="btn btn-primary mt-2">Réserver maintenant</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

