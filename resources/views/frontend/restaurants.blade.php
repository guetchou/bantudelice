@extends('frontend.layouts.app-modern')

@section('title', 'Tous les restaurants | BantuDelice')

@section('content')
<!-- Page Tous les Restaurants -->
<section class="section" style="padding-top: 100px; padding-bottom: 60px; background: #FAFAFA; min-height: 80vh;">
    <div class="container">
        <!-- En-tête -->
        <div style="margin-bottom: 32px;">
            <h1 style="font-size: 2rem; font-weight: 700; margin: 0 0 8px 0; color: #191919;">Restaurants populaires</h1>
            <p style="color: #6B6B6B; font-size: 1rem; margin: 0;">Découvrez les restaurants les mieux notés par nos clients</p>
        </div>
        
        <!-- Filtres et Tri -->
        <div style="background: white; border-radius: 16px; padding: 1.5rem; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <!-- Filtre Cuisine -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #191919; font-size: 0.875rem;">Cuisine</label>
                    <select id="filterCuisine" class="filter-select" style="width: 100%; padding: 0.625rem; border: 1px solid #E0E0E0; border-radius: 8px; font-size: 0.875rem;">
                        <option value="">Toutes les cuisines</option>
                        @if(isset($cuisines) && $cuisines->count() > 0)
                            @foreach($cuisines as $cuisine)
                                <option value="{{ $cuisine->id }}" {{ (isset($cuisineId) && $cuisineId == $cuisine->id) ? 'selected' : '' }}>{{ $cuisine->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                
                <!-- Filtre Note minimale -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #191919; font-size: 0.875rem;">Note minimum</label>
                    <select id="filterMinRating" class="filter-select" style="width: 100%; padding: 0.625rem; border: 1px solid #E0E0E0; border-radius: 8px; font-size: 0.875rem;">
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
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #191919; font-size: 0.875rem;">Frais max (FCFA)</label>
                    <select id="filterMaxDeliveryFee" class="filter-select" style="width: 100%; padding: 0.625rem; border: 1px solid #E0E0E0; border-radius: 8px; font-size: 0.875rem;">
                        <option value="">Aucune limite</option>
                        <option value="1000">≤ 1 000 FCFA</option>
                        <option value="2000">≤ 2 000 FCFA</option>
                        <option value="3000">≤ 3 000 FCFA</option>
                        <option value="5000">≤ 5 000 FCFA</option>
                    </select>
                </div>
                
                <!-- Tri -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #191919; font-size: 0.875rem;">Trier par</label>
                    <select id="filterSort" class="filter-select" style="width: 100%; padding: 0.625rem; border: 1px solid #E0E0E0; border-radius: 8px; font-size: 0.875rem;">
                        <option value="popular" {{ request('sort') == 'popular' || !request('sort') ? 'selected' : '' }}>Popularité</option>
                        <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Meilleure note</option>
                        <option value="delivery_fee" {{ request('sort') == 'delivery_fee' ? 'selected' : '' }}>Frais de livraison</option>
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Nom (A-Z)</option>
                    </select>
                </div>
            </div>
            
            <!-- Recherche textuelle -->
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #191919; font-size: 0.875rem;">Rechercher</label>
                <input type="text" id="filterSearch" placeholder="Nom, adresse..." style="width: 100%; padding: 0.625rem; border: 1px solid #E0E0E0; border-radius: 8px; font-size: 0.875rem;" value="{{ request('search') }}">
            </div>
        </div>
        
        <!-- Résultats -->
        <div id="restaurantsContainer">
            @include('frontend.partials.restaurants_list', ['restaurants' => $restaurants ?? collect()])
        </div>
        
        <!-- Pagination -->
        @if(isset($paginator) && $paginator->lastPage() > 1)
        <div id="paginationContainer" style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 2rem;">
            <button id="prevPage" class="pagination-btn" data-page="{{ $paginator->currentPage() - 1 }}" 
                    style="padding: 0.625rem 1.25rem; border: 1px solid #E0E0E0; border-radius: 8px; background: white; cursor: pointer; font-size: 0.875rem; {{ $paginator->currentPage() <= 1 ? 'opacity: 0.5; cursor: not-allowed;' : '' }}"
                    {{ $paginator->currentPage() <= 1 ? 'disabled' : '' }}>
                <i class="fas fa-chevron-left"></i> Précédent
            </button>
            
            <span style="color: #6B7280; font-size: 0.875rem;">
                Page <span id="currentPage">{{ $paginator->currentPage() }}</span> / <span id="lastPage">{{ $paginator->lastPage() }}</span>
                (<span id="totalResults">{{ $paginator->total() }}</span> restaurants)
            </span>
            
            <button id="nextPage" class="pagination-btn" data-page="{{ $paginator->currentPage() + 1 }}"
                    style="padding: 0.625rem 1.25rem; border: 1px solid #E0E0E0; border-radius: 8px; background: white; cursor: pointer; font-size: 0.875rem; {{ $paginator->currentPage() >= $paginator->lastPage() ? 'opacity: 0.5; cursor: not-allowed;' : '' }}"
                    {{ $paginator->currentPage() >= $paginator->lastPage() ? 'disabled' : '' }}>
                Suivant <i class="fas fa-chevron-right"></i>
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
    
    // Fonction pour charger les restaurants
    async function loadRestaurants(page = 1) {
        if (loading) return;
        
        loading = true;
        const container = document.getElementById('restaurantsContainer');
        const paginationContainer = document.getElementById('paginationContainer');
        
        // Afficher le loader
        container.innerHTML = '<div style="text-align: center; padding: 60px 20px;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #05944F;"></i><p style="margin-top: 1rem; color: #6B7280;">Chargement des restaurants...</p></div>';
        
        // Récupérer les filtres
        const filters = {
            cuisine: document.getElementById('filterCuisine')?.value || '',
            min_rating: document.getElementById('filterMinRating')?.value || '',
            max_delivery_fee: document.getElementById('filterMaxDeliveryFee')?.value || '',
            sort: document.getElementById('filterSort')?.value || 'popular',
            search: document.getElementById('filterSearch')?.value || '',
            page: page,
            per_page: 12
        };
        
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
                container.innerHTML = '<div style="text-align: center; padding: 60px 20px; color: #6B7280;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 16px; opacity: 0.3;"></i><p>Aucun restaurant trouvé</p></div>';
            }
        } catch (error) {
            console.error('Error loading restaurants:', error);
            container.innerHTML = '<div style="text-align: center; padding: 60px 20px; color: #EF4444;"><i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 16px;"></i><p>Erreur lors du chargement des restaurants</p></div>';
        } finally {
            loading = false;
        }
    }
    
    // Fonction pour rendre les restaurants
    function renderRestaurants(restaurants) {
        if (!restaurants || restaurants.length === 0) {
            return '<div style="text-align: center; padding: 60px 20px; color: #6B7280;"><i class="fas fa-utensils" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i><p style="font-size: 1rem; margin: 0;">Aucun restaurant disponible pour le moment</p></div>';
        }
        
        let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">';
        
        restaurants.forEach(restaurant => {
            const thumbnailUrl = restaurant.thumbnail_url || '{{ asset("images/placeholder.png") }}';
            const detailUrl = `{{ url('/restaurant') }}/${restaurant.id}`;
            
            html += `
                <article class="restaurant-card" style="position: relative; border-radius: 16px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;"
                        onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'"
                        onclick="window.location.href='${detailUrl}'">
                    <div style="position: relative; height: 160px; overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <img src="${thumbnailUrl}" 
                             alt="${restaurant.name}"
                             style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                             onerror="this.src='{{ asset("images/placeholder.png") }}'"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform='scale(1)'">
                        <div style="pointer-events: none; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.05) 50%, transparent 100%);"></div>
                        <div style="position: absolute; bottom: 8px; left: 8px; display: flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); padding: 4px 10px; border-radius: 500px; font-size: 0.75rem; font-weight: 500; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                            <i class="fas fa-clock" style="font-size: 0.625rem; color: #6B6B6B;"></i>
                            <span>${restaurant.eta_display || '20-35 min'}</span>
                        </div>
                        ${restaurant.is_top_rated ? `
                        <div style="position: absolute; top: 8px; left: 8px;">
                            <span style="background: #05944F; color: white; padding: 4px 10px; border-radius: 500px; font-size: 0.6875rem; font-weight: 600; box-shadow: 0 2px 8px rgba(5,148,79,0.3);">
                                ${restaurant.is_featured ? 'Top noté' : `⭐ ${restaurant.avg_rating}`}
                            </span>
                        </div>
                        ` : ''}
                    </div>
                    <div style="padding: 14px 16px 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                            <h3 style="font-size: 1rem; font-weight: 600; margin: 0; color: #191919; line-height: 1.3; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${restaurant.name}
                            </h3>
                            <div style="display: flex; align-items: center; gap: 3px; background: #F6F6F6; padding: 4px 8px; border-radius: 500px; font-size: 0.8125rem; font-weight: 600; flex-shrink: 0;">
                                <span style="color: #191919;">${restaurant.avg_rating || '4.5'}</span>
                                <i class="fas fa-star" style="color: #FFB800; font-size: 0.625rem;"></i>
                            </div>
                        </div>
                        <p style="color: #6B6B6B; font-size: 0.8125rem; margin: 0 0 8px 0; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${restaurant.cuisines_display || 'Cuisine variée'}
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid #F0F0F0;">
                            <span style="color: #6B6B6B; font-size: 0.8125rem;">
                                Frais de livraison : <strong style="color: #191919;">${new Intl.NumberFormat('fr-FR').format(restaurant.delivery_fee || 1500)} FCFA</strong>
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
            prevBtn.style.opacity = currentPage <= 1 ? '0.5' : '1';
            prevBtn.style.cursor = currentPage <= 1 ? 'not-allowed' : 'pointer';
            prevBtn.setAttribute('data-page', currentPage - 1);
        }
        
        if (nextBtn) {
            nextBtn.disabled = currentPage >= lastPage;
            nextBtn.style.opacity = currentPage >= lastPage ? '0.5' : '1';
            nextBtn.style.cursor = currentPage >= lastPage ? 'not-allowed' : 'pointer';
            nextBtn.setAttribute('data-page', currentPage + 1);
        }
        
        if (currentPageSpan) currentPageSpan.textContent = currentPage;
        if (lastPageSpan) lastPageSpan.textContent = lastPage;
        if (totalResultsSpan) totalResultsSpan.textContent = totalResults;
        
        // Afficher/masquer la pagination
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            if (lastPage > 1) {
                paginationContainer.style.display = 'flex';
            } else {
                paginationContainer.style.display = 'none';
            }
        }
    }
    
    // Event listeners pour les filtres
    document.addEventListener('DOMContentLoaded', function() {
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
