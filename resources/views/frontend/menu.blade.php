@extends('frontend.layouts.app-modern')
@section('title', $restaurant->name . ' | BantuDelice')
@section('description', $restaurant->description ?? 'Découvrez le menu de ' . $restaurant->name . ' et commandez vos plats préférés.')

@php
    // Récupérer les valeurs par défaut depuis ConfigService (DB)
    $defaultRating = \App\Services\ConfigService::getDefaultRating();
@endphp

@section('styles')
<style>
    .restaurant-page {
        padding-top: 80px;
        background: #F8FAFC;
        min-height: 100vh;
    }
    
    /* Hero Section */
    .restaurant-hero {
        position: relative;
        height: 350px;
        overflow: hidden;
    }
    
    .hero-bg {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: brightness(0.4);
    }
    
    .hero-content {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 3rem 0 2rem;
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);
    }
    
    .hero-content .container {
        display: flex;
        align-items: flex-end;
        gap: 2rem;
    }
    
    .restaurant-logo {
        width: 120px;
        height: 120px;
        border-radius: 20px;
        border: 4px solid white;
        object-fit: cover;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        flex-shrink: 0;
    }
    
    .restaurant-info {
        flex: 1;
    }
    
    .restaurant-name {
        font-size: 2.5rem;
        font-weight: 800;
        color: white;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    
    .restaurant-slogan {
        font-size: 1.1rem;
        color: rgba(255,255,255,0.9);
        margin-bottom: 0.75rem;
    }
    
    .restaurant-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        align-items: center;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: rgba(255,255,255,0.9);
        font-size: 0.95rem;
    }
    
    .meta-item i {
        color: var(--primary);
    }
    
    .rating-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        color: white;
    }
    
    .rating-badge i {
        color: #FBBF24;
    }
    
    .cuisines-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    
    .cuisine-tag {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        padding: 0.375rem 0.875rem;
        border-radius: 15px;
        font-size: 0.85rem;
        color: white;
    }
    
    /* Menu Content */
    .menu-content {
        padding: 2rem 0 4rem;
    }
    
    /* Breadcrumb */
    .breadcrumb-modern {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        margin-bottom: 2rem;
    }
    
    .breadcrumb-modern a {
        color: var(--gray-500);
        text-decoration: none;
        transition: color 0.3s ease;
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
    
    /* Category Section */
    .category-section {
        margin-bottom: 3rem;
    }
    
    .category-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--gray-200);
    }
    
    .category-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-900);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .category-title::before {
        content: '';
        width: 4px;
        height: 28px;
        background: var(--primary);
        border-radius: 2px;
    }
    
    .category-count {
        font-size: 0.9rem;
        color: var(--gray-500);
        font-weight: 500;
    }
    
    /* Products Grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .product-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
    }
    
    .product-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }
    
    .product-image-wrapper {
        position: relative;
        height: 180px;
        overflow: hidden;
    }
    
    .product-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .product-card:hover .product-image {
        transform: scale(1.05);
    }
    
    .product-badge {
        position: absolute;
        top: 0.75rem;
        left: 0.75rem;
        background: var(--primary);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .product-info {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .product-name {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .product-description {
        font-size: 0.85rem;
        color: var(--gray-500);
        margin-bottom: 1rem;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }
    
    .product-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .product-price {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    .btn-view {
        padding: 0.5rem 1rem;
        background: var(--gray-100);
        border: none;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--gray-700);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-view:hover {
        background: var(--primary);
        color: white;
    }
    
    /* Empty State */
    .empty-category {
        text-align: center;
        padding: 3rem 2rem;
        background: white;
        border-radius: 16px;
        color: var(--gray-500);
    }
    
    .empty-category i {
        font-size: 3rem;
        color: var(--gray-300);
        margin-bottom: 1rem;
    }
    
    /* Sticky Cart Summary */
    .floating-cart {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: var(--primary);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 50px;
        box-shadow: 0 10px 40px rgba(232, 90, 36, 0.4);
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.3s ease;
        z-index: 100;
    }
    
    .floating-cart:hover {
        transform: scale(1.05);
        box-shadow: 0 15px 50px rgba(232, 90, 36, 0.5);
    }
    
    .cart-count {
        background: white;
        color: var(--primary);
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .restaurant-hero {
            height: 280px;
        }
        
        .hero-content .container {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .restaurant-logo {
            width: 80px;
            height: 80px;
        }
        
        .restaurant-name {
            font-size: 1.75rem;
        }
        
        .products-grid {
            grid-template-columns: 1fr;
        }
        
        .floating-cart {
            bottom: 1rem;
            right: 1rem;
            left: 1rem;
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<div class="restaurant-page">
    <!-- Hero Section -->
    <section class="restaurant-hero">
        <img src="{{ $restaurant->cover_image ? (strpos($restaurant->cover_image, 'http') === 0 ? $restaurant->cover_image : asset('images/restaurant_images/' . $restaurant->cover_image)) : ($restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/placeholder.png')) }}" 
             alt="{{ $restaurant->name }}"
             class="hero-bg"
             onerror="this.src='{{ asset('images/i1.jpg') }}'">
        
        <div class="hero-content">
            <div class="container">
                <img src="{{ $restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/placeholder.png') }}" 
                     alt="{{ $restaurant->name }}"
                     class="restaurant-logo"
                     onerror="this.src='{{ asset('images/placeholder.png') }}'">
                
                <div class="restaurant-info">
                    <h1 class="restaurant-name">{{ $restaurant->name }}</h1>
                    
                    @if($restaurant->slogan)
                    <p class="restaurant-slogan">{{ $restaurant->slogan }}</p>
                    @endif
                    
                    <div class="restaurant-meta">
                        <!-- Badge Ouvert/Fermé -->
                        @if(isset($status))
                        <div class="status-badge" style="display: flex; align-items: center; gap: 0.5rem; background: {{ $status['is_open'] ? 'rgba(5,148,79,0.2)' : 'rgba(239,68,68,0.2)' }}; backdrop-filter: blur(10px); padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; color: white; border: 2px solid {{ $status['is_open'] ? '#05944F' : '#EF4444' }};">
                            <i class="fas {{ $status['is_open'] ? 'fa-check-circle' : 'fa-times-circle' }}" style="color: {{ $status['is_open'] ? '#05944F' : '#EF4444' }};"></i>
                            <span>{{ $status['is_open'] ? 'Ouvert' : 'Fermé' }}</span>
                            @if(!$status['is_open'] && $status['next_opening'])
                            <span style="font-size: 0.85rem; opacity: 0.9;">• Réouvre {{ $status['next_opening'] }}</span>
                            @endif
                        </div>
                        @endif
                        
                        <div class="rating-badge">
                            <i class="fas fa-star"></i>
                            {{ number_format((float)($restaurant->avg_rating ?? $defaultRating), 1) }}
                            @if($restaurant->rating_count ?? 0 > 0)
                            <span style="font-size: 0.85rem; opacity: 0.9;">({{ $restaurant->rating_count }} avis)</span>
                            @endif
                        </div>
                        
                        @php
                            $defaultDeliveryTimeMin = \App\Services\ConfigService::getDefaultDeliveryTimeMin();
                            $defaultDeliveryTimeMax = \App\Services\ConfigService::getDefaultDeliveryTimeMax();
                            $etaMin = $defaultDeliveryTimeMin;
                            $etaMax = $defaultDeliveryTimeMax;
                            if ($restaurant->avg_delivery_time) {
                                try {
                                    $time = \Carbon\Carbon::parse($restaurant->avg_delivery_time);
                                    $minutes = $time->hour * 60 + $time->minute;
                                    if ($minutes > 0) {
                                        $etaMin = max(15, $minutes - 5);
                                        $etaMax = $minutes + 5;
                                    }
                                } catch (\Exception $e) {}
                            }
                        @endphp
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            {{ $etaMin }}-{{ $etaMax }} min
                        </div>
                        
                        @php
                            $deliveryFee = $restaurant->delivery_charges ?? $defaultDeliveryFee;
                        @endphp
                        <div class="meta-item">
                            <i class="fas fa-motorcycle"></i>
                            Livraison : {{ number_format($deliveryFee, 0, ',', ' ') }} FCFA
                        </div>
                        
                        @if($restaurant->min_order)
                        <div class="meta-item">
                            <i class="fas fa-shopping-basket"></i>
                            Min. {{ number_format($restaurant->min_order, 0, ',', ' ') }} FCFA
                        </div>
                        @endif
                    </div>
                    
                    @if($restaurant->cuisines && $restaurant->cuisines->count() > 0)
                    <div class="cuisines-tags">
                        @foreach($restaurant->cuisines as $cuisine)
                        <span class="cuisine-tag">{{ $cuisine->name }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    
    <!-- Menu Content -->
    <section class="menu-content">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb-modern">
                <a href="{{ route('home') }}">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <span class="separator">/</span>
                <span class="current">{{ $restaurant->name }}</span>
            </nav>
            
            <!-- Promotions Actives -->
            @if(isset($activePromos) && $activePromos->count() > 0)
            <div style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%); border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; color: white;">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-tags"></i> Promotions actives
                </h3>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                    @foreach($activePromos as $promo)
                    <div style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); padding: 1rem 1.5rem; border-radius: 12px; border: 2px solid rgba(255,255,255,0.3);">
                        <div style="font-weight: 700; font-size: 1.5rem; margin-bottom: 0.25rem;">-{{ $promo->discount }}%</div>
                        <div style="font-size: 0.875rem; opacity: 0.9;">{{ $promo->name }}</div>
                        <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 0.5rem;">
                            Jusqu'au {{ \Carbon\Carbon::parse($promo->end_date)->format('d/m/Y') }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            @foreach($abc as $category)
            <div class="category-section">
                <div class="category-header">
                    <h2 class="category-title">{{ $category->name }}</h2>
                    <span class="category-count">{{ $category->products->count() }} plat{{ $category->products->count() > 1 ? 's' : '' }}</span>
                </div>
                
                @if($category->products->count() > 0)
                <div class="products-grid">
                    @foreach($category->products as $pro)
                    <a href="{{ route('pro.detail', $pro->id) }}" class="product-card" style="{{ (isset($pro->is_available) && !$pro->is_available) ? 'opacity:0.75;' : '' }}">
                        <div class="product-image-wrapper">
                            @if($pro->featured)
                            <span class="product-badge">Populaire</span>
                            @endif
                            @if(isset($pro->is_available) && !$pro->is_available)
                            <span class="product-badge" style="left:auto; right: 12px; background: rgba(17,24,39,0.9);">
                                Indisponible
                            </span>
                            @endif
                            <img src="{{ $pro->image ? (strpos($pro->image, 'http') === 0 ? $pro->image : asset('images/product_images/' . $pro->image)) : asset('images/placeholder.png') }}" 
                                 alt="{{ $pro->name }}"
                                 class="product-image"
                                 onerror="this.src='{{ asset('images/placeholder.png') }}'">
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name">{{ $pro->name }}</h3>
                            <p class="product-description">
                                {{ $pro->description ?: 'Délicieux plat préparé avec soin' }}
                            </p>
                            <div class="product-footer">
                                <span class="product-price">{{ number_format($pro->price, 0, ',', ' ') }} FCFA</span>
                                <span class="btn-view">
                                    <i class="fas fa-eye"></i>
                                    Voir
                                </span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="empty-category">
                    <i class="fas fa-utensils"></i>
                    <p>Aucun plat disponible dans cette catégorie</p>
                </div>
                @endif
            </div>
            @endforeach
            
            @if($abc->count() == 0)
            <div class="empty-category" style="max-width: 500px; margin: 3rem auto;">
                <i class="fas fa-store"></i>
                <h3 style="margin-bottom: 0.5rem; color: var(--gray-700);">Menu en cours de préparation</h3>
                <p>Ce restaurant n'a pas encore ajouté de plats à son menu.</p>
                <div style="display:flex; gap: 0.75rem; flex-wrap: wrap; justify-content: center; margin-top: 1rem;">
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Voir d'autres restaurants
                    </a>
                    <a href="{{ route('restaurant.login.page') }}" class="btn btn-secondary" style="background:#111827; border-color:#111827; color:white;">
                        <i class="fas fa-store"></i> Espace restaurant
                    </a>
                </div>
            </div>
            @endif
            
            <!-- Section Avis Clients -->
            @if(isset($recentReviews) && $recentReviews->count() > 0)
            <div style="margin-top: 4rem; padding-top: 3rem; border-top: 2px solid #E5E7EB;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 style="font-size: 1.75rem; font-weight: 700; color: #191919; margin: 0;">
                        <i class="fas fa-star" style="color: #FFB800;"></i> Avis clients
                    </h2>
                    <a href="#all-reviews" style="color: #05944F; text-decoration: none; font-weight: 600; font-size: 0.9375rem;">
                        Voir tous les avis ({{ $restaurant->rating_count ?? 0 }})
                        <i class="fas fa-arrow-right" style="font-size: 0.75rem; margin-left: 0.25rem;"></i>
                    </a>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;" id="reviewsContainer">
                    @foreach($recentReviews as $review)
                    <div style="background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.875rem;">
                                    {{ $review->user ? substr($review->user->name, 0, 2) : '??' }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #191919; font-size: 0.9375rem;">
                                        {{ $review->user->name ?? 'Anonyme' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: #6B7280;">
                                        {{ $review->created_at->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.25rem; background: #F6F6F6; padding: 0.375rem 0.75rem; border-radius: 500px;">
                                <span style="font-weight: 600; color: #191919;">{{ $review->rating }}</span>
                                <i class="fas fa-star" style="color: #FFB800; font-size: 0.75rem;"></i>
                            </div>
                        </div>
                        @if($review->reviews)
                        <p style="color: #4B5563; font-size: 0.9375rem; line-height: 1.6; margin: 0;">
                            {{ $review->reviews }}
                        </p>
                        @else
                        <p style="color: #9CA3AF; font-size: 0.875rem; font-style: italic; margin: 0;">
                            Aucun commentaire
                        </p>
                        @endif
                    </div>
                    @endforeach
                </div>
                
                @if(($restaurant->rating_count ?? 0) > 10)
                <div style="text-align: center; margin-top: 2rem;">
                    <button onclick="loadAllReviews({{ $restaurant->id }})" style="padding: 0.75rem 2rem; background: #05944F; color: white; border: none; border-radius: 500px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#05944F'">
                        <i class="fas fa-chevron-down"></i> Charger plus d'avis
                    </button>
                </div>
                @endif
            </div>
            @endif
        </div>
    </section>
</div>

<!-- Floating Cart Button -->
@if(Auth::check())
<a href="{{ route('cart.view') }}" class="floating-cart">
    <i class="fas fa-shopping-cart"></i>
    <span>Voir le panier</span>
</a>
@endif
@endsection

@section('scripts')
<script>
    // Smooth scroll for category navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>
@endsection
