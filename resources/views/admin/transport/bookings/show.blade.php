@extends('layouts.admin-modern')
@section('title', 'Détail réservation transport')
@section('page_title', 'Détail réservation')
@section('nav_active', 'transport')

@php
    $paymentExperience = $paymentExperience ?? null;
    $paymentPillStyle = match($paymentExperience['status'] ?? null) {
        'PAID'               => 'background:#d1fae5;color:#065f46;',
        'FAILED','CANCELLED' => 'background:#fee2e2;color:#991b1b;',
        'PENDING'            => 'background:#fef3c7;color:#92400e;',
        default              => 'background:#dbeafe;color:#1e40af;',
    };
@endphp

@section('style')
<style>
.trk-page { padding:24px; }
.trk-layout { display:grid; grid-template-columns:300px 1fr; gap:20px; }
.trk-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:16px; }
.trk-card:last-child { margin-bottom:0; }
.trk-card__header { padding:12px 18px; border-bottom:1px solid #f3f4f6; }
.trk-card__title { font-size:13px; font-weight:700; color:#111827; margin:0; }
.trk-card__body { padding:18px; }
.trk-id { font-size:16px; font-weight:700; color:#111827; text-align:center; margin-bottom:4px; }
.trk-sub { font-size:13px; color:#9ca3af; text-align:center; margin-bottom:14px; }
.trk-list { list-style:none; padding:0; margin:0; border-top:1px solid #f3f4f6; }
.trk-list li { display:flex; justify-content:space-between; padding:8px 0; font-size:13px; border-bottom:1px solid #f3f4f6; gap:8px; }
.trk-list li:last-child { border-bottom:none; }
.trk-list li b { color:#374151; flex-shrink:0; }
.trk-list li span { color:#6b7280; text-align:right; }
.trk-pill { display:inline-flex; align-items:center; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:700; }
.trk-info-row { display:flex; flex-direction:column; gap:4px; margin-bottom:12px; font-size:13px; color:#374151; }
.trk-info-row:last-child { margin-bottom:0; }
.trk-info-row b { color:#9ca3af; font-size:12px; font-weight:600; }
@media (max-width:900px) { .trk-layout { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="trk-page">
    <div class="trk-layout">
        {{-- Sidebar --}}
        <div>
            <div class="trk-card">
                <div class="trk-card__body">
                    <div class="trk-id">{{ $booking->booking_no }}</div>
                    <div class="trk-sub">{{ $booking->status->label() }}</div>
                    <ul class="trk-list">
                        <li><b>Client</b><span>{{ $booking->user->name ?? 'N/A' }}</span></li>
                        <li><b>Chauffeur</b><span>{{ $booking->driver->name ?? 'Non assigné' }}</span></li>
                        <li><b>Véhicule</b><span>{{ $booking->vehicle->plate_number ?? 'N/A' }}</span></li>
                        <li><b>Total</b><span>{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} FCFA</span></li>
                        <li>
                            <b>Paiement</b>
                            <span class="trk-pill" style="{{ $paymentPillStyle }}">
                                {{ $paymentExperience['status'] ?? strtoupper($booking->payment_status ?? 'pending') }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Main --}}
        <div>
            <div class="trk-card">
                <div class="trk-card__header">
                    <h3 class="trk-card__title">Diagnostic paiement</h3>
                </div>
                <div class="trk-card__body">
                    @if($paymentExperience)
                        <div class="trk-info-row"><b>Message client</b>{{ $paymentExperience['customer_message'] }}</div>
                        <div class="trk-info-row"><b>Action support</b>{{ $paymentExperience['support_action'] ?? 'Aucune' }}</div>
                        <div class="trk-info-row"><b>Code provider</b>{{ $paymentExperience['failure_reason'] ?? 'N/A' }}</div>
                        <div class="trk-info-row"><b>Message provider</b>{{ $paymentExperience['failure_message'] ?? 'N/A' }}</div>
                    @else
                        <div style="color:#9ca3af;font-size:13px;">Aucun paiement externe associé à cette réservation.</div>
                    @endif
                </div>
            </div>

            <div class="trk-card">
                <div class="trk-card__header">
                    <h3 class="trk-card__title">Trajet</h3>
                </div>
                <div class="trk-card__body">
                    <div class="trk-info-row"><b>Départ</b>{{ $booking->pickup_address }}</div>
                    <div class="trk-info-row"><b>Arrivée</b>{{ $booking->dropoff_address }}</div>
                    <div class="trk-info-row"><b>Créée le</b>{{ $booking->created_at->format('d/m/Y H:i') }}</div>
                    <div class="trk-info-row"><b>Dernière mise à jour</b>{{ $booking->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
