@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('description', 'Recherchez des restaurants, des plats et des menus disponibles pour la livraison ' . $foodBrandName . ' près de chez vous.')

@section('title', trans('ui.search.title') . ' | ' . trans('ui.site.name'))
@section('body_class', 'bd-search-page')

@section('content')
@php
    $filtersData = $filtersData ?? [];
    $restaurants = collect($restaurants ?? []);
    $products = collect($products ?? []);
    $recommendations = collect($recommendations ?? []);
    $productRecommendations = collect($productRecommendations ?? []);
    $searchUi = trans('ui.search');
    $commonUi = trans('ui.common');
    $recommendationLabels = [
        'favorite_restaurant' => 'Favori',
        'history_restaurant' => 'Commandé souvent',
        'history_cuisine' => 'Cuisine aimée',
        'history_category' => 'Catégorie aimée',
        'city' => 'Dans votre zone',
        'distance' => 'Proche de vous',
        'price_band' => 'Budget adapté',
        'daypart' => 'Adapté au moment',
        'featured' => 'En vedette',
        'discount' => 'Promo',
        'recent' => 'Récent',
        'name' => 'Correspondance forte',
        'restaurant' => 'Restaurant lié',
        'category' => 'Catégorie liée',
        'query' => 'Votre requête',
    ];
@endphp

<section class="search-hero">
    <div class="container">
        <div class="search-hero-copy">
            <div class="search-hero-badge">{{ data_get($searchUi, 'advanced', 'Recherche avancée') }}</div>
            <h1 class="search-hero-title">{{ $qurey }}</h1>
            <p class="search-hero-description">Restaurants, plats et recommandations triés par pertinence, proximité et popularité.</p>
        </div>
    </div>
</section>

<section class="search-page-shell">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="card shadow-sm border-0 search-panel">
                    <div class="card-body">
                        <h5 class="search-panel-title">{{ data_get($searchUi, 'filters', 'Filtres') }}</h5>
                        <form method="GET" action="{{ route('search') }}" class="search-filter-form">
                            <input type="hidden" name="query" value="{{ $qurey }}">
                            <div class="form-group">
                                <label>{{ data_get($searchUi, 'sort', 'Tri') }}</label>
                                <select name="sort" class="form-control">
                                    @foreach([
                                        'relevance' => 'Pertinence',
                                        'recommended' => 'Recommandés',
                                        'rating' => 'Mieux notés',
                                        'delivery_fee' => 'Frais de livraison',
                                        'distance' => 'Distance',
                                        'featured' => 'En vedette',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" {{ data_get($filtersData, 'sort', 'relevance') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ data_get($searchUi, 'city', 'Ville') }}</label>
                                <input type="text" name="city" value="{{ data_get($filtersData, 'city') }}" class="form-control" placeholder="Brazzaville">
                            </div>
                            <div class="form-group">
                                <label>{{ data_get($searchUi, 'min_rating', 'Note minimale') }}</label>
                                <select name="min_rating" class="form-control">
                                    <option value="">Toutes</option>
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" {{ (string) data_get($filtersData, 'min_rating') === (string) $i ? 'selected' : '' }}>{{ $i }}+</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ data_get($searchUi, 'product_sort', 'Tri des plats') }}</label>
                                <select name="product_sort" class="form-control">
                                    @foreach([
                                        'relevance' => 'Pertinence',
                                        'featured' => 'En vedette',
                                        'price_low' => 'Prix croissant',
                                        'price_high' => 'Prix décroissant',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" {{ data_get($filtersData, 'product_sort', 'relevance') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ data_get($searchUi, 'max_delivery_fee', 'Frais livraison max') }}</label>
                                <input type="number" name="max_delivery_fee" min="0" step="1" value="{{ data_get($filtersData, 'max_delivery_fee') }}" class="form-control" placeholder="5000">
                            </div>
                            <div class="form-group">
                                <label>{{ data_get($searchUi, 'cuisine', 'Cuisine') }}</label>
                                <select name="cuisine_id" class="form-control">
                                    <option value="">Toutes</option>
                                    @foreach($cuisines as $cuisine)
                                        <option value="{{ $cuisine->id }}" {{ (string) data_get($filtersData, 'cuisine_id') === (string) $cuisine->id ? 'selected' : '' }}>
                                            {{ $cuisine->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ data_get($searchUi, 'min_price', 'Prix min') }}</label>
                                <input type="number" name="min_price" min="0" step="1" value="{{ data_get($filtersData, 'min_price') }}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>{{ data_get($searchUi, 'max_price', 'Prix max') }}</label>
                                <input type="number" name="max_price" min="0" step="1" value="{{ data_get($filtersData, 'max_price') }}" class="form-control">
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="featuredOnly" name="featured" value="1" {{ data_get($filtersData, 'featured') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="featuredOnly">{{ data_get($searchUi, 'featured_only', 'Seulement en vedette') }}</label>
                                </div>
                            </div>
                            <button class="search-filter-submit" type="submit">{{ data_get($searchUi, 'apply', 'Appliquer') }}</button>
                            <a href="{{ route('search', ['query' => $qurey]) }}" class="search-filter-reset">{{ data_get($searchUi, 'reset', 'Réinitialiser les filtres') }}</a>
                        </form>
                    </div>
                </div>

                @if($recommendations->isNotEmpty())
                    <div class="card shadow-sm border-0 mt-4 search-panel">
                        <div class="card-body">
                            <h5 class="search-panel-title">{{ $commonUi['for_you'] ?? 'Pour vous' }}</h5>
                            <div class="d-grid gap-3">
                                @foreach($recommendations as $restaurant)
                                    @php
                                        $restaurantImage = method_exists($restaurant, 'publicIdentityImageUrl')
                                            ? $restaurant->publicIdentityImageUrl()
                                            : ($restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/home/service-restaurant.jpg'));
                                    @endphp
                                    <a href="{{ route('restaurant.detail', $restaurant->id) }}" class="search-reco-link">
                                        <img src="{{ $restaurantImage }}" alt="{{ $restaurant->name }}" class="search-reco-thumb">
                                        <div class="search-reco-copy">
                                            <div class="search-reco-name">{{ $restaurant->name }}</div>
                                            <small class="search-reco-meta">{{ number_format((float) ($restaurant->ratings_avg_rating ?? 0), 1) }} ★</small>
                                            @if(!empty($restaurant->search_reason))
                                                <div class="search-reco-badges">
                                                    @foreach(array_slice($restaurant->search_reason, 0, 3) as $reason)
                                                        <span class="search-reco-badge">{{ $recommendationLabels[$reason] ?? $reason }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if($productRecommendations->isNotEmpty())
                    <div class="card shadow-sm border-0 mt-4 search-panel">
                        <div class="card-body">
                            <h5 class="search-panel-title">{{ $commonUi['products_for_you'] ?? 'Plats pour vous' }}</h5>
                            <div class="d-grid gap-3">
                                @foreach($productRecommendations as $product)
                                    @php
                                        $productImage = method_exists($product, 'publicImageUrl')
                                            ? $product->publicImageUrl()
                                            : ($product->image ? asset('images/product_images/' . $product->image) : asset('images/product_images/default-food.jpg'));
                                    @endphp
                                    <a href="{{ route('pro.detail', $product->id) }}" class="search-reco-link">
                                        <img src="{{ $productImage }}" alt="{{ $product->name }}" class="search-reco-thumb">
                                        <div class="search-reco-copy">
                                            <div class="search-reco-name">{{ $product->name }}</div>
                                            <small class="search-reco-meta">{{ number_format((float) ($product->price ?? 0), 0, ',', ' ') }} FCFA</small>
                                            @if(!empty($product->search_reason))
                                                <div class="search-reco-badges">
                                                    @foreach(array_slice($product->search_reason, 0, 3) as $reason)
                                                        <span class="search-reco-badge">{{ $recommendationLabels[$reason] ?? $reason }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-9">
                <div class="search-section-head">
                    <h3 class="search-section-title">{{ data_get($searchUi, 'restaurants', 'Restaurants') }}</h3>
                    <small class="search-section-count">{{ $restaurants->count() }} {{ $commonUi['results'] ?? 'résultats' }}</small>
                </div>

                <div class="row">
                    @forelse($restaurants as $restaurant)
                        @php
                            $restaurantImage = method_exists($restaurant, 'publicIdentityImageUrl')
                                ? $restaurant->publicIdentityImageUrl()
                                : ($restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/home/service-restaurant.jpg'));
                        @endphp
                        <div class="col-md-6 col-xl-4 mb-4">
                            <a href="{{ route('restaurant.detail', $restaurant->id) }}" class="search-result-card">
                                <div class="search-result-media">
                                    <img src="{{ $restaurantImage }}" alt="{{ $restaurant->name }}" class="search-result-image">
                                    <div class="search-result-score">{{ number_format((float) ($restaurant->ratings_avg_rating ?? 0), 1) }} ★</div>
                                </div>
                                <div class="search-result-body">
                                    <h5 class="search-result-title">{{ $restaurant->name }}</h5>
                                    <div class="search-result-meta">{{ $restaurant->address }} · {{ $restaurant->city }}</div>
                                    <div class="search-result-foot">
                                        <span class="search-result-chip">{{ $restaurant->cuisines->pluck('name')->implode(' · ') }}</span>
                                        <strong class="search-result-rating">{{ number_format((float) ($restaurant->ratings_avg_rating ?? 0), 1) }} ★</strong>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-light border search-empty-alert">Aucun restaurant ne correspond à votre recherche.</div>
                        </div>
                    @endforelse
                </div>

                <div class="search-section-head search-section-head--spaced">
                    <h3 class="search-section-title">{{ data_get($searchUi, 'products', 'Plats') }}</h3>
                    <small class="search-section-count">{{ $products->count() }} {{ $commonUi['results'] ?? 'résultats' }}</small>
                </div>

                <div class="row">
                    @forelse($products as $product)
                        @php
                            $productImage = method_exists($product, 'publicImageUrl')
                                ? $product->publicImageUrl()
                                : ($product->image ? asset('images/product_images/' . $product->image) : asset('images/product_images/default-food.jpg'));
                        @endphp
                        <div class="col-md-6 col-xl-4 mb-4">
                            <a href="{{ route('pro.detail', $product->id) }}" class="search-result-card">
                                <div class="search-result-media">
                                    <img src="{{ $productImage }}" alt="{{ $product->name }}" class="search-result-image">
                                    @if(($product->discount_price ?? 0) > 0 && $product->discount_price < $product->price)
                                        <div class="search-result-score search-result-score--promo">Promo</div>
                                    @endif
                                </div>
                                <div class="search-result-body">
                                    <h5 class="search-result-title">{{ $product->name }}</h5>
                                    <div class="search-result-meta">{{ $product->description }}</div>
                                    <div class="search-result-foot">
                                        <div class="search-result-price-group">
                                            <strong class="search-result-price">{{ number_format((float) ($product->discount_price > 0 ? $product->discount_price : $product->price), 0, ',', ' ') }} FCFA</strong>
                                            @if(($product->discount_price ?? 0) > 0 && $product->discount_price < $product->price)
                                                <div class="search-result-price-old"><s>{{ number_format((float) $product->price, 0, ',', ' ') }} FCFA</s></div>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <span class="search-result-chip">{{ optional($product->restaurants)->name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-light border search-empty-alert">Aucun plat trouvé.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
