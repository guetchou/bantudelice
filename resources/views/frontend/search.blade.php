@extends('frontend.layouts.app-modern')
@section('title', 'Recherche : ' . $qurey . ' | BantuDelice')
@section('description', 'Résultats de recherche pour ' . $qurey . ' sur BantuDelice.')

@section('styles')
<style>
    .search-page {
        padding: 120px 0 60px;
        background: linear-gradient(135deg, #FAFAFA 0%, #FFFFFF 100%);
        min-height: calc(100vh - 80px);
    }
    
    .page-header {
        margin-bottom: 2rem;
    }
    
    .breadcrumb-modern {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        margin-bottom: 1rem;
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
        color: var(--gray-900);
        font-weight: 600;
    }
    
    .search-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .search-title span {
        color: var(--primary);
    }
    
    .results-count {
        color: var(--gray-600);
    }
    
    .restaurants-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .restaurant-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .restaurant-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }
    
    .restaurant-image {
        width: 100%;
        height: 160px;
        object-fit: cover;
    }
    
    .restaurant-content {
        padding: 1.25rem;
    }
    
    .restaurant-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .restaurant-cuisines {
        font-size: 0.85rem;
        color: var(--gray-500);
        margin-bottom: 0.75rem;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .restaurant-rating {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        color: #B45309;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .restaurant-rating i {
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
<section class="search-page">
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <nav class="breadcrumb-modern">
                <a href="{{ route('home') }}">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <span class="separator">/</span>
                <span class="current">Recherche</span>
            </nav>
            
            <h1 class="search-title">Résultats pour "<span>{{ $qurey }}</span>"</h1>
            <p class="results-count">{{ $restaurants->count() }} restaurant{{ $restaurants->count() > 1 ? 's' : '' }} trouvé{{ $restaurants->count() > 1 ? 's' : '' }}</p>
        </div>
        
        <!-- Filtres avancés -->
        <div class="search-filters" style="background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <form action="{{ route('serach') }}" method="get" id="filterForm">
                <input type="hidden" name="qurey" value="{{ $qurey }}">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #191919; font-size: 0.875rem;">Note minimum</label>
                        @php
                            $topRatedThreshold = \App\Services\ConfigService::getTopRatedThreshold();
                        @endphp
                        <select name="min_rating" class="form-select" style="width: 100%; padding: 0.625rem; border: 1px solid #E0E0E0; border-radius: 8px;">
                            <option value="">Toutes les notes</option>
                            <option value="{{ number_format($topRatedThreshold, 1) }}" {{ request('min_rating') == number_format($topRatedThreshold, 1) ? 'selected' : '' }}>{{ number_format($topRatedThreshold, 1) }}+ ⭐</option>
                            <option value="4.0" {{ request('min_rating') == '4.0' ? 'selected' : '' }}>4.0+ ⭐</option>
                            <option value="3.5" {{ request('min_rating') == '3.5' ? 'selected' : '' }}>3.5+ ⭐</option>
                            <option value="3.0" {{ request('min_rating') == '3.0' ? 'selected' : '' }}>3.0+ ⭐</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #191919; font-size: 0.875rem;">Cuisine</label>
                        <select name="cuisine_id" class="form-select" style="width: 100%; padding: 0.625rem; border: 1px solid #E0E0E0; border-radius: 8px;">
                            <option value="">Toutes les cuisines</option>
                            @php $cuisines = \App\Cuisine::all(); @endphp
                            @foreach($cuisines as $cuisine)
                                <option value="{{ $cuisine->id }}" {{ request('cuisine_id') == $cuisine->id ? 'selected' : '' }}>{{ $cuisine->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #191919; font-size: 0.875rem;">Ville</label>
                        <input type="text" name="city" value="{{ request('city') }}" placeholder="Filtrer par ville" class="form-control" style="width: 100%; padding: 0.625rem; border: 1px solid #E0E0E0; border-radius: 8px;">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.625rem;">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        @if($restaurants->count() > 0)
        <div class="restaurants-grid">
            @foreach($restaurants as $restaurant)
            <a href="{{ route('resturant.detail', $restaurant->id) }}" class="restaurant-card">
                <img src="{{ $restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/placeholder.png') }}" 
                     alt="{{ $restaurant->name }}"
                     class="restaurant-image"
                     onerror="this.src='{{ asset('images/placeholder.png') }}'">
                
                <div class="restaurant-content">
                    <h3 class="restaurant-name">{{ $restaurant->name }}</h3>
                    <p class="restaurant-cuisines">{{ $restaurant->cuisines->pluck('name')->implode(', ') }}</p>
                    <span class="restaurant-rating">
                        <i class="fas fa-star"></i>
                        {{ number_format($restaurant->avg_rating ?? $restaurant->ratings()->avg('rating') ?? 4.0, 1) }}
                    </span>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Aucun résultat</h3>
            <p>Nous n'avons trouvé aucun restaurant correspondant à "{{ $qurey }}".</p>
            <a href="{{ route('home') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>
        </div>
        @endif
    </div>
</section>
@endsection
