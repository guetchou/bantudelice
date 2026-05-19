@extends('layouts.admin-modern')
@section('title', 'Détail réservation transport')
@section('page_title', 'Détail réservation')
@section('nav_active', 'transport')

@php
    $paymentExperience = $paymentExperience ?? null;
    $paymentBadgeClass = match($paymentExperience['status'] ?? null) {
        'PAID' => 'badge-success',
        'FAILED', 'CANCELLED' => 'badge-danger',
        'PENDING' => 'badge-warning',
        default => 'badge-info',
    };
@endphp

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Réservation {{ $booking->booking_no }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.transport.dashboard') }}">Transport</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.transport.bookings.index') }}">Réservations</a></li>
                    <li class="breadcrumb-item active">{{ $booking->booking_no }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body">
                        <h3 class="profile-username text-center">{{ $booking->booking_no }}</h3>
                        <p class="text-muted text-center">{{ $booking->status->label() }}</p>
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Client</b> <span class="float-right">{{ $booking->user->name ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Chauffeur</b> <span class="float-right">{{ $booking->driver->name ?? 'Non assigné' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Véhicule</b> <span class="float-right">{{ $booking->vehicle->plate_number ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Total</b> <span class="float-right">{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} FCFA</span>
                            </li>
                            <li class="list-group-item">
                                <b>Paiement</b>
                                <span class="float-right badge {{ $paymentBadgeClass }}">{{ $paymentExperience['status'] ?? strtoupper($booking->payment_status ?? 'pending') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Diagnostic paiement</h3>
                    </div>
                    <div class="card-body">
                        @if($paymentExperience)
                            <p><strong>Message client:</strong> {{ $paymentExperience['customer_message'] }}</p>
                            <p><strong>Action support:</strong> {{ $paymentExperience['support_action'] ?? 'Aucune' }}</p>
                            <p><strong>Code provider:</strong> {{ $paymentExperience['failure_reason'] ?? 'N/A' }}</p>
                            <p><strong>Message provider:</strong> {{ $paymentExperience['failure_message'] ?? 'N/A' }}</p>
                        @else
                            <p class="text-muted">Aucun paiement externe associé à cette réservation.</p>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Trajet</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Départ:</strong> {{ $booking->pickup_address }}</p>
                        <p><strong>Arrivée:</strong> {{ $booking->dropoff_address }}</p>
                        <p><strong>Créée le:</strong> {{ $booking->created_at->format('d/m/Y H:i') }}</p>
                        <p><strong>Dernière mise à jour:</strong> {{ $booking->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
