@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', 'Mes restaurants favoris | ' . $foodBrandName)
@section('description', 'Retrouvez les restaurants que vous avez enregistrés dans vos favoris.')

@section('content')
<section class="section" style="padding-top: 110px; background: linear-gradient(180deg, #fff 0%, #f8fafc 100%); min-height: 80vh;">
    <div class="container">
        <div style="margin-bottom: 1.5rem;">
            <h1 style="font-size: clamp(2rem, 3vw, 2.8rem); font-weight: 800; margin-bottom: 0.5rem;">Restaurants favoris</h1>
            <p style="color: var(--gray-500); max-width: 720px;">Retrouvez ici les restaurants que vous avez enregistrés pour commander plus vite.</p>
        </div>

        @if($restaurants->count())
            <div class="bd-home-restaurant-grid">
                @foreach($restaurants as $restaurant)
                    @php
                        $deliveryFee = $restaurant->delivery_charges ?? \App\Services\ConfigService::getDefaultDeliveryFee();
                        $rating = number_format($restaurant->avg_rating ?? \App\Services\ConfigService::getDefaultRating(), 1);
                        $cuisinesList = $restaurant->cuisines->pluck('name')->take(3)->implode(' · ') ?: 'Cuisine variée';
                        $restaurantImage = method_exists($restaurant, 'publicIdentityImageUrl')
                            ? $restaurant->publicIdentityImageUrl()
                            : ($restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/home/service-restaurant.jpg'));
                    @endphp
                    <article class="bd-home-restaurant-card" onclick="window.location.href='{{ route('restaurant.detail', $restaurant->id) }}'">
                        <div class="bd-home-restaurant-card__media">
                            <img src="{{ $restaurantImage }}"
                                 alt="{{ $restaurant->name }}"
                                 onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
                            <div class="bd-home-restaurant-card__gradient"></div>
                            <span class="bd-home-restaurant-card__badge">Favori</span>
                        </div>
                        <div class="bd-home-restaurant-card__body">
                            <div class="bd-home-restaurant-card__title-row">
                                <div>
                                    <h3>{{ $restaurant->name }}</h3>
                                    <p>{{ $cuisinesList }}</p>
                                </div>
                                <span class="bd-home-restaurant-card__rating">{{ $rating }}</span>
                            </div>
                            <div class="bd-home-restaurant-card__meta">
                                <span>Livraison</span>
                                <strong>{{ number_format($deliveryFee, 0, ',', ' ') }} FCFA</strong>
                            </div>
                            <span class="bd-home-restaurant-card__cta">Commander maintenant</span>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="bd-home-empty-card">
                <h3>Aucun favori pour le moment</h3>
                <p>Ajoutez des restaurants depuis la page d’accueil ou la fiche restaurant.</p>
                <a href="{{ route('restaurants.all') }}" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;margin-top:.75rem;">Explorer les restaurants</a>
            </div>
        @endif
    </div>
</section>
@endsection
