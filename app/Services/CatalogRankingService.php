<?php

namespace App\Services;

use App\Order;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class CatalogRankingService
{
    public function rankRestaurants(Collection $restaurants, array $context = []): Collection
    {
        $queryText   = $this->normalize((string) ($context['query'] ?? ''));
        $profile     = is_array($context['profile'] ?? null) ? $context['profile'] : [];
        $favoriteIds = array_map('intval', (array) ($context['favorite_restaurant_ids'] ?? ($profile['favorite_restaurant_ids'] ?? [])));
        $historyRestaurantWeights = (array) ($profile['recent_restaurant_weights'] ?? []);
        $historyCuisineWeights    = (array) ($profile['cuisine_affinities'] ?? []);
        $latitude  = isset($context['latitude'])  ? (float) $context['latitude']  : (isset($profile['latitude'])  ? (float) $profile['latitude']  : null);
        $longitude = isset($context['longitude']) ? (float) $context['longitude'] : (isset($profile['longitude']) ? (float) $profile['longitude'] : null);
        $preferredCity    = $this->normalize((string) ($context['city']    ?? ($profile['preferred_city'] ?? '')));
        $daypart          = $this->normalize((string) ($context['daypart'] ?? ($profile['daypart']        ?? '')));
        $priceBandFloor   = isset($profile['price_floor'])   ? (float) $profile['price_floor']   : null;
        $priceBandCeiling = isset($profile['price_ceiling']) ? (float) $profile['price_ceiling'] : null;
        $daypartKeywords  = $this->daypartKeywords($daypart);

        $weighted = $restaurants->map(function ($restaurant) use (
            $queryText, $favoriteIds, $historyRestaurantWeights, $historyCuisineWeights,
            $latitude, $longitude, $preferredCity, $priceBandFloor, $priceBandCeiling, $daypartKeywords
        ) {
            $score   = 0.0;
            $reasons = [];
            $name    = $this->normalize((string) ($restaurant->name    ?? ''));
            $address = $this->normalize((string) ($restaurant->address ?? ''));
            $city    = $this->normalize((string) ($restaurant->city    ?? ''));
            $cuisines    = collect($restaurant->cuisines ?? [])->pluck('name')->map(fn ($n) => $this->normalize((string) $n))->filter()->all();
            $avgRating   = (float) ($restaurant->avg_rating ?? $restaurant->ratings_avg_rating ?? 0);
            $ratingCount = (int)   ($restaurant->ratings_count ?? $restaurant->rating_count ?? 0);
            $deliveryFee = (float) ($restaurant->delivery_charges ?? 0);
            $minOrder    = (float) ($restaurant->min_order ?? 0);

            if (!empty($historyRestaurantWeights[(int) $restaurant->id] ?? null)) {
                $score   += min(5, (float) $historyRestaurantWeights[(int) $restaurant->id] * $this->weight('history_restaurant', 3.2));
                $reasons[] = 'history_restaurant';
            }

            foreach ($cuisines as $cuisine) {
                if (!empty($historyCuisineWeights[$cuisine] ?? null)) {
                    $score   += min(4, (float) $historyCuisineWeights[$cuisine] * $this->weight('history_cuisine', 2.4));
                    $reasons[] = 'history_cuisine';
                    break;
                }
            }

            if ($queryText !== '') {
                if (Str::contains($name, $queryText))    { $score += $this->weight('query_name',    8.0); $reasons[] = 'name'; }
                if (Str::contains($address, $queryText)) { $score += $this->weight('query_address', 2.5); $reasons[] = 'address'; }
                if (Str::contains($city, $queryText))    { $score += $this->weight('query_city',    1.2); $reasons[] = 'city'; }

                foreach ($cuisines as $cuisine) {
                    if (Str::contains($cuisine, $queryText)) {
                        $score += $this->weight('query_cuisine', 4.0);
                        $reasons[] = 'cuisine';
                        break;
                    }
                }

                foreach (collect($restaurant->products ?? []) as $product) {
                    $pName = $this->normalize((string) ($product->name ?? ''));
                    $pDesc = $this->normalize((string) ($product->description ?? ''));
                    if (Str::contains($pName, $queryText) || Str::contains($pDesc, $queryText)) {
                        $score += $this->weight('query_product', 5.0);
                        $reasons[] = 'product';
                        break;
                    }
                }
            }

            if (!empty($restaurant->featured)) {
                $score += $this->weight('featured', 2.5);
                $reasons[] = 'featured';
            }

            if ($preferredCity !== '' && ($city === $preferredCity || Str::contains($city, $preferredCity))) {
                $score += $this->weight('city', 1.0);
                $reasons[] = 'city';
            }

            if ($priceBandFloor !== null && $priceBandCeiling !== null && $minOrder > 0 && $minOrder >= $priceBandFloor && $minOrder <= $priceBandCeiling) {
                $score += $this->weight('price_band', 1.2);
                $reasons[] = 'price_band';
            }

            if ($avgRating > 0) {
                $score += min(5, ($avgRating / 5) * $this->weight('rating', 1.8) * 2);
                $reasons[] = 'rating';
            }

            if ($ratingCount > 0) {
                $score += min(4, log($ratingCount + 1, 10) * $this->weight('rating_count', 1.4) * 2);
                $reasons[] = 'popularity';
            }

            if (in_array((int) $restaurant->id, $favoriteIds, true)) {
                $score += $this->weight('favorite', 1.8);
                $reasons[] = 'favorite';
            }

            if ($latitude !== null && $longitude !== null && !empty($restaurant->latitude) && !empty($restaurant->longitude)) {
                $distance = $this->distanceKm($latitude, $longitude, (float) $restaurant->latitude, (float) $restaurant->longitude);
                $restaurant->distance_km = round($distance, 2);
                $score += max(0, $this->weight('distance', 2.0) - min($distance, 10) / 3);
                $reasons[] = 'distance';
            }

            if (!empty($daypartKeywords) && !empty($restaurant->products)) {
                foreach (collect($restaurant->products) as $product) {
                    $haystack = $this->normalize(implode(' ', array_filter([
                        (string) ($product->name ?? ''),
                        (string) ($product->description ?? ''),
                    ])));
                    if ($this->matchesAnyKeyword($haystack, $daypartKeywords)) {
                        $score += $this->weight('daypart', 1.4);
                        $reasons[] = 'daypart';
                        break;
                    }
                }
            }

            $score += $deliveryFee <= 0
                ? 0.5
                : max(0, $this->weight('delivery_fee', 1.0) - min($deliveryFee, 5000) / 5000);

            $restaurant->search_score  = round($score, 2);
            $restaurant->search_reason = array_values(array_unique($reasons));

            return $restaurant;
        });

        $sort   = strtolower((string) ($context['sort'] ?? 'relevance'));
        $limit  = (int) ($context['limit'] ?? config('commerce.search.restaurant_limit', 12));

        $weighted = match ($sort) {
            'rating'       => $weighted->sortByDesc(fn ($r) => [(float) ($r->avg_rating ?? 0), (int) ($r->ratings_count ?? 0), (float) ($r->search_score ?? 0)]),
            'delivery_fee' => $weighted->sortBy(fn ($r) => [(float) ($r->delivery_charges ?? 0), -(float) ($r->avg_rating ?? 0)]),
            'distance'     => $weighted->sortBy(fn ($r) => [(float) ($r->distance_km ?? PHP_FLOAT_MAX), -(float) ($r->search_score ?? 0)]),
            'featured'     => $weighted->sortByDesc(fn ($r) => [(int) (!empty($r->featured) ? 1 : 0), (float) ($r->search_score ?? 0)]),
            default        => $weighted->sortByDesc(fn ($r) => [(float) ($r->search_score ?? 0), (float) ($r->avg_rating ?? 0), (int) ($r->ratings_count ?? 0)]),
        };

        return $weighted->take($limit)->values();
    }

    public function rankProducts(Collection $products, array $context = []): Collection
    {
        $queryText   = $this->normalize((string) ($context['query'] ?? ''));
        $profile     = is_array($context['profile'] ?? null) ? $context['profile'] : [];
        $favoriteRestaurantIds   = array_map('intval', (array) ($context['favorite_restaurant_ids'] ?? ($profile['favorite_restaurant_ids'] ?? [])));
        $recentRestaurantWeights = (array) ($profile['recent_restaurant_weights'] ?? []);
        $recentProductWeights    = (array) ($profile['recent_product_weights']    ?? []);
        $recentCategoryWeights   = (array) ($profile['recent_category_weights']   ?? []);
        $preferredCity    = $this->normalize((string) ($context['city']    ?? ($profile['preferred_city'] ?? '')));
        $daypart          = $this->normalize((string) ($context['daypart'] ?? ($profile['daypart']        ?? '')));
        $priceBandFloor   = isset($profile['price_floor'])   ? (float) $profile['price_floor']   : null;
        $priceBandCeiling = isset($profile['price_ceiling']) ? (float) $profile['price_ceiling'] : null;
        $daypartKeywords  = $this->daypartKeywords($daypart);

        $weighted = $products->map(function ($product) use (
            $queryText, $favoriteRestaurantIds, $recentRestaurantWeights, $recentProductWeights,
            $recentCategoryWeights, $preferredCity, $priceBandFloor, $priceBandCeiling, $daypartKeywords
        ) {
            if (Schema::hasColumn('products', 'is_available') && isset($product->is_available) && !$product->is_available) {
                return null;
            }

            $score        = 0.0;
            $reasons      = [];
            $name         = $this->normalize((string) ($product->name ?? ''));
            $description  = $this->normalize((string) ($product->description ?? ''));
            $restaurantName = $this->normalize((string) optional($product->restaurants)->name);
            $categoryName   = $this->normalize((string) optional($product->categories)->name);
            $restaurantCity = $this->normalize((string) optional($product->restaurants)->city);
            $price          = (float) ($product->price ?? 0);
            $discountPrice  = (float) ($product->discount_price ?? 0);

            if (!empty($recentProductWeights[(int) $product->id] ?? null)) {
                $score += min(4, (float) $recentProductWeights[(int) $product->id] * $this->weight('product_recent', 1.0));
                $reasons[] = 'history_product';
            }

            if (!empty($product->restaurant_id) && !empty($recentRestaurantWeights[(int) $product->restaurant_id] ?? null)) {
                $score += min(4, (float) $recentRestaurantWeights[(int) $product->restaurant_id] * $this->weight('history_restaurant', 3.2));
                $reasons[] = 'history_restaurant';
            }

            if ($queryText !== '') {
                if (Str::contains($name, $queryText))           { $score += $this->weight('query_name',    8.0); $reasons[] = 'name'; }
                if (Str::contains($description, $queryText))    { $score += $this->weight('query_product', 5.0); $reasons[] = 'description'; }
                if (Str::contains($restaurantName, $queryText)) { $score += 2.0;                                 $reasons[] = 'restaurant'; }
                if (Str::contains($categoryName, $queryText))   { $score += 2.5;                                 $reasons[] = 'category'; }
            }

            if (!empty($product->featured) || !empty($product->is_featured)) {
                $score += $this->weight('product_featured', 2.0);
                $reasons[] = 'featured';
            }

            if ($categoryName !== '' && !empty($recentCategoryWeights[$categoryName] ?? null)) {
                $score += min(3, (float) $recentCategoryWeights[$categoryName] * $this->weight('history_category', 2.0));
                $reasons[] = 'history_category';
            }

            if ($discountPrice > 0 && $discountPrice < $price) {
                $score += $this->weight('product_discount', 1.5);
                $reasons[] = 'discount';
            }

            if (!empty($product->restaurant_id) && in_array((int) $product->restaurant_id, $favoriteRestaurantIds, true)) {
                $score += $this->weight('favorite_restaurant', 4.0) * 0.5;
                $reasons[] = 'favorite_restaurant';
            }

            if (!empty($product->image)) {
                $score += 0.5;
            }

            if ($preferredCity !== '' && ($restaurantCity === $preferredCity || ($restaurantCity !== '' && Str::contains($restaurantCity, $preferredCity)))) {
                $score += $this->weight('city', 1.0);
                $reasons[] = 'city';
            }

            if ($priceBandFloor !== null && $priceBandCeiling !== null && $price > 0 && $price >= $priceBandFloor && $price <= $priceBandCeiling) {
                $score += $this->weight('price_band', 1.2);
                $reasons[] = 'price_band';
            }

            if (!empty($daypartKeywords)) {
                $haystack = $this->normalize(implode(' ', array_filter([
                    (string) ($product->name ?? ''),
                    (string) ($product->description ?? ''),
                    (string) $categoryName,
                ])));
                if ($this->matchesAnyKeyword($haystack, $daypartKeywords)) {
                    $score += $this->weight('daypart', 1.4);
                    $reasons[] = 'daypart';
                }
            }

            $timestamp = $product->updated_at ?? $product->created_at ?? null;
            if ($timestamp) {
                try {
                    $daysAgo     = now()->diffInDays(Carbon::parse($timestamp));
                    $recentBoost = max(0, $this->weight('product_recent', 1.0) - min($daysAgo, 30) / 30);
                    $score      += $recentBoost;
                    if ($recentBoost > 0) {
                        $reasons[] = 'recent';
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            $product->search_score  = round($score, 2);
            $product->search_reason = array_values(array_unique($reasons));

            return $product;
        })->filter();

        $sort  = strtolower((string) ($context['sort'] ?? 'relevance'));
        $limit = (int) ($context['limit'] ?? config('commerce.search.product_limit', 12));

        $weighted = match ($sort) {
            'price_low'  => $weighted->sortBy(fn ($p) => [(float) ($p->discount_price > 0 ? $p->discount_price : $p->price), -(float) ($p->search_score ?? 0)]),
            'price_high' => $weighted->sortByDesc(fn ($p) => [(float) ($p->discount_price > 0 ? $p->discount_price : $p->price), (float) ($p->search_score ?? 0)]),
            'featured'   => $weighted->sortByDesc(fn ($p) => [(int) (!empty($p->featured) || !empty($p->is_featured) ? 1 : 0), (float) ($p->search_score ?? 0)]),
            default      => $weighted->sortByDesc(fn ($p) => [(float) ($p->search_score ?? 0), (float) (($p->discount_price > 0 ? $p->discount_price : $p->price) * -1)]),
        };

        return $weighted->take($limit)->values();
    }

    public function recommendRestaurants(?User $user = null, array $context = [], int $limit = null): Collection
    {
        $limit   = $limit ?? (int) config('commerce.search.restaurant_limit', 12);
        $profile = $this->recommendationProfile($user, $context);

        $restaurants = \App\Restaurant::query()
            ->with([
                'cuisines',
                'products' => fn ($q) => $q->select('id', 'restaurant_id', 'name', 'description'),
            ])
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->limit((int) config('commerce.search.candidate_limit', 50))
            ->get();

        return $this->rankRestaurants($restaurants, array_merge($context, [
            'profile'                => $profile,
            'favorite_restaurant_ids'=> $profile['favorite_restaurant_ids'] ?? [],
            'latitude'               => $context['latitude']  ?? $profile['latitude']        ?? null,
            'longitude'              => $context['longitude'] ?? $profile['longitude']       ?? null,
            'city'                   => $context['city']      ?? $profile['preferred_city']  ?? null,
            'daypart'                => $context['daypart']   ?? $profile['daypart']         ?? null,
            'limit'                  => $limit,
        ]));
    }

    public function recommendProducts(?User $user = null, array $context = [], int $limit = null): Collection
    {
        $limit   = $limit ?? (int) config('commerce.search.product_recommendation_limit', 8);
        $profile = $this->recommendationProfile($user, $context);

        $products = \App\Product::query()
            ->with(['restaurants', 'categories'])
            ->when(Schema::hasColumn('products', 'is_available'), function ($query) {
                $query->where(fn ($q) => $q->whereNull('is_available')->orWhere('is_available', true));
            })
            ->limit((int) config('commerce.search.candidate_limit', 50))
            ->get();

        $ranked = $this->rankProducts($products, array_merge($context, [
            'profile'                => $profile,
            'limit'                  => $limit,
            'favorite_restaurant_ids'=> $profile['favorite_restaurant_ids'] ?? [],
            'city'                   => $context['city']    ?? $profile['preferred_city'] ?? null,
            'daypart'                => $context['daypart'] ?? $profile['daypart']        ?? null,
            'sort'                   => $context['sort']    ?? 'featured',
        ]));

        return $this->diversifyByRestaurant($ranked, (int) config('commerce.recommendations.max_per_restaurant', 2))
            ->take($limit)
            ->values();
    }

    public function recommendationProfile(?User $user = null, array $context = []): array
    {
        $historyLimit = (int) config('commerce.recommendations.history_limit', 24);
        $windowDays   = (int) config('commerce.recommendations.history_window_days', 120);

        $profile = [
            'favorite_restaurant_ids'    => [],
            'recent_restaurant_weights'  => [],
            'recent_product_weights'     => [],
            'recent_category_weights'    => [],
            'cuisine_affinities'         => [],
            'preferred_city'             => $this->normalize((string) ($context['city'] ?? '')),
            'latitude'                   => isset($context['latitude'])  ? (float) $context['latitude']  : null,
            'longitude'                  => isset($context['longitude']) ? (float) $context['longitude'] : null,
            'average_order_value'        => null,
            'price_floor'                => null,
            'price_ceiling'              => null,
            'daypart'                    => $this->currentDaypart(),
            'recent_order_count'         => 0,
        ];

        if ($user && method_exists($user, 'favoriteRestaurants')) {
            try {
                $profile['favorite_restaurant_ids'] = $user->favoriteRestaurants()->pluck('restaurants.id')->map(fn ($id) => (int) $id)->all();
            } catch (\Throwable $e) {
                $profile['favorite_restaurant_ids'] = [];
            }
        }

        $userId = $this->resolveUserId($user);
        if ($userId) {
            $orders = $this->recentUserOrders($userId, $historyLimit, $windowDays);
            $profile['recent_order_count'] = $orders->count();

            $priceSamples   = [];
            $daypartWeights = [];

            foreach ($orders as $index => $order) {
                $recency      = max(0.45, 1.0 - ($index / max(1, $historyLimit)) * 0.85);
                $restaurantId = (int) ($order->restaurant_id ?? 0);
                $productId    = (int) ($order->product_id   ?? 0);
                $orderDate    = $order->ordered_time ?? $order->delivered_time ?? $order->created_at ?? null;
                $daypart      = $this->currentDaypart($orderDate ? Carbon::parse($orderDate) : null);
                $price        = (float) ($order->total ?? $order->sub_total ?? $order->price ?? 0);

                if ($restaurantId > 0) {
                    $profile['recent_restaurant_weights'][$restaurantId] = ($profile['recent_restaurant_weights'][$restaurantId] ?? 0) + $recency;
                }
                if ($productId > 0) {
                    $profile['recent_product_weights'][$productId] = ($profile['recent_product_weights'][$productId] ?? 0) + $recency;
                }
                if ($price > 0) {
                    $priceSamples[] = $price;
                }

                $daypartWeights[$daypart] = ($daypartWeights[$daypart] ?? 0) + $recency;

                if ($order->restaurant && !empty($order->restaurant->cuisines)) {
                    foreach (collect($order->restaurant->cuisines) as $cuisine) {
                        $name = $this->normalize((string) ($cuisine->name ?? ''));
                        if ($name !== '') {
                            $profile['cuisine_affinities'][$name] = ($profile['cuisine_affinities'][$name] ?? 0) + $recency;
                        }
                    }
                }

                if ($order->product && !empty($order->product->categories)) {
                    $cats = $order->product->categories instanceof Collection ? $order->product->categories : collect([$order->product->categories]);
                    foreach ($cats as $category) {
                        $name = $this->normalize((string) ($category->name ?? ''));
                        if ($name !== '') {
                            $profile['recent_category_weights'][$name] = ($profile['recent_category_weights'][$name] ?? 0) + $recency;
                        }
                    }
                }
            }

            if (!empty($daypartWeights)) {
                arsort($daypartWeights);
                $profile['daypart'] = array_key_first($daypartWeights) ?: $profile['daypart'];
            }

            if (!empty($priceSamples)) {
                $average = array_sum($priceSamples) / count($priceSamples);
                $profile['average_order_value'] = round($average, 2);
                $profile['price_floor']         = round(max(0, $average * 0.7), 2);
                $profile['price_ceiling']        = round($average * 1.35, 2);
            }

            if ($profile['preferred_city'] === '' && $user && method_exists($user, 'addresses')) {
                try {
                    $address = $user->addresses()->where('is_default', true)->first() ?: $user->addresses()->latest('id')->first();
                    if ($address) {
                        $profile['preferred_city'] = $this->normalize((string) ($address->city ?? $address->town ?? ''));
                        $profile['latitude']  = $profile['latitude']  ?? (isset($address->latitude)  ? (float) $address->latitude  : null);
                        $profile['longitude'] = $profile['longitude'] ?? (isset($address->longitude) ? (float) $address->longitude : null);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if ($profile['preferred_city'] === '' && !empty($orders)) {
                $topCity = collect($orders)
                    ->pluck('restaurant.city')
                    ->map(fn ($v) => $this->normalize((string) $v))
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->keys()
                    ->first();
                if ($topCity) {
                    $profile['preferred_city'] = (string) $topCity;
                }
            }
        }

        return $profile;
    }

    // -------------------------------------------------------------------------
    // Helpers internes
    // -------------------------------------------------------------------------

    protected function recentUserOrders(int $userId, int $limit, int $windowDays): Collection
    {
        return Order::query()
            ->with(['restaurant.cuisines', 'product.categories'])
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereIn('business_status', ['delivered', 'closed', 'picked_up_by_customer'])
                  ->orWhere('status', 'completed');
            })
            ->when(Schema::hasColumn('orders', 'created_at'), function ($q) use ($windowDays) {
                $q->where('created_at', '>=', now()->subDays(max(7, $windowDays)));
            })
            ->orderByDesc('created_at')
            ->limit(max(1, $limit))
            ->get();
    }

    protected function resolveUserId(?User $user): ?int
    {
        if (!$user) {
            return null;
        }

        return method_exists($user, 'getKey') && $user->getKey()
            ? (int) $user->getKey()
            : (isset($user->id) ? (int) $user->id : null);
    }

    protected function diversifyByRestaurant(Collection $products, int $maxPerRestaurant): Collection
    {
        if ($maxPerRestaurant < 1) {
            return $products->values();
        }

        $counts      = [];
        $diversified = collect();

        foreach ($products as $product) {
            $restaurantId = (int) ($product->restaurant_id ?? optional($product->restaurants)->id ?? 0);
            if ($restaurantId > 0) {
                $counts[$restaurantId] = ($counts[$restaurantId] ?? 0) + 1;
                if ($counts[$restaurantId] > $maxPerRestaurant) {
                    continue;
                }
            }
            $diversified->push($product);
        }

        return $diversified->values();
    }

    protected function currentDaypart(?Carbon $when = null): string
    {
        $hour = ($when ?? now())->hour;

        return match (true) {
            $hour >= 5  && $hour < 11 => 'morning',
            $hour >= 11 && $hour < 15 => 'lunch',
            $hour >= 15 && $hour < 19 => 'afternoon',
            $hour >= 19 && $hour < 23 => 'evening',
            default                    => 'late',
        };
    }

    protected function daypartKeywords(string $daypart): array
    {
        return match ($daypart) {
            'morning'   => ['petit dej', 'dejeuner', 'breakfast', 'cafe', 'the', 'jus', 'beignet', 'omelette', 'pain'],
            'lunch'     => ['plat du jour', 'riz', 'poulet', 'poisson', 'sauce', 'menu', 'grill', 'braise'],
            'afternoon' => ['snack', 'sandwich', 'frites', 'boisson', 'jus', 'beignet', 'burger'],
            'evening'   => ['grill', 'braise', 'brochette', 'pizza', 'burger', 'shawarma', 'plaque'],
            'late'      => ['snack', 'sandwich', 'frites', 'boisson', 'jus'],
            default     => [],
        };
    }

    protected function matchesAnyKeyword(string $haystack, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && Str::contains($haystack, $this->normalize($keyword))) {
                return true;
            }
        }

        return false;
    }

    protected function normalize(string $value): string
    {
        return trim(Str::lower($value));
    }

    protected function weight(string $key, float $default): float
    {
        return (float) data_get(config('commerce.search.weights', []), $key, $default);
    }

    protected function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latFrom     = deg2rad($lat1);
        $lngFrom     = deg2rad($lng1);
        $latTo       = deg2rad($lat2);
        $lngTo       = deg2rad($lng2);
        $latDelta    = $latTo - $latFrom;
        $lngDelta    = $lngTo - $lngFrom;
        $angle       = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
