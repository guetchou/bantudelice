@extends('frontend.layouts.app-modern')

@php
    $restaurants = collect($restaurants ?? []);
    $products = collect($products ?? []);
    $recommendations = collect($recommendations ?? []);
    $productRecommendations = collect($productRecommendations ?? []);
    $filtersData = $filtersData ?? [];
    $query = trim((string) ($query ?? ''));
    $locationLabel = trim((string) data_get($filtersData, 'location_label', ''));
    $latitude = data_get($filtersData, 'latitude');
    $longitude = data_get($filtersData, 'longitude');
    $hasLocation = $latitude !== null && $longitude !== null;
    $resultCount = $restaurants->count() + $products->count();
    $restaurantSort = data_get($filtersData, 'sort', 'relevance');
    $productSort = data_get($filtersData, 'product_sort', 'relevance');
    $fallbackRestaurant = asset('images/home/service-restaurant.jpg');
    $fallbackProduct = asset('images/product_images/default-food.jpg');

    $popularSearches = ['Poulet', 'Pizza', 'Poisson', 'Congolais', 'Petit-déjeuner'];
    $sortOptions = [
        'relevance' => 'Pertinence',
        'recommended' => 'Recommandés',
        'rating' => 'Mieux notés',
        'delivery_fee' => 'Frais de livraison',
        'distance' => 'Distance',
        'featured' => 'En vedette',
    ];
    $productSortOptions = [
        'relevance' => 'Pertinence',
        'featured' => 'En vedette',
        'price_low' => 'Prix croissant',
        'price_high' => 'Prix décroissant',
    ];

    $activeFilters = collect();
    if (data_get($filtersData, 'city')) {
        $activeFilters->push('Ville : ' . data_get($filtersData, 'city'));
    }
    if (data_get($filtersData, 'min_rating')) {
        $activeFilters->push('Note : ' . data_get($filtersData, 'min_rating') . '+');
    }
    if (data_get($filtersData, 'cuisine_id')) {
        $activeCuisine = collect($cuisines ?? [])->firstWhere('id', (int) data_get($filtersData, 'cuisine_id'));
        $activeFilters->push('Cuisine : ' . ($activeCuisine->name ?? 'sélectionnée'));
    }
    if (data_get($filtersData, 'max_delivery_fee')) {
        $activeFilters->push('Livraison ≤ ' . number_format((float) data_get($filtersData, 'max_delivery_fee'), 0, ',', ' ') . ' FCFA');
    }
    if (data_get($filtersData, 'min_price')) {
        $activeFilters->push('Prix min. : ' . number_format((float) data_get($filtersData, 'min_price'), 0, ',', ' ') . ' FCFA');
    }
    if (data_get($filtersData, 'max_price')) {
        $activeFilters->push('Prix max. : ' . number_format((float) data_get($filtersData, 'max_price'), 0, ',', ' ') . ' FCFA');
    }
    if (data_get($filtersData, 'featured')) {
        $activeFilters->push('En vedette');
    }

    $filterCount = $activeFilters->count();
    $resetParameters = array_filter([
        'query' => $query,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'location_label' => $locationLabel,
    ], static fn ($value) => $value !== null && $value !== '');
@endphp

@section('title', ($query !== '' ? 'Recherche : ' . $query : 'Trouver un repas') . ' | ' . trans('ui.site.name'))
@section('description', 'Trouvez rapidement un restaurant, un plat ou une cuisine disponible près de chez vous sur BantuDelice.')
@section('body_class', 'bd-search-page')

@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/pages/search.css') }}">
@endsection

@section('content')
<a href="#searchResults" class="search-skip-link">Aller aux résultats</a>
<div id="searchLiveRegion" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

<section class="search-hero" aria-labelledby="searchPageTitle">
    <div class="container">
        <div class="search-hero-grid">
            <div class="search-hero-copy">
                <div class="search-eyebrow">
                    <i class="fas fa-utensils" aria-hidden="true"></i>
                    Saveurs locales, choix rapide
                </div>

                <h1 id="searchPageTitle" class="search-hero-title">
                    @if($query !== '')
                        Résultats pour <strong>« {{ $query }} »</strong>
                    @else
                        Trouvez votre <strong>prochain repas</strong>
                    @endif
                </h1>

                <p class="search-hero-description">
                    Recherchez un restaurant, un plat ou une cuisine. BantuDelice classe les résultats selon la pertinence, la qualité et votre position.
                </p>

                <form id="catalogSearchForm" method="GET" action="{{ route('search') }}" class="search-main-form" role="search">
                    <label for="catalogSearchQuery" class="visually-hidden">Restaurant, plat ou cuisine</label>
                    <div class="search-main-field">
                        <span class="search-main-icon" aria-hidden="true"><i class="fas fa-magnifying-glass"></i></span>
                        <input id="catalogSearchQuery"
                               type="search"
                               name="query"
                               value="{{ $query }}"
                               class="search-main-input"
                               maxlength="120"
                               placeholder="Restaurant, plat ou cuisine…"
                               autocomplete="off"
                               enterkeyhint="search">
                    </div>
                    @if($hasLocation)
                        <input type="hidden" name="latitude" value="{{ $latitude }}">
                        <input type="hidden" name="longitude" value="{{ $longitude }}">
                        @if($locationLabel !== '')
                            <input type="hidden" name="location_label" value="{{ $locationLabel }}">
                        @endif
                    @endif
                    <button type="submit" class="search-submit">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span data-submit-label>Rechercher</span>
                    </button>
                </form>

                @if($hasLocation)
                    <div class="search-context-row">
                        <span class="search-location-pill">
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                            {{ $locationLabel !== '' ? $locationLabel : 'Position actuelle' }}
                        </span>
                        <a href="{{ route('search', array_filter(['query' => $query])) }}" class="search-context-link">
                            <i class="fas fa-xmark" aria-hidden="true"></i>
                            Retirer la position
                        </a>
                    </div>
                @endif

                <div class="search-popular" aria-label="Recherches populaires">
                    <span class="search-popular-label">Recherches populaires</span>
                    @foreach($popularSearches as $popularSearch)
                        <a href="{{ route('search', array_merge($hasLocation ? ['latitude' => $latitude, 'longitude' => $longitude, 'location_label' => $locationLabel] : [], ['query' => $popularSearch])) }}"
                           class="search-popular-link">{{ $popularSearch }}</a>
                    @endforeach
                </div>

                @if($errors->any())
                    <div class="search-validation" role="alert">
                        <strong>La recherche contient une erreur.</strong>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="search-hero-visual" aria-hidden="true">
                <div class="search-orbit"></div>
                <div class="search-dish"></div>
                <div class="search-visual-card search-visual-card--rating">
                    <i class="fas fa-star"></i>
                    <span><strong>Mieux notés</strong><small>Qualité vérifiée</small></span>
                </div>
                <div class="search-visual-card search-visual-card--delivery">
                    <i class="fas fa-motorcycle"></i>
                    <span><strong>Près de vous</strong><small>Livraison locale</small></span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="search-page-shell">
    <div class="container">
        <div class="search-mobile-toolbar" aria-label="Outils de recherche mobiles">
            <button id="searchFiltersOpen"
                    type="button"
                    class="search-mobile-filter-button"
                    aria-controls="searchFiltersPanel"
                    aria-expanded="false">
                <i class="fas fa-sliders" aria-hidden="true"></i>
                Filtres @if($filterCount > 0)({{ $filterCount }})@endif
            </button>
            <label for="searchMobileSort" class="visually-hidden">Trier les résultats</label>
            <select id="searchMobileSort" class="search-mobile-sort">
                @foreach($sortOptions as $value => $label)
                    <option value="{{ $value }}" {{ $restaurantSort === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div id="searchFiltersBackdrop" class="search-filter-backdrop" aria-hidden="true"></div>

        <div class="search-layout">
            <aside id="searchFiltersPanel" class="search-sidebar" aria-label="Filtres de recherche" aria-hidden="false">
                <div class="search-panel">
                    <div class="search-panel-header">
                        <h2 class="search-panel-title">
                            <i class="fas fa-sliders" aria-hidden="true"></i>
                            Affiner
                            @if($filterCount > 0)<span class="search-filter-count">{{ $filterCount }}</span>@endif
                        </h2>
                        <button id="searchFiltersClose" type="button" class="search-panel-close" aria-label="Fermer les filtres">
                            <i class="fas fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>

                    <form id="searchFilterForm" method="GET" action="{{ route('search') }}" class="search-filter-form">
                        <input type="hidden" name="query" value="{{ $query }}">
                        @if($hasLocation)
                            <input type="hidden" name="latitude" value="{{ $latitude }}">
                            <input type="hidden" name="longitude" value="{{ $longitude }}">
                            <input type="hidden" name="location_label" value="{{ $locationLabel }}">
                        @endif

                        <div class="search-filter-group">
                            <label for="searchSort" class="search-filter-label">Tri des restaurants</label>
                            <select id="searchSort" name="sort" class="search-control">
                                @foreach($sortOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $restaurantSort === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="search-filter-group">
                            <label for="searchCity" class="search-filter-label">Ville</label>
                            <input id="searchCity" type="text" name="city" maxlength="120"
                                   value="{{ data_get($filtersData, 'city') }}" class="search-control" placeholder="Brazzaville">
                        </div>

                        <div class="search-filter-group">
                            <label for="searchRating" class="search-filter-label">Note minimale</label>
                            <select id="searchRating" name="min_rating" class="search-control">
                                <option value="">Toutes les notes</option>
                                @for($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" {{ (string) data_get($filtersData, 'min_rating') === (string) $i ? 'selected' : '' }}>{{ $i }} étoiles et plus</option>
                                @endfor
                            </select>
                        </div>

                        <div class="search-filter-group">
                            <label for="searchCuisine" class="search-filter-label">Cuisine</label>
                            <select id="searchCuisine" name="cuisine_id" class="search-control">
                                <option value="">Toutes les cuisines</option>
                                @foreach($cuisines as $cuisine)
                                    <option value="{{ $cuisine->id }}" {{ (string) data_get($filtersData, 'cuisine_id') === (string) $cuisine->id ? 'selected' : '' }}>{{ $cuisine->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="search-filter-group">
                            <label for="searchDeliveryFee" class="search-filter-label">Frais de livraison maximum</label>
                            <input id="searchDeliveryFee" type="number" name="max_delivery_fee" min="0" max="1000000" step="100"
                                   value="{{ data_get($filtersData, 'max_delivery_fee') }}" class="search-control" placeholder="5 000 FCFA">
                        </div>

                        <hr class="search-filter-divider">

                        <div class="search-filter-group">
                            <label for="searchProductSort" class="search-filter-label">Tri des plats</label>
                            <select id="searchProductSort" name="product_sort" class="search-control">
                                @foreach($productSortOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $productSort === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="search-filter-group">
                            <span class="search-filter-label">Fourchette de prix</span>
                            <div class="search-price-grid">
                                <div>
                                    <label for="searchMinPrice" class="visually-hidden">Prix minimum</label>
                                    <input id="searchMinPrice" type="number" name="min_price" min="0" max="10000000" step="100"
                                           value="{{ data_get($filtersData, 'min_price') }}" class="search-control" placeholder="Minimum">
                                </div>
                                <div>
                                    <label for="searchMaxPrice" class="visually-hidden">Prix maximum</label>
                                    <input id="searchMaxPrice" type="number" name="max_price" min="0" max="10000000" step="100"
                                           value="{{ data_get($filtersData, 'max_price') }}" class="search-control" placeholder="Maximum">
                                </div>
                            </div>
                        </div>

                        <label class="search-switch" for="featuredOnly">
                            <span>Seulement en vedette</span>
                            <input type="checkbox" id="featuredOnly" name="featured" value="1" {{ data_get($filtersData, 'featured') ? 'checked' : '' }}>
                            <span class="search-switch-track" aria-hidden="true"></span>
                        </label>

                        <div class="search-filter-actions">
                            <button class="search-filter-submit" type="submit">
                                <i class="fas fa-check" aria-hidden="true"></i>
                                <span data-submit-label>Appliquer les filtres</span>
                            </button>
                            <a href="{{ route('search', $resetParameters) }}" class="search-filter-reset">
                                <i class="fas fa-rotate-left" aria-hidden="true"></i>
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>

                @if($recommendations->isNotEmpty())
                    <div class="search-recommendations">
                        <h2 class="search-recommendations-title">Suggestions pour vous</h2>
                        <div class="search-reco-list">
                            @foreach($recommendations->take(5) as $restaurant)
                                @php
                                    $restaurantImage = method_exists($restaurant, 'publicIdentityImageUrl')
                                        ? $restaurant->publicIdentityImageUrl()
                                        : ($restaurant->logo ? asset('images/restaurant_images/' . $restaurant->logo) : $fallbackRestaurant);
                                    $restaurantRating = (float) ($restaurant->ratings_avg_rating ?? 0);
                                @endphp
                                <a href="{{ route('restaurant.detail', $restaurant->id) }}" class="search-reco-link">
                                    <img src="{{ $restaurantImage }}" data-image-fallback="{{ $fallbackRestaurant }}" alt="" class="search-reco-thumb" loading="lazy">
                                    <span class="search-reco-copy">
                                        <span class="search-reco-name">{{ $restaurant->name }}</span>
                                        <span class="search-reco-meta">{{ $restaurantRating > 0 ? number_format($restaurantRating, 1) . ' ★' : 'À découvrir' }}</span>
                                    </span>
                                    <i class="fas fa-chevron-right search-reco-arrow" aria-hidden="true"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>

            <main id="searchResults" class="search-results" tabindex="-1">
                <div class="search-results-toolbar">
                    <div>
                        <div class="search-results-kicker">Catalogue BantuDelice</div>
                        <h2 class="search-results-title">
                            @if(!$hasCriteria)
                                Explorez nos meilleures adresses
                            @elseif($resultCount > 0)
                                Vos résultats
                            @else
                                Aucun résultat exact
                            @endif
                        </h2>
                        <p class="search-results-summary">
                            @if(!$hasCriteria)
                                Lancez une recherche ou choisissez une suggestion populaire.
                            @else
                                {{ $resultCount }} résultat{{ $resultCount > 1 ? 's' : '' }} trouvé{{ $resultCount > 1 ? 's' : '' }}
                            @endif
                        </p>
                    </div>
                    <span class="search-result-count-badge" aria-label="{{ $resultCount }} résultats">{{ $resultCount }}</span>
                </div>

                @if($activeFilters->isNotEmpty())
                    <div class="search-active-filters" aria-label="Filtres actifs">
                        @foreach($activeFilters as $activeFilter)
                            <span class="search-active-chip"><i class="fas fa-check" aria-hidden="true"></i>{{ $activeFilter }}</span>
                        @endforeach
                    </div>
                @endif

                @if(!$hasCriteria)
                    <div class="search-empty-state">
                        <div>
                            <div class="search-empty-icon"><i class="fas fa-bowl-food" aria-hidden="true"></i></div>
                            <h3 class="search-empty-title">Une envie particulière ?</h3>
                            <p class="search-empty-copy">Saisissez le nom d’un restaurant, d’un plat ou d’une cuisine. Vous pouvez aussi commencer par l’une de ces recherches populaires.</p>
                            <div class="search-empty-links">
                                @foreach($popularSearches as $popularSearch)
                                    <a href="{{ route('search', ['query' => $popularSearch]) }}" class="search-empty-link">{{ $popularSearch }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @elseif($resultCount === 0)
                    <div class="search-empty-state">
                        <div>
                            <div class="search-empty-icon"><i class="fas fa-magnifying-glass-minus" aria-hidden="true"></i></div>
                            <h3 class="search-empty-title">Aucun résultat pour cette combinaison</h3>
                            <p class="search-empty-copy">Essayez un terme plus court, retirez certains filtres ou parcourez tous les restaurants disponibles.</p>
                            <div class="search-empty-links">
                                <a href="{{ route('search', $resetParameters) }}" class="search-empty-link">Retirer les filtres</a>
                                <a href="{{ route('restaurants.all') }}" class="search-empty-link">Tous les restaurants</a>
                            </div>
                        </div>
                    </div>
                @else
                    @if($restaurants->isNotEmpty())
                        <section class="search-section" aria-labelledby="restaurantResultsTitle">
                            <div class="search-section-head">
                                <h3 id="restaurantResultsTitle" class="search-section-title">Restaurants</h3>
                                <span class="search-section-count">{{ $restaurants->count() }} résultat{{ $restaurants->count() > 1 ? 's' : '' }}</span>
                            </div>
                            <div class="search-results-grid">
                                @foreach($restaurants as $restaurant)
                                    @php
                                        $restaurantImage = method_exists($restaurant, 'publicIdentityImageUrl')
                                            ? $restaurant->publicIdentityImageUrl()
                                            : ($restaurant->logo ? asset('images/restaurant_images/' . $restaurant->logo) : $fallbackRestaurant);
                                        $restaurantRating = (float) ($restaurant->ratings_avg_rating ?? 0);
                                        $restaurantCuisines = collect($restaurant->cuisines ?? [])->pluck('name')->filter()->take(3)->implode(' · ');
                                        $restaurantMeta = collect([$restaurant->address ?? null, $restaurant->city ?? null])->filter()->implode(' · ');
                                    @endphp
                                    <a href="{{ route('restaurant.detail', $restaurant->id) }}"
                                       class="search-result-card"
                                       aria-label="Voir le restaurant {{ $restaurant->name }}">
                                        <span class="search-result-media">
                                            <img src="{{ $restaurantImage }}"
                                                 data-image-fallback="{{ $fallbackRestaurant }}"
                                                 alt="{{ $restaurant->name }}"
                                                 class="search-result-image"
                                                 width="640" height="400" loading="lazy">
                                            <span class="search-result-badge">
                                                <i class="fas fa-star" aria-hidden="true"></i>
                                                {{ $restaurantRating > 0 ? number_format($restaurantRating, 1) : 'Nouveau' }}
                                            </span>
                                            @if(isset($restaurant->distance_km))
                                                <span class="search-result-distance">{{ number_format((float) $restaurant->distance_km, 1, ',', ' ') }} km</span>
                                            @endif
                                        </span>
                                        <span class="search-result-body">
                                            <span class="search-result-title">{{ $restaurant->name }}</span>
                                            <span class="search-result-meta">{{ $restaurantMeta !== '' ? $restaurantMeta : 'Restaurant disponible sur BantuDelice' }}</span>
                                            <span class="search-result-footer">
                                                <span class="search-result-chip">{{ $restaurantCuisines !== '' ? $restaurantCuisines : 'Restaurant' }}</span>
                                                <span class="search-result-price">Découvrir</span>
                                            </span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if($products->isNotEmpty())
                        <section class="search-section" aria-labelledby="productResultsTitle">
                            <div class="search-section-head">
                                <h3 id="productResultsTitle" class="search-section-title">Plats</h3>
                                <span class="search-section-count">{{ $products->count() }} résultat{{ $products->count() > 1 ? 's' : '' }}</span>
                            </div>
                            <div class="search-results-grid">
                                @foreach($products as $product)
                                    @php
                                        $productImage = method_exists($product, 'publicImageUrl')
                                            ? $product->publicImageUrl()
                                            : ($product->image ? asset('images/product_images/' . $product->image) : $fallbackProduct);
                                        $hasDiscount = ($product->discount_price ?? 0) > 0 && $product->discount_price < $product->price;
                                        $displayPrice = $hasDiscount ? $product->discount_price : $product->price;
                                    @endphp
                                    <a href="{{ route('frontend.product.show', ['id' => $product->id, 'slug' => \Illuminate\Support\Str::slug($product->name)]) }}"
                                       class="search-result-card"
                                       aria-label="Voir le plat {{ $product->name }}">
                                        <span class="search-result-media">
                                            <img src="{{ $productImage }}"
                                                 data-image-fallback="{{ $fallbackProduct }}"
                                                 alt="{{ $product->name }}"
                                                 class="search-result-image"
                                                 width="640" height="400" loading="lazy">
                                            @if($hasDiscount)
                                                <span class="search-result-badge search-result-badge--promo"><i class="fas fa-tag" aria-hidden="true"></i>Promo</span>
                                            @endif
                                        </span>
                                        <span class="search-result-body">
                                            <span class="search-result-title">{{ $product->name }}</span>
                                            <span class="search-result-meta">{{ \Illuminate\Support\Str::limit(trim((string) $product->description), 105) ?: 'Un plat à découvrir sur BantuDelice.' }}</span>
                                            <span class="search-result-footer">
                                                <span class="search-result-chip">{{ optional($product->restaurants)->name ?? 'Restaurant partenaire' }}</span>
                                                <span class="search-result-price">
                                                    @if($hasDiscount)<span class="search-result-price-old">{{ number_format((float) $product->price, 0, ',', ' ') }} FCFA</span>@endif
                                                    {{ number_format((float) $displayPrice, 0, ',', ' ') }} FCFA
                                                </span>
                                            </span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endif

                @if($productRecommendations->isNotEmpty())
                    <section class="search-section" aria-labelledby="discoverProductsTitle">
                        <div class="search-section-head">
                            <h3 id="discoverProductsTitle" class="search-section-title">Plats à découvrir</h3>
                            <span class="search-section-count">Sélection BantuDelice</span>
                        </div>
                        <div class="search-results-grid">
                            @foreach($productRecommendations->take(6) as $product)
                                @php
                                    $productImage = method_exists($product, 'publicImageUrl')
                                        ? $product->publicImageUrl()
                                        : ($product->image ? asset('images/product_images/' . $product->image) : $fallbackProduct);
                                    $hasDiscount = ($product->discount_price ?? 0) > 0 && $product->discount_price < $product->price;
                                    $displayPrice = $hasDiscount ? $product->discount_price : $product->price;
                                @endphp
                                <a href="{{ route('frontend.product.show', ['id' => $product->id, 'slug' => \Illuminate\Support\Str::slug($product->name)]) }}"
                                   class="search-result-card"
                                   aria-label="Découvrir le plat {{ $product->name }}">
                                    <span class="search-result-media">
                                        <img src="{{ $productImage }}" data-image-fallback="{{ $fallbackProduct }}" alt="{{ $product->name }}"
                                             class="search-result-image" width="640" height="400" loading="lazy">
                                    </span>
                                    <span class="search-result-body">
                                        <span class="search-result-title">{{ $product->name }}</span>
                                        <span class="search-result-meta">{{ optional($product->restaurants)->name ?? 'Suggestion personnalisée' }}</span>
                                        <span class="search-result-footer">
                                            <span class="search-result-chip">À découvrir</span>
                                            <span class="search-result-price">{{ number_format((float) $displayPrice, 0, ',', ' ') }} FCFA</span>
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </main>
        </div>
    </div>
</section>
@endsection

@section('scripts')
    <script src="{{ asset('frontend/js/pages/search.js') }}" defer></script>
@endsection
