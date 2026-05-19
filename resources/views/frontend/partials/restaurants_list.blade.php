@if($restaurants->count() > 0)
<div class="restaurants-grid restaurants-results-grid">
    @foreach($restaurants as $restaurant)
        @php
            // Récupérer les valeurs par défaut depuis ConfigService (DB)
            $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
            $defaultDeliveryTimeMin = \App\Services\ConfigService::getDefaultDeliveryTimeMin();
            $defaultDeliveryTimeMax = \App\Services\ConfigService::getDefaultDeliveryTimeMax();
            $defaultRating = \App\Services\ConfigService::getDefaultRating();
            
            // Calculer le temps de livraison depuis DB
            $etaMin = $defaultDeliveryTimeMin;
            $etaMax = $defaultDeliveryTimeMax;
            
            if (isset($restaurant->avg_delivery_time) && $restaurant->avg_delivery_time) {
                try {
                    $time = \Carbon\Carbon::parse($restaurant->avg_delivery_time);
                    $minutes = $time->hour * 60 + $time->minute;
                    if ($minutes > 0) {
                        $etaMin = max(15, $minutes - 5);
                        $etaMax = $minutes + 5;
                    }
                } catch (\Exception $e) {}
            }
            
            $deliveryTime = $etaMin . '-' . $etaMax . ' min';
            
            // Frais de livraison depuis DB
            $deliveryFee = isset($restaurant->delivery_charges) ? $restaurant->delivery_charges : $defaultDeliveryFee;
            $deliveryFeeFormatted = number_format($deliveryFee, 0, ',', ' ') . ' FCFA';
            
            // Cuisines depuis la DB
            $cuisinesList = isset($restaurant->cuisines) ? $restaurant->cuisines->pluck('name')->take(3)->implode(' · ') : 'Cuisine variée';
            if (!$cuisinesList) {
                $cuisinesList = 'Cuisine variée';
            }
            
            // Rating calculé depuis la DB
            $rating = isset($restaurant->avg_rating) ? number_format($restaurant->avg_rating, 1) : number_format($defaultRating, 1);
            
            // Badge Top noté
            $topRatedThreshold = \App\Services\ConfigService::getTopRatedThreshold();
            $topRatedMinReviews = \App\Services\ConfigService::getTopRatedMinReviews();
            $isTopRated = (isset($restaurant->featured) && $restaurant->featured) || 
                         ((isset($restaurant->avg_rating) ? $restaurant->avg_rating : 0) >= $topRatedThreshold && 
                          (isset($restaurant->rating_count) ? $restaurant->rating_count : 0) >= $topRatedMinReviews);
        @endphp
        
        <!-- Carte Restaurant -->
        <article class="restaurant-card restaurants-feed-card" data-href="{{ route('restaurant.detail', $restaurant->id) }}">
            @php
                $restaurantImage = method_exists($restaurant, 'publicIdentityImageUrl')
                    ? $restaurant->publicIdentityImageUrl()
                    : ($restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/home/service-restaurant.jpg'));
            @endphp
            
            <!-- Image Container -->
            <div class="restaurant-card-image restaurants-feed-card__media">
                <img src="{{ $restaurantImage }}" 
                     alt="{{ $restaurant->name }}"
                     class="restaurants-feed-card__image"
                     onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
                
                <!-- Overlay gradient -->
                <div class="restaurants-feed-card__overlay"></div>
                
                <!-- Badge temps -->
                <div class="restaurants-feed-card__eta">{{ $deliveryTime }}</div>
                
                <!-- Badge Top noté -->
                @if($isTopRated)
                <div class="restaurants-feed-card__badge-wrap">
                    <span class="restaurants-feed-card__badge">
                        @if(isset($restaurant->featured) && $restaurant->featured)
                            Top noté
                        @else
                            ⭐ {{ $rating }}
                        @endif
                    </span>
                </div>
                @endif

                @auth
                    <form method="POST" action="{{ route('restaurants.favorite.toggle', $restaurant->id) }}" onclick="event.stopPropagation();" class="restaurants-feed-card__favorite-form">
                        @csrf
                        <button type="submit" aria-label="Ajouter aux favoris" class="restaurants-feed-card__favorite-btn{{ !empty($restaurant->is_favorite) ? ' is-active' : '' }}">
                            <i class="fas fa-heart"></i>
                        </button>
                    </form>
                @endauth
            </div>
            
            <!-- Contenu -->
            <div class="restaurant-card-content restaurants-feed-card__content">
                <div class="restaurants-feed-card__top">
                    <h3 class="restaurant-card-title restaurants-feed-card__title">
                        {{ $restaurant->name }}
                    </h3>
                    <div class="restaurants-feed-card__rating">{{ $rating }}</div>
                </div>
                
                <p class="restaurant-card-cuisine restaurants-feed-card__cuisine">
                    {{ $cuisinesList }}
                </p>
                
                <div class="restaurant-card-meta restaurants-feed-card__meta">
                    <span class="restaurant-card-delivery restaurants-feed-card__delivery">
                        Frais de livraison : <strong>{{ $deliveryFeeFormatted }}</strong>
                    </span>
                </div>
            </div>
        </article>
    @endforeach
</div>
@else
<div class="restaurants-feedback"><p>Aucun restaurant disponible pour le moment.</p></div>
@endif
