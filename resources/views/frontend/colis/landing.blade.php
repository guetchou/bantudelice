@extends('frontend.layouts.app-modern')

@section('title', 'Service de Livraison Colis Congo | BantuDelice')

@section('content')
<!-- Banner -->
<div class="banner-colis text-white py-5" style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('{{ asset('images/i2.jpg') }}'); background-size: cover; background-position: center;">
    <div class="container py-5 text-center">
        <h1 class="display-4 font-weight-bold mb-3" style="color: #fff !important;">Envoyez vos colis en toute confiance</h1>
        <p class="lead mb-4">Livraison rapide, sécurisée et traçable à Brazzaville, Pointe-Noire et partout au Congo.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="{{ route('colis.create') }}" class="btn btn-primary btn-lg px-5">CRÉER UN ENVOI</a>
            <a href="{{ route('colis.track_public') }}" class="btn btn-outline-light btn-lg px-5">SUIVRE UN COLIS</a>
        </div>
    </div>
</div>

<!-- Features -->
<div class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="font-weight-bold">Pourquoi choisir BantuDelice Colis ?</h2>
            <p class="text-muted">Le leader de la livraison au Congo avec une expertise locale.</p>
        </div>
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <div class="p-4 rounded-circle bg-light d-inline-block mb-3" style="width: 80px; height: 80px; line-height: 40px;">
                    <i class="fa fa-clock-o fa-2x text-primary"></i>
                </div>
                <h4>Rapide & Express</h4>
                <p class="text-muted">Livraison le jour même ou en 24h selon le niveau de service choisi.</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="p-4 rounded-circle bg-light d-inline-block mb-3" style="width: 80px; height: 80px; line-height: 40px;">
                    <i class="fa fa-map-marker fa-2x text-primary"></i>
                </div>
                <h4>Suivi Temps Réel</h4>
                <p class="text-muted">Gardez un oeil sur votre colis de l'enlèvement jusqu'à la remise en main propre.</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="p-4 rounded-circle bg-light d-inline-block mb-3" style="width: 80px; height: 80px; line-height: 40px;">
                    <i class="fa fa-shield fa-2x text-primary"></i>
                </div>
                <h4>Sécurisé & Garanti</h4>
                <p class="text-muted">Preuve de livraison par OTP et assurance disponible pour vos objets de valeur.</p>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Preview -->
<div class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <h2 class="font-weight-bold mb-4">Tarifs transparents</h2>
                <p class="mb-4">Nous proposons des tarifs adaptés à la réalité congolaise, basés sur la distance et le poids.</p>
                <ul class="list-unstyled">
                    <li class="mb-3"><i class="fa fa-check text-success mr-2"></i> À partir de <strong>1 500 XAF</strong> pour les petits colis</li>
                    <li class="mb-3"><i class="fa fa-check text-success mr-2"></i> Option <strong>Contre-remboursement (COD)</strong></li>
                    <li class="mb-3"><i class="fa fa-check text-success mr-2"></i> Réseau de <strong>Points Relais</strong> partenaires</li>
                </ul>
                <a href="{{ route('colis.create') }}" class="btn btn-primary mt-3">OBTENIR UN DEVIS GRATUIT</a>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4 text-center" style="background: #4A67B2; color: #fff;">
                    <i class="fa fa-calculator fa-3x mb-3"></i>
                    <h3>Calculateur Intégré</h3>
                    <p>Obtenez un prix exact en quelques clics avant même de valider votre commande.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="py-5 text-white" style="background: #ff0000;">
    <div class="container text-center">
        <h2 class="font-weight-bold mb-3">Prêt à expédier ?</h2>
        <p class="lead mb-4">Rejoignez des milliers de clients satisfaits au Congo.</p>
        <a href="{{ route('colis.create') }}" class="btn btn-light btn-lg px-5 font-weight-bold" style="color: #ff0000 !important;">COMMANDER UNE LIVRAISON</a>
    </div>
</div>
@endsection

