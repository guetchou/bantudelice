<?php

namespace App\Services;

use App\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

/**
 * Service pour gérer les restaurants avec filtres et pagination
 */
class RestaurantService
{
    /**
     * Rechercher des restaurants avec filtres et pagination
     * 
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchRestaurants(array $filters = [])
    {
        // Récupérer les valeurs par défaut depuis ConfigService
        $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
        $defaultDeliveryTimeMin = \App\Services\ConfigService::getDefaultDeliveryTimeMin();
        $defaultDeliveryTimeMax = \App\Services\ConfigService::getDefaultDeliveryTimeMax();
        $defaultRating = \App\Services\ConfigService::getDefaultRating();
        
        // Construire la requête de base
        $query = Restaurant::with(['cuisines', 'ratings'])
            ->where('approved', true);
        
        // Filtre par ville
        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }
        
        // Filtre par note minimale
        if (!empty($filters['min_rating'])) {
            $query->whereHas('ratings', function($q) use ($filters) {
                $q->selectRaw('AVG(rating) as avg_rating')
                  ->groupBy('restaurant_id')
                  ->havingRaw('AVG(rating) >= ?', [(float)$filters['min_rating']]);
            });
        }
        
        // Filtre par frais de livraison maximum
        if (!empty($filters['max_delivery_fee'])) {
            $query->where(function($q) use ($filters, $defaultDeliveryFee) {
                $q->where('delivery_charges', '<=', (float)$filters['max_delivery_fee'])
                  ->orWhere(function($subQ) use ($filters, $defaultDeliveryFee) {
                      $subQ->whereNull('delivery_charges')
                           ->whereRaw('? <= ?', [$defaultDeliveryFee, (float)$filters['max_delivery_fee']]);
                  });
            });
        }
        
        // Filtre par cuisine
        if (!empty($filters['cuisine'])) {
            if (is_array($filters['cuisine'])) {
                $query->whereHas('cuisines', function($q) use ($filters) {
                    $q->whereIn('cuisines.id', $filters['cuisine']);
                });
            } else {
                $query->whereHas('cuisines', function($q) use ($filters) {
                    $q->where('cuisines.id', $filters['cuisine']);
                });
            }
        }
        
        // Recherche textuelle
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        
        // Trier
        $sort = $filters['sort'] ?? 'popular';
        
        switch ($sort) {
            case 'rating':
                // Trier par note moyenne décroissante, puis par nombre d'avis
                // On va trier après avoir récupéré les résultats pour éviter les conflits avec les relations
                $query->orderBy('featured', 'desc')
                      ->orderBy('created_at', 'desc');
                break;
                
            case 'delivery_fee':
                // Trier par frais de livraison croissant
                $query->orderByRaw('COALESCE(delivery_charges, ?) ASC', [$defaultDeliveryFee])
                      ->orderBy('name', 'asc');
                break;
                
            case 'name':
                // Trier par nom alphabétique
                $query->orderBy('name', 'asc');
                break;
                
            case 'popular':
            default:
                // Trier par popularité (featured + date de création)
                $query->orderByDesc('featured')
                      ->orderByDesc('created_at');
                break;
        }
        
        // Pagination
        $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 12;
        
        $paginator = $query->paginate($perPage);
        
        // Calculer les notes moyennes pour chaque restaurant
        $paginator->getCollection()->transform(function($restaurant) use ($defaultRating, $defaultDeliveryFee, $defaultDeliveryTimeMin, $defaultDeliveryTimeMax, $sort) {
            // Calculer la note moyenne
            $avgRating = $restaurant->ratings()->avg('rating');
            $restaurant->avg_rating = $avgRating ? (float)$avgRating : $defaultRating;
            
            // Calculer le nombre d'avis
            $restaurant->rating_count = $restaurant->ratings()->count();
            
            // Frais de livraison
            $restaurant->delivery_fee = $restaurant->delivery_charges ?? $defaultDeliveryFee;
            
            // Temps de livraison
            $restaurant->eta_min = $defaultDeliveryTimeMin;
            $restaurant->eta_max = $defaultDeliveryTimeMax;
            if ($restaurant->avg_delivery_time) {
                try {
                    $time = \Carbon\Carbon::parse($restaurant->avg_delivery_time);
                    $minutes = $time->hour * 60 + $time->minute;
                    if ($minutes > 0) {
                        $restaurant->eta_min = max(15, $minutes - 5);
                        $restaurant->eta_max = $minutes + 5;
                    }
                } catch (\Exception $e) {
                    // Garder les valeurs par défaut
                }
            }
            
            return $restaurant;
        });
        
        // Trier la collection après avoir calculé les notes (pour le tri par rating)
        if ($sort === 'rating' || $sort === 'popular') {
            $sorted = $paginator->getCollection()->sortByDesc(function($restaurant) {
                // Score de popularité : featured (1000 points) + rating * 100 + nombre d'avis
                $score = ($restaurant->featured ? 1000 : 0) + ($restaurant->avg_rating * 100) + ($restaurant->rating_count / 10);
                return $score;
            })->values();
            
            // Remplacer la collection triée
            $paginator->setCollection($sorted);
        }
        
        return $paginator;
    }
}

