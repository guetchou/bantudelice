@extends('layouts.app')
@section('title', 'Réservations Transport')
@section('style')
<link rel="stylesheet" href="{{asset(env('ASSET_URL') .'/plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
@endsection
@section('transport_nav', 'active')
@section('transport_bookings_nav', 'active')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Réservations Transport</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.transport.dashboard') }}">Transport</a></li>
                        <li class="breadcrumb-item active">Réservations</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-hover" id="bookingsTable">
                        <thead>
                            <tr>
                                <th>Booking No</th>
                                <th>Type</th>
                                <th>Client</th>
                                <th>Chauffeur</th>
                                <th>Trajet</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $booking)
                                <tr>
                                    <td>{{ $booking->booking_no }}</td>
                                    <td><span class="badge badge-info">{{ $booking->type->label() }}</span></td>
                                    <td>{{ $booking->user->name }}</td>
                                    <td>{{ $booking->driver->name ?? 'Non assigné' }}</td>
                                    <td>
                                        <small>
                                            De: {{ $booking->pickup_address }}<br>
                                            À: {{ $booking->dropoff_address }}
                                        </small>
                                    </td>
                                    <td>{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} FCFA</td>
                                    <td><span class="badge badge-secondary">{{ $booking->status->label() }}</span></td>
                                    <td>{{ $booking->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">
                        {{ $bookings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

