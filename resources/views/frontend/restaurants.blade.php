@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('description', 'Parcourez tous les restaurants disponibles sur ' . $foodBrandName . ' et commandez en ligne pour une livraison rapide.')
@section('title', 'Tous les restaurants | ' . $foodBrandName)
@section('body_class', 'bd-restaurants-page')

@section('content')
<!-- Page Tous les Restaurants -->
<section class="section restaurants-page-shell">
    <div class="container">
        <!-- En-tête -->
        <div class="restaurants-page-heading">
            <div class="restaurants-page-heading-row">
                <div>
                    <h1 class="restaurants-page-title">Tous les restaurants</h1>
                    <p class="restaurants-page-subtitle">Filtrez les restaurants par cuisine, note, frais de livraison et disponibilité.</p>
                </div>
                <button type="button" id="nearMeBtn" class="restaurants-near-btn" title="Restaurants près de moi">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
                    <span id="nearMeBtnLabel">Près de moi</span>
                </button>
            </div>
        </div>
        <input type="hidden" id="userLat">
        <input type="hidden" id="userLng">
        
        <!-- Filtres et Tri -->
        <div class="restaurants-filter-card">
            <div class="restaurants-filter-grid">
                <!-- Filtre Cuisine -->
                <div class="restaurants-filter-field">
                    <label class="restaurants-filter-label" for="filterCuisine">Cuisine</label>
                    <select id="filterCuisine" class="filter-select restaurants-filter-input">
                        <option value="">Toutes les cuisines</option>
                        @if(isset($cuisines) && $cuisines->count() > 0)
                            @foreach($cuisines as $cuisine)
                                <option value="{{ $cuisine->id }}" {{ (isset($cuisineId) && $cuisineId == $cuisine->id) ? 'selected' : '' }}>{{ $cuisine->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                
                <!-- Filtre Note minimale -->
                <div class="restaurants-filter-field">
                    <label class="restaurants-filter-label" for="filterMinRating">Note minimum</label>
                    <select id="filterMinRating" class="filter-select restaurants-filter-input">
                        <option value="">Toutes les notes</option>
                        @php
                            $topRatedThreshold = \App\Services\ConfigService::getTopRatedThreshold();
                        @endphp
                        <option value="{{ number_format($topRatedThreshold, 1) }}">{{ number_format($topRatedThreshold, 1) }}+ ⭐</option>
                        <option value="4.0">4.0+ ⭐</option>
                        <option value="3.5">3.5+ ⭐</option>
                        <option value="3.0">3.0+ ⭐</option>
                    </select>
                </div>
                
                <!-- Filtre Frais de livraison max -->
                <div class="restaurants-filter-field">
                    <label class="restaurants-filter-label" for="filterMaxDeliveryFee">Frais max (FCFA)</label>
                    <select id="filterMaxDeliveryFee" class="filter-select restaurants-filter-input">
                        <option value="">Aucune limite</option>
                        <option value="1000">≤ 1 000 FCFA</option>
                        <option value="2000">≤ 2 000 FCFA</option>
                        <option value="3000">≤ 3 000 FCFA</option>
                        <option value="5000">≤ 5 000 FCFA</option>
                    </select>
                </div>
                
                <!-- Tri -->
                <div class="restaurants-filter-field">
                    <label class="restaurants-filter-label" for="filterSort">Trier par</label>
                    <select id="filterSort" class="filter-select restaurants-filter-input">
                        <option value="popular" {{ request('sort') == 'popular' || !request('sort') ? 'selected' : '' }}>Popularité</option>
                        <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Meilleure note</option>
                        <option value="delivery_fee" {{ request('sort') == 'delivery_fee' ? 'selected' : '' }}>Frais de livraison</option>
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Nom (A-Z)</option>
                    </select>
                </div>
            </div>
            
            <!-- Recherche textuelle -->
            <div class="restaurants-filter-field">
                <label class="restaurants-filter-label" for="filterSearch">Rechercher</label>
                <input type="text" id="filterSearch" class="restaurants-filter-input" placeholder="Nom, adresse..." value="{{ request('search') }}">
            </div>
        </div>
        
        <!-- Résultats -->
        <div id="restaurantsContainer">
            @include('frontend.partials.restaurants_list', ['restaurants' => $restaurants ?? collect()])
        </div>
        
        <!-- Pagination -->
        @if(isset($paginator) && $paginator->lastPage() > 1)
        <div id="paginationContainer" class="restaurants-pagination">
            <button id="prevPage" class="pagination-btn {{ $paginator->currentPage() <= 1 ? 'is-disabled' : '' }}" data-page="{{ $paginator->currentPage() - 1 }}"
                    {{ $paginator->currentPage() <= 1 ? 'disabled' : '' }}>
                Précédent
            </button>
            
            <span class="restaurants-pagination-status">
                Page <span id="currentPage">{{ $paginator->currentPage() }}</span> / <span id="lastPage">{{ $paginator->lastPage() }}</span>
                (<span id="totalResults">{{ $paginator->total() }}</span> restaurants)
            </span>
            
            <button id="nextPage" class="pagination-btn {{ $paginator->currentPage() >= $paginator->lastPage() ? 'is-disabled' : '' }}" data-page="{{ $paginator->currentPage() + 1 }}"
                    {{ $paginator->currentPage() >= $paginator->lastPage() ? 'disabled' : '' }}>
                Suivant
            </button>
        </div>
        @endif
    </div>
</section>
@endsection

@section('scripts')
<script>
    let currentPage = {{ isset($paginator) ? $paginator->currentPage() : 1 }};
    let lastPage = {{ isset($paginator) ? $paginator->lastPage() : 1 }};
    let totalResults = {{ isset($paginator) ? $paginator->total() : 0 }};
    let loading = false;
    const placeholderImageUrl = @json(asset('images/home/food-orb3.jpg'));
    const MAPBOX_TOKEN = @json(mapbox_public_token());
    const restaurantDetailBaseUrl = @json(url('restaurant/view'));
    const restaurantFavoriteBaseUrl = @json(url('restaurants'));
    
    // Fonction pour charger les restaurants
    async function loadRestaurants(page = 1) {
        if (loading) return;
        
        loading = true;
        const container = document.getElementById('restaurantsContainer');
        const paginationContainer = document.getElementById('paginationContainer');
        
        // Afficher le loader
        container.innerHTML = renderFeedbackState('Chargement des restaurants...');
        
        // Récupérer les filtres + coords GPS si disponibles
        const filters = {
            cuisine: document.getElementById('filterCuisine')?.value || '',
            min_rating: document.getElementById('filterMinRating')?.value || '',
            max_delivery_fee: document.getElementById('filterMaxDeliveryFee')?.value || '',
            sort: document.getElementById('filterSort')?.value || 'popular',
            search: document.getElementById('filterSearch')?.value || '',
            page: page,
            per_page: 12
        };
        const lat = document.getElementById('userLat')?.value;
        const lng = document.getElementById('userLng')?.value;
        if (lat && lng) { filters.lat = lat; filters.lng = lng; }
        
        // Nettoyer les filtres vides
        Object.keys(filters).forEach(key => {
            if (filters[key] === '' || filters[key] === null) {
                delete filters[key];
            }
        });
        
        try {
            const params = new URLSearchParams(filters);
            const response = await fetch(`{{ route('restaurants.all') }}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.status && data.data) {
                // Mettre à jour les restaurants
                container.innerHTML = renderRestaurants(data.data);
                bindRestaurantCards();
                
                // Mettre à jour la pagination
                if (data.meta) {
                    currentPage = data.meta.current_page;
                    lastPage = data.meta.last_page;
                    totalResults = data.meta.total;
                    updatePagination();
                }
                
                // Mettre à jour l'URL sans recharger la page
                const url = new URL(window.location);
                Object.keys(filters).forEach(key => {
                    if (filters[key]) {
                        url.searchParams.set(key, filters[key]);
                    } else {
                        url.searchParams.delete(key);
                    }
                });
                window.history.pushState({}, '', url);
            } else {
                showEmptyWithFallback(container);
            }
        } catch (error) {
            console.error('Error loading restaurants:', error);
            container.innerHTML = renderFeedbackState('Erreur lors du chargement des restaurants.', 'is-error');
        } finally {
            loading = false;
        }
    }

    function renderFeedbackState(message, modifier = '') {
        return `<div class="restaurants-feedback ${modifier}"><p>${message}</p></div>`;
    }

    let _fallbackMap = null;

    function showEmptyWithFallback(container) {
        const lat = parseFloat(document.getElementById('userLat')?.value) || null;
        const lng = parseFloat(document.getElementById('userLng')?.value) || null;

        // Coordonnées par défaut : centre de Brazzaville
        const centerLat = lat || -4.2767;
        const centerLng = lng || 15.2832;

        container.innerHTML = `
        <div class="restaurants-feedback">
            <div class="restaurants-empty-icon">🍽️</div>
            <p class="restaurants-empty-title">Aucun restaurant BantuDelice dans cette zone pour l'instant.</p>
            <p class="restaurants-empty-sub">Notre réseau couvre Brazzaville et Pointe-Noire. Voici un aperçu de la zone — nous y ajoutons de nouveaux partenaires régulièrement.</p>
            <div class="restaurants-fallback-map" id="fallbackMapBox" style="height:300px;border-radius:14px;overflow:hidden;margin:16px 0;"></div>
            <a href="https://www.openstreetmap.org/search?query=restaurant+Brazzaville" target="_blank" rel="noopener noreferrer" class="restaurants-empty-cta">
                Voir sur OpenStreetMap
            </a>
        </div>`;

        // Initialiser la carte Leaflet + tuiles Mapbox/OSM
        if (typeof L === 'undefined') {
            // Charger Leaflet à la volée si pas encore chargé
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(css);
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = function() { _initFallbackMap(centerLat, centerLng, lat && lng); };
            document.head.appendChild(script);
        } else {
            _initFallbackMap(centerLat, centerLng, lat && lng);
        }
    }

    function _initFallbackMap(centerLat, centerLng, hasUserPos) {
        const box = document.getElementById('fallbackMapBox');
        if (!box) return;
        if (_fallbackMap) { _fallbackMap.remove(); _fallbackMap = null; }

        _fallbackMap = L.map(box, { zoomControl: true }).setView([centerLat, centerLng], hasUserPos ? 14 : 13);

        const tileUrl = MAPBOX_TOKEN
            ? `https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=${MAPBOX_TOKEN}`
            : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        const tileOpts = MAPBOX_TOKEN
            ? { tileSize: 512, zoomOffset: -1, attribution: '© Mapbox © OpenStreetMap', maxZoom: 18 }
            : { attribution: '© OpenStreetMap', maxZoom: 18 };

        L.tileLayer(tileUrl, tileOpts).addTo(_fallbackMap);

        if (hasUserPos) {
            const icon = L.divIcon({
                html: '<div style="width:16px;height:16px;background:#3b82f6;border:3px solid #fff;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.3)"></div>',
                iconSize: [16,16], iconAnchor: [8,8], className: ''
            });
            L.marker([centerLat, centerLng], { icon })
                .addTo(_fallbackMap)
                .bindPopup('Votre position')
                .openPopup();
        }

        // Marqueurs pour Brazzaville et Pointe-Noire si pas de position précise
        if (!hasUserPos) {
            const dotIcon = function(color) {
                return L.divIcon({
                    html: `<div style="width:12px;height:12px;background:${color};border:2px solid #fff;border-radius:50%;box-shadow:0 1px 4px rgba(0,0,0,.3)"></div>`,
                    iconSize: [12,12], iconAnchor: [6,6], className: ''
                });
            };
            L.marker([-4.2767, 15.2832], { icon: dotIcon('#009543') }).addTo(_fallbackMap).bindPopup('Brazzaville');
            L.marker([-4.7769, 11.8632], { icon: dotIcon('#009543') }).addTo(_fallbackMap).bindPopup('Pointe-Noire');
            _fallbackMap.setView([-4.5, 13.5], 6);
        }
    }
    
    // Fonction pour rendre les restaurants
    function renderRestaurants(restaurants) {
        if (!restaurants || restaurants.length === 0) {
            return renderFeedbackState('Aucun restaurant disponible pour le moment.');
        }
        
        let html = '<div class="restaurants-grid restaurants-results-grid">';
        
        restaurants.forEach(restaurant => {
            const thumbnailUrl = restaurant.thumbnail_url || placeholderImageUrl;
            const detailUrl = `${restaurantDetailBaseUrl}/${restaurant.id}`;
            const favoriteUrl = `${restaurantFavoriteBaseUrl}/${restaurant.id}/favorite`;
            const topRatedLabel = restaurant.is_featured
                ? 'Top note'
                : `Note ${restaurant.avg_rating || '4.5'}`;
            const favoriteClass = restaurant.is_favorite ? ' is-active' : '';
            
            html += `
                <article class="restaurant-card restaurants-feed-card" data-href="${detailUrl}">
                    <div class="restaurant-card-image restaurants-feed-card__media">
                        <img src="${thumbnailUrl}" 
                             alt="${restaurant.name}"
                             class="restaurants-feed-card__image"
                             onerror="this.src='${placeholderImageUrl}'">
                        <div class="restaurants-feed-card__overlay"></div>
                        <div class="restaurants-feed-card__eta">
                            <span>${restaurant.eta_display || '20-35 min'}</span>
                        </div>
                        ${restaurant.is_top_rated ? `
                        <div class="restaurants-feed-card__badge-wrap">
                            <span class="restaurants-feed-card__badge">
                                ${topRatedLabel}
                            </span>
                        </div>
                        ` : ''}
                        @auth
                        <form method="POST" action="${favoriteUrl}" class="restaurants-feed-card__favorite-form" onclick="event.stopPropagation();">
                            @csrf
                            <button type="submit" aria-label="Ajouter aux favoris" class="restaurants-feed-card__favorite-btn${favoriteClass}">
                                <i class="fas fa-heart"></i>
                            </button>
                        </form>
                        @endauth
                    </div>
                    <div class="restaurant-card-content restaurants-feed-card__content">
                        <div class="restaurants-feed-card__top">
                            <h3 class="restaurant-card-title restaurants-feed-card__title">
                                ${restaurant.name}
                            </h3>
                            <div class="restaurants-feed-card__rating">
                                <span>${restaurant.avg_rating || '4.5'}</span>
                            </div>
                        </div>
                        <p class="restaurant-card-cuisine restaurants-feed-card__cuisine">
                            ${restaurant.cuisines_display || 'Cuisine variée'}
                        </p>
                        <div class="restaurant-card-meta restaurants-feed-card__meta">
                            <span class="restaurant-card-delivery restaurants-feed-card__delivery">
                                Frais de livraison : <strong>${new Intl.NumberFormat('fr-FR').format(restaurant.delivery_fee || 1500)} FCFA</strong>
                            </span>
                        </div>
                    </div>
                </article>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    // Fonction pour mettre à jour la pagination
    function updatePagination() {
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const currentPageSpan = document.getElementById('currentPage');
        const lastPageSpan = document.getElementById('lastPage');
        const totalResultsSpan = document.getElementById('totalResults');
        
        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
            prevBtn.classList.toggle('is-disabled', currentPage <= 1);
            prevBtn.setAttribute('data-page', currentPage - 1);
        }
        
        if (nextBtn) {
            nextBtn.disabled = currentPage >= lastPage;
            nextBtn.classList.toggle('is-disabled', currentPage >= lastPage);
            nextBtn.setAttribute('data-page', currentPage + 1);
        }
        
        if (currentPageSpan) currentPageSpan.textContent = currentPage;
        if (lastPageSpan) lastPageSpan.textContent = lastPage;
        if (totalResultsSpan) totalResultsSpan.textContent = totalResults;
        
        // Afficher/masquer la pagination
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.classList.toggle('is-hidden', lastPage <= 1);
        }
    }

    function bindRestaurantCards() {
        document.querySelectorAll('.restaurants-feed-card[data-href]').forEach(card => {
            if (card.dataset.bound === '1') {
                return;
            }

            card.dataset.bound = '1';
            card.addEventListener('click', function(event) {
                if (event.target.closest('form, button, a')) {
                    return;
                }

                window.location.href = this.dataset.href;
            });
        });
    }
    
    // ── Bouton "Près de moi" ───────────────────────────────────────
    function initNearMeBtn() {
        const btn = document.getElementById('nearMeBtn');
        const label = document.getElementById('nearMeBtnLabel');
        if (!btn) return;

        // Si lat/lng déjà passés depuis la homepage hero
        const urlLat = new URLSearchParams(window.location.search).get('lat');
        const urlLng = new URLSearchParams(window.location.search).get('lng');
        if (urlLat && urlLng) {
            document.getElementById('userLat').value = urlLat;
            document.getElementById('userLng').value = urlLng;
            if (label) label.textContent = 'Position active';
            btn.classList.add('is-active');
        }

        btn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('Géolocalisation non disponible sur cet appareil.');
                return;
            }
            btn.disabled = true;
            if (label) label.textContent = 'Localisation…';
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    document.getElementById('userLat').value = pos.coords.latitude;
                    document.getElementById('userLng').value = pos.coords.longitude;
                    if (label) label.textContent = 'Position active';
                    btn.disabled = false;
                    btn.classList.add('is-active');
                    currentPage = 1;
                    loadRestaurants(1);
                },
                function() {
                    btn.disabled = false;
                    if (label) label.textContent = 'Près de moi';
                    alert('Impossible de récupérer votre position. Vérifiez les permissions du navigateur.');
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        });
    }

    // Event listeners pour les filtres
    document.addEventListener('DOMContentLoaded', function() {
        bindRestaurantCards();
        initNearMeBtn();

        // Filtres
        const filterSelects = document.querySelectorAll('.filter-select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                currentPage = 1; // Reset à la page 1
                loadRestaurants(1);
            });
        });
        
        // Recherche avec debounce
        let searchTimeout;
        const searchInput = document.getElementById('filterSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    loadRestaurants(1);
                }, 500);
            });
        }
        
        // Pagination
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (!this.disabled && currentPage > 1) {
                    loadRestaurants(currentPage - 1);
                }
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (!this.disabled && currentPage < lastPage) {
                    loadRestaurants(currentPage + 1);
                }
            });
        }
    });
</script>
@endsection
