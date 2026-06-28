<?php

namespace App\Http\Controllers;

use App\Cuisine;
use App\Services\CatalogSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CatalogSearchController extends Controller
{
    private const RESTAURANT_SORTS = [
        'relevance', 'recommended', 'rating', 'delivery_fee', 'distance', 'featured',
    ];

    private const PRODUCT_SORTS = [
        'relevance', 'featured', 'price_low', 'price_high',
    ];

    public function __construct(private CatalogSearchService $catalog)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $input = $this->canonicalInput($request);
        $validator = $this->validator($input);

        if ($validator->fails()) {
            return redirect()
                ->route('search')
                ->withErrors($validator)
                ->withInput($input);
        }

        $input = $validator->validated();
        $results = $this->execute($input);
        $cuisines = Schema::hasTable('cuisines')
            ? Cuisine::query()->orderBy('name')->get()
            : collect();

        return view('frontend.search-v2', array_merge($results, [
            'query' => $input['query'] ?? '',
            'filtersData' => $input,
            'cuisines' => $cuisines,
            'hasCriteria' => $this->hasCriteria($input),
        ]));
    }

    public function ajax(Request $request): JsonResponse
    {
        return $this->api($request);
    }

    public function api(Request $request): JsonResponse
    {
        $input = $this->canonicalInput($request);
        $validator = $this->validator($input);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Paramètres de recherche invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $input = $validator->validated();

        if (! $this->hasCriteria($input)) {
            return response()->json([
                'status' => true,
                'query' => '',
                'filters' => $input,
                'restaurants' => [],
                'products' => [],
                'recommendations' => [],
                'product_recommendations' => [],
                'meta' => ['restaurants_count' => 0, 'products_count' => 0],
            ]);
        }

        $results = $this->execute($input);

        return response()->json([
            'status' => true,
            'query' => $input['query'] ?? '',
            'filters' => $input,
            'restaurants' => $results['restaurants']->map(fn ($restaurant) => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'address' => $restaurant->address,
                'city' => $restaurant->city,
                'logo' => method_exists($restaurant, 'publicIdentityImageUrl')
                    ? $restaurant->publicIdentityImageUrl()
                    : ($restaurant->logo ? asset('images/restaurant_images/' . $restaurant->logo) : null),
                'rating' => round((float) ($restaurant->ratings_avg_rating ?? 0), 1),
                'cuisines' => collect($restaurant->cuisines ?? [])->pluck('name')->values(),
                'distance_km' => isset($restaurant->distance_km) ? (float) $restaurant->distance_km : null,
                'url' => route('restaurant.detail', $restaurant->id),
                'relevance_score' => $restaurant->search_score ?? null,
                'relevance_reason' => $restaurant->search_reason ?? [],
            ])->values(),
            'products' => $results['products']->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'discount_price' => (float) ($product->discount_price ?? 0),
                'image' => method_exists($product, 'publicImageUrl')
                    ? $product->publicImageUrl()
                    : ($product->image ? asset('images/product_images/' . $product->image) : null),
                'restaurant' => optional($product->restaurants)->name,
                'category' => optional($product->categories)->name,
                'url' => route('frontend.product.show', [
                    'id' => $product->id,
                    'slug' => Str::slug($product->name),
                ]),
                'relevance_score' => $product->search_score ?? null,
                'relevance_reason' => $product->search_reason ?? [],
            ])->values(),
            'recommendations' => $results['recommendations']->map(fn ($restaurant) => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'rating' => round((float) ($restaurant->ratings_avg_rating ?? 0), 1),
                'url' => route('restaurant.detail', $restaurant->id),
            ])->values(),
            'product_recommendations' => $results['productRecommendations']->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) (($product->discount_price ?? 0) > 0 ? $product->discount_price : $product->price),
                'url' => route('frontend.product.show', [
                    'id' => $product->id,
                    'slug' => Str::slug($product->name),
                ]),
            ])->values(),
            'meta' => [
                'restaurants_count' => $results['restaurants']->count(),
                'products_count' => $results['products']->count(),
            ],
        ]);
    }

    private function execute(array $input): array
    {
        $query = trim((string) ($input['query'] ?? ''));
        $hasCriteria = $this->hasCriteria($input);
        $profileContext = $this->profileContext($input);
        $profile = $this->catalog->recommendationProfile(auth()->user(), $profileContext);
        $context = array_filter(array_merge($input, [
            'profile' => $profile,
            'latitude' => $input['latitude'] ?? null,
            'longitude' => $input['longitude'] ?? null,
        ]), static fn ($value) => $value !== null && $value !== '');

        $restaurants = $hasCriteria
            ? $this->catalog->searchRestaurants($query, $context, 24)
            : collect();

        $shouldSearchProducts = $query !== ''
            || isset($input['min_price'])
            || isset($input['max_price'])
            || ! empty($input['featured']);

        $productContext = $context;
        $productContext['sort'] = $input['product_sort'] ?? 'relevance';
        $products = $shouldSearchProducts
            ? $this->catalog->searchProducts($query, $productContext, 12)
            : collect();

        $recommendations = $this->approvedRestaurants(
            $this->catalog->recommendRestaurants(auth()->user(), array_merge($profileContext, [
                'profile' => $profile,
                'sort' => 'recommended',
                'limit' => 6,
            ]), 6)
        );

        $productRecommendations = $this->approvedProducts(
            $this->catalog->recommendProducts(auth()->user(), array_merge($profileContext, [
                'profile' => $profile,
                'query' => $query,
                'sort' => 'featured',
                'limit' => 6,
            ]), 6)
        );

        return compact('restaurants', 'products', 'recommendations', 'productRecommendations');
    }

    private function canonicalInput(Request $request): array
    {
        $query = $this->firstFilled($request, ['query', 'q', 'qurey', 'searchTerm']);
        $latitude = $this->firstFilled($request, ['latitude', 'lat']);
        $longitude = $this->firstFilled($request, ['longitude', 'lng', 'lon']);
        $sort = strtolower(trim((string) $request->input('sort', 'relevance')));
        $productSort = strtolower(trim((string) $request->input('product_sort', 'relevance')));

        if (! in_array($sort, self::RESTAURANT_SORTS, true)) {
            $sort = 'relevance';
        }

        if (! in_array($productSort, self::PRODUCT_SORTS, true)) {
            $productSort = 'relevance';
        }

        return array_filter([
            'query' => trim((string) $query),
            'location_label' => trim((string) $this->firstFilled($request, ['location_label', 'location'])),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'city' => trim((string) $request->input('city', '')),
            'min_rating' => $request->input('min_rating'),
            'cuisine_id' => $request->input('cuisine_id'),
            'sort' => $sort,
            'product_sort' => $productSort,
            'max_delivery_fee' => $request->input('max_delivery_fee'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'featured' => $request->boolean('featured') ? 1 : null,
            'daypart' => trim((string) $request->input('daypart', '')),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function validator(array $input)
    {
        return Validator::make($input, [
            'query' => ['nullable', 'string', 'max:120'],
            'location_label' => ['nullable', 'string', 'max:180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'city' => ['nullable', 'string', 'max:120'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'cuisine_id' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'in:' . implode(',', self::RESTAURANT_SORTS)],
            'product_sort' => ['nullable', 'in:' . implode(',', self::PRODUCT_SORTS)],
            'max_delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price', 'max:10000000'],
            'featured' => ['nullable', 'boolean'],
            'daypart' => ['nullable', 'string', 'max:40'],
        ], [
            'query.max' => 'La recherche ne peut pas dépasser 120 caractères.',
            'latitude.between' => 'La latitude est invalide.',
            'longitude.between' => 'La longitude est invalide.',
            'max_price.gte' => 'Le prix maximum doit être supérieur ou égal au prix minimum.',
        ]);
    }

    private function firstFilled(Request $request, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = $request->input($key);
            if ($value !== null && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function hasCriteria(array $input): bool
    {
        foreach (['query', 'latitude', 'longitude', 'city', 'min_rating', 'cuisine_id', 'max_delivery_fee', 'min_price', 'max_price', 'featured'] as $key) {
            if (array_key_exists($key, $input) && $input[$key] !== null && $input[$key] !== '') {
                return true;
            }
        }

        return false;
    }

    private function profileContext(array $input): array
    {
        return array_filter([
            'city' => $input['city'] ?? null,
            'latitude' => $input['latitude'] ?? null,
            'longitude' => $input['longitude'] ?? null,
            'daypart' => $input['daypart'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function approvedRestaurants(Collection $restaurants): Collection
    {
        return $restaurants
            ->filter(fn ($restaurant) => ! isset($restaurant->approved) || (bool) $restaurant->approved)
            ->values();
    }

    private function approvedProducts(Collection $products): Collection
    {
        return $products
            ->filter(function ($product) {
                $restaurant = $product->restaurants ?? null;
                return ! $restaurant || ! isset($restaurant->approved) || (bool) $restaurant->approved;
            })
            ->values();
    }
}
