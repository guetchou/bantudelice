@extends('frontend.layouts.app-modern')
@section('title', 'Restaurants ' . ($cuisine->name ?? '') . ' | BantuDelice')
@section('description', 'Découvrez tous les restaurants de cuisine ' . ($cuisine->name ?? '') . ' sur BantuDelice.')

@section('styles')
<style>
    .cuisine-page {
        padding: 120px 0 60px;
        background: linear-gradient(135deg, #FAFAFA 0%, #FFFFFF 100%);
        min-height: calc(100vh - 80px);
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .page-header h1 span {
        color: var(--primary);
    }
    
    .page-header p {
        color: var(--gray-600);
        font-size: 1.1rem;
    }
    
    .breadcrumb-modern {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
    
    .breadcrumb-modern a {
        color: var(--gray-500);
        text-decoration: none;
    }
    
    .breadcrumb-modern a:hover {
        color: var(--primary);
    }
    
    .breadcrumb-modern .separator {
        color: var(--gray-300);
    }
    
    .breadcrumb-modern .current {
        color: var(--primary);
        font-weight: 600;
    }
    
    .restaurants-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    
    .restaurant-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .restaurant-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.12);
    }
    
    .restaurant-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }
    
    .restaurant-content {
        padding: 1.25rem;
    }
    
    .restaurant-name {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .restaurant-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        color: var(--gray-500);
        font-size: 0.875rem;
        margin-bottom: 0.75rem;
    }
    
    .restaurant-meta span {
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }
    
    .restaurant-meta i {
        color: var(--primary);
    }
    
    .rating {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        background: rgba(251, 191, 36, 0.1);
        color: #B45309;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .rating i {
        color: #FBBF24;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 20px;
        max-width: 500px;
        margin: 0 auto;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: var(--gray-300);
        margin-bottom: 1rem;
    }
    
    .empty-state h3 {
        color: var(--gray-700);
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: var(--gray-500);
        margin-bottom: 1.5rem;
    }
</style>
@endsection

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
            <a href="{{ route('resturant.detail', $restaurant->id) }}" class="restaurant-card">
                <img src="{{ $restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/placeholder.png') }}" 
                     alt="{{ $restaurant->name }}"
                     class="restaurant-image"
                     onerror="this.src='{{ asset('images/placeholder.png') }}'">
                
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
            <a href="{{ route('home') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>
        </div>
        @endif
    </div>
</section>
@endsection
