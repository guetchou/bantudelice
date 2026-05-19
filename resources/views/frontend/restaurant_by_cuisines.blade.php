@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', 'Restaurants ' . ($cuisine->name ?? '') . ' | ' . $foodBrandName)
@section('description', 'Découvrez tous les restaurants de cuisine ' . ($cuisine->name ?? '') . ' sur ' . $foodBrandName . '.')
@section('body_class', 'bd-cuisine-page')

@section('content')
<section class="cuisine-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-modern">
            <a href="{{ route('home') }}">
                <i class="fas fa-home"></i> Accueil
            </a>
            <span class="separator">/</span>
            <span class="current">{{ $cuisine->name }}</span>
        </nav>
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>Cuisine <span>{{ $cuisine->name }}</span></h1>
            <p>{{ $cuisine->restaurants->count() }} restaurant{{ $cuisine->restaurants->count() > 1 ? 's' : '' }} disponible{{ $cuisine->restaurants->count() > 1 ? 's' : '' }}</p>
        </div>
        
        @if($cuisine->restaurants->count() > 0)
        <div class="restaurants-grid">
            @foreach($cuisine->restaurants as $restaurant)
            @php
                $restaurantImage = method_exists($restaurant, 'publicIdentityImageUrl')
                    ? $restaurant->publicIdentityImageUrl()
                    : ($restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/home/service-restaurant.jpg'));
            @endphp
            <a href="{{ route('restaurant.detail', $restaurant->id) }}" class="restaurant-card">
                <div class="cuisine-restaurant-media">
                    <img src="{{ $restaurantImage }}" 
                         alt="{{ $restaurant->name }}"
                         class="restaurant-card-image">
                </div>
                
                <div class="restaurant-content">
                    <h3 class="restaurant-name">{{ $restaurant->name }}</h3>
                    
                    <div class="restaurant-meta">
                        @if($restaurant->avg_delivery_time)
                        <span>
                            <i class="fas fa-clock"></i>
                            {{ $restaurant->avg_delivery_time }}
                        </span>
                        @endif
                        
                        @if($restaurant->delivery_charges)
                        <span>
                            <i class="fas fa-motorcycle"></i>
                            {{ number_format($restaurant->delivery_charges, 0, ',', ' ') }} FCFA
                        </span>
                        @endif
                    </div>
                    
                    <span class="rating">
                        <i class="fas fa-star"></i>
                        {{ number_format($restaurant->avg_rating ?? $restaurant->ratings()->avg('rating') ?? 4.0, 1) }}
                    </span>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="empty-state">
            <i class="fas fa-store-slash"></i>
            <h3>Aucun restaurant disponible</h3>
            <p>Il n'y a pas encore de restaurants pour cette cuisine.</p>
            <a href="{{ route('home') }}" class="cuisine-page-cta">
                <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>
        </div>
        @endif
    </div>
</section>
@endsection
