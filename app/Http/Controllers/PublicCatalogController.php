<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Category;
use App\Cuisine;
use App\Product;
use App\Restaurant;
use App\Services\CatalogSearchService;
use App\Services\ConfigService;
use App\Services\DataSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pages publiques légères sans écriture DB.
 * - getCartCount  : lecture seule (Cart sum)
 * - restaurantByCuisine : lecture seule (DataSyncService + Cuisine)
 * - colisLanding  : vue statique + config
 * - createShipment : vue statique (auth check)
 */
class PublicCatalogController extends Controller
{
    public function getCartCount(): JsonResponse
    {
        if (auth()->check()) {
            $count = Cart::where('user_id', auth()->user()->id)->sum('qty');
        } else {
            $cart  = session()->get('cart', []);
            $count = array_sum(array_column($cart, 'qty'));
        }

        return response()->json(['count' => $count]);
    }

    public function restaurantByCuisine(int $id): View
    {
        $restaurants = DataSyncService::getRestaurantsByCuisine($id);
        $cuisine     = Cuisine::find($id);

        if (! $cuisine) {
            abort(404, 'Cuisine non trouvée');
        }

        return view('frontend.restaurant_by_cuisines', compact('restaurants', 'cuisine'));
    }

    public function colisLanding(): View
    {
        $homeContent = ConfigService::getHomeContent('mema');

        return view('frontend.colis.landing', compact('homeContent'));
    }

    public function createShipment(): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('user.login');
        }

        return view('frontend.colis.create');
    }

    public function allRestaurants(Request $request)
    {
        $cuisines = DataSyncService::getCuisinesWithRestaurants(null);
        $defaultDeliveryFee = ConfigService::getDefaultDeliveryFee();
        $topRatedThreshold = ConfigService::getTopRatedThreshold();

        if ($request->ajax() || $request->wantsJson()) {
            $restaurantService = new \App\Services\RestaurantService();
            $filters = [
                'city' => $request->get('city'),
                'min_rating' => $request->get('min_rating'),
                'max_delivery_fee' => $request->get('max_delivery_fee'),
                'cuisine' => $request->get('cuisine'),
                'search' => $request->get('search'),
                'sort' => $request->get('sort', 'popular'),
                'per_page' => $request->get('per_page', 12),
            ];
            $filters = array_filter($filters, function ($value) {
                return $value !== null && $value !== '';
            });
            if (isset($filters['cuisine']) && is_string($filters['cuisine'])) {
                $filters['cuisine'] = explode(',', $filters['cuisine']);
            }
            $paginator = $restaurantService->searchRestaurants($filters);
            $defaultDeliveryTimeMin = ConfigService::getDefaultDeliveryTimeMin();
            $defaultDeliveryTimeMax = ConfigService::getDefaultDeliveryTimeMax();
            $defaultRating = ConfigService::getDefaultRating();
            $topRatedMinReviews = ConfigService::getTopRatedMinReviews();
            $data = $paginator->getCollection()->map(function ($restaurant) use ($defaultDeliveryFee, $defaultDeliveryTimeMin, $defaultDeliveryTimeMax, $defaultRating, $topRatedThreshold, $topRatedMinReviews) {
                $etaMin = $defaultDeliveryTimeMin;
                $etaMax = $defaultDeliveryTimeMax;
                if ($restaurant->avg_delivery_time) {
                    try {
                        $time = \Carbon\Carbon::parse($restaurant->avg_delivery_time);
                        $minutes = $time->hour * 60 + $time->minute;
                        if ($minutes > 0) { $etaMin = max(15, $minutes - 5); $etaMax = $minutes + 5; }
                    } catch (\Exception $e) {}
                }
                $deliveryFee = $restaurant->delivery_charges ?? $defaultDeliveryFee;
                $restaurantCuisines = $restaurant->cuisines->pluck('name')->toArray();
                $isTopRated = ($restaurant->featured ?? false) || ($restaurant->avg_rating >= $topRatedThreshold && $restaurant->rating_count >= $topRatedMinReviews);
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'avg_rating' => round($restaurant->avg_rating, 1),
                    'rating_count' => $restaurant->rating_count,
                    'delivery_fee' => (float) $deliveryFee,
                    'eta_min' => (int) $etaMin,
                    'eta_max' => (int) $etaMax,
                    'eta_display' => $etaMin . '-' . $etaMax . ' min',
                    'cuisines' => $restaurantCuisines,
                    'cuisines_display' => implode(' · ', array_slice($restaurantCuisines, 0, 3)),
                    'is_top_rated' => $isTopRated,
                    'is_featured' => $restaurant->featured ?? false,
                    'thumbnail_url' => method_exists($restaurant, 'publicIdentityImageUrl') ? $restaurant->publicIdentityImageUrl() : ($restaurant->logo ? url('images/restaurant_images/' . $restaurant->logo) : null),
                    'city' => $restaurant->city,
                    'address' => $restaurant->address,
                    'min_order' => $restaurant->min_order ?? 0,
                ];
            });
            return response()->json([
                'status' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]);
        }

        $restaurantService = new \App\Services\RestaurantService();
        $searchQuery = trim((string) ($request->get('search', '')));
        $filters = [
            'cuisine' => $request->get('cuisine'),
            'sort' => $request->get('sort', 'popular'),
            'per_page' => 12,
        ];
        if ($searchQuery !== '') { $filters['search'] = $searchQuery; }
        if (isset($filters['cuisine']) && is_string($filters['cuisine'])) {
            $filters['cuisine'] = [(int) $filters['cuisine']];
        }
        $paginator = $restaurantService->searchRestaurants($filters);
        $restaurants = $paginator->getCollection();
        $cuisineId = $request->get('cuisine');

        return view('frontend.restaurants', compact(
            'restaurants', 'cuisines', 'cuisineId', 'paginator',
            'defaultDeliveryFee', 'topRatedThreshold', 'searchQuery'
        ));
    }

    public function resturantDetail($id)
    {
        $restaurant = Restaurant::where('id', $id)
            ->where('approved', true)
            ->with(['cuisines', 'ratings'])
            ->first();
        if (!$restaurant) { abort(404, 'Restaurant non trouvé ou non approuvé'); }
        $isFavorite = auth()->check()
            ? auth()->user()->favoriteRestaurants()->where('restaurants.id', $restaurant->id)->exists()
            : false;
        try { $restaurant->load(['ratings.user', 'cuisines']); } catch (\Exception $e) {}
        try {
            $status = class_exists(\App\Services\RestaurantStatusService::class)
                ? \App\Services\RestaurantStatusService::getStatus($restaurant)
                : ['is_open' => true, 'message' => 'Ouvert'];
        } catch (\Exception $e) { $status = ['is_open' => true, 'message' => 'Ouvert']; }
        try {
            $recentReviews = $restaurant->ratings()->with('user')->orderBy('created_at', 'desc')->limit(10)->get();
        } catch (\Exception $e) { $recentReviews = collect([]); }
        try {
            $activePromos = $restaurant->vouchers()->where('start_date', '<=', now())->where('end_date', '>=', now())->orderBy('discount', 'desc')->get();
        } catch (\Exception $e) { $activePromos = collect([]); }
        $abc = Category::where('restaurant_id', $id)
            ->with(['products' => function ($q) use ($id) {
                $q->where('restaurant_id', $id)->orderBy('sort_order')->orderBy('featured', 'desc')->orderBy('name');
            }])
            ->orderBy('sort_order')->orderBy('name')->get();
        $cuisines = DataSyncService::getCuisinesWithRestaurants();
        $defaultRating = ConfigService::getDefaultRating();
        $defaultDeliveryFee = ConfigService::getDefaultDeliveryFee();
        return view('frontend.menu', compact(
            'restaurant', 'cuisines', 'abc', 'status', 'recentReviews',
            'activePromos', 'defaultRating', 'defaultDeliveryFee', 'isFavorite'
        ));
    }

    public function proDetail($id, $slug = null)
    {
        $proDetail = Product::findOrFail($id);
        $restaurant = Restaurant::findOrFail($proDetail->restaurant_id);
        $products = Product::where('restaurant_id', $proDetail->restaurant_id)
            ->where('id', '!=', $proDetail->id)
            ->orderByDesc('featured')
            ->orderBy('name')
            ->get();
        return view('frontend.product_detail', compact('products', 'proDetail', 'restaurant'));
    }

    public function searchResult(Request $request)
    {
        $qurey = trim((string) $request->get('query', $request->get('qurey', '')));
        if ($qurey === '') {
            return back()->withErrors(['query' => 'Veuillez saisir un terme de recherche.']);
        }
        $filterCallback = static function ($value) { return $value !== null && $value !== ''; };
        $catalog = app(CatalogSearchService::class);
        $recommendationProfile = $catalog->recommendationProfile(auth()->user(), [
            'city' => $request->get('city'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'daypart' => $request->get('daypart'),
        ]);
        $filters = [
            'search' => $qurey,
            'min_rating' => $request->min_rating ?? null,
            'cuisine_id' => $request->cuisine_id ?? null,
            'city' => $request->city ?? null,
            'sort' => $request->get('sort', 'relevance'),
            'max_delivery_fee' => $request->get('max_delivery_fee'),
            'featured' => $request->boolean('featured') ? true : null,
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'product_sort' => $request->get('product_sort', 'relevance'),
        ];
        $restaurants = $catalog->searchRestaurants($qurey, array_filter(array_merge($filters, [
            'profile' => $recommendationProfile,
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'daypart' => $request->get('daypart'),
        ]), $filterCallback), 24);
        $productFilters = array_filter(array_merge($filters, [
            'profile' => $recommendationProfile,
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'daypart' => $request->get('daypart'),
            'sort' => $request->get('product_sort', $request->get('sort', 'relevance')),
        ]), $filterCallback);
        $products = $catalog->searchProducts($qurey, $productFilters, 12);
        $recommendations = $catalog->recommendRestaurants(auth()->user(), ['profile' => $recommendationProfile, 'sort' => 'recommended', 'limit' => 6], 6);
        $productRecommendations = $catalog->recommendProducts(auth()->user(), ['profile' => $recommendationProfile, 'query' => $qurey, 'sort' => 'featured', 'limit' => 6], 6);
        $cuisines = Cuisine::query()->orderBy('name')->get();
        $filtersData = array_merge($filters, ['q' => $qurey]);
        return view('frontend.search', compact('restaurants', 'products', 'qurey', 'filtersData', 'cuisines', 'recommendations', 'productRecommendations'));
    }

    public function searchAjax(Request $request)
    {
        return $this->searchApi($request);
    }

    public function searchApi(Request $request)
    {
        $query = trim((string) $request->get('q', $request->get('query', $request->get('qurey', ''))));
        $filterCallback = static function ($value) { return $value !== null && $value !== ''; };
        if (strlen($query) < 1) {
            return response()->json(['status' => true, 'query' => $query, 'restaurants' => [], 'products' => [], 'filters' => [], 'recommendations' => []]);
        }
        $catalog = app(CatalogSearchService::class);
        $recommendationProfile = $catalog->recommendationProfile(auth()->user(), [
            'city' => $request->get('city'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'daypart' => $request->get('daypart'),
        ]);
        $filters = [
            'search' => $query,
            'min_rating' => $request->get('min_rating'),
            'cuisine_id' => $request->get('cuisine_id'),
            'city' => $request->get('city'),
            'sort' => $request->get('sort', 'relevance'),
            'product_sort' => $request->get('product_sort', 'relevance'),
            'max_delivery_fee' => $request->get('max_delivery_fee'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'featured' => $request->boolean('featured') ? true : null,
        ];
        $restaurants = $catalog->searchRestaurants($query, array_filter(array_merge($filters, [
            'profile' => $recommendationProfile,
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'daypart' => $request->get('daypart'),
        ]), $filterCallback), 12);
        $productFilters = array_filter(array_merge($filters, [
            'profile' => $recommendationProfile,
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'daypart' => $request->get('daypart'),
            'sort' => $request->get('product_sort', $request->get('sort', 'relevance')),
        ]), $filterCallback);
        $products = $catalog->searchProducts($query, $productFilters, 12);
        $recommendations = $catalog->recommendRestaurants(auth()->user(), ['profile' => $recommendationProfile, 'sort' => 'recommended', 'limit' => 6], 6);
        $productRecommendations = $catalog->recommendProducts(auth()->user(), ['profile' => $recommendationProfile, 'query' => $query, 'sort' => 'featured', 'limit' => 6], 6);
        return response()->json([
            'status' => true,
            'query' => $query,
            'filters' => $filters,
            'restaurants' => $restaurants->map(function ($restaurant) {
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'address' => $restaurant->address,
                    'city' => $restaurant->city,
                    'logo' => method_exists($restaurant, 'publicIdentityImageUrl') ? $restaurant->publicIdentityImageUrl() : ($restaurant->logo ? asset('images/restaurant_images/' . $restaurant->logo) : null),
                    'rating' => number_format((float) ($restaurant->ratings_avg_rating ?? ConfigService::getDefaultRating()), 1),
                    'cuisines' => $restaurant->cuisines->pluck('name')->implode(', '),
                    'url' => route('restaurant.detail', $restaurant->id),
                    'relevance_score' => $restaurant->search_score ?? null,
                    'relevance_reason' => $restaurant->search_reason ?? [],
                ];
            })->values(),
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (float) $product->price,
                    'discount_price' => (float) ($product->discount_price ?? 0),
                    'image' => $product->image ? asset('images/product_images/' . $product->image) : null,
                    'restaurant' => optional($product->restaurants)->name,
                    'category' => optional($product->categories)->name,
                    'url' => route('frontend.product.show', ['id' => $product->id, 'slug' => Str::slug($product->name)]),
                    'relevance_score' => $product->search_score ?? null,
                    'relevance_reason' => $product->search_reason ?? [],
                ];
            })->values(),
            'recommendations' => $recommendations->map(function ($restaurant) {
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'logo' => method_exists($restaurant, 'publicIdentityImageUrl') ? $restaurant->publicIdentityImageUrl() : ($restaurant->logo ? asset('images/restaurant_images/' . $restaurant->logo) : null),
                    'rating' => number_format((float) ($restaurant->ratings_avg_rating ?? ConfigService::getDefaultRating()), 1),
                    'score' => $restaurant->search_score ?? null,
                    'reasons' => $restaurant->search_reason ?? [],
                ];
            })->values(),
            'product_recommendations' => $productRecommendations->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (float) ($product->discount_price > 0 ? $product->discount_price : $product->price),
                    'discount_price' => (float) ($product->discount_price ?? 0),
                    'image' => $product->image ? asset('images/product_images/' . $product->image) : null,
                    'restaurant' => optional($product->restaurants)->name,
                    'url' => route('frontend.product.show', ['id' => $product->id, 'slug' => Str::slug($product->name)]),
                    'score' => $product->search_score ?? null,
                    'reasons' => $product->search_reason ?? [],
                ];
            })->values(),
        ]);
    }
}
