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
@endphp

@section('title', ($query !== '' ? 'Recherche : ' . $query : 'Recherche') . ' | ' . trans('ui.site.name'))
@section('description', 'Recherchez des restaurants, plats et cuisines disponibles sur BantuDelice.')
@section('body_class', 'bd-search-page')

@section('content')
<section class="search-hero">
    <div class="container">
        <div class="search-hero-copy">
            <div class="search-hero-badge">Recherche catalogue</div>
            <h1 class="search-hero-title">{{ $query !== '' ? $query : 'Que souhaitez-vous commander ?' }}</h1>
            <p class="search-hero-description">
                Restaurants, plats et cuisines dans un seul moteur de recherche.
                @if($hasLocation) Résultats classés aussi selon votre position. @endif
            </p>
        </div>

        <form method="GET" action="{{ route('search') }}" class="card border-0 shadow-sm mt-4" role="search">
            <div class="card-body p-3 p-md-4">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-9">
                        <label for="catalogSearchQuery" class="visually-hidden">Restaurant, plat ou cuisine</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search" aria-hidden="true"></i></span>
                            <input id="catalogSearchQuery" type="search" name="query" value="{{ $query }}"
                                   class="form-control border-start-0" maxlength="120"
                                   placeholder="Restaurant, plat ou cuisine…" autocomplete="off" autofocus>
                        </div>
                    </div>
                    <div class="col-lg-3 d-grid">
                        <button type="submit" class="btn btn-success btn-lg">Rechercher</button>
                    </div>
                </div>

                @if($hasLocation)
                    <input type="hidden" name="latitude" value="{{ $latitude }}">
                    <input type="hidden" name="longitude" value="{{ $longitude }}">
                    @if($locationLabel !== '')<input type="hidden" name="location_label" value="{{ $locationLabel }}">@endif
                    <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
                        <span class="badge rounded-pill text-bg-light border px-3 py-2">
                            <i class="fas fa-location-dot me-1" aria-hidden="true"></i>
                            {{ $locationLabel !== '' ? $locationLabel : 'Position actuelle' }}
                        </span>
                        <a href="{{ route('search', array_filter(['query' => $query])) }}" class="small">Retirer la position</a>
                    </div>
                @endif
            </div>
        </form>

        @if($errors->any())
            <div class="alert alert-danger mt-3" role="alert">
                <strong>Recherche invalide.</strong>
                <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
    </div>
</section>

<section class="search-page-shell">
    <div class="container">
        <div class="row">
            <aside class="col-lg-3 mb-4">
                <div class="card shadow-sm border-0 search-panel">
                    <div class="card-body">
                        <h2 class="h5 search-panel-title">Affiner</h2>
                        <form method="GET" action="{{ route('search') }}" class="search-filter-form">
                            <input type="hidden" name="query" value="{{ $query }}">
                            @if($hasLocation)
                                <input type="hidden" name="latitude" value="{{ $latitude }}">
                                <input type="hidden" name="longitude" value="{{ $longitude }}">
                                <input type="hidden" name="location_label" value="{{ $locationLabel }}">
                            @endif

                            <div class="form-group mb-3">
                                <label for="searchSort">Tri des restaurants</label>
                                <select id="searchSort" name="sort" class="form-control">
                                    @foreach(['relevance'=>'Pertinence','recommended'=>'Recommandés','rating'=>'Mieux notés','delivery_fee'=>'Frais de livraison','distance'=>'Distance','featured'=>'En vedette'] as $value => $label)
                                        <option value="{{ $value }}" {{ data_get($filtersData, 'sort', 'relevance') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="searchCity">Ville</label>
                                <input id="searchCity" type="text" name="city" maxlength="120" value="{{ data_get($filtersData, 'city') }}" class="form-control" placeholder="Brazzaville">
                            </div>

                            <div class="form-group mb-3">
                                <label for="searchRating">Note minimale</label>
                                <select id="searchRating" name="min_rating" class="form-control">
                                    <option value="">Toutes</option>
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" {{ (string) data_get($filtersData, 'min_rating') === (string) $i ? 'selected' : '' }}>{{ $i }}+</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="searchCuisine">Cuisine</label>
                                <select id="searchCuisine" name="cuisine_id" class="form-control">
                                    <option value="">Toutes</option>
                                    @foreach($cuisines as $cuisine)
                                        <option value="{{ $cuisine->id }}" {{ (string) data_get($filtersData, 'cuisine_id') === (string) $cuisine->id ? 'selected' : '' }}>{{ $cuisine->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="searchDeliveryFee">Frais de livraison maximum</label>
                                <input id="searchDeliveryFee" type="number" name="max_delivery_fee" min="0" max="1000000" step="100"
                                       value="{{ data_get($filtersData, 'max_delivery_fee') }}" class="form-control" placeholder="5000">
                            </div>

                            <hr>

                            <div class="form-group mb-3">
                                <label for="searchProductSort">Tri des plats</label>
                                <select id="searchProductSort" name="product_sort" class="form-control">
                                    @foreach(['relevance'=>'Pertinence','featured'=>'En vedette','price_low'=>'Prix croissant','price_high'=>'Prix décroissant'] as $value => $label)
                                        <option value="{{ $value }}" {{ data_get($filtersData, 'product_sort', 'relevance') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="searchMinPrice">Prix min.</label>
                                    <input id="searchMinPrice" type="number" name="min_price" min="0" max="10000000" step="100" value="{{ data_get($filtersData, 'min_price') }}" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label for="searchMaxPrice">Prix max.</label>
                                    <input id="searchMaxPrice" type="number" name="max_price" min="0" max="10000000" step="100" value="{{ data_get($filtersData, 'max_price') }}" class="form-control">
                                </div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="featuredOnly" name="featured" value="1" {{ data_get($filtersData, 'featured') ? 'checked' : '' }}>
                                <label class="form-check-label" for="featuredOnly">Seulement en vedette</label>
                            </div>

                            <button class="search-filter-submit" type="submit">Appliquer les filtres</button>
                            <a href="{{ route('search', array_filter(['query'=>$query,'latitude'=>$latitude,'longitude'=>$longitude,'location_label'=>$locationLabel])) }}" class="search-filter-reset">Réinitialiser les filtres</a>
                        </form>
                    </div>
                </div>

                @if($recommendations->isNotEmpty())
                    <div class="card shadow-sm border-0 mt-4 search-panel">
                        <div class="card-body">
                            <h2 class="h5 search-panel-title">Suggestions</h2>
                            <div class="d-grid gap-3">
                                @foreach($recommendations as $restaurant)
                                    @php
                                        $restaurantImage = method_exists($restaurant, 'publicIdentityImageUrl')
                                            ? $restaurant->publicIdentityImageUrl()
                                            : ($restaurant->logo ? asset('images/restaurant_images/' . $restaurant->logo) : asset('images/home/service-restaurant.jpg'));
                                    @endphp
                                    <a href="{{ route('restaurant.detail', $restaurant->id) }}" class="search-reco-link">
                                        <img src="{{ $restaurantImage }}" alt="{{ $restaurant->name }}" class="search-reco-thumb">
                                        <div class="search-reco-copy">
                                            <div class="search-reco-name">{{ $restaurant->name }}</div>
                                            <small class="search-reco-meta">{{ number_format((float) ($restaurant->ratings_avg_rating ?? 0), 1) }} ★</small>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </aside>

            <div class="col-lg-9">
                @if(! $hasCriteria)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4 p-md-5 text-center">
                            <i class="fas fa-magnifying-glass fa-2x mb-3 text-success" aria-hidden="true"></i>
                            <h2 class="h4">Commencez votre recherche</h2>
                            <p class="text-muted mb-0">Saisissez le nom d’un restaurant, d’un plat ou d’une cuisine.</p>
                        </div>
                    </div>
                @else
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-4">
                        <div><h2 class="h4 mb-1">Résultats</h2><p class="text-muted mb-0">{{ $resultCount }} résultat{{ $resultCount > 1 ? 's' : '' }}</p></div>
                    </div>

                    <div class="search-section-head"><h3 class="search-section-title">Restaurants</h3><small class="search-section-count">{{ $restaurants->count() }} résultat{{ $restaurants->count() > 1 ? 's' : '' }}</small></div>
                    <div class="row">
                        @forelse($restaurants as $restaurant)
                            @php
                                $restaurantImage = method_exists($restaurant, 'publicIdentityImageUrl') ? $restaurant->publicIdentityImageUrl() : ($restaurant->logo ? asset('images/restaurant_images/' . $restaurant->logo) : asset('images/home/service-restaurant.jpg'));
                            @endphp
                            <div class="col-md-6 col-xl-4 mb-4">
                                <a href="{{ route('restaurant.detail', $restaurant->id) }}" class="search-result-card">
                                    <div class="search-result-media">
                                        <img src="{{ $restaurantImage }}" alt="{{ $restaurant->name }}" class="search-result-image" loading="lazy">
                                        <div class="search-result-score">{{ number_format((float) ($restaurant->ratings_avg_rating ?? 0), 1) }} ★</div>
                                    </div>
                                    <div class="search-result-body">
                                        <h4 class="h5 search-result-title">{{ $restaurant->name }}</h4>
                                        <div class="search-result-meta">{{ collect([$restaurant->address, $restaurant->city])->filter()->implode(' · ') }}</div>
                                        <div class="search-result-foot">
                                            <span class="search-result-chip">{{ collect($restaurant->cuisines ?? [])->pluck('name')->take(3)->implode(' · ') ?: 'Restaurant' }}</span>
                                            @if(isset($restaurant->distance_km))<strong class="search-result-rating">{{ number_format((float) $restaurant->distance_km, 1, ',', ' ') }} km</strong>@endif
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="col-12"><div class="alert alert-light border search-empty-alert">Aucun restaurant ne correspond à ces critères.</div></div>
                        @endforelse
                    </div>

                    <div class="search-section-head search-section-head--spaced"><h3 class="search-section-title">Plats</h3><small class="search-section-count">{{ $products->count() }} résultat{{ $products->count() > 1 ? 's' : '' }}</small></div>
                    <div class="row">
                        @forelse($products as $product)
                            @php
                                $productImage = method_exists($product, 'publicImageUrl') ? $product->publicImageUrl() : ($product->image ? asset('images/product_images/' . $product->image) : asset('images/product_images/default-food.jpg'));
                                $displayPrice = ($product->discount_price ?? 0) > 0 ? $product->discount_price : $product->price;
                            @endphp
                            <div class="col-md-6 col-xl-4 mb-4">
                                <a href="{{ route('frontend.product.show', ['id'=>$product->id,'slug'=>\Illuminate\Support\Str::slug($product->name)]) }}" class="search-result-card">
                                    <div class="search-result-media">
                                        <img src="{{ $productImage }}" alt="{{ $product->name }}" class="search-result-image" loading="lazy">
                                        @if(($product->discount_price ?? 0) > 0 && $product->discount_price < $product->price)<div class="search-result-score search-result-score--promo">Promo</div>@endif
                                    </div>
                                    <div class="search-result-body">
                                        <h4 class="h5 search-result-title">{{ $product->name }}</h4>
                                        <div class="search-result-meta">{{ \Illuminate\Support\Str::limit((string) $product->description, 90) }}</div>
                                        <div class="search-result-foot">
                                            <span class="search-result-chip">{{ optional($product->restaurants)->name ?? 'Restaurant' }}</span>
                                            <strong class="search-result-rating">{{ number_format((float) $displayPrice, 0, ',', ' ') }} FCFA</strong>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="col-12"><div class="alert alert-light border search-empty-alert">Aucun plat ne correspond à ces critères.</div></div>
                        @endforelse
                    </div>
                @endif

                @if($productRecommendations->isNotEmpty())
                    <div class="search-section-head search-section-head--spaced"><h3 class="search-section-title">Plats à découvrir</h3></div>
                    <div class="row">
                        @foreach($productRecommendations as $product)
                            @php
                                $productImage = method_exists($product, 'publicImageUrl') ? $product->publicImageUrl() : ($product->image ? asset('images/product_images/' . $product->image) : asset('images/product_images/default-food.jpg'));
                                $displayPrice = ($product->discount_price ?? 0) > 0 ? $product->discount_price : $product->price;
                            @endphp
                            <div class="col-md-6 col-xl-4 mb-4">
                                <a href="{{ route('frontend.product.show', ['id'=>$product->id,'slug'=>\Illuminate\Support\Str::slug($product->name)]) }}" class="search-result-card">
                                    <div class="search-result-media"><img src="{{ $productImage }}" alt="{{ $product->name }}" class="search-result-image" loading="lazy"></div>
                                    <div class="search-result-body"><h4 class="h5 search-result-title">{{ $product->name }}</h4><div class="search-result-foot"><span class="search-result-chip">{{ optional($product->restaurants)->name ?? 'Suggestion' }}</span><strong class="search-result-rating">{{ number_format((float) $displayPrice, 0, ',', ' ') }} FCFA</strong></div></div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
