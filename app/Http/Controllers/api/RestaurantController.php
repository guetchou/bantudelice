<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Restaurant;
use App\Cuisine;
use App\Filter;
use App\Rating;
use App\SearchFilter;
use App\Review;
use App\Services\RestaurantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

if (!defined('BASE_URL_PROFILE')) define('BASE_URL_PROFILE',URL::to('/').'/images/profile_images/');

class RestaurantController extends Controller
{
    protected $restaurantService;
    
    public function __construct()
    {
        $this->restaurantService = new RestaurantService();
    }
    public function search(Request $request)
    {
    	$validator = Validator::make(
            $request->all(),
            array(
                'query'=>'required',
            ));
           
        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('status' => false, 'error_code' => 101, 'message' => $error_messages);
        } 
        else{
              $restaurants = Restaurant::where('name','like','%'.$request->get('query').'%')->get();
              foreach ($restaurants as $restaurant) {
                $restaurant['ratings'] = $restaurant->ratings()->avg('rating');
                  }
                
            }
            return response()->json([
            	'status' => true,
             'data' => $restaurants
            ]);
    }
    public function restaurantsByCuisine($cuisine)
    {
    	$get=Cuisine::find($cuisine);
    	$res=$get->restaurants()->select('restaurant_id','name','city','phone','address','cover_image','logo','slogan','latitude','longitude')->get();
    	return response()->json([
           'status' => true,
           'data' => $res
    	]);
    }
    
    public function sendFilters()
    {
        $filters=Filter::with('searchfilters')->get();
        return response()->json([
         'filter' => $filters
        ]);
    }
    
    public function restaurantAbout($restaurant)
   {
        $restaurantDetail=Restaurant::select('address','name','logo','cover_image','slogan')->with('working_hours')->find($restaurant);
        
        $restaurantDetail['reviews']=DB::table('ratings')
        ->join('users', 'users.id', '=', 'ratings.user_id')
            ->select('users.image', 'users.name', 'ratings.rating', 'ratings.reviews')
            ->where('restaurant_id',$restaurant)->get();
        $avg=DB::table('ratings')->where('restaurant_id',$restaurant)->avg('rating');
                 
        return response()->json([
        'status'=> true,
        'data' =>$restaurantDetail,
        'avg' =>$avg,
        'BASE_URL_PROFILE'=>BASE_URL_PROFILE,
        ]);
   }
   
    /**
     * API Endpoint: GET /api/restaurants/popular
     * Retourne les restaurants populaires avec toutes les données structurées
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function popular(Request $request)
    {
        try {
            // Paramètres de la requête
            $city = $request->get('city', null);
            $limit = $request->get('limit', 8);
            $cuisineId = $request->get('cuisine', null);
            
            // Récupérer les valeurs par défaut depuis la table charges (si existe)
            $defaultCharge = DB::table('charges')->first();
            // La table charges utilise 'delivery_fee' ou 'delivery_charges' selon la migration
            // Récupérer les valeurs par défaut depuis ConfigService (DB)
            $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
            $defaultDeliveryTimeMin = \App\Services\ConfigService::getDefaultDeliveryTimeMin();
            $defaultDeliveryTimeMax = \App\Services\ConfigService::getDefaultDeliveryTimeMax();
            $defaultRating = \App\Services\ConfigService::getDefaultRating();
            
            // Construire la requête
            $query = Restaurant::with(['cuisines', 'ratings'])
                ->where('approved', true);
            
            // Filtre par ville
            if ($city) {
                $query->where('city', 'like', '%' . $city . '%');
            }
            
            // Filtre par cuisine
            if ($cuisineId) {
                $query->whereHas('cuisines', function($q) use ($cuisineId) {
                    $q->where('cuisines.id', $cuisineId);
                });
            }
            
            // Trier par featured puis par rating
            $query->orderBy('featured', 'desc')
                  ->orderBy('created_at', 'desc');
            
            if ($limit) {
                $query->limit($limit);
            }
            
            $restaurants = $query->get();
            
            // Formater les données pour l'API
            $formattedRestaurants = $restaurants->map(function($restaurant) use ($defaultDeliveryFee, $defaultDeliveryTimeMin, $defaultDeliveryTimeMax) {
                // Calculer la note moyenne depuis la DB
                $avgRating = $restaurant->ratings()->avg('rating');
                $ratingCount = $restaurant->ratings()->count();
                
                // Calculer le temps de livraison depuis avg_delivery_time ou depuis les commandes réelles
                $etaMin = $defaultDeliveryTimeMin;
                $etaMax = $defaultDeliveryTimeMax;
                
                if ($restaurant->avg_delivery_time) {
                    $time = \Carbon\Carbon::parse($restaurant->avg_delivery_time);
                    $minutes = $time->hour * 60 + $time->minute;
                    if ($minutes > 0) {
                        $etaMin = max(15, $minutes - 5);
                        $etaMax = $minutes + 5;
                    }
                } else {
                    // Calculer depuis les commandes réelles (temps moyen entre ordered_time et delivered_time)
                    $avgDeliveryMinutes = DB::table('orders')
                        ->where('restaurant_id', $restaurant->id)
                        ->where('status', 'completed')
                        ->whereNotNull('ordered_time')
                        ->whereNotNull('delivered_time')
                        ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, ordered_time, delivered_time)) as avg_minutes')
                        ->value('avg_minutes');
                    
                    if ($avgDeliveryMinutes && $avgDeliveryMinutes > 0) {
                        $etaMin = max(15, round($avgDeliveryMinutes) - 5);
                        $etaMax = round($avgDeliveryMinutes) + 5;
                    }
                }
                
                // Frais de livraison depuis la DB (ou valeur par défaut)
                $deliveryFee = $restaurant->delivery_charges ?? $defaultDeliveryFee;
                
                // Cuisines
                $cuisines = $restaurant->cuisines->pluck('name')->toArray();
                
                // Badge "Top noté" : featured OU rating >= seuil configuré avec au moins X avis
                $topRatedThreshold = \App\Services\ConfigService::getTopRatedThreshold();
                $topRatedMinReviews = \App\Services\ConfigService::getTopRatedMinReviews();
                $isTopRated = ($restaurant->featured ?? false) || ($avgRating >= $topRatedThreshold && $ratingCount >= $topRatedMinReviews);
                
                // URL de l'image
                $thumbnailUrl = $restaurant->logo 
                    ? URL::to('/') . '/images/restaurant_images/' . $restaurant->logo
                    : null;
                
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'slug' => $restaurant->user_name ?? Str::slug($restaurant->name),
                    'avg_rating' => $avgRating ? round($avgRating, 1) : $defaultRating,
                    'rating_count' => $ratingCount,
                    'delivery_fee' => (float)$deliveryFee,
                    'eta_min' => (int)$etaMin,
                    'eta_max' => (int)$etaMax,
                    'eta_display' => $etaMin . '-' . $etaMax . ' min',
                    'cuisines' => $cuisines,
                    'cuisines_display' => implode(' · ', array_slice($cuisines, 0, 3)),
                    'is_top_rated' => $isTopRated,
                    'is_featured' => $restaurant->featured ?? false,
                    'thumbnail_url' => $thumbnailUrl,
                    'city' => $restaurant->city,
                    'address' => $restaurant->address,
                    'min_order' => $restaurant->min_order ?? 0,
                ];
            })->sortByDesc(function($restaurant) {
                // Trier par rating puis par nombre d'avis
                return ($restaurant['avg_rating'] * 100) + ($restaurant['rating_count'] / 10);
            })->values();
            
            return response()->json([
                'status' => true,
                'data' => $formattedRestaurants,
                'count' => $formattedRestaurants->count(),
                'BASE_URL_RESTAURANT' => URL::to('/') . '/images/restaurant_images/',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('RestaurantController@popular Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération des restaurants',
                'error' => $e->getMessage() . ' - Line: ' . $e->getLine() . ' - File: ' . basename($e->getFile())
            ], 500);
        }
    }
    
    /**
     * API Endpoint: GET /api/restaurants
     * Retourne la liste des restaurants avec filtres, tri et pagination
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Récupérer les filtres depuis la requête
            $filters = [
                'city' => $request->query('city'),
                'min_rating' => $request->query('min_rating'),
                'max_delivery_fee' => $request->query('max_delivery_fee'),
                'cuisine' => $request->query('cuisine'), // Peut être un ID ou un tableau d'IDs
                'search' => $request->query('search'),
                'sort' => $request->query('sort', 'popular'),
                'per_page' => $request->query('per_page', 12),
            ];
            
            // Nettoyer les filtres vides
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            // Convertir cuisine en tableau si c'est une chaîne
            if (isset($filters['cuisine']) && is_string($filters['cuisine'])) {
                $filters['cuisine'] = explode(',', $filters['cuisine']);
            }
            
            // Récupérer les restaurants avec pagination
            $paginator = $this->restaurantService->searchRestaurants($filters);
            
            // Formater les données
            $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
            $defaultDeliveryTimeMin = \App\Services\ConfigService::getDefaultDeliveryTimeMin();
            $defaultDeliveryTimeMax = \App\Services\ConfigService::getDefaultDeliveryTimeMax();
            $defaultRating = \App\Services\ConfigService::getDefaultRating();
            $topRatedThreshold = \App\Services\ConfigService::getTopRatedThreshold();
            $topRatedMinReviews = \App\Services\ConfigService::getTopRatedMinReviews();
            
            $data = $paginator->getCollection()->map(function($restaurant) use ($defaultDeliveryFee, $defaultDeliveryTimeMin, $defaultDeliveryTimeMax, $defaultRating, $topRatedThreshold, $topRatedMinReviews) {
                // Calculer le temps de livraison
                $etaMin = $defaultDeliveryTimeMin;
                $etaMax = $defaultDeliveryTimeMax;
                if ($restaurant->avg_delivery_time) {
                    try {
                        $time = \Carbon\Carbon::parse($restaurant->avg_delivery_time);
                        $minutes = $time->hour * 60 + $time->minute;
                        if ($minutes > 0) {
                            $etaMin = max(15, $minutes - 5);
                            $etaMax = $minutes + 5;
                        }
                    } catch (\Exception $e) {
                        // Garder les valeurs par défaut
                    }
                }
                
                // Frais de livraison
                $deliveryFee = $restaurant->delivery_charges ?? $defaultDeliveryFee;
                
                // Cuisines
                $cuisines = $restaurant->cuisines->pluck('name')->toArray();
                
                // Badge "Top noté"
                $isTopRated = ($restaurant->featured ?? false) || ($restaurant->avg_rating >= $topRatedThreshold && $restaurant->rating_count >= $topRatedMinReviews);
                
                // URL de l'image
                $thumbnailUrl = $restaurant->logo 
                    ? URL::to('/') . '/images/restaurant_images/' . $restaurant->logo
                    : null;
                
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'slug' => $restaurant->user_name ?? Str::slug($restaurant->name),
                    'avg_rating' => round($restaurant->avg_rating, 1),
                    'rating_count' => $restaurant->rating_count,
                    'delivery_fee' => (float)$deliveryFee,
                    'eta_min' => (int)$etaMin,
                    'eta_max' => (int)$etaMax,
                    'eta_display' => $etaMin . '-' . $etaMax . ' min',
                    'cuisines' => $cuisines,
                    'cuisines_display' => implode(' · ', array_slice($cuisines, 0, 3)),
                    'is_top_rated' => $isTopRated,
                    'is_featured' => $restaurant->featured ?? false,
                    'thumbnail_url' => $thumbnailUrl,
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
                'BASE_URL_RESTAURANT' => URL::to('/') . '/images/restaurant_images/',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('RestaurantController@index Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération des restaurants',
                'error' => config('app.debug') ? $e->getMessage() . ' - Line: ' . $e->getLine() . ' - File: ' . basename($e->getFile()) : null
            ], 500);
        }
    }
    
    /**
     * API Endpoint: GET /api/restaurants/{id}/reviews
     * Retourne la liste paginée des avis d'un restaurant
     * 
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReviews($id, Request $request)
    {
        try {
            $restaurant = Restaurant::findOrFail($id);
            
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            
            $paginator = \App\Rating::where('restaurant_id', $id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
            
            $data = $paginator->getCollection()->map(function($rating) {
                return [
                    'id' => $rating->id,
                    'user_name' => $rating->user->name ?? 'Anonyme',
                    'user_image' => $rating->user->image ? URL::to('/') . '/images/profile_images/' . $rating->user->image : null,
                    'rating' => (int)$rating->rating,
                    'comment' => $rating->reviews,
                    'created_at' => $rating->created_at->toIso8601String(),
                    'created_at_formatted' => $rating->created_at->format('d/m/Y'),
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
                ],
            ]);
            
        } catch (\Exception $e) {
            \Log::error('RestaurantController@getReviews Error', [
                'restaurant_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération des avis',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * API Endpoint: GET /api/restaurants/{id}/promos
     * Retourne les promos actives d'un restaurant
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivePromos($id)
    {
        try {
            $restaurant = Restaurant::findOrFail($id);
            
            $promos = \App\Voucher::where('restaurant_id', $id)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->orderBy('discount', 'desc')
                ->get();
            
            $data = $promos->map(function($voucher) {
                return [
                    'id' => $voucher->id,
                    'name' => $voucher->name,
                    'discount' => (int)$voucher->discount,
                    'start_date' => $voucher->start_date->format('Y-m-d'),
                    'end_date' => $voucher->end_date->format('Y-m-d'),
                    'end_date_formatted' => $voucher->end_date->format('d/m/Y'),
                ];
            });
            
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('RestaurantController@getActivePromos Error', [
                'restaurant_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération des promos',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
