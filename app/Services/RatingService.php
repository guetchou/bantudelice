<?php

namespace App\Services;

use App\Order;
use App\Rating;
use App\CompletedOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public function rateOrder($orderId, $userId, $ratingValue, $comment = null)
    {
        // Chercher d'abord dans completed_orders (commandes terminées)
        $completedOrder = CompletedOrder::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();
        
        if ($completedOrder) {
            return $this->rateCompletedOrder($completedOrder, $userId, $ratingValue, $comment);
        }
        
        // Sinon chercher dans orders
        $order = Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();
        
        if (!$order) {
            throw new \RuntimeException("Commande introuvable ou non livrée.");
        }
        
        return $this->rateOrderRecord($order, $userId, $ratingValue, $comment);
    }
    
    /**
     * Noter une commande depuis completed_orders
     */
    protected function rateCompletedOrder(CompletedOrder $completedOrder, $userId, $ratingValue, $comment = null)
    {
        // Vérifier qu'il n'y a pas déjà une note pour cette commande
        $existingRating = Rating::where('order_id', $completedOrder->id)
            ->where('user_id', $userId)
            ->first();
        
        if ($existingRating) {
            throw new \RuntimeException("Cette commande a déjà été notée.");
        }
        
        return DB::transaction(function () use ($completedOrder, $userId, $ratingValue, $comment) {
            // Créer la note
            $rating = Rating::create([
                'restaurant_id' => $completedOrder->restaurant_id,
                'user_id' => $userId,
                'order_id' => $completedOrder->id,
                'rating' => $ratingValue,
                'reviews' => $comment,
            ]);
            
            // Recalculer les stats du restaurant
            $this->recalculateRestaurantRating($completedOrder->restaurant_id);
            
            return $rating;
        });
    }
    
    /**
     * Noter une commande depuis orders
     */
    protected function rateOrderRecord(Order $order, $userId, $ratingValue, $comment = null)
    {
        // Vérifier qu'il n'y a pas déjà une note pour cette commande
        $existingRating = Rating::where('order_id', $order->id)
            ->where('user_id', $userId)
            ->first();
        
        if ($existingRating) {
            throw new \RuntimeException("Cette commande a déjà été notée.");
        }
        
        return DB::transaction(function () use ($order, $userId, $ratingValue, $comment) {
            // Créer la note
            $rating = Rating::create([
                'restaurant_id' => $order->restaurant_id,
                'user_id' => $userId,
                'order_id' => $order->id,
                'rating' => $ratingValue,
                'reviews' => $comment,
            ]);
            
            // Recalculer les stats du restaurant
            $this->recalculateRestaurantRating($order->restaurant_id);
            
            return $rating;
        });
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
            
            // Mettre à jour le restaurant
            DB::table('restaurants')
                ->where('id', $restaurantId)
                ->update([
                    'avg_rating' => $avgRating,
                    'rating_count' => $ratingCount,
                    'updated_at' => now(),
                ]);
            
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
    
    /**
     * Vérifier si une commande peut être notée
     * 
     * @param int $orderId
     * @param int $userId
     * @return array ['can_rate' => bool, 'message' => string, 'existing_rating' => Rating|null]
     */
    public function canRateOrder($orderId, $userId)
    {
        // Chercher dans completed_orders
        $completedOrder = CompletedOrder::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();
        
        if ($completedOrder) {
            $existingRating = Rating::where('order_id', $completedOrder->id)
                ->where('user_id', $userId)
                ->first();
            
            if ($existingRating) {
                return [
                    'can_rate' => false,
                    'message' => 'Cette commande a déjà été notée.',
                    'existing_rating' => $existingRating,
                ];
            }
            
            return [
                'can_rate' => true,
                'message' => 'Vous pouvez noter cette commande.',
                'existing_rating' => null,
            ];
        }
        
        // Chercher dans orders
        $order = Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();
        
        if (!$order) {
            return [
                'can_rate' => false,
                'message' => 'Commande introuvable ou non livrée.',
                'existing_rating' => null,
            ];
        }
        
        $existingRating = Rating::where('order_id', $order->id)
            ->where('user_id', $userId)
            ->first();
        
        if ($existingRating) {
            return [
                'can_rate' => false,
                'message' => 'Cette commande a déjà été notée.',
                'existing_rating' => $existingRating,
            ];
        }
        
        return [
            'can_rate' => true,
            'message' => 'Vous pouvez noter cette commande.',
            'existing_rating' => null,
        ];
    }
}

