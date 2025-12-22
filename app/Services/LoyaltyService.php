<?php

namespace App\Services;

use App\LoyaltyPoint;
use App\LoyaltyTransaction;
use App\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LoyaltyService
{
    /**
     * Points gagnés par 1000 FCFA dépensés
     */
    const POINTS_PER_1000 = 10;
    
    /**
     * Points requis pour 1000 FCFA de réduction
     */
    const POINTS_FOR_1000 = 100;
    
    /**
     * Durée de validité des points (en jours)
     */
    const POINTS_EXPIRY_DAYS = 365;
    
    /**
     * Obtenir ou créer les points de fidélité d'un utilisateur
     * 
     * @param int $userId
     * @return LoyaltyPoint
     */
    public static function getOrCreatePoints($userId)
    {
        return LoyaltyPoint::firstOrCreate(
            ['user_id' => $userId],
            ['points' => 0, 'total_earned' => 0, 'total_spent' => 0]
        );
    }
    
    /**
     * Calculer les points à gagner pour un montant
     * 
     * @param float $amount
     * @return int
     */
    public static function calculatePoints($amount)
    {
        return floor(($amount / 1000) * self::POINTS_PER_1000);
    }
    
    /**
     * Calculer la réduction pour un nombre de points
     * 
     * @param int $points
     * @return float
     */
    public static function calculateDiscount($points)
    {
        return floor(($points / self::POINTS_FOR_1000) * 1000);
    }
    
    /**
     * Ajouter des points après une commande
     * 
     * @param int $userId
     * @param int $orderId
     * @param float $orderTotal
     * @return int Points gagnés
     */
    public static function addPointsFromOrder($userId, $orderId, $orderTotal)
    {
        $pointsEarned = self::calculatePoints($orderTotal);
        
        if ($pointsEarned <= 0) {
            return 0;
        }
        
        DB::beginTransaction();
        try {
            $loyaltyPoint = self::getOrCreatePoints($userId);
            $loyaltyPoint->points += $pointsEarned;
            $loyaltyPoint->total_earned += $pointsEarned;
            $loyaltyPoint->save();
            
            // Créer la transaction
            LoyaltyTransaction::create([
                'user_id' => $userId,
                'order_id' => $orderId,
                'points' => $pointsEarned,
                'type' => 'earned',
                'description' => 'Points gagnés pour la commande #' . $orderId,
                'expires_at' => Carbon::now()->addDays(self::POINTS_EXPIRY_DAYS)
            ]);
            
            DB::commit();
            
            Log::info('Loyalty points added', [
                'user_id' => $userId,
                'order_id' => $orderId,
                'points' => $pointsEarned
            ]);
            
            return $pointsEarned;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add loyalty points', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'order_id' => $orderId
            ]);
            return 0;
        }
    }
    
    /**
     * Utiliser des points pour une réduction
     * 
     * @param int $userId
     * @param int $pointsToUse
     * @param int|null $orderId
     * @return bool
     */
    public static function usePoints($userId, $pointsToUse, $orderId = null)
    {
        $loyaltyPoint = self::getOrCreatePoints($userId);
        
        if ($loyaltyPoint->points < $pointsToUse) {
            return false;
        }
        
        DB::beginTransaction();
        try {
            $loyaltyPoint->points -= $pointsToUse;
            $loyaltyPoint->total_spent += $pointsToUse;
            $loyaltyPoint->save();
            
            // Créer la transaction
            LoyaltyTransaction::create([
                'user_id' => $userId,
                'order_id' => $orderId,
                'points' => -$pointsToUse,
                'type' => 'spent',
                'description' => 'Points utilisés pour réduction'
            ]);
            
            DB::commit();
            
            Log::info('Loyalty points used', [
                'user_id' => $userId,
                'order_id' => $orderId,
                'points' => $pointsToUse
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to use loyalty points', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Obtenir le solde de points d'un utilisateur
     * 
     * @param int $userId
     * @return int
     */
    public static function getBalance($userId)
    {
        $loyaltyPoint = self::getOrCreatePoints($userId);
        return $loyaltyPoint->points;
    }
    
    /**
     * Obtenir l'historique des transactions
     * 
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getHistory($userId, $limit = 20)
    {
        return LoyaltyTransaction::where('user_id', $userId)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Expirer les points anciens
     * 
     * @return int Nombre de points expirés
     */
    public static function expireOldPoints()
    {
        $expiredTransactions = LoyaltyTransaction::where('type', 'earned')
            ->where('expires_at', '<', now())
            ->where('points', '>', 0)
            ->get();
        
        $totalExpired = 0;
        
        foreach ($expiredTransactions as $transaction) {
            $loyaltyPoint = self::getOrCreatePoints($transaction->user_id);
            
            if ($loyaltyPoint->points >= $transaction->points) {
                $loyaltyPoint->points -= $transaction->points;
                $loyaltyPoint->save();
                
                LoyaltyTransaction::create([
                    'user_id' => $transaction->user_id,
                    'points' => -$transaction->points,
                    'type' => 'expired',
                    'description' => 'Points expirés'
                ]);
                
                $totalExpired += $transaction->points;
            }
        }
        
        return $totalExpired;
    }
}


