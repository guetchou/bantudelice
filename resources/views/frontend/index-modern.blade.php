@extends('frontend.layouts.app-modern')

@section('title', 'BantuDelice - Livraison à domicile | Restaurants, Courses & Plus')
@section('description', 'Commandez vos repas préférés, courses, fleurs et plus encore. Livraison rapide à domicile avec BantuDelice.')

@section('content')
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background">
            <img src="{{ asset('images/thedrop24BG.jpg') }}" alt="BantuDelice Background">
        </div>
        <div class="container">
            <div class="hero-content animate-fadeInUp">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    Service disponible maintenant
                </div>
                <h1 class="hero-title">
                    Des plats délicieux <br>
                    <span>livrés chez vous</span>
                </h1>
                <p class="hero-description">
                    Découvrez les meilleurs restaurants de votre ville et faites-vous livrer 
                    vos plats préférés en quelques clics. Rapide, simple et savoureux.
                </p>
                
                <!-- Search Box avec recherche en temps réel -->
                <div class="hero-search-wrapper">
                    <form action="{{ route('serach') }}" method="get" class="hero-search" id="searchForm">
                        <div class="search-input-wrapper">
                            <input type="text" 
                                   name="qurey" 
                                   id="searchInput"
                                   placeholder="Rechercher un restaurant, une cuisine..." 
                                   autocomplete="off"
                                   required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </div>
                        <!-- Résultats de recherche en temps réel -->
                        <div class="search-results-dropdown" id="searchResults" style="display: none;"></div>
                    </form>
                </div>
                
                <!-- Stats -->
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-value">{{ $restaurants->count() }}+</div>
                        <div class="hero-stat-label">Restaurants</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value">{{ $products->count() }}+</div>
                        <div class="hero-stat-label">Plats disponibles</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value">30 min</div>
                        <div class="hero-stat-label">Livraison moyenne</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Restaurants Populaires Section - Style Moderne -->
    <section class="section" style="padding-top: 60px; padding-bottom: 60px; background: #FAFAFA;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h2 style="font-size: 1.75rem; font-weight: 700; margin: 0 0 4px 0; color: #191919;">Restaurants populaires</h2>
                    <p style="color: #6B6B6B; font-size: 0.9375rem; margin: 0;">Les mieux notés par nos clients</p>
                </div>
                <a href="{{ route('restaurants.all') }}" style="display: flex; align-items: center; gap: 8px; color: #191919; font-weight: 500; text-decoration: none; padding: 8px 16px; border-radius: 500px; background: #F6F6F6; transition: all 0.2s; white-space: nowrap;" 
                   onmouseover="this.style.background='#EEEEEE'; this.style.transform='translateX(2px)'" 
                   onmouseout="this.style.background='#F6F6F6'; this.style.transform='translateX(0)'">
                    Voir tout <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                </a>
            </div>
            
            @if($restaurants->count() > 0)
            <!-- Conteneur : Carrousel mobile / Grille desktop -->
            <div class="restaurants-grid" style="display: flex; overflow-x: auto; gap: 16px; padding-bottom: 8px; -webkit-overflow-scrolling: touch; scroll-snap-type: x mandatory; scrollbar-width: none; -ms-overflow-style: none;">
                @php
                    // Récupérer les valeurs par défaut UNE SEULE FOIS avant la boucle
                    $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
                    $defaultDeliveryTimeMin = \App\Services\ConfigService::getDefaultDeliveryTimeMin();
                    $defaultDeliveryTimeMax = \App\Services\ConfigService::getDefaultDeliveryTimeMax();
                    $defaultRating = \App\Services\ConfigService::getDefaultRating();
                @endphp
                @foreach($restaurants as $restaurant)
                    @php
                        // Calculer le temps de livraison depuis avg_delivery_time (DB) ou depuis les commandes réelles
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
                            } catch (\Exception $e) {
                                // Utiliser les valeurs par défaut en cas d'erreur
                            }
                        }
                        // Note: avg_delivery_time est déjà préchargé par DataSyncService
                        // Pas besoin de requête DB supplémentaire dans la boucle
                        
                        $deliveryTime = $etaMin . '-' . $etaMax . ' min';
                        
                        // Frais de livraison depuis la DB (restaurant->delivery_charges) ou valeur par défaut depuis charges
                        $deliveryFee = $restaurant->delivery_charges ?? $defaultDeliveryFee;
                        $deliveryFeeFormatted = number_format($deliveryFee, 0, ',', ' ') . ' FCFA';
                        
                        // Cuisines depuis la DB (relation many-to-many)
                        $cuisinesList = $restaurant->cuisines->pluck('name')->take(3)->implode(' · ');
                        if (!$cuisinesList) {
                            $cuisinesList = 'Cuisine variée';
                        }
                        
                        // Rating calculé depuis la DB (table ratings) ou valeur par défaut depuis ConfigService
                        $rating = number_format($restaurant->avg_rating ?? $defaultRating, 1);
                    @endphp
                    
                    <!-- Carte Restaurant -->
                    <article class="restaurant-card" style="min-width: 240px; max-width: 280px; flex-shrink: 0; position: relative; border-radius: 16px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); scroll-snap-align: start; cursor: pointer;"
                            onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'"
                            onclick="window.location.href='{{ route('resturant.detail', $restaurant->id) }}'">
                        
                        <!-- Image Container avec overlay gradient -->
                        <div style="position: relative; height: 160px; overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            @if($restaurant->logo)
                            <img src="{{ strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo) }}" 
                                 alt="{{ $restaurant->name }}"
                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                                 onerror="this.src='https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=300&fit=crop'"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform='scale(1)'">
                            @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 2rem;">
                                {{ substr($restaurant->name, 0, 2) }}
                            </div>
                            @endif
                            
                            <!-- Overlay gradient léger pour lisibilité -->
                            <div style="pointer-events: none; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.05) 50%, transparent 100%);"></div>
                            
                            <!-- Badge temps de livraison -->
                            <div style="position: absolute; bottom: 8px; left: 8px; display: flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); padding: 4px 10px; border-radius: 500px; font-size: 0.75rem; font-weight: 500; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                <i class="fas fa-clock" style="font-size: 0.625rem; color: #6B6B6B;"></i>
                                <span>{{ $deliveryTime }}</span>
                            </div>
                            
                            <!-- Badge Top noté (si featured ou rating élevé) -->
                            @php
                                $topRatedThreshold = \App\Services\ConfigService::getTopRatedThreshold();
                                $topRatedMinReviews = \App\Services\ConfigService::getTopRatedMinReviews();
                                $isTopRated = ($restaurant->featured ?? false) || (($restaurant->avg_rating ?? 0) >= $topRatedThreshold && ($restaurant->rating_count ?? 0) >= $topRatedMinReviews);
                            @endphp
                            @if($isTopRated)
                            <div style="position: absolute; top: 8px; left: 8px; display: flex; gap: 4px;">
                                <span style="background: #05944F; color: white; padding: 4px 10px; border-radius: 500px; font-size: 0.6875rem; font-weight: 600; box-shadow: 0 2px 8px rgba(5,148,79,0.3);">
                                    @if($restaurant->featured ?? false)
                                        Top noté
                                    @else
                                        ⭐ {{ $rating }}
                                    @endif
                                </span>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Contenu de la carte -->
                        <div style="padding: 14px 16px 16px;">
                            <!-- Titre + Rating -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                                <h3 style="font-size: 1rem; font-weight: 600; margin: 0; color: #191919; line-height: 1.3; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $restaurant->name }}
                                </h3>
                                <div style="display: flex; align-items: center; gap: 3px; background: #F6F6F6; padding: 4px 8px; border-radius: 500px; font-size: 0.8125rem; font-weight: 600; flex-shrink: 0; white-space: nowrap;">
                                    <span style="color: #191919;">{{ $rating }}</span>
                                    <i class="fas fa-star" style="color: #FFB800; font-size: 0.625rem;"></i>
                                </div>
                            </div>
                            
                            <!-- Types de cuisine (chips) -->
                            <p style="color: #6B6B6B; font-size: 0.8125rem; margin: 0 0 8px 0; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $restaurant->cuisines->pluck('name')->implode(' · ') }}">
                                {{ $cuisinesList }}
                            </p>
                            
                            <!-- Frais de livraison -->
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid #F0F0F0;">
                                <span style="color: #6B6B6B; font-size: 0.8125rem;">
                                    Frais de livraison : <strong style="color: #191919;">{{ $deliveryFeeFormatted }}</strong>
                                </span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            
            <!-- Styles pour desktop (grille) -->
            <style>
                @media (min-width: 768px) {
                    .restaurants-grid {
                        display: grid !important;
                        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                        overflow-x: visible !important;
                        gap: 24px;
                    }
                    .restaurant-card {
                        min-width: auto !important;
                        max-width: none !important;
                    }
                }
                
                @media (min-width: 1280px) {
                    .restaurants-grid {
                        grid-template-columns: repeat(4, 1fr);
                    }
                }
                
                /* Masquer la scrollbar sur mobile */
                .restaurants-grid::-webkit-scrollbar {
                    display: none;
                }
            </style>
            @else
            <div style="text-align: center; padding: 60px 20px; color: #6B6B6B;">
                <i class="fas fa-utensils" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                <p style="font-size: 1rem; margin: 0;">Aucun restaurant disponible pour le moment</p>
                </div>
                <h3 style="color: #191919; font-size: 1.125rem; margin: 0 0 8px 0; font-weight: 500;">Restaurants bientot disponibles</h3>
                <p style="color: #6B6B6B; font-size: 0.9375rem; margin: 0;">Nos partenaires seront bientot en ligne.</p>
            </div>
            @endif
        </div>
    </section>
    
    <!-- Plat du jour (rotation quotidienne) -->
    @if(isset($dailySpecials) && $dailySpecials->count() > 0)
    <section class="section" style="padding-top: 50px; padding-bottom: 50px; background: #FFFFFF;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; background: rgba(255,107,53,0.12); color: #E55A2B; font-weight: 700; font-size: 0.8125rem; margin-bottom: 8px;">
                        <i class="fas fa-bolt"></i>
                        Plat du jour
                        <span style="opacity: 0.7; font-weight: 600;">({{ now()->format('d/m') }})</span>
                    </div>
                    <h2 style="font-size: 1.75rem; font-weight: 800; margin: 0 0 4px 0; color: #191919;">À ne pas rater aujourd’hui</h2>
                    <p style="color: #6B6B6B; font-size: 0.9375rem; margin: 0;">Une sélection qui change chaque jour.</p>
                </div>
                <a href="#"
                   style="display: flex; align-items: center; gap: 8px; color: #191919; font-weight: 500; text-decoration: none; padding: 8px 16px; border-radius: 500px; background: #F6F6F6; transition: all 0.2s; white-space: nowrap;"
                   onmouseover="this.style.background='#EEEEEE'; this.style.transform='translateX(2px)'"
                   onmouseout="this.style.background='#F6F6F6'; this.style.transform='translateX(0)'">
                    Voir plus <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                </a>
            </div>

            <!-- Mobile: carrousel / Desktop: grille -->
            <div class="daily-specials-grid" style="display: flex; overflow-x: auto; gap: 14px; padding-bottom: 8px; -webkit-overflow-scrolling: touch; scroll-snap-type: x mandatory; scrollbar-width: none; -ms-overflow-style: none;">
                @foreach($dailySpecials as $sp)
                    @php
                        $img = $sp->image ?? null;
                        $imgSrc = $img
                            ? (strpos($img, 'http') === 0 ? $img : asset('images/product_images/' . $img))
                            : asset('images/product_images/default-food.jpg');
                        $price = ($sp->discount_price && $sp->discount_price > 0 && $sp->discount_price < ($sp->price ?? 0)) ? $sp->discount_price : ($sp->price ?? 0);
                        $restaurantName = $sp->restaurants->name ?? 'Restaurant';
                    @endphp

                    <a href="{{ route('pro.detail', $sp->id) }}"
                       style="min-width: 260px; max-width: 320px; flex-shrink: 0; scroll-snap-align: start; text-decoration: none; color: inherit; border-radius: 16px; overflow: hidden; border: 1px solid #EDEDED; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.06); transition: all 0.25s;"
                       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.10)'"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)'">
                        <div style="position: relative; height: 160px; background: #F6F6F6; overflow: hidden;">
                            <img src="{{ $imgSrc }}"
                                 alt="{{ $sp->name }}"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
                            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.45), transparent 55%);"></div>
                            <div style="position: absolute; top: 10px; left: 10px; display:flex; gap:6px; align-items:center;">
                                <span style="background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); color: #E55A2B; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 800;">
                                    Plat du jour
                                </span>
                                @if($sp->featured)
                                <span style="background: rgba(5,148,79,0.95); backdrop-filter: blur(8px); color: #fff; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 800;">
                                    Populaire
                                </span>
                                @endif
                            </div>
                        </div>
                        <div style="padding: 14px 14px 12px;">
                            <div style="display:flex; justify-content: space-between; align-items:flex-start; gap: 10px;">
                                <div style="min-width: 0;">
                                    <div style="font-size: 1rem; font-weight: 800; color:#191919; margin:0; overflow:hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $sp->name }}
                                    </div>
                                    <div style="color:#6B6B6B; font-size: 0.8125rem; overflow:hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $restaurantName }}
                                    </div>
                                </div>
                                <button type="button"
                                        onclick="event.preventDefault(); addToCart({{ $sp->id }}, {{ $sp->restaurant_id ?? 'null' }})"
                                        style="width: 38px; height: 38px; border-radius: 999px; background: #F6F6F6; border: none; cursor: pointer; display:flex; align-items:center; justify-content:center; transition: all .2s;"
                                        onmouseover="this.style.background='#05944F'; this.style.color='white'"
                                        onmouseout="this.style.background='#F6F6F6'; this.style.color='#191919'">
                                    <i class="fas fa-plus" style="font-size: 0.85rem;"></i>
                                </button>
                            </div>

                            <div style="display:flex; align-items:baseline; justify-content: space-between; margin-top: 10px;">
                                <div style="font-size: 1rem; font-weight: 900; color:#191919;">
                                    {{ number_format($price, 0, ',', ' ') }} FCFA
                                    @if($sp->discount_price && $sp->discount_price > 0 && $sp->discount_price < ($sp->price ?? 0))
                                        <span style="margin-left: 8px; font-size: 0.8125rem; color:#9CA3AF; font-weight: 700; text-decoration: line-through;">
                                            {{ number_format($sp->price, 0, ',', ' ') }} FCFA
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size: 0.75rem; color:#6B6B6B; font-weight: 600;">
                                    Livraison rapide
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <style>
                .daily-specials-grid::-webkit-scrollbar { display: none; }
                @media (min-width: 768px) {
                    .daily-specials-grid {
                        display: grid !important;
                        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                        overflow-x: visible !important;
                        gap: 18px;
                    }
                }
            </style>
        </div>
    </section>
    @endif

    <!-- Services Spéciaux Section (Design 2025 + emojis) -->
    <section class="section services-section services-2025" id="services" style="padding-top: 70px; padding-bottom: 70px; background: radial-gradient(1200px 600px at 10% 0%, rgba(255,107,53,0.10), transparent 60%), radial-gradient(900px 500px at 90% 10%, rgba(5,148,79,0.10), transparent 55%), #FFFFFF;">
        <div class="container">
            <div class="services2025-head">
                <div>
                    <div class="services2025-badge">
                        <span class="dot"></span>
                        Services (design moderne)
                    </div>
                    <h2 class="services2025-title">Nos Services Spéciaux</h2>
                    <p class="services2025-subtitle">
                        Bien plus qu'une simple livraison de repas, découvrez tous nos services à votre disposition.
                    </p>
                </div>
                <a class="services2025-cta" href="{{ route('contact.us') }}">
                    Demander un service <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            @php
                $services = [
                    [
                        'tag' => 'Livraison',
                        'emoji' => '🛵',
                        'icon' => 'fas fa-motorcycle',
                        'title' => 'Collecte & Livraison',
                        'desc' => "Commandez auprès de nos marchands de nourriture et d'alcool. Nous récupérons votre commande et la livrons à votre porte.",
                        'accent' => 'rgba(255,107,53,0.16)',
                        'accent2' => 'rgba(255,107,53,0.10)',
                    ],
                    [
                        'tag' => 'Réservation',
                        'emoji' => '🍽️',
                        'icon' => 'fas fa-calendar-check',
                        'title' => 'Réservation de Table',
                        'desc' => "Réservez une table dans l'un des restaurants gastronomiques de notre liste en quelques clics.",
                        'accent' => 'rgba(99,102,241,0.16)',
                        'accent2' => 'rgba(99,102,241,0.10)',
                    ],
                    [
                        'tag' => 'Traiteur',
                        'emoji' => '🎉',
                        'icon' => 'fas fa-concierge-bell',
                        'title' => 'Service Traiteur',
                        'desc' => "Vous organisez un événement et avez besoin d'un traiteur ? Choisissez parmi notre sélection de professionnels.",
                        'accent' => 'rgba(245,158,11,0.18)',
                        'accent2' => 'rgba(245,158,11,0.10)',
                    ],
                    [
                        'tag' => 'Colis',
                        'emoji' => '📦',
                        'icon' => 'fas fa-box',
                        'title' => 'Livraison de Colis',
                        'desc' => "Pour les entreprises qui ont besoin d'un professionnel local pour récupérer et livrer un colis.",
                        'accent' => 'rgba(17,24,39,0.12)',
                        'accent2' => 'rgba(17,24,39,0.06)',
                    ],
                    [
                        'tag' => 'Courses',
                        'emoji' => '🛒',
                        'icon' => 'fas fa-shopping-basket',
                        'title' => 'Courses & Épicerie',
                        'desc' => "Plus de courses à faire ? Envoyez-nous votre liste par SMS ou email et nous faisons vos achats pour vous.",
                        'accent' => 'rgba(5,148,79,0.16)',
                        'accent2' => 'rgba(5,148,79,0.10)',
                    ],
                    [
                        'tag' => 'Fleurs',
                        'emoji' => '🌹',
                        'icon' => 'fas fa-seedling',
                        'title' => 'Livraison de Fleurs',
                        'desc' => "Une occasion spéciale ? Surprenez vos proches avec des fleurs et laissez-nous faire la livraison pour vous.",
                        'accent' => 'rgba(236,72,153,0.16)',
                        'accent2' => 'rgba(236,72,153,0.10)',
                    ],
                    [
                        'tag' => 'Pressing',
                        'emoji' => '👔',
                        'icon' => 'fas fa-tshirt',
                        'title' => 'Pressing & Linge',
                        'desc' => "Pas envie de faire la lessive ? Envoyez-nous votre demande et nous nous occupons de tout.",
                        'accent' => 'rgba(14,165,233,0.16)',
                        'accent2' => 'rgba(14,165,233,0.10)',
                    ],
                    [
                        'tag' => 'Boissons',
                        'emoji' => '🍾',
                        'icon' => 'fas fa-wine-bottle',
                        'title' => 'Boissons & Spiritueux',
                        'desc' => "Longue journée ? Besoin de vous détendre ? Commandez vos boissons préférées et nous livrons.",
                        'accent' => 'rgba(168,85,247,0.16)',
                        'accent2' => 'rgba(168,85,247,0.10)',
                    ],
                ];
            @endphp

            <div class="services2025-grid">
                @foreach($services as $s)
                    <a class="services2025-card" href="{{ route('contact.us') }}" style="--accent: {{ $s['accent'] }}; --accent2: {{ $s['accent2'] }};">
                        <div class="services2025-cardTop">
                            <div class="services2025-tag">{{ $s['tag'] }}</div>
                            <div class="services2025-iconWrap">
                                <div class="services2025-emoji" aria-hidden="true">{{ $s['emoji'] }}</div>
                                <i class="{{ $s['icon'] }} services2025-icon" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="services2025-cardBody">
                            <h3 class="services2025-cardTitle">{{ $s['title'] }}</h3>
                            <p class="services2025-cardDesc">{{ $s['desc'] }}</p>
                        </div>
                        <div class="services2025-cardFoot">
                            <span class="services2025-link">Découvrir</span>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="services2025-footnote">
                <div class="services2025-footnoteBox">
                    <div class="services2025-footnoteTitle">Tu veux un service rapide et fiable ?</div>
                    <div class="services2025-footnoteText">
                        Clique sur un service et dis-nous ce que tu veux. On te répond rapidement (WhatsApp/SMS/Email selon ton choix).
                    </div>
                </div>
            </div>

            <style>
                .services-2025 .services2025-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:22px}
                .services-2025 .services2025-badge{display:inline-flex;align-items:center;gap:10px;padding:7px 12px;border-radius:999px;background:rgba(255,255,255,0.7);backdrop-filter:blur(10px);border:1px solid rgba(0,0,0,0.06);font-weight:800;font-size:.8125rem;color:#191919}
                .services-2025 .services2025-badge .dot{width:10px;height:10px;border-radius:50%;background:#05944F;box-shadow:0 0 0 6px rgba(5,148,79,0.15)}
                .services-2025 .services2025-title{font-size:2rem;font-weight:900;letter-spacing:-.02em;margin:10px 0 6px;color:#191919}
                .services-2025 .services2025-subtitle{color:#6B6B6B;font-size:1rem;line-height:1.6;margin:0;max-width:56ch}
                .services-2025 .services2025-cta{display:inline-flex;align-items:center;gap:10px;padding:10px 14px;border-radius:999px;background:#191919;color:#fff;text-decoration:none;font-weight:800;white-space:nowrap;transition:transform .15s ease, box-shadow .15s ease}
                .services-2025 .services2025-cta:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(0,0,0,0.18)}

                .services-2025 .services2025-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
                .services-2025 .services2025-card{display:flex;flex-direction:column;gap:10px;text-decoration:none;color:inherit;border-radius:18px;background:linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.95));border:1px solid rgba(0,0,0,0.07);box-shadow:0 2px 10px rgba(0,0,0,0.05);padding:14px;transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;position:relative;overflow:hidden}
                .services-2025 .services2025-card::before{content:"";position:absolute;inset:-2px;background:radial-gradient(500px 240px at 20% 10%, var(--accent), transparent 60%), radial-gradient(420px 220px at 80% 0%, var(--accent2), transparent 55%);opacity:.9}
                .services-2025 .services2025-card > *{position:relative}
                .services-2025 .services2025-card:hover{transform:translateY(-4px);box-shadow:0 18px 45px rgba(0,0,0,0.12);border-color:rgba(0,0,0,0.14)}

                .services-2025 .services2025-cardTop{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
                .services-2025 .services2025-tag{font-size:.75rem;font-weight:900;color:#191919;background:rgba(255,255,255,0.85);border:1px solid rgba(0,0,0,0.06);padding:6px 10px;border-radius:999px}
                .services-2025 .services2025-iconWrap{display:flex;align-items:center;gap:10px}
                .services-2025 .services2025-emoji{width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.85);border:1px solid rgba(0,0,0,0.06);font-size:1.25rem}
                .services-2025 .services2025-icon{opacity:.75}

                .services-2025 .services2025-cardTitle{font-size:1.02rem;font-weight:900;margin:0 0 6px;color:#191919;letter-spacing:-.01em}
                .services-2025 .services2025-cardDesc{margin:0;color:#4B5563;font-size:.875rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
                .services-2025 .services2025-cardFoot{display:flex;justify-content:space-between;align-items:center;padding-top:8px;margin-top:auto;border-top:1px dashed rgba(0,0,0,0.12);color:#191919}
                .services-2025 .services2025-link{font-weight:900}

                .services-2025 .services2025-footnote{margin-top:16px}
                .services-2025 .services2025-footnoteBox{border-radius:18px;padding:16px;background:rgba(255,255,255,0.65);border:1px solid rgba(0,0,0,0.06);backdrop-filter:blur(10px)}
                .services-2025 .services2025-footnoteTitle{font-weight:900;color:#191919;margin-bottom:4px}
                .services-2025 .services2025-footnoteText{color:#6B6B6B}

                @media (max-width: 1024px){
                    .services-2025 .services2025-grid{grid-template-columns:repeat(2,1fr)}
                    .services-2025 .services2025-head{align-items:flex-start}
                }
                @media (max-width: 640px){
                    .services-2025 .services2025-grid{grid-template-columns:1fr}
                    .services-2025 .services2025-title{font-size:1.6rem}
                    .services-2025 .services2025-cta{width:100%;justify-content:center}
                    .services-2025 .services2025-head{flex-direction:column;align-items:stretch}
                }
            </style>
        </div>
    </section>
    
    <!-- Services Marchands Section -->
    <section class="section" style="background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%);">
        <div class="container">
            <div class="section-header">
                <span class="section-badge" style="background: rgba(255,255,255,0.1); color: #FF6B35;">
                    <img src="{{ asset('images/svg/bowl.png') }}" alt="Marchands" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 6px;">
                    Marchands
                </span>
                <h2 class="section-title" style="color: white;">Services Marchands</h2>
                <p class="section-description" style="color: rgba(255,255,255,0.7);">
                    Découvrez nos partenaires marchands et leurs offres exclusives.
                </p>
            </div>
            
            <div class="services-grid">
                <!-- Poissons & Viandes -->
                <div class="service-card" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                    <div class="service-icon">
                        <img src="{{ asset('images/svg/fish1.png') }}" alt="Poisson">
                    </div>
                    <h3 class="service-title" style="color: white;">Poissons & Viandes</h3>
                    <p class="service-description" style="color: rgba(255,255,255,0.6);">
                        Commandez directement auprès de nos marchands de fruits de mer 
                        affichés sur le site ou téléchargez l'application.
                    </p>
                </div>
                
                <!-- Pharmacie -->
                <div class="service-card" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                    <div class="service-icon">
                        <img src="{{ asset('images/svg/bowl.png') }}" alt="Pharmacie">
                    </div>
                    <h3 class="service-title" style="color: white;">Pharmacie</h3>
                    <p class="service-description" style="color: rgba(255,255,255,0.6);">
                        Besoin de récupérer votre ordonnance ? Choisissez un marchand, 
                        puis envoyez-nous un SMS ou email pour demander le service.
                    </p>
                </div>
                
                <!-- Pâtisseries & Gâteaux -->
                <div class="service-card" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                    <div class="service-icon">
                        <img src="{{ asset('images/svg/birthday.png') }}" alt="Gâteaux">
                    </div>
                    <h3 class="service-title" style="color: white;">Pâtisseries & Gâteaux</h3>
                    <p class="service-description" style="color: rgba(255,255,255,0.6);">
                        Envie de commander ce gâteau d'anniversaire ou cette douceur ? 
                        Commandez auprès de l'un de nos marchands.
                    </p>
                </div>
                
                <!-- Colis & Paquets -->
                <a href="{{ route('colis.landing') }}" class="service-card" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); text-decoration: none; display: block; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(-5px)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.transform='translateY(0)'">
                    <div class="service-icon">
                        <img src="{{ asset('images/svg/package.png') }}" alt="Colis">
                    </div>
                    <h3 class="service-title" style="color: white;">Colis & Paquets</h3>
                    <p class="service-description" style="color: rgba(255,255,255,0.6);">
                        Un colis à récupérer ? Nous récupérons et livrons les colis 
                        de toutes tailles jusqu'à votre porte.
                    </p>
                    <div class="mt-3">
                        <span class="btn btn-sm btn-outline-light" style="border-radius: 50px;">Suivre un colis</span>
                    </div>
                </a>
            </div>
        </div>
    </section>
    
    <!-- Plats Populaires Section - Style Moderne -->
    <section class="section" style="padding-top: 60px; padding-bottom: 60px;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h2 style="font-size: 1.75rem; font-weight: 700; margin: 0 0 4px 0; color: #191919;">Plats populaires</h2>
                    <p style="color: #6B6B6B; font-size: 0.9375rem; margin: 0;">Les plus commandes du moment</p>
                </div>
                <a href="#" style="display: flex; align-items: center; gap: 8px; color: #191919; font-weight: 500; text-decoration: none; padding: 8px 16px; border-radius: 500px; background: #F6F6F6; transition: background 0.2s;">
                    Voir tout <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                </a>
            </div>
            
            @if($products->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;">
                @foreach($products as $product)
                    <a href="{{ route('pro.detail', $product->id) }}" style="text-decoration: none; color: inherit; display: flex; gap: 12px; padding: 12px; border-radius: 12px; background: #fff; border: 1px solid #E8E8E8; transition: all 0.2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.1)'; this.style.borderColor='#CCCCCC'" onmouseout="this.style.boxShadow='none'; this.style.borderColor='#E8E8E8'">
                        <!-- Product Image -->
                        <div style="width: 100px; height: 100px; border-radius: 8px; overflow: hidden; flex-shrink: 0; background: #F6F6F6;">
                            <img src="{{ $product->image ? (strpos($product->image, 'http') === 0 ? $product->image : asset('images/product_images/' . $product->image)) : asset('images/product_images/default-food.jpg') }}" 
                                 alt="{{ $product->name }}"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
                        </div>
                        
                        <!-- Product Info -->
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between; min-width: 0;">
                            <div>
                                <h4 style="font-size: 0.9375rem; font-weight: 500; margin: 0 0 4px 0; color: #191919; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $product->name }}</h4>
                                <p style="font-size: 0.8125rem; color: #6B6B6B; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ Str::limit($product->description ?? 'Delicieux plat prepare avec soin', 60) }}
                                </p>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                                <span style="font-size: 0.9375rem; font-weight: 600; color: #191919;">
                                    {{ number_format($product->price ?? 0, 0, ',', ' ') }} FCFA
                                </span>
                                <button type="button" onclick="event.preventDefault(); addToCart({{ $product->id }}, {{ $product->restaurant_id ?? 'null' }})" style="width: 32px; height: 32px; border-radius: 50%; background: #F6F6F6; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#05944F'; this.style.color='white'" onmouseout="this.style.background='#F6F6F6'; this.style.color='#191919'">
                                    <i class="fas fa-plus" style="font-size: 0.75rem;"></i>
                                </button>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            @else
            <div style="text-align: center; padding: 80px 20px; background: #F6F6F6; border-radius: 16px;">
                <div style="width: 64px; height: 64px; background: #EEEEEE; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="fas fa-utensils" style="font-size: 1.5rem; color: #6B6B6B;"></i>
                </div>
                <h3 style="color: #191919; font-size: 1.125rem; margin: 0 0 8px 0; font-weight: 500;">Plats bientot disponibles</h3>
                <p style="color: #6B6B6B; font-size: 0.9375rem; margin: 0;">Nos delicieux plats seront bientot en ligne.</p>
            </div>
            @endif
        </div>
    </section>
    
    <!-- Témoignages Section -->
    <section class="section" style="background: var(--gray-50);">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">
                    <img src="{{ asset('images/icons/happy-customer.svg') }}" alt="Avis" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 6px;">
                    Témoignages
                </span>
                <h2 class="section-title">Ce que disent nos clients</h2>
                <p class="section-description">
                    Découvrez les avis de nos clients satisfaits.
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                <!-- Témoignage 1 -->
                <div style="background: white; padding: 24px; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">JM</div>
                        <div>
                            <h4 style="font-size: 1rem; margin: 0;">Jean-Marie K.</h4>
                            <div style="color: #F59E0B;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p style="color: var(--gray-600); font-size: 0.9375rem; line-height: 1.6; margin: 0;">
                        "Service excellent ! La livraison était rapide et les plats étaient encore chauds. Je recommande vivement BantuDelice."
                    </p>
                </div>
                
                <!-- Témoignage 2 -->
                <div style="background: white; padding: 24px; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">AN</div>
                        <div>
                            <h4 style="font-size: 1rem; margin: 0;">Arlette N.</h4>
                            <div style="color: #F59E0B;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                    <p style="color: var(--gray-600); font-size: 0.9375rem; line-height: 1.6; margin: 0;">
                        "J'adore le service de livraison de fleurs ! J'ai pu surprendre ma mère pour son anniversaire. Merci BantuDelice !"
                    </p>
                </div>
                
                <!-- Témoignage 3 -->
                <div style="background: white; padding: 24px; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">PM</div>
                        <div>
                            <h4 style="font-size: 1rem; margin: 0;">Patrick M.</h4>
                            <div style="color: #F59E0B;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p style="color: var(--gray-600); font-size: 0.9375rem; line-height: 1.6; margin: 0;">
                        "Le service traiteur pour mon événement était parfait. Qualité professionnelle et ponctualité. Bravo !"
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="section" style="background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%); padding: 80px 0;">
        <div class="container text-center">
            <img src="{{ asset('images/icons/delivery-scooter.svg') }}" alt="Livraison" style="width: 80px; height: 80px; margin-bottom: 20px;">
            <h2 style="color: white; margin-bottom: 16px;">Prêt à commander ?</h2>
            <p style="color: rgba(255,255,255,0.9); font-size: 1.125rem; margin-bottom: 32px; max-width: 500px; margin-left: auto; margin-right: auto;">
                Téléchargez notre application et commencez à commander dès maintenant. 
                C'est simple, rapide et délicieux !
            </p>
            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="#" class="btn" style="background: white; color: #FF6B35; padding: 12px 24px;">
                    <i class="fab fa-google-play"></i> Google Play
                </a>
                <a href="#" class="btn" style="background: white; color: #FF6B35; padding: 12px 24px;">
                    <i class="fab fa-apple"></i> App Store
                </a>
            </div>
        </div>
    </section>
@endsection

@section('styles')
<style>
    /* Dropdown Menu */
    .nav-dropdown {
        position: relative;
    }
    
    .nav-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        min-width: 200px;
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        padding: var(--space-sm) 0;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all var(--transition-base);
        z-index: 100;
    }
    
    .nav-dropdown:hover .nav-dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .nav-dropdown-menu a {
        display: block;
        padding: var(--space-sm) var(--space-lg);
        color: var(--gray-700);
        font-size: 0.9375rem;
        transition: all var(--transition-fast);
    }
    
    .nav-dropdown-menu a:hover {
        background: var(--gray-50);
        color: var(--primary);
        padding-left: var(--space-xl);
    }
    
    /* Recherche en temps réel */
    .hero-search-wrapper {
        position: relative;
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .search-input-wrapper {
        position: relative;
        display: flex;
        gap: 0.5rem;
    }
    
    .search-results-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        max-height: 400px;
        overflow-y: auto;
        z-index: 1000;
        margin-top: 8px;
    }
    
    .search-result-item {
        padding: 12px 16px;
        border-bottom: 1px solid #F0F0F0;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .search-result-item:hover {
        background: #F8F8F8;
    }
    
    .search-result-item:last-child {
        border-bottom: none;
    }
    
    .search-result-image {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        flex-shrink: 0;
    }
    
    .search-result-content {
        flex: 1;
        min-width: 0;
    }
    
    .search-result-name {
        font-weight: 600;
        color: #191919;
        margin-bottom: 4px;
        font-size: 0.9375rem;
    }
    
    .search-result-meta {
        font-size: 0.8125rem;
        color: #6B6B6B;
    }
    
    .search-loading {
        padding: 16px;
        text-align: center;
        color: #6B6B6B;
    }
    
    .search-no-results {
        padding: 16px;
        text-align: center;
        color: #6B6B6B;
    }
</style>
@endsection

@section('scripts')
<script>
// Recherche en temps réel
(function() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;
    let currentSearch = '';
    
    if (!searchInput || !searchResults) return;
    
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // Masquer les résultats si la recherche est vide
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        // Délai pour éviter trop de requêtes
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Masquer les résultats quand on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    function performSearch(query) {
        if (query === currentSearch) return;
        currentSearch = query;
        
        searchResults.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Recherche...</div>';
        searchResults.style.display = 'block';
        
        fetch(`{{ route('search.ajax') }}?q=${encodeURIComponent(query)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.results && data.results.length > 0) {
                let html = '';
                data.results.forEach(result => {
                    html += `
                        <a href="${result.url}" class="search-result-item">
                            ${result.logo ? `<img src="${result.logo}" alt="${result.name}" class="search-result-image">` : `<div class="search-result-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; border-radius: 8px;">${result.name ? result.name.substring(0, 2) : ''}</div>`}
                            <div class="search-result-content">
                                <div class="search-result-name">${result.name}</div>
                                <div class="search-result-meta">
                                    ${result.cuisines} • <i class="fas fa-star" style="color: #FBBF24;"></i> ${result.rating}
                                </div>
                            </div>
                        </a>
                    `;
                });
                searchResults.innerHTML = html;
            } else {
                searchResults.innerHTML = '<div class="search-no-results">Aucun résultat trouvé</div>';
            }
        })
        .catch(error => {
            console.error('Erreur de recherche:', error);
            searchResults.innerHTML = '<div class="search-no-results">Erreur lors de la recherche</div>';
        });
    }
})();

// Fonction pour ajouter un produit au panier
function addToCart(productId, restaurantId = null) {
    // ===== VALIDATION CÔTÉ CLIENT =====
    if (!productId || productId <= 0) {
        showMessage('ID produit invalide', 'error');
        return;
    }
    
    if (restaurantId && restaurantId <= 0) {
        showMessage('ID restaurant invalide', 'error');
        return;
    }
    
    const qty = 1; // Quantité par défaut
    if (qty < 1 || qty > 20) {
        showMessage('La quantité doit être entre 1 et 20', 'error');
        return;
    }
    
    // Récupérer le bouton qui a déclenché l'événement
    let button = null;
    if (window.event && window.event.target) {
        button = window.event.target.closest('button');
    }
    if (!button) {
        // Fallback : trouver le bouton par son onclick
        const buttons = document.querySelectorAll('button[onclick*="addToCart(' + productId);
        button = buttons[0];
    }
    
    if (!button) {
        console.error('Bouton non trouvé');
        showMessage('Erreur : bouton non trouvé', 'error');
        return;
    }
    
    // Vérifier le token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        showMessage('Erreur de sécurité : token CSRF manquant', 'error');
        return;
    }
    
    // Désactiver le bouton et afficher le chargement
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 0.75rem;"></i>';
    button.disabled = true;
    
    // Préparer les données
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('qty', qty.toString());
    if (restaurantId) {
        formData.append('restaurant_id', restaurantId);
    }
    formData.append('_token', csrfToken);
    
    // ===== REQUÊTE AJAX AVEC TIMEOUT =====
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // Timeout de 10 secondes
    
    fetch('{{ route("cart") }}', {
        method: 'POST',
        body: formData,
        signal: controller.signal,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        clearTimeout(timeoutId);
        
        // Gérer les différents codes de statut
        if (response.status === 419) {
            throw new Error('Session expirée. Veuillez rafraîchir la page.');
        }
        
        if (response.status === 422) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Erreur de validation');
        }
        
        if (response.status >= 500) {
            throw new Error('Erreur serveur. Veuillez réessayer plus tard.');
        }
        
        // Si redirection, c'est une requête classique (non-AJAX)
        if (response.redirected) {
            // Recharger la page pour afficher le message flash
            window.location.reload();
            return;
        }
        
        // Parser la réponse JSON
        const data = await response.json();
        return data;
    })
    .then(data => {
        if (data && data.success) {
            // Succès : afficher message et mettre à jour le compteur
            showMessage(data.message || 'Produit ajouté au panier !', 'success');
            
            // Mettre à jour le compteur avec la valeur retournée par le serveur
            if (data.total_items !== undefined) {
                updateCartBadge(data.total_items);
            } else {
                // Fallback : appeler l'API pour récupérer le compteur
                updateCartCount();
            }
        } else if (data && data.message) {
            // Erreur avec message
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Erreur ajout au panier:', error);
        
        // Messages d'erreur spécifiques selon le type d'erreur
        let errorMessage = 'Erreur lors de l\'ajout au panier';
        
        if (error.name === 'AbortError') {
            errorMessage = 'Délai d\'attente dépassé. Vérifiez votre connexion internet.';
        } else if (error.message) {
            errorMessage = error.message;
        } else if (error instanceof TypeError && error.message.includes('fetch')) {
            errorMessage = 'Erreur de connexion. Vérifiez votre connexion internet.';
        }
        
        showMessage(errorMessage, 'error');
    })
    .finally(() => {
        // Restaurer le bouton
        button.innerHTML = originalHTML;
        button.disabled = false;
    });
}

// Fonction pour mettre à jour le badge du panier directement
function updateCartBadge(count) {
    const cartBadge = document.getElementById('cartBadge') || document.querySelector('.cart-badge');
    if (cartBadge) {
        const countNum = parseInt(count) || 0;
        if (countNum > 0) {
            cartBadge.textContent = countNum;
            cartBadge.style.display = 'inline-block';
            // Animation de mise à jour
            cartBadge.style.transform = 'scale(1.2)';
            cartBadge.style.transition = 'transform 0.2s ease';
            setTimeout(() => {
                cartBadge.style.transform = 'scale(1)';
            }, 200);
        } else {
            cartBadge.style.display = 'none';
        }
    }
}

// Fonction pour mettre à jour le compteur de panier via API
function updateCartCount() {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000); // Timeout de 5 secondes
    
    fetch('{{ route("cart.count") }}', {
        signal: controller.signal,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error('Erreur HTTP ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        updateCartBadge(data.count);
    })
    .catch(error => {
        clearTimeout(timeoutId);
        // Ne pas afficher d'erreur pour le compteur (non bloquant)
        console.warn('Erreur mise à jour compteur:', error);
    });
}

// Fonction pour afficher un message
function showMessage(message, type = 'success') {
    // Créer ou réutiliser un élément de notification
    let notification = document.getElementById('cart-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'cart-notification';
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-weight: 500; transition: all 0.3s;';
        document.body.appendChild(notification);
    }
    
    notification.style.background = type === 'success' ? '#05944F' : '#DC2626';
    notification.style.color = 'white';
    notification.textContent = message;
    notification.style.display = 'block';
    notification.style.opacity = '0';
    notification.style.transform = 'translateY(-20px)';
    
    // Animation d'apparition
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
    }, 10);
    
    // Masquer après 3 secondes
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 300);
    }, 3000);
}
</script>
@endsection
