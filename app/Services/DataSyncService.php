<?php

namespace App\Services;

use App\Restaurant;
use App\Product;
use App\Cuisine;
use App\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DataSyncService
{
    /**
     * Durée du cache en minutes
     */
    const CACHE_DURATION = 60;
    
    /**
     * Activer les logs pour le debugging
     */
    const ENABLE_LOGGING = true;

    /**
     * Récupérer les restaurants actifs avec leurs relations
     * 
     * @param int|null $limit
     * @param bool $featured
     * @param array $filters Filtres additionnels (cuisine_id, city, min_rating, etc.)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveRestaurants($limit = null, $featured = false, $filters = [])
    {
        $filtersKey = md5(json_encode($filters));
        $cacheKey = 'restaurants_active_' . ($featured ? 'featured' : 'all') . '_' . ($limit ?? 'all') . '_' . $filtersKey;
        
        $startTime = microtime(true);
        
        $result = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($limit, $featured, $filters) {
            $query = Restaurant::with(['cuisines', 'ratings'])
                ->where('approved', true);
            
            if ($featured) {
                $query->where('featured', true);
            }
            
            // Filtres additionnels
            if (isset($filters['cuisine_id'])) {
                $query->whereHas('cuisines', function($q) use ($filters) {
                    $q->where('cuisines.id', $filters['cuisine_id']);
                });
            }
            
            if (isset($filters['city'])) {
                $query->where('city', 'like', '%' . $filters['city'] . '%');
            }
            
            if (isset($filters['min_rating'])) {
                $query->whereHas('ratings', function($q) use ($filters) {
                    $q->selectRaw('AVG(rating) as avg_rating')
                      ->havingRaw('AVG(rating) >= ?', [$filters['min_rating']]);
                });
            }
            
            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('address', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }
            
            $query->orderBy('featured', 'desc')
                  ->orderBy('created_at', 'desc');
            
            if ($limit) {
                $query->limit($limit);
            }
            
            $restaurants = $query->get();
            
            // Calculer les notes moyennes (stocker comme float, pas formaté)
            foreach ($restaurants as $restaurant) {
                $avgRating = $restaurant->ratings()->avg('rating');
                $restaurant->avg_rating = $avgRating ? (float)$avgRating : \App\Services\ConfigService::getDefaultRating();
            }
            
            return $restaurants->sortByDesc('avg_rating')->values();
        });
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        if (self::ENABLE_LOGGING) {
            Log::info('DataSyncService::getActiveRestaurants', [
                'cache_key' => $cacheKey,
                'execution_time_ms' => $executionTime,
                'count' => $result->count(),
                'from_cache' => Cache::has($cacheKey)
            ]);
        }
        
        return $result;
    }

    /**
     * Récupérer un restaurant avec toutes ses données synchronisées
     * 
     * @param int $id
     * @return Restaurant|null
     */
    public static function getRestaurantWithData($id)
    {
        $cacheKey = 'restaurant_full_' . $id;
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($id) {
            $restaurant = Restaurant::with([
                'cuisines',
                'categories.products' => function($query) use ($id) {
                    $query->where('restaurant_id', $id)
                          ->orderBy('featured', 'desc')
                          ->orderBy('name');
                },
                'ratings',
                'working_hours'
            ])
            ->where('approved', true)
            ->find($id);
            
            if ($restaurant) {
                $avgRating = $restaurant->ratings()->avg('rating');
                $restaurant->avg_rating = $avgRating ? (float)$avgRating : \App\Services\ConfigService::getDefaultRating();
            }
            
            return $restaurant;
        });
    }

    /**
     * Récupérer les produits d'un restaurant
     * 
     * @param int $restaurantId
     * @param bool $featured
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRestaurantProducts($restaurantId, $featured = false)
    {
        $cacheKey = 'products_restaurant_' . $restaurantId . '_' . ($featured ? 'featured' : 'all');
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($restaurantId, $featured) {
            $query = Product::with(['categories', 'restaurants'])
                ->where('restaurant_id', $restaurantId);
            
            if ($featured) {
                $query->where('featured', true);
            }
            
            return $query->orderBy('featured', 'desc')
                         ->orderBy('name')
                         ->get();
        });
    }

    /**
     * Récupérer les produits populaires/featured
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getFeaturedProducts($limit = 12)
    {
        $cacheKey = 'products_featured_' . $limit;
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($limit) {
            return Product::with(['restaurants', 'categories'])
                ->whereHas('restaurants', function($query) {
                    $query->where('approved', true);
                })
                ->where('featured', true)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Produits "Plat du jour" (rotation quotidienne)
     * - déterministe par date (seed)
     * - cache jusqu'à la fin de journée
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getDailySpecialProducts($limit = 8)
    {
        $dayKey = now()->format('Ymd');
        $cacheKey = 'products_daily_special_' . $dayKey . '_' . $limit;
        $ttlSeconds = max(60, now()->diffInSeconds(now()->endOfDay()));

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($limit, $dayKey) {
            $q = Product::with(['restaurants', 'categories'])
                ->whereHas('restaurants', function ($rq) {
                    $rq->where('approved', true);
                });

            // Ne proposer que des produits/catégories disponibles si la colonne existe
            if (Schema::hasColumn('products', 'is_available')) {
                $q->where('is_available', true);
            }
            if (Schema::hasColumn('categories', 'is_available')) {
                $q->whereHas('categories', function ($cq) {
                    $cq->where('is_available', true);
                });
            }

            // Priorité: featured, puis rotation quotidienne
            if (Schema::hasColumn('products', 'featured')) {
                $q->orderBy('featured', 'desc');
            }

            $seed = (int)substr($dayKey, -6); // seed stable par date

            // MySQL: RAND(seed) => ordre stable et simple
            if (DB::getDriverName() === 'mysql') {
                $q->orderByRaw('RAND(?)', [$seed]);
                return $q->limit($limit)->get();
            }

            // Fallback: récupérer un set et "shuffle" en PHP de façon déterministe
            $candidates = $q->limit(max($limit * 5, 40))->get();
            if ($candidates->isEmpty()) {
                return $candidates;
            }

            $arr = $candidates->all();
            mt_srand($seed);
            shuffle($arr);
            mt_srand();
            return collect(array_slice($arr, 0, $limit));
        });
    }

    /**
     * Récupérer toutes les cuisines avec leurs restaurants
     * 
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCuisinesWithRestaurants($limit = null)
    {
        $cacheKey = 'cuisines_restaurants_' . ($limit ?? 'all');
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($limit) {
            $query = Cuisine::with(['restaurants' => function($q) {
                $q->where('approved', true)
                  ->orderBy('featured', 'desc');
            }]);
            
            if ($limit) {
                $query->limit($limit);
            }
            
            return $query->orderBy('name')->get();
        });
    }

    /**
     * Récupérer les restaurants par cuisine
     * 
     * @param int $cuisineId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRestaurantsByCuisine($cuisineId)
    {
        $cacheKey = 'restaurants_cuisine_' . $cuisineId;
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($cuisineId) {
            $cuisine = Cuisine::with(['restaurants' => function($query) {
                $query->where('approved', true)
                      ->with('cuisines', 'ratings')
                      ->orderBy('featured', 'desc');
            }])->find($cuisineId);
            
            if ($cuisine) {
                foreach ($cuisine->restaurants as $restaurant) {
                    $avgRating = $restaurant->ratings()->avg('rating');
                    $restaurant->avg_rating = $avgRating ? (float)$avgRating : \App\Services\ConfigService::getDefaultRating();
                }
            }
            
            return $cuisine ? $cuisine->restaurants : collect();
        });
    }

    /**
     * Invalider le cache pour un restaurant
     * 
     * @param int $restaurantId
     */
    public static function invalidateRestaurantCache($restaurantId)
    {
        Cache::forget('restaurant_full_' . $restaurantId);
        Cache::forget('products_restaurant_' . $restaurantId . '_all');
        Cache::forget('products_restaurant_' . $restaurantId . '_featured');
        
        // Invalider les caches généraux
        Cache::forget('restaurants_active_all_all');
        Cache::forget('restaurants_active_featured_all');
        Cache::forget('products_featured_12');
        Cache::forget('cuisines_restaurants_all');
    }

    /**
     * Invalider le cache pour un produit
     * 
     * @param int $productId
     */
    public static function invalidateProductCache($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            self::invalidateRestaurantCache($product->restaurant_id);
        }
        
        Cache::forget('products_featured_12');
    }

    /**
     * Invalider le cache pour une cuisine
     * 
     * @param int $cuisineId
     */
    public static function invalidateCuisineCache($cuisineId)
    {
        Cache::forget('restaurants_cuisine_' . $cuisineId);
        Cache::forget('cuisines_restaurants_all');
    }

    /**
     * Invalider tout le cache
     */
    public static function invalidateAllCache()
    {
        Cache::flush();
        if (self::ENABLE_LOGGING) {
            Log::info('DataSyncService::invalidateAllCache - All cache cleared');
        }
    }
    
    /**
     * Obtenir les statistiques du cache
     * 
     * @return array
     */
    public static function getCacheStats()
    {
        $stats = [
            'total_restaurants' => Restaurant::where('approved', true)->count(),
            'total_products' => Product::count(),
            'total_cuisines' => Cuisine::count(),
            'featured_restaurants' => Restaurant::where('approved', true)->where('featured', true)->count(),
            'featured_products' => Product::where('featured', true)->count(),
        ];
        
        return $stats;
    }
    
    /**
     * Recherche avancée de restaurants
     * 
     * @param string $query
     * @param array $filters
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function searchRestaurants($query, $filters = [], $limit = null)
    {
        $cacheKey = 'search_restaurants_' . md5($query . json_encode($filters) . ($limit ?? 'all'));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION / 2, function () use ($query, $filters, $limit) {
            $searchQuery = Restaurant::with(['cuisines', 'ratings'])
                ->where('approved', true)
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('address', 'like', '%' . $query . '%')
                      ->orWhere('description', 'like', '%' . $query . '%')
                      ->orWhere('city', 'like', '%' . $query . '%')
                      ->orWhereHas('cuisines', function($cq) use ($query) {
                          $cq->where('name', 'like', '%' . $query . '%');
                      });
                });
            
            // Appliquer les filtres
            if (isset($filters['cuisine_id'])) {
                $searchQuery->whereHas('cuisines', function($q) use ($filters) {
                    $q->where('cuisines.id', $filters['cuisine_id']);
                });
            }
            
            if (isset($filters['min_rating'])) {
                $searchQuery->whereHas('ratings', function($q) use ($filters) {
                    $q->selectRaw('AVG(rating) as avg_rating')
                      ->havingRaw('AVG(rating) >= ?', [$filters['min_rating']]);
                });
            }
            
            if (isset($filters['max_delivery_time'])) {
                $searchQuery->where('avg_delivery_time', '<=', $filters['max_delivery_time']);
            }
            
            if (isset($filters['featured'])) {
                $searchQuery->where('featured', $filters['featured']);
            }
            
            $searchQuery->orderBy('featured', 'desc')
                       ->orderBy('created_at', 'desc');
            
            if ($limit) {
                $searchQuery->limit($limit);
            }
            
            $restaurants = $searchQuery->get();
            
            // Calculer les notes moyennes (stocker comme float, pas formaté)
            foreach ($restaurants as $restaurant) {
                $avgRating = $restaurant->ratings()->avg('rating');
                $restaurant->avg_rating = $avgRating ? (float)$avgRating : \App\Services\ConfigService::getDefaultRating();
            }
            
            return $restaurants->sortByDesc('avg_rating')->values();
        });
    }
    
    /**
     * Obtenir les restaurants recommandés pour un utilisateur
     * Basé sur l'historique de commandes, les préférences, etc.
     * 
     * @param int|null $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecommendedRestaurants($userId = null, $limit = 8)
    {
        $cacheKey = 'recommended_restaurants_' . ($userId ?? 'guest') . '_' . $limit;
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($userId, $limit) {
            $query = Restaurant::with(['cuisines', 'ratings'])
                ->where('approved', true)
                ->where('featured', true);
            
            // Si l'utilisateur est connecté, personnaliser les recommandations
            if ($userId) {
                // Logique de recommandation basée sur l'historique
                // Pour l'instant, on retourne les restaurants les mieux notés
            }
            
            $query->orderBy('featured', 'desc')
                  ->orderBy('created_at', 'desc')
                  ->limit($limit);
            
            $restaurants = $query->get();
            
            // Calculer les notes moyennes (stocker comme float, pas formaté)
            foreach ($restaurants as $restaurant) {
                $avgRating = $restaurant->ratings()->avg('rating');
                $restaurant->avg_rating = $avgRating ? (float)$avgRating : \App\Services\ConfigService::getDefaultRating();
            }
            
            return $restaurants->sortByDesc('avg_rating')->values();
        });
    }
    
    /**
     * Précharger les données les plus utilisées dans le cache
     * Utile pour améliorer les performances au démarrage
     */
    public static function warmupCache()
    {
        if (self::ENABLE_LOGGING) {
            Log::info('DataSyncService::warmupCache - Starting cache warmup');
        }
        
        // Précharger les restaurants actifs
        self::getActiveRestaurants(20, false);
        self::getActiveRestaurants(12, true);
        
        // Précharger les produits en vedette
        self::getFeaturedProducts(20);
        
        // Précharger les cuisines
        self::getCuisinesWithRestaurants(20);
        
        if (self::ENABLE_LOGGING) {
            Log::info('DataSyncService::warmupCache - Cache warmup completed');
        }
    }
}

