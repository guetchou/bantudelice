@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();

    $featuredOffers = [
        [
            'tone' => 'sunset',
            'badge' => 'NOUVEAU CLIENT',
            'value' => '-20%',
            'title' => 'Sur votre première commande',
            'description' => 'Inscrivez-vous et recevez automatiquement 20% de réduction sur votre première commande.',
            'code' => 'BIENVENUE20',
            'cta' => null,
            'cta_url' => null,
        ],
        [
            'tone' => 'forest',
            'badge' => 'LIVRAISON',
            'value' => 'Livraison gratuite',
            'title' => 'Dès 15 000 FCFA',
            'description' => 'Profitez de la livraison gratuite sur toutes les commandes supérieures à 15 000 FCFA.',
            'code' => null,
            'cta' => 'Commander maintenant',
            'cta_url' => route('home'),
        ],
        [
            'tone' => 'ember',
            'badge' => 'WEEK-END',
            'value' => '-15%',
            'title' => 'Spécial Week-end',
            'description' => 'Chaque samedi et dimanche, profitez de 15% de réduction sur toutes vos commandes.',
            'code' => 'WEEKEND15',
            'cta' => null,
            'cta_url' => null,
        ],
    ];

    $secondaryOffers = [
        [
            'tone' => 'sunset',
            'name' => 'Anniversaire',
            'value' => '-25% Offert',
            'description' => 'Recevez 25% de réduction le jour de votre anniversaire. Ajoutez votre date de naissance dans votre profil.',
            'cta' => 'Mettre à jour mon profil',
            'url' => route('user.profile'),
        ],
        [
            'tone' => 'forest',
            'name' => 'Parrainage',
            'value' => '5 000 FCFA Chacun',
            'description' => 'Parrainez un ami et recevez chacun 5 000 FCFA de crédit sur votre prochaine commande.',
            'cta' => 'Parrainer un ami',
            'url' => route('user.profile'),
        ],
        [
            'tone' => 'terra',
            'name' => 'Fidélité',
            'value' => '1 Commande = 1 Point',
            'description' => 'Cumulez des points à chaque commande et échangez-les contre des réductions et avantages utiles.',
            'cta' => 'Voir mes points',
            'url' => route('user.profile'),
        ],
        [
            'tone' => 'gold',
            'name' => 'Happy Hour',
            'value' => '-10% de 14h à 17h',
            'description' => 'Profitez de 10% de réduction sur vos commandes passées entre 14h et 17h en semaine.',
            'cta' => 'Commander',
            'url' => route('home'),
        ],
    ];
@endphp
@section('title', 'Promotions & Offres | ' . $foodBrandName)
@section('description', 'Consultez les offres et avantages disponibles sur ' . $foodBrandName . '.')
@section('body_class', 'bd-offers-page')

@section('content')
<section class="offers-hero">
    <div class="offers-hero-orb offers-hero-orb--left"></div>
    <div class="offers-hero-orb offers-hero-orb--right"></div>

    <div class="container offers-hero-inner">
        <span class="section-badge offers-hero-badge">Offres en cours</span>
        <h1 class="offers-hero-title">Promotions & Offres</h1>
        <p class="offers-hero-copy">
            Consultez les avantages actuellement proposés et les conditions pour en bénéficier.
        </p>
    </div>
</section>

<section class="section offers-featured-section">
    <div class="container">
        <div class="offers-featured-grid">
            @foreach($featuredOffers as $offer)
                <article class="offers-featured-card offers-featured-card--{{ $offer['tone'] }}">
                    <div class="offers-featured-orb offers-featured-orb--a"></div>
                    <div class="offers-featured-orb offers-featured-orb--b"></div>

                    <span class="offers-featured-badge">{{ $offer['badge'] }}</span>
                    <h2 class="offers-featured-value">{{ $offer['value'] }}</h2>
                    <h3 class="offers-featured-title">{{ $offer['title'] }}</h3>
                    <p class="offers-featured-copy">{{ $offer['description'] }}</p>

                    @if($offer['code'])
                        <div class="offers-code-box">
                            <div>
                                <small class="offers-code-label">Code promo</small>
                                <p class="offers-code-value">{{ $offer['code'] }}</p>
                            </div>
                            <button type="button" class="offers-code-button" onclick="copyCode('{{ $offer['code'] }}')">
                                Copier
                            </button>
                        </div>
                    @elseif($offer['cta'])
                        <a href="{{ $offer['cta_url'] }}" class="offers-featured-cta">
                            {{ $offer['cta'] }}
                        </a>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="section offers-secondary-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Plus d'offres</span>
            <h2 class="section-title">Offres en cours</h2>
        </div>

        <div class="offers-secondary-grid">
            @foreach($secondaryOffers as $offer)
                <article class="offers-secondary-card">
                    <div class="offers-secondary-head offers-secondary-head--{{ $offer['tone'] }}">
                        <h3 class="offers-secondary-name">{{ $offer['name'] }}</h3>
                    </div>
                    <div class="offers-secondary-body">
                        <h4 class="offers-secondary-value">{{ $offer['value'] }}</h4>
                        <p class="offers-secondary-copy">{{ $offer['description'] }}</p>
                        <a href="{{ $offer['url'] }}" class="offers-secondary-link">{{ $offer['cta'] }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="offers-reminder">
            <h2 class="offers-reminder-title">Ne ratez aucune offre</h2>
            <p class="offers-reminder-copy">
                Pour toute question sur une offre ou un code promo, contactez l'équipe avant votre commande.
            </p>

            <div class="offers-reminder-actions">
                <a href="{{ route('contact.us') }}" class="offers-reminder-primary">Nous contacter</a>
                <a href="{{ route('help') }}" class="offers-reminder-secondary">Centre d'aide</a>
            </div>
        </div>
    </div>
</section>

<div id="copyToast" class="offers-toast" role="status" aria-live="polite">
    Code copié
</div>
@endsection

@section('scripts')
<script>
    function copyCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            const toast = document.getElementById('copyToast');
            toast.classList.add('is-visible');
            setTimeout(() => {
                toast.classList.remove('is-visible');
            }, 2000);
        });
    }
</script>
@endsection
