@extends('frontend.layouts.app')
@section('title', 'Services de Transport | BantuDelice')

@section('content')
<div class="banner-text">
    <div class="container">
        <h2>Services de Transport <br> <span>Choisissez votre mode de déplacement.</span></h2>
    </div>
</div>

<div class="m9agile-deals" id="services">
    <div class="container">
        <h3 class="m9ls-title">Nos Solutions de Transport</h3>
        <div class="row">
            <!-- Taxi / VTC -->
            <div class="col-md-4 col-sm-4 deals-grids">
                <a href="{{ route('transport.taxi') }}" style="text-decoration: none; color: inherit;">
                    <div class="thumbnail text-center p-4" style="border-radius: 15px; transition: transform .2s;">
                        <img src="{{ asset('images/svg/taxi.png') }}" alt="Taxi" style="width: 80px; margin: 20px auto;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3063/3063822.png'">
                        <div class="caption">
                            <h4>Taxi (Ride-hailing)</h4>
                            <p>Commandez une course immédiate. Chauffeurs professionnels et tarifs transparents.</p>
                            <div class="mt-3">
                                <span class="btn btn-primary">Commander</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Covoiturage -->
            <div class="col-md-4 col-sm-4 deals-grids">
                <a href="{{ route('transport.carpool') }}" style="text-decoration: none; color: inherit;">
                    <div class="thumbnail text-center p-4" style="border-radius: 15px; transition: transform .2s;">
                        <img src="{{ asset('images/svg/car-sharing.png') }}" alt="Covoiturage" style="width: 80px; margin: 20px auto;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2830/2830211.png'">
                        <div class="caption">
                            <h4>Covoiturage</h4>
                            <p>Partagez vos trajets et réduisez vos frais. Voyagez malin et faites des rencontres.</p>
                            <div class="mt-3">
                                <span class="btn btn-primary">Réserver un trajet</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Location de voiture -->
            <div class="col-md-4 col-sm-4 deals-grids">
                <a href="{{ route('transport.rental') }}" style="text-decoration: none; color: inherit;">
                    <div class="thumbnail text-center p-4" style="border-radius: 15px; transition: transform .2s;">
                        <img src="{{ asset('images/svg/car-rental.png') }}" alt="Location" style="width: 80px; margin: 20px auto;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2558/2558362.png'">
                        <div class="caption">
                            <h4>Location de voiture</h4>
                            <p>Besoin d'un véhicule pour un jour ou plus ? Découvrez notre large catalogue.</p>
                            <div class="mt-3">
                                <span class="btn btn-primary">Voir le catalogue</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row mt-5 text-center">
            <div class="col-md-12">
                <a href="{{ route('transport.my_bookings') }}" class="btn btn-outline-primary btn-lg">
                    <i class="fa fa-list"></i> Mes réservations transport
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .deals-grids a:hover .thumbnail {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .thumbnail {
        padding: 25px;
        min-height: 380px;
    }
</style>
@endsection

