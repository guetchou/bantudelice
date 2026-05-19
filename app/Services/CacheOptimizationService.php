<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service d'optimisation du cache
 * 
 * Stratégies :
 * - Tags pour invalidation groupée
 * - TTL adaptatifs selon la donnée
 * - Préchargement des données fréquentes
 */
class CacheOptimizationService
{
    /**
     * Invalider tous les caches liés à un restaurant
     * 
     * @param int $restaurantId
     * @return void
     */
    public static function invalidateRestaurantCache(int $restaurantId): void
    {
        // Invalider les caches spécifiques au restaurant
        Cache::forget("restaurant_{$restaurantId}_data");
        Cache::forget("restaurant_{$restaurantId}_menu");
        Cache::forget("restaurant_{$restaurantId}_products");
        
        // Invalider les caches globaux qui incluent ce restaurant
        Cache::forget('active_restaurants');
        Cache::forget('featured_restaurants');
        
        // Invalider le cache des cuisines (peut inclure ce restaurant)
        DataSyncService::invalidateCuisineCache();
    }

    /**
     * Invalider tous les caches liés à un produit
     * 
     * @param int $productId
     * @return void
     */
    public static function invalidateProductCache(int $productId): void
    {
        $product = \App\Product::find($productId);
        if ($product) {
            Cache::forget("product_{$productId}_data");
            Cache::forget("restaurant_{$product->restaurant_id}_products");
            Cache::forget('featured_products');
            Cache::forget('daily_special_products');
        }
    }

    /**
     * Précharger les données fréquemment utilisées
     * 
     * @return void
     */
    public static function preloadFrequentData(): void
    {
        // Précharger les restaurants actifs
        DataSyncService::getActiveRestaurants(20, false);
        
        // Précharger les produits en vedette
        DataSyncService::getFeaturedProducts(20);
        
        // Précharger les cuisines
        DataSyncService::getCuisinesWithRestaurants(20);
        
        // Précharger les métriques temps réel
        $metricsService = new MetricsService();
        $metricsService->getRealtimeMetrics();
    }

    /**
     * Nettoyer les caches expirés (job cron)
     * 
     * @return array Statistiques de nettoyage
     */
    public static function cleanupExpiredCache(): array
    {
        // Laravel gère automatiquement l'expiration, mais on peut forcer un cleanup
        // si on utilise un driver qui ne le fait pas automatiquement
        
        $stats = [
            'cleared' => 0,
            'kept' => 0,
        ];
        
        // Si on utilise Redis, on peut nettoyer manuellement les clés expirées
        if (config('cache.default') === 'redis') {
            // Redis gère automatiquement l'expiration, pas besoin de nettoyage manuel
            $stats['note'] = 'Redis gère automatiquement l\'expiration';
        }
        
        return $stats;
    }

    /**
     * Obtenir ou mettre en cache avec TTL adaptatif
     * 
     * @param string $key
     * @param callable $callback
     * @param int|null $ttlSeconds TTL en secondes (null = TTL par défaut selon le type)
     * @return mixed
     */
    public static function remember(string $key, callable $callback, ?int $ttlSeconds = null): mixed
    {
        // TTL par défaut selon le type de donnée
        if ($ttlSeconds === null) {
            if (strpos($key, 'realtime') !== false || strpos($key, 'metrics') !== false) {
                $ttlSeconds = 60; // 1 minute pour données temps réel
            } elseif (strpos($key, 'restaurant') !== false || strpos($key, 'product') !== false) {
                $ttlSeconds = 300; // 5 minutes pour données catalogue
            } else {
                $ttlSeconds = 3600; // 1 heure par défaut
            }
        }
        
        return Cache::remember($key, $ttlSeconds, $callback);
    }
}

