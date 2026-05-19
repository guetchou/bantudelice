<?php

namespace App\Services;

use App\Driver;
use App\Order;
use App\Rating;
use App\Review;
use App\CompletedOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Service pour gérer les notes et avis des restaurants
 */
class RatingService
{
    /**
     * Noter une commande (depuis completed_orders ou orders)
     * 
     * @param int $orderId ID de la commande (dans orders ou completed_orders)
     * @param int $userId ID de l'utilisateur
     * @param int $ratingValue Note entre 1 et 5
     * @param string|null $comment Commentaire optionnel
     * @return Rating
     * @throws \RuntimeException
     */
    public function rateOrder($orderId, $userId, $ratingValue, $comment = null, $driverRatingValue = null, $driverComment = null)
    {
        $completedOrder = $this->findCompletedOrder($orderId, $userId);
        if ($completedOrder) {
            return $this->rateCompletedOrder($completedOrder, $userId, $ratingValue, $comment, $driverRatingValue, $driverComment);
        }

        $order = $this->findDeliveredOrder($orderId, $userId);
        if (!$order) {
            throw new \RuntimeException("Commande introuvable ou non livrée.");
        }

        return $this->rateOrderRecord($order, $userId, $ratingValue, $comment, $driverRatingValue, $driverComment);
    }
    
    /**
     * Noter une commande depuis completed_orders
     */
    protected function rateCompletedOrder(CompletedOrder $completedOrder, $userId, $ratingValue, $comment = null, $driverRatingValue = null, $driverComment = null)
    {
        $existingRating = Rating::where('order_id', $completedOrder->id)->where('user_id', $userId)->first();
        if (!$existingRating && !$ratingValue) {
            throw new \RuntimeException("Veuillez donner une note au restaurant.");
        }
        if ($existingRating && !$driverRatingValue) {
            throw new \RuntimeException("Cette commande a déjà été notée.");
        }

        return DB::transaction(function () use ($completedOrder, $userId, $ratingValue, $comment, $driverRatingValue, $driverComment, $existingRating) {
            $rating = $existingRating ?: Rating::create([
                'restaurant_id' => $completedOrder->restaurant_id,
                'user_id' => $userId,
                'order_id' => $completedOrder->id,
                'rating' => $ratingValue,
                'reviews' => $comment,
            ]);

            if (!$existingRating) {
                $this->recalculateRestaurantRating($completedOrder->restaurant_id);
            }

            $driverReview = null;
            if ($driverRatingValue && $completedOrder->driver_id ?? null) {
                $driverReview = $this->createDriverReview(
                    $completedOrder->driver_id,
                    $completedOrder->id,
                    $userId,
                    $driverRatingValue,
                    $driverComment
                );
            }

            return [
                'restaurant_rating' => $rating,
                'driver_review' => $driverReview,
            ];
        });
    }
    
    /**
     * Noter une commande depuis orders
     */
    protected function rateOrderRecord(Order $order, $userId, $ratingValue, $comment = null, $driverRatingValue = null, $driverComment = null)
    {
        $existingRating = Rating::where('order_id', $order->id)->where('user_id', $userId)->first();
        if (!$existingRating && !$ratingValue) {
            throw new \RuntimeException("Veuillez donner une note au restaurant.");
        }
        if ($existingRating && !$driverRatingValue) {
            throw new \RuntimeException("Cette commande a déjà été notée.");
        }

        return DB::transaction(function () use ($order, $userId, $ratingValue, $comment, $driverRatingValue, $driverComment, $existingRating) {
            $rating = $existingRating ?: Rating::create([
                'restaurant_id' => $order->restaurant_id,
                'user_id' => $userId,
                'order_id' => $order->id,
                'rating' => $ratingValue,
                'reviews' => $comment,
            ]);

            if (!$existingRating) {
                $this->recalculateRestaurantRating($order->restaurant_id);
            }

            $driverReview = null;
            if ($driverRatingValue && $order->driver_id) {
                $driverReview = $this->createDriverReview(
                    $order->driver_id,
                    $order->id,
                    $userId,
                    $driverRatingValue,
                    $driverComment
                );
            }

            return [
                'restaurant_rating' => $rating,
                'driver_review' => $driverReview,
            ];
        });
    }

    protected function createDriverReview(int $driverId, int $orderId, int $userId, int $ratingValue, ?string $comment = null): ?Review
    {
        if (!Schema::hasTable('reviews')) {
            return null;
        }

        $existingDriverReview = Review::where('driver_id', $driverId)
            ->where('user_id', $userId)
            ->when(Schema::hasColumn('reviews', 'order_id'), fn ($query) => $query->where('order_id', $orderId))
            ->first();

        if ($existingDriverReview) {
            throw new \RuntimeException("Le livreur a déjà été noté pour cette commande.");
        }

        $payload = [
            'driver_id' => $driverId,
            'user_id' => $userId,
            'rating' => $ratingValue,
            'reviews' => $comment,
        ];

        if (Schema::hasColumn('reviews', 'order_id')) {
            $payload['order_id'] = $orderId;
        }

        $review = Review::create($payload);
        $this->recalculateDriverRating($driverId);

        return $review;
    }
    
    /**
     * Recalculer la note moyenne et le nombre d'avis d'un restaurant
     * 
     * @param int $restaurantId
     * @return void
     */
    public function recalculateRestaurantRating($restaurantId)
    {
        try {
            $stats = Rating::where('restaurant_id', $restaurantId)
                ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as rating_count')
                ->first();
            
            $avgRating = $stats->avg_rating ? round((float)$stats->avg_rating, 1) : 0;
            $ratingCount = (int)($stats->rating_count ?? 0);

            $payload = ['updated_at' => now()];
            if (Schema::hasColumn('restaurants', 'avg_rating')) {
                $payload['avg_rating'] = $avgRating;
            }
            if (Schema::hasColumn('restaurants', 'rating_count')) {
                $payload['rating_count'] = $ratingCount;
            }

            if (count($payload) > 1) {
                DB::table('restaurants')
                    ->where('id', $restaurantId)
                    ->update($payload);
            }
            
            // Invalider le cache de ConfigService si nécessaire
            \App\Services\ConfigService::clearCache();
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du recalcul de la note du restaurant', [
                'restaurant_id' => $restaurantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function recalculateDriverRating($driverId): void
    {
        if (!Schema::hasTable('reviews')) {
            return;
        }

        $stats = Review::where('driver_id', $driverId)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as rating_count')
            ->first();

        $avgRating = $stats->avg_rating ? round((float) $stats->avg_rating, 1) : 0;
        $ratingCount = (int) ($stats->rating_count ?? 0);

        $payload = ['updated_at' => now()];
        if (Schema::hasColumn('drivers', 'avg_rating')) {
            $payload['avg_rating'] = $avgRating;
        }
        if (Schema::hasColumn('drivers', 'rating_count')) {
            $payload['rating_count'] = $ratingCount;
        }

        if (count($payload) > 1) {
            DB::table('drivers')->where('id', $driverId)->update($payload);
        }
    }
    
    /**
     * Vérifier si une commande peut être notée
     * 
     * @param int $orderId
     * @param int $userId
     * @return array ['can_rate' => bool, 'message' => string, 'existing_rating' => Rating|null]
     */
    public function canRateOrder($orderId, $userId)
    {
        $completedOrder = $this->findCompletedOrder($orderId, $userId);
        
        if ($completedOrder) {
            $existingRating = Rating::where('order_id', $completedOrder->id)->where('user_id', $userId)->first();
            $existingDriverReview = Schema::hasTable('reviews')
                ? Review::where('driver_id', $completedOrder->driver_id ?? 0)
                    ->where('user_id', $userId)
                    ->when(Schema::hasColumn('reviews', 'order_id'), fn ($query) => $query->where('order_id', $completedOrder->id))
                    ->first()
                : null;
            
            if ($existingRating && (!$completedOrder->driver_id || $existingDriverReview)) {
                return [
                    'can_rate' => false,
                    'message' => 'Cette commande a déjà été notée.',
                    'existing_rating' => $existingRating,
                    'existing_driver_review' => $existingDriverReview,
                ];
            }
            
            return [
                'can_rate' => true,
                'message' => 'Vous pouvez noter cette commande.',
                'existing_rating' => null,
                'existing_driver_review' => $existingDriverReview,
            ];
        }

        $order = $this->findDeliveredOrder($orderId, $userId);
        
        if (!$order) {
            return [
                'can_rate' => false,
                'message' => 'Commande introuvable ou non livrée.',
                'existing_rating' => null,
                'existing_driver_review' => null,
            ];
        }
        
        $existingRating = Rating::where('order_id', $order->id)->where('user_id', $userId)->first();
        $existingDriverReview = Schema::hasTable('reviews')
            ? Review::where('driver_id', $order->driver_id ?? 0)
                ->where('user_id', $userId)
                ->when(Schema::hasColumn('reviews', 'order_id'), fn ($query) => $query->where('order_id', $order->id))
                ->first()
            : null;
        
        if ($existingRating && (!$order->driver_id || $existingDriverReview)) {
            return [
                'can_rate' => false,
                'message' => 'Cette commande a déjà été notée.',
                'existing_rating' => $existingRating,
                'existing_driver_review' => $existingDriverReview,
            ];
        }
        
        return [
            'can_rate' => true,
            'message' => 'Vous pouvez noter cette commande.',
            'existing_rating' => $existingRating,
            'existing_driver_review' => $existingDriverReview,
        ];
    }

    protected function findCompletedOrder(int $orderId, int $userId): ?CompletedOrder
    {
        return CompletedOrder::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();
    }

    protected function findDeliveredOrder(int $orderId, int $userId): ?Order
    {
        return Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->where('status', 'completed');
                if (Schema::hasColumn('orders', 'business_status')) {
                    $query->orWhere('business_status', 'delivered');
                }
            })
            ->first();
    }
}
