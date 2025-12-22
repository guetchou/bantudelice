@extends('frontend.layouts.app-modern')
@section('title', 'À propos | BantuDelice')
@section('description', 'Découvrez l\'histoire de BantuDelice, votre service de livraison à domicile de confiance au Congo.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <img src="{{ asset('images/icons/happy-customer.svg') }}" alt="À propos" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 6px;">
            À propos
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">
            Notre Histoire
        </h1>
        <p style="color: rgba(255,255,255,0.8); max-width: 600px; margin: 1rem auto 0; font-size: 1.125rem;">
            Découvrez qui nous sommes et notre mission de vous servir au mieux.
        </p>
    </div>
</section>

<!-- Story Section -->
<section class="section">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <!-- Notre Histoire -->
            <div style="margin-bottom: 3rem;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary) 0%, var(--warning) 100%); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <img src="{{ asset('images/icons/restaurant.svg') }}" alt="Histoire" style="width: 36px; height: 36px;">
                    </div>
                    <h2 style="margin: 0;">Notre Histoire</h2>
                </div>
                <p style="font-size: 1.0625rem; line-height: 1.8; color: var(--gray-600);">
                    Dans un monde où chaque minute compte pour les clients, la nouvelle vague du commerce est le e-commerce associé à une livraison rapide. Dans une communauté avec tant de personnes talentueuses et créatives, offrant des plats alléchants, nous avons pensé qu'il serait formidable de trouver tous ces plats en un seul endroit pratique en ligne, connectant les clients à des plats frais, copieux et simplement inoubliables.
                </p>
                <p style="font-size: 1.0625rem; line-height: 1.8; color: var(--gray-600);">
                    C'est ainsi que <strong>BantuDelice</strong> a été lancé en tant que marketplace, pour augmenter le choix des marques, apporter de la valeur à notre communauté et concrétiser cette vision.
                </p>
            </div>
            
            <!-- Comment ça marche -->
            <div style="margin-bottom: 3rem; background: var(--gray-50); padding: 2rem; border-radius: var(--radius-2xl);">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary) 0%, var(--warning) 100%); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <img src="{{ asset('images/icons/delivery-scooter.svg') }}" alt="Comment ça marche" style="width: 36px; height: 36px;">
                    </div>
                    <h2 style="margin: 0;">Comment ça marche ?</h2>
                </div>
                <p style="font-size: 1.0625rem; line-height: 1.8; color: var(--gray-600);">
                    Chaque minute compte, alors installez-vous confortablement et laissez-nous nous occuper de vous, en livrant directement à votre porte. Sur la plateforme BantuDelice, en tant que nouveau client, inscrivez-vous ou connectez-vous via le site web ou téléchargez l'une des applications sur Google Play Store ou l'Apple Store.
                </p>
                <p style="font-size: 1.0625rem; line-height: 1.8; color: var(--gray-600);">
                    Après vous être connecté, indiquez si votre commande sera pour la livraison ou le retrait. BantuDelice est une plateforme de commande de nourriture en ligne et mobile qui vous livre et vous sert, que ce soit par retrait ou livraison de nourriture, boissons et colis, des meilleurs restaurants et marchands locaux près de chez vous.
                </p>
            </div>
            
            <!-- Notre Mission -->
            <div style="margin-bottom: 3rem;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary) 0%, var(--warning) 100%); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <img src="{{ asset('images/icons/celebration.svg') }}" alt="Mission" style="width: 36px; height: 36px;">
                    </div>
                    <h2 style="margin: 0;">Notre Mission</h2>
                </div>
                <p style="font-size: 1.0625rem; line-height: 1.8; color: var(--gray-600);">
                    Notre objectif est de pouvoir augmenter la portée des clients et des marchands ; en livrant les commandes que les clients ne peuvent pas récupérer. En vous associant à BantuDelice, nous pouvons vous offrir qualité et commodité directement à votre entreprise ou à votre porte.
                </p>
                <p style="font-size: 1.0625rem; line-height: 1.8; color: var(--gray-600);">
                    Notre ambition est également d'élargir les choix et la commodité des clients, en utilisant de nouvelles innovations pour livrer dans un rayon de 8 km et assurer une livraison le jour même des colis reçus avant midi.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="section" style="background: var(--gray-50);">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">
                <img src="{{ asset('images/icons/happy-customer.svg') }}" alt="Valeurs" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 6px;">
                Nos Valeurs
            </span>
            <h2 class="section-title">Ce qui nous anime</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
            <div style="text-align: center; padding: 2rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <img src="{{ asset('images/icons/delivery-scooter.svg') }}" alt="Rapidité" style="width: 48px; height: 48px;">
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Rapidité</h3>
                <p style="color: var(--gray-500);">Livraison express pour satisfaire vos envies sans attendre.</p>
            </div>
            
            <div style="text-align: center; padding: 2rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <img src="{{ asset('images/icons/restaurant.svg') }}" alt="Qualité" style="width: 48px; height: 48px;">
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Qualité</h3>
                <p style="color: var(--gray-500);">Des partenaires sélectionnés pour leur excellence.</p>
            </div>
            
            <div style="text-align: center; padding: 2rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <img src="{{ asset('images/icons/package-box.svg') }}" alt="Confiance" style="width: 48px; height: 48px;">
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Confiance</h3>
                <p style="color: var(--gray-500);">Un service fiable sur lequel vous pouvez compter.</p>
            </div>
            
            <div style="text-align: center; padding: 2rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <img src="{{ asset('images/icons/happy-customer.svg') }}" alt="Communauté" style="width: 48px; height: 48px;">
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Communauté</h3>
                <p style="color: var(--gray-500);">Soutenir les commerces locaux et créer des liens.</p>
            </div>
        </div>
    </div>
</section>

<!-- Social & Apps Section -->
<section class="section">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 3rem; align-items: center;">
            <!-- Social -->
            <div style="text-align: center;">
                <h3 style="margin-bottom: 1.5rem;">Restez connectés</h3>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="https://www.facebook.com/bantudelice/" target="_blank" 
                       style="width: 50px; height: 50px; background: #1877F2; color: white; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; transition: transform 0.2s;">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/bantudelice" target="_blank"
                       style="width: 50px; height: 50px; background: #1DA1F2; color: white; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; transition: transform 0.2s;">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.instagram.com/bantudelice/" target="_blank"
                       style="width: 50px; height: 50px; background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); color: white; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; transition: transform 0.2s;">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#"
                       style="width: 50px; height: 50px; background: #25D366; color: white; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; transition: transform 0.2s;">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
            
            <!-- Apps -->
            <div style="text-align: center;">
                <h3 style="margin-bottom: 1.5rem;">Téléchargez notre application</h3>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="#" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">
                        <i class="fab fa-apple" style="font-size: 1.5rem; margin-right: 0.5rem;"></i>
                        <div style="text-align: left;">
                            <small style="display: block; font-size: 0.7rem; opacity: 0.7;">Télécharger sur</small>
                            App Store
                        </div>
                    </a>
                    <a href="#" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">
                        <i class="fab fa-google-play" style="font-size: 1.5rem; margin-right: 0.5rem;"></i>
                        <div style="text-align: left;">
                            <small style="display: block; font-size: 0.7rem; opacity: 0.7;">Disponible sur</small>
                            Google Play
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
