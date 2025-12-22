@if($restaurants->count() > 0)
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
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
        <article class="restaurant-card" style="position: relative; border-radius: 16px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;"
                onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'"
                onclick="window.location.href='{{ route('resturant.detail', $restaurant->id) }}'">
            
            <!-- Image Container -->
            <div style="position: relative; height: 160px; overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                @if(isset($restaurant->logo) && $restaurant->logo)
                <img src="{{ strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo) }}" 
                     alt="{{ $restaurant->name }}"
                     style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                     onerror="this.src='{{ asset('images/placeholder.png') }}'"
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform='scale(1)'">
                @else
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 2rem;">
                    {{ substr($restaurant->name, 0, 2) }}
                </div>
                @endif
                
                <!-- Overlay gradient -->
                <div style="pointer-events: none; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.05) 50%, transparent 100%);"></div>
                
                <!-- Badge temps -->
                <div style="position: absolute; bottom: 8px; left: 8px; display: flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); padding: 4px 10px; border-radius: 500px; font-size: 0.75rem; font-weight: 500; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                    <i class="fas fa-clock" style="font-size: 0.625rem; color: #6B6B6B;"></i>
                    <span>{{ $deliveryTime }}</span>
                </div>
                
                <!-- Badge Top noté -->
                @if($isTopRated)
                <div style="position: absolute; top: 8px; left: 8px;">
                    <span style="background: #05944F; color: white; padding: 4px 10px; border-radius: 500px; font-size: 0.6875rem; font-weight: 600; box-shadow: 0 2px 8px rgba(5,148,79,0.3);">
                        @if(isset($restaurant->featured) && $restaurant->featured)
                            Top noté
                        @else
                            ⭐ {{ $rating }}
                        @endif
                    </span>
                </div>
                @endif
            </div>
            
            <!-- Contenu -->
            <div style="padding: 14px 16px 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin: 0; color: #191919; line-height: 1.3; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $restaurant->name }}
                    </h3>
                    <div style="display: flex; align-items: center; gap: 3px; background: #F6F6F6; padding: 4px 8px; border-radius: 500px; font-size: 0.8125rem; font-weight: 600; flex-shrink: 0;">
                        <span style="color: #191919;">{{ $rating }}</span>
                        <i class="fas fa-star" style="color: #FFB800; font-size: 0.625rem;"></i>
                    </div>
                </div>
                
                <p style="color: #6B6B6B; font-size: 0.8125rem; margin: 0 0 8px 0; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ $cuisinesList }}
                </p>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid #F0F0F0;">
                    <span style="color: #6B6B6B; font-size: 0.8125rem;">
                        Frais de livraison : <strong style="color: #191919;">{{ $deliveryFeeFormatted }}</strong>
                    </span>
                </div>
            </div>
        </article>
    @endforeach
</div>
@else
<div style="text-align: center; padding: 60px 20px; color: #6B6B6B;">
    <i class="fas fa-utensils" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
    <p style="font-size: 1rem; margin: 0;">Aucun restaurant disponible pour le moment</p>
</div>
@endif

