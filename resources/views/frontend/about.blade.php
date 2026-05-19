@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', 'À propos | ' . $foodBrandName)
@section('description', 'Découvrez l\'histoire de ' . $foodBrandName . ', votre service de livraison à domicile de confiance au Congo.')
@section('body_class', 'bd-about-page')

@section('content')
<section class="about-hero">
    <div class="container">
        <span class="section-badge about-hero-badge">À propos</span>
        <h1 class="about-hero-title">
            Notre Histoire
        </h1>
        <p class="about-hero-copy">
            Découvrez qui nous sommes et notre mission de vous servir au mieux.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="about-shell">
            <div class="about-block">
                <div class="about-heading-row">
                    <h2 class="about-heading">Notre Histoire</h2>
                </div>
                <p class="about-copy">
                    Dans un monde où chaque minute compte pour les clients, la nouvelle vague du commerce est le e-commerce associé à une livraison rapide. Dans une communauté avec tant de personnes talentueuses et créatives, offrant des plats alléchants, nous avons pensé qu'il serait formidable de trouver tous ces plats en un seul endroit pratique en ligne, connectant les clients à des plats frais, copieux et simplement inoubliables.
                </p>
                <p class="about-copy">
                    C'est ainsi que <strong>{{ $foodBrandName }}</strong> a été lancé en tant que marketplace, pour augmenter le choix des marques, apporter de la valeur à notre communauté et concrétiser cette vision.
                </p>
            </div>
            
            <div class="about-block about-block--panel">
                <div class="about-heading-row">
                    <h2 class="about-heading">Comment ça marche ?</h2>
                </div>
                <p class="about-copy">
                    Chaque minute compte, alors installez-vous confortablement et laissez-nous nous occuper de vous, en livrant directement à votre porte. Sur la plateforme {{ $foodBrandName }}, créez votre compte, connectez-vous et commandez directement depuis le site web.
                </p>
                <p class="about-copy">
                    Après vous être connecté, indiquez si votre commande sera pour la livraison ou le retrait. {{ $foodBrandName }} est une plateforme de commande de nourriture en ligne et mobile qui vous livre et vous sert, que ce soit par retrait ou livraison de nourriture, boissons et colis, des meilleurs restaurants et marchands locaux près de chez vous.
                </p>
            </div>
            
            <div class="about-block">
                <div class="about-heading-row">
                    <h2 class="about-heading">Notre Mission</h2>
                </div>
                <p class="about-copy">
                    Notre objectif est de pouvoir augmenter la portée des clients et des marchands ; en livrant les commandes que les clients ne peuvent pas récupérer. En vous associant à {{ $foodBrandName }}, nous pouvons vous offrir qualité et commodité directement à votre entreprise ou à votre porte.
                </p>
                <p class="about-copy">
                    Notre ambition est également d'élargir les choix et la commodité des clients, en utilisant de nouvelles innovations pour livrer dans un rayon de 8 km et assurer une livraison le jour même des colis reçus avant midi.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section about-values">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Nos valeurs</span>
            <h2 class="section-title">Ce qui nous anime</h2>
        </div>
        
        <div class="about-values-grid">
            <div class="about-value-card">
                <h3 class="about-value-title">Rapidité</h3>
                <p class="about-value-copy">Livraison express pour satisfaire vos envies sans attendre.</p>
            </div>
            
            <div class="about-value-card">
                <h3 class="about-value-title">Qualité</h3>
                <p class="about-value-copy">Des partenaires sélectionnés pour leur excellence.</p>
            </div>
            
            <div class="about-value-card">
                <h3 class="about-value-title">Confiance</h3>
                <p class="about-value-copy">Un service fiable sur lequel vous pouvez compter.</p>
            </div>
            
            <div class="about-value-card">
                <h3 class="about-value-title">Communauté</h3>
                <p class="about-value-copy">Soutenir les commerces locaux et créer des liens.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="about-actions-grid">
            <div class="about-social-block">
                <h3 class="about-action-heading">Restez connectés</h3>
            </div>

            <div class="about-actions-block">
                <h3 class="about-action-heading">Aller plus loin</h3>
                <div class="about-actions-row">
                    <a href="{{ route('contact.us') }}" class="about-action-link">
                        <i class="fas fa-headset about-action-icon"></i>
                        <div class="about-action-copy">
                            <small class="about-action-overline">Besoin d'aide ?</small>
                            Contacter l'équipe
                        </div>
                    </a>
                    <a href="{{ route('offers') }}" class="about-action-link">
                        <i class="fas fa-tags about-action-icon"></i>
                        <div class="about-action-copy">
                            <small class="about-action-overline">À découvrir</small>
                            Voir les offres
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
