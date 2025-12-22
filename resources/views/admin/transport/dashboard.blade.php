@extends('layouts.app')
@section('title', 'Transport Dashboard | BantuDelice')
@section('transport_nav', 'active')
@section('transport_dashboard_nav', 'active')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Module Transport</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Accueil</a></li>
                        <li class="breadcrumb-item active">Transport</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $stats['total_bookings'] }}</h3>
                            <p>Total Réservations</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <a href="{{ route('admin.transport.bookings.index') }}" class="small-box-footer">Plus d'infos <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $stats['pending_bookings'] }}</h3>
                            <p>En attente</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <a href="{{ route('admin.transport.bookings.index', ['status' => 'requested']) }}" class="small-box-footer">Plus d'infos <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ number_format($stats['total_revenue'], 0, ',', ' ') }} <small>FCFA</small></h3>
                            <p>Revenus Transport</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <a href="#" class="small-box-footer">Plus d'infos <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ \App\Domain\Transport\Models\TransportVehicle::count() }}</h3>
                            <p>Véhicules</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-truck-pickup"></i>
                        </div>
                        <a href="{{ route('admin.transport.vehicles.index') }}" class="small-box-footer">Plus d'infos <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header border-0">
                            <h3 class="card-title">Services Transport</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped table-valign-middle">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Réservations</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Taxi (Ride-hailing)</td>
                                        <td>{{ \App\Domain\Transport\Models\TransportBooking::where('type', 'taxi')->count() }}</td>
                                        <td><span class="badge badge-success">Actif</span></td>
                                        <td><a href="{{ route('admin.transport.pricing.index') }}" class="text-muted"><i class="fas fa-cog"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Covoiturage (Ride-sharing)</td>
                                        <td>{{ \App\Domain\Transport\Models\TransportBooking::where('type', 'carpool')->count() }}</td>
                                        <td><span class="badge badge-success">Actif</span></td>
                                        <td><a href="{{ route('admin.transport.pricing.index') }}" class="text-muted"><i class="fas fa-cog"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td>Location (Car Rental)</td>
                                        <td>{{ \App\Domain\Transport\Models\TransportBooking::where('type', 'rental')->count() }}</td>
                                        <td><span class="badge badge-success">Actif</span></td>
                                        <td><a href="{{ route('admin.transport.pricing.index') }}" class="text-muted"><i class="fas fa-cog"></i></a></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

