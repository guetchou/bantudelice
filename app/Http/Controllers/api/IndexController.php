<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Cuisine;
use App\Product;
use App\Extra;
use App\Rating;
use App\AddOnTitle;
use App\Restaurant;
use App\Order;
use App\Optional;
use App\Required;
use App\Category;
use App\SearchFilter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use DB;
use Illuminate\Support\Facades\Validator;


if (!defined('BASE_URL_PRODUCT')) define('BASE_URL_PRODUCT',URL::to('/').'/images/product_images/');
if (!defined('BASE_URL_RESTAURANT')) define('BASE_URL_RESTAURANT',URL::to('/').'/images/restaurant_images/');
if (!defined('BASE_URL_CUISINE')) define('BASE_URL_CUISINE',URL::to('/').'/images/cuisine/');

class IndexController extends Controller
{
   protected function normalizeFilterInput($value): array
   {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => is_string($item) ? trim($item) : $item)
                ->filter(fn ($item) => $item !== null && $item !== '')
                ->values()
                ->all();
        }

        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)
                ->map(fn ($item) => is_string($item) ? trim($item) : $item)
                ->filter(fn ($item) => $item !== null && $item !== '')
                ->values()
                ->all();
        }

        return collect(explode(',', str_replace(['[', ']'], '', (string) $value)))
            ->map(fn ($item) => trim($item))
            ->filter(fn ($item) => $item !== '')
            ->values()
            ->all();
   }

   protected function resolveRestaurantImageUrl($value): ?string
   {
        if (empty($value)) {
            return URL::to('/') . '/images/placeholder.png';
        }

        if (preg_match('/^https?:\/\//i', (string) $value)) {
            return $value;
        }

        return URL::to('/') . '/images/restaurant_images/' . ltrim((string) $value, '/');
   }

   protected function formatRestaurantCard($restaurant): array
   {
        $data = $restaurant instanceof Restaurant
            ? $restaurant->getAttributes()
            : (array) $restaurant;

        $restaurantId = $data['id'] ?? null;
        $restaurantName = $data['name'] ?? $data['restuarant_name'] ?? $data['restaurant_name'] ?? null;
        $logo = $data['logo'] ?? null;
        $cover = $data['cover_image'] ?? null;

        if ($restaurant instanceof Restaurant && method_exists($restaurant, 'publicIdentityImageUrl')) {
            $logo = $restaurant->publicIdentityImageUrl();
        }

        if ($restaurant instanceof Restaurant && method_exists($restaurant, 'publicCoverImageUrl')) {
            $cover = $restaurant->publicCoverImageUrl();
        }

        return [
            'id' => $restaurantId,
            'name' => $restaurantName,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'slogan' => $data['slogan'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'distance' => isset($data['distance']) ? round((float) $data['distance'], 2) : null,
            'logo_url' => $this->resolveRestaurantImageUrl($logo),
            'cover_image_url' => $this->resolveRestaurantImageUrl($cover),
        ];
   }

   protected function formatProductCard($product): array
   {
        $image = $product->image ?? null;

        return [
            'id' => $product->id,
            'product_name' => $product->product_name ?? $product->name ?? null,
            'category_id' => $product->category_id ?? null,
            'price' => $product->price ?? null,
            'featured' => (int) ($product->featured ?? 0),
            'restaurant_id' => $product->restaurant_id ?? null,
            'restaurant_name' => $product->resturant_name ?? null,
            'image_url' => !empty($image) ? URL::to('/') . '/images/product_images/' . $image : null,
        ];
   }

   public function index(Request $request,$radius = 1000)
   {
        $latitude=31.4644341;
        $longitude=74.2416731;
        $nearbyRestaurants = Restaurant::selectRaw("id, name, address, latitude, longitude, slogan, logo, city,cover_image,
                     ( 6371 * acos( cos( radians(?) ) *
                       cos( radians( latitude ) )
                       * cos( radians( longitude ) - radians(?)
                       ) + sin( radians(?) ) *
                       sin( radians( latitude ) ) )
                     ) AS distance", [$latitude, $longitude, $latitude])
        
        ->orderBy("distance",'asc')
        ->limit(20)
        ->get();

   	$trendingCuisine=Cuisine::get();
   	$trendingProduct=DB::table('products')
            ->join('restaurants', 'restaurants.id', '=', 'products.restaurant_id')
            ->select('products.id','products.name as product_name','products.category_id','products.price','products.featured','products.image','restaurants.id as restaurant_id', 'restaurants.name as resturant_name')
            ->get();
   	$featuredRestaurant=Restaurant::selectRaw("id, name as restuarant_name, address, latitude, longitude, slogan, logo, city,cover_image,
        ( 6371 * acos( cos( radians(?) ) *
          cos( radians( latitude ) )
          * cos( radians( longitude ) - radians(?)
          ) + sin( radians(?) ) *
          sin( radians( latitude ) ) )
        ) AS distance", [$latitude, $longitude, $latitude])
->where('featured', '=', 1)

->orderBy("distance",'asc')
->limit(20)
->get();

    $nearbyRestaurants = $nearbyRestaurants->map(fn ($restaurant) => $this->formatRestaurantCard($restaurant))->values();
    $featuredRestaurant = $featuredRestaurant->map(fn ($restaurant) => $this->formatRestaurantCard($restaurant))->values();
    $trendingProduct = $trendingProduct->map(fn ($product) => $this->formatProductCard($product))->values();
    $trendingCuisine = $trendingCuisine->map(function ($cuisine) {
        return [
            'id' => $cuisine->id,
            'name' => $cuisine->name,
            'image_url' => !empty($cuisine->image) ? URL::to('/') . '/images/cuisine/' . $cuisine->image : null,
        ];
    })->values();

   return response()->json([
   	'status' =>true,
   	'featuredRestaurants' =>$featuredRestaurant,
   	'trendingCuisine' =>$trendingCuisine,
   	'trendingProduct' =>$trendingProduct,
     'nearbyRestaurants' =>$nearbyRestaurants,
     'BASE_URL_PRODUCT'=>BASE_URL_PRODUCT,
     'BASE_URL_RESTAURANT'=>BASE_URL_RESTAURANT,
     'BASE_URL_CUISINE'=>BASE_URL_CUISINE,
   ]);
   }

   public function searchQurey(Request $request)
   {
        $searchTerm = trim((string) ($request->searchTerm ?? $request->query ?? $request->q ?? $request->qurey ?? ''));

        if ($searchTerm === '') {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => 'Le mot-clé de recherche est requis',
            ], 422);
        }

        $query = DB::table('restaurants')->where('name', 'like', '%' . $searchTerm . '%');

        if (Schema::hasColumn('restaurants', 'keywords')) {
            $query->orWhere('keywords', 'like', '%' . $searchTerm . '%');
        }

        $resultData = $query->get()->map(fn ($restaurant) => $this->formatRestaurantCard($restaurant))->values();

        return response()->json([
            'status' => true,
            'search' => $resultData,
        ]);
   }
   public function restaurantDetail($restaurant)
   {
        $restaurantDetail=Restaurant::find($restaurant);
        $RestaurantCate=Category::with('products')->where('restaurant_id',$restaurant)->get();

        if (!$restaurantDetail) {
            return response()->json([
                'status' => false,
                'message' => 'Restaurant introuvable',
            ], 404);
        }

        $restaurantPayload = [
            'id' => $restaurantDetail->id,
            'name' => $restaurantDetail->name,
            'address' => $restaurantDetail->address,
            'city' => $restaurantDetail->city,
            'phone' => $restaurantDetail->phone,
            'slogan' => $restaurantDetail->slogan,
            'description' => $restaurantDetail->description,
            'latitude' => $restaurantDetail->latitude,
            'longitude' => $restaurantDetail->longitude,
            'logo_url' => $restaurantDetail->publicIdentityImageUrl(),
            'cover_image_url' => $restaurantDetail->publicCoverImageUrl(),
        ];

        $categoryPayload = $RestaurantCate->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'products' => collect($category->products ?? [])->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'discount_price' => $product->discount_price ?? null,
                        'image_url' => !empty($product->image) ? URL::to('/') . '/images/product_images/' . $product->image : null,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
        'status'=> true,
        'data' => $restaurantPayload,
        'categoryWithPro' => $categoryPayload,
        ]);
   }

   public function proDetail($id)
   {
        $proDetail=Product::find($id);

        if (!$proDetail) {
            return response()->json([
                'status' => false,
                'message' => 'Produit introuvable',
                'data' => null,
            ], 404);
        }

        $getAddOn = null;
        $getOptional = null;

        if (Schema::hasTable('optionals')) {
            $getOptional = Optional::where('product_id', $id)->get();
        }

        if (Schema::hasTable('requireds')) {
            $hasRequired = Required::where('product_id', $id)->exists();

            if ($hasRequired) {
                $getAddOn = AddOnTitle::select('id', 'title')->with(['requireds' => function($q) use ($id) {
                    $q->where('product_id', '=', $id);
                }])->get();
            }
        }
       
        return response()->json([
          'status'=> true,
          'data' => [
              'id' => $proDetail->id,
              'name' => $proDetail->name,
              'description' => $proDetail->description,
              'price' => $proDetail->price,
              'discount_price' => $proDetail->discount_price ?? null,
              'restaurant_id' => $proDetail->restaurant_id,
              'category_id' => $proDetail->category_id,
              'image_url' => !empty($proDetail->image) ? URL::to('/') . '/images/product_images/' . $proDetail->image : null,
              'addOns' => $getAddOn,
              'optional' => $getOptional,
          ],
          ]);
   }
    public function searchFilter(Request $request)
    {
            $offersArray = $this->normalizeFilterInput($request->offers);
            $cuisinesArray = $this->normalizeFilterInput($request->cuisines);

            $restaurants = Restaurant::query();
            $hasFilter = false;

            $offerColumnMap = [
                '1' => 'delivery',
                'free delivery' => 'delivery',
                'free_delivery' => 'delivery',
                'delivery' => 'delivery',
                '2' => 'deal',
                'deal' => 'deal',
                '3' => 'vouchers',
                'accept vouchers' => 'vouchers',
                'accept_vouchers' => 'vouchers',
                'vouchers' => 'vouchers',
            ];

            $offerColumns = collect($offersArray)
                ->map(function ($offer) use ($offerColumnMap) {
                    $normalized = strtolower(trim((string) $offer));
                    return $offerColumnMap[$normalized] ?? null;
                })
                ->filter()
                ->unique()
                ->values();

            if ($offerColumns->isNotEmpty()) {
                $restaurants->where(function ($query) use ($offerColumns) {
                    foreach ($offerColumns as $column) {
                        if (Schema::hasColumn('restaurants', $column)) {
                            $query->orWhere($column, 'yes');
                        }
                    }
                });
                $hasFilter = true;
            }

            $cuisineIds = collect($cuisinesArray)
                ->filter(fn ($item) => is_numeric($item))
                ->map(fn ($item) => (int) $item)
                ->values();

            $cuisineNames = collect($cuisinesArray)
                ->filter(fn ($item) => !is_numeric($item))
                ->map(fn ($item) => trim((string) $item))
                ->values();

            if ($cuisineIds->isNotEmpty() || $cuisineNames->isNotEmpty()) {
                $restaurants->whereHas('cuisines', function ($query) use ($cuisineIds, $cuisineNames) {
                    if ($cuisineIds->isNotEmpty()) {
                        $query->whereIn('cuisines.id', $cuisineIds);
                    }

                    if ($cuisineNames->isNotEmpty()) {
                        $method = $cuisineIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $query->{$method}('cuisines.name', $cuisineNames);
                    }
                });
                $hasFilter = true;
            }

            $getFinal = $hasFilter
                ? $restaurants->get()->map(fn ($restaurant) => $this->formatRestaurantCard($restaurant))->values()
                : collect();

            return response()->json([
               'data' => $getFinal
            ]);
            
                  

    }
}
