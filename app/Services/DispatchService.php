<?php

namespace App\Services;

use App\Delivery;
use App\Driver;
use App\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Service de dispatch automatique intelligent
 * 
 * Algorithme d'assignation basé sur :
 * - Distance restaurant → livreur → client
 * - Disponibilité livreur
 * - Charge de travail
 * - Historique performance
 */
class DispatchService
{
    /**
     * Trouver le meilleur livreur pour une livraison
     * 
     * @param Delivery $delivery
     * @return Driver|null
     */
    public function findBestDriver(Delivery $delivery): ?Driver
    {
        $order = $delivery->order;
        if (!$order) {
            Log::warning('Delivery sans order', ['delivery_id' => $delivery->id]);
            return null;
        }

        $restaurant = $order->restaurant;
        if (!$restaurant) {
            Log::warning('Order sans restaurant', ['order_id' => $order->id]);
            return null;
        }

        // Coordonnées du restaurant
        $restaurantLat = $restaurant->latitude ?? null;
        $restaurantLng = $restaurant->longitude ?? null;
        
        // Coordonnées de livraison (client)
        $deliveryLat = $order->d_lat ?? $order->latitude ?? null;
        $deliveryLng = $order->d_lng ?? $order->longitude ?? null;

        if (!$restaurantLat || !$restaurantLng) {
            Log::warning('Restaurant sans coordonnées', ['restaurant_id' => $restaurant->id]);
            return null;
        }

        // Récupérer tous les livreurs disponibles
        $availableDrivers = $this->getAvailableDrivers($restaurant);

        if ($availableDrivers->isEmpty()) {
            Log::info('Aucun livreur disponible', [
                'restaurant_id' => $restaurant->id,
                'delivery_id' => $delivery->id
            ]);
            return null;
        }

        // Calculer un score pour chaque livreur
        $scoredDrivers = $availableDrivers->map(function ($driver) use ($restaurantLat, $restaurantLng, $deliveryLat, $deliveryLng, $restaurant) {
            $score = $this->calculateDriverScore(
                $driver,
                $restaurantLat,
                $restaurantLng,
                $deliveryLat,
                $deliveryLng,
                $restaurant
            );
            return [
                'driver' => $driver,
                'score' => $score
            ];
        })->sortByDesc('score');

        // Retourner le livreur avec le meilleur score
        $best = $scoredDrivers->first();
        return $best ? $best['driver'] : null;
    }

    /**
     * Calculer le score d'un livreur (0-100, plus élevé = meilleur)
     * 
     * @param Driver $driver
     * @param float $restaurantLat
     * @param float $restaurantLng
     * @param float|null $deliveryLat
     * @param float|null $deliveryLng
     * @param \App\Restaurant $restaurant
     * @return float
     */
    protected function calculateDriverScore(
        Driver $driver,
        float $restaurantLat,
        float $restaurantLng,
        ?float $deliveryLat,
        ?float $deliveryLng,
        \App\Restaurant $restaurant
    ): float {
        $score = 100.0;

        // 1. Distance restaurant → livreur (40% du score)
        $driverLat = $driver->latitude ?? null;
        $driverLng = $driver->longitude ?? null;
        
        if ($driverLat && $driverLng) {
            $distanceToRestaurant = $this->calculateDistance(
                $restaurantLat,
                $restaurantLng,
                $driverLat,
                $driverLng
            );
            
            // Pénalité basée sur la distance (0-10km = pas de pénalité, 10-20km = -20%, 20+km = -40%)
            if ($distanceToRestaurant > 20) {
                $score -= 40;
            } elseif ($distanceToRestaurant > 10) {
                $score -= 20;
            } elseif ($distanceToRestaurant > 5) {
                $score -= 10;
            }
        } else {
            // Livreur sans position → pénalité forte
            $score -= 30;
        }

        // 2. Charge de travail (30% du score)
        $activeDeliveries = Delivery::where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->count();
        
        // 0 livraison = +10, 1 = 0, 2 = -15, 3+ = -30
        if ($activeDeliveries === 0) {
            $score += 10;
        } elseif ($activeDeliveries >= 3) {
            $score -= 30;
        } elseif ($activeDeliveries >= 2) {
            $score -= 15;
        }

        // 3. Historique performance (20% du score)
        $performanceScore = $this->calculatePerformanceScore($driver);
        $score += ($performanceScore - 50) * 0.4; // Normaliser sur -20 à +20

        // 4. Disponibilité immédiate (10% du score)
        $isAvailable = $this->isDriverAvailable($driver);
        if (!$isAvailable) {
            $score -= 50; // Pénalité majeure si non disponible
        }

        // 5. Bonus si livreur du restaurant (10% du score)
        if ($driver->restaurant_id == $restaurant->id) {
            $score += 10;
        }

        return max(0, min(100, $score)); // Clamper entre 0 et 100
    }

    /**
     * Calculer la distance en km entre deux points (formule Haversine)
     * 
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance en km
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Calculer le score de performance d'un livreur (0-100)
     * 
     * @param Driver $driver
     * @return float
     */
    protected function calculatePerformanceScore(Driver $driver): float
    {
        // Récupérer les livraisons complétées du livreur (30 derniers jours)
        $completedDeliveries = Delivery::where('driver_id', $driver->id)
            ->where('status', 'DELIVERED')
            ->where('delivered_at', '>=', now()->subDays(30))
            ->get();

        if ($completedDeliveries->isEmpty()) {
            return 50.0; // Score neutre si pas d'historique
        }

        $totalDeliveries = $completedDeliveries->count();
        $onTimeDeliveries = 0;
        $totalTime = 0;

        foreach ($completedDeliveries as $delivery) {
            if ($delivery->assigned_at && $delivery->delivered_at) {
                $duration = $delivery->assigned_at->diffInMinutes($delivery->delivered_at);
                $totalTime += $duration;
                
                // Considérer "à temps" si < 60 minutes
                if ($duration < 60) {
                    $onTimeDeliveries++;
                }
            }
        }

        // Score basé sur :
        // - Taux de livraison à temps (60%)
        // - Temps moyen (40%)
        $onTimeRate = ($onTimeDeliveries / $totalDeliveries) * 100;
        $avgTime = $totalTime / $totalDeliveries;
        
        // Temps moyen idéal = 45min, pénalité si > 60min
        $timeScore = 100;
        if ($avgTime > 90) {
            $timeScore = 20;
        } elseif ($avgTime > 60) {
            $timeScore = 60;
        }

        $performanceScore = ($onTimeRate * 0.6) + ($timeScore * 0.4);
        return round($performanceScore, 2);
    }

    /**
     * Vérifier si un livreur est disponible
     * 
     * @param Driver $driver
     * @return bool
     */
    protected function isDriverAvailable(Driver $driver): bool
    {
        // Vérifier le statut
        if (Schema::hasColumn('drivers', 'status')) {
            if ($driver->status !== 'online') {
                return false;
            }
        }

        if (Schema::hasColumn('drivers', 'is_available')) {
            if (!$driver->is_available) {
                return false;
            }
        }

        // Vérifier qu'il n'a pas trop de livraisons actives
        $activeDeliveries = Delivery::where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->count();

        // Limite : 3 livraisons actives max
        return $activeDeliveries < 3;
    }

    /**
     * Récupérer les livreurs disponibles pour un restaurant
     * 
     * @param \App\Restaurant $restaurant
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAvailableDrivers(\App\Restaurant $restaurant)
    {
        $query = Driver::query();

        // Filtrer par restaurant (si livreurs liés au restaurant)
        // OU tous les livreurs indépendants (si restaurant_id = null)
        $query->where(function ($q) use ($restaurant) {
            $q->where('restaurant_id', $restaurant->id)
              ->orWhereNull('restaurant_id'); // Livreurs indépendants
        });

        // Filtrer par statut disponible
        if (Schema::hasColumn('drivers', 'status')) {
            $query->where('status', 'online');
        }

        if (Schema::hasColumn('drivers', 'is_available')) {
            $query->where('is_available', true);
        }

        return $query->get();
    }

    /**
     * Assigner automatiquement un livreur à une livraison
     * 
     * @param Delivery $delivery
     * @return bool True si assignation réussie
     */
    public function autoAssign(Delivery $delivery): bool
    {
        if ($delivery->status !== 'PENDING') {
            Log::warning('Tentative d\'assignation auto sur livraison non-PENDING', [
                'delivery_id' => $delivery->id,
                'status' => $delivery->status
            ]);
            return false;
        }

        $bestDriver = $this->findBestDriver($delivery);

        if (!$bestDriver) {
            Log::info('Aucun livreur trouvé pour assignation auto', [
                'delivery_id' => $delivery->id
            ]);
            return false;
        }

        try {
            $deliveryService = new DeliveryService();
            $deliveryService->assignDriver($delivery, $bestDriver);

            Log::info('Livreur assigné automatiquement', [
                'delivery_id' => $delivery->id,
                'driver_id' => $bestDriver->id,
                'driver_name' => $bestDriver->name
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'assignation automatique', [
                'delivery_id' => $delivery->id,
                'driver_id' => $bestDriver->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Traiter toutes les livraisons en attente (pour scheduler)
     * 
     * @param int $limit Nombre max de livraisons à traiter
     * @return array ['processed' => int, 'assigned' => int, 'failed' => int]
     */
    public function processPendingDeliveries(int $limit = 10): array
    {
        $pendingDeliveries = Delivery::where('status', 'PENDING')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $processed = 0;
        $assigned = 0;
        $failed = 0;

        foreach ($pendingDeliveries as $delivery) {
            $processed++;
            if ($this->autoAssign($delivery)) {
                $assigned++;
            } else {
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'assigned' => $assigned,
            'failed' => $failed
        ];
    }
}

