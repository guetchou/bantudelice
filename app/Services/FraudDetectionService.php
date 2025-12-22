<?php

namespace App\Services;

use App\Payment;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service de détection de fraude basique
 * 
 * Règles :
 * - Limite de montant par transaction
 * - Limite de fréquence (nombre de paiements par heure/jour)
 * - Blacklist d'utilisateurs
 * - Détection de montants suspects (multiples de 1000, etc.)
 */
class FraudDetectionService
{
    /**
     * Montant maximum autorisé par transaction (en centimes)
     */
    const MAX_AMOUNT = 1000000; // 10 000 FCFA

    /**
     * Nombre maximum de paiements par heure
     */
    const MAX_PAYMENTS_PER_HOUR = 10;

    /**
     * Nombre maximum de paiements par jour
     */
    const MAX_PAYMENTS_PER_DAY = 50;

    /**
     * Vérifier si un paiement est suspect
     * 
     * @param Payment $payment
     * @param array $context Données additionnelles (IP, device, etc.)
     * @return array ['is_fraud' => bool, 'risk_score' => int, 'reasons' => array]
     */
    public function checkFraud(Payment $payment, array $context = []): array
    {
        $reasons = [];
        $riskScore = 0;

        // 1. Vérifier le montant
        if ($payment->amount > self::MAX_AMOUNT) {
            $reasons[] = 'Montant supérieur à la limite autorisée (' . self::MAX_AMOUNT . ' FCFA)';
            $riskScore += 50;
        }

        // 2. Vérifier la fréquence (nombre de paiements récents)
        $frequencyCheck = $this->checkFrequency($payment->user_id);
        if ($frequencyCheck['is_suspicious']) {
            $reasons[] = $frequencyCheck['reason'];
            $riskScore += $frequencyCheck['risk_score'];
        }

        // 3. Vérifier la blacklist
        if ($this->isBlacklisted($payment->user_id)) {
            $reasons[] = 'Utilisateur sur liste noire';
            $riskScore += 100;
        }

        // 4. Vérifier les montants suspects (multiples de 1000, montants ronds)
        if ($this->isSuspiciousAmount($payment->amount)) {
            $reasons[] = 'Montant suspect (multiple de 1000)';
            $riskScore += 10;
        }

        // 5. Vérifier l'IP (si disponible)
        if (isset($context['ip'])) {
            $ipCheck = $this->checkIP($context['ip'], $payment->user_id);
            if ($ipCheck['is_suspicious']) {
                $reasons[] = $ipCheck['reason'];
                $riskScore += $ipCheck['risk_score'];
            }
        }

        // 6. Vérifier les paiements échoués récents
        $failedPayments = $this->countRecentFailedPayments($payment->user_id);
        if ($failedPayments > 5) {
            $reasons[] = "Trop de paiements échoués récents ({$failedPayments})";
            $riskScore += 20;
        }

        $isFraud = $riskScore >= 50; // Seuil : 50 points = suspect, 100+ = fraude probable

        return [
            'is_fraud' => $isFraud,
            'risk_score' => $riskScore,
            'reasons' => $reasons,
            'recommendation' => $this->getRecommendation($riskScore)
        ];
    }

    /**
     * Vérifier la fréquence des paiements
     * 
     * @param int $userId
     * @return array
     */
    protected function checkFrequency(int $userId): array
    {
        $now = now();
        
        // Paiements de la dernière heure
        $paymentsLastHour = Payment::where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subHour())
            ->count();
        
        if ($paymentsLastHour >= self::MAX_PAYMENTS_PER_HOUR) {
            return [
                'is_suspicious' => true,
                'reason' => "Trop de paiements dans la dernière heure ({$paymentsLastHour})",
                'risk_score' => 30
            ];
        }

        // Paiements des dernières 24h
        $paymentsLastDay = Payment::where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subDay())
            ->count();
        
        if ($paymentsLastDay >= self::MAX_PAYMENTS_PER_DAY) {
            return [
                'is_suspicious' => true,
                'reason' => "Trop de paiements dans les dernières 24h ({$paymentsLastDay})",
                'risk_score' => 20
            ];
        }

        return [
            'is_suspicious' => false,
            'reason' => '',
            'risk_score' => 0
        ];
    }

    /**
     * Vérifier si un utilisateur est blacklisté
     * 
     * @param int $userId
     * @return bool
     */
    protected function isBlacklisted(int $userId): bool
    {
        // Vérifier dans le cache d'abord
        $cacheKey = "fraud_blacklist_user_{$userId}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Vérifier dans la DB (si table existe)
        try {
            $user = User::find($userId);
            if ($user && isset($user->is_blacklisted) && $user->is_blacklisted) {
                Cache::put($cacheKey, true, 3600); // Cache 1h
                return true;
            }
        } catch (\Exception $e) {
            // Si la colonne n'existe pas, ignorer
        }

        // Vérifier dans une table dédiée (si existe)
        try {
            $blacklisted = DB::table('fraud_blacklist')
                ->where('user_id', $userId)
                ->where('active', true)
                ->exists();
            
            if ($blacklisted) {
                Cache::put($cacheKey, true, 3600);
                return true;
            }
        } catch (\Exception $e) {
            // Table n'existe pas, ignorer
        }

        Cache::put($cacheKey, false, 3600);
        return false;
    }

    /**
     * Vérifier si un montant est suspect
     * 
     * @param int $amount
     * @return bool
     */
    protected function isSuspiciousAmount(int $amount): bool
    {
        // Montants ronds multiples de 1000 (ex: 10000, 20000, 50000)
        if ($amount > 0 && $amount % 1000 === 0 && $amount >= 10000) {
            return true;
        }

        // Montants très élevés
        if ($amount > 500000) { // 5 000 FCFA
            return true;
        }

        return false;
    }

    /**
     * Vérifier l'IP
     * 
     * @param string $ip
     * @param int $userId
     * @return array
     */
    protected function checkIP(string $ip, int $userId): array
    {
        // Vérifier si cette IP a fait beaucoup de paiements récents
        $recentPaymentsFromIP = Payment::where('meta->ip', $ip)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($recentPaymentsFromIP > 20) {
            return [
                'is_suspicious' => true,
                'reason' => "Trop de paiements depuis cette IP ({$recentPaymentsFromIP})",
                'risk_score' => 25
            ];
        }

        // Vérifier si l'IP est blacklistée
        $cacheKey = "fraud_blacklist_ip_{$ip}";
        if (Cache::has($cacheKey) && Cache::get($cacheKey)) {
            return [
                'is_suspicious' => true,
                'reason' => 'IP sur liste noire',
                'risk_score' => 50
            ];
        }

        return [
            'is_suspicious' => false,
            'reason' => '',
            'risk_score' => 0
        ];
    }

    /**
     * Compter les paiements échoués récents
     * 
     * @param int $userId
     * @return int
     */
    protected function countRecentFailedPayments(int $userId): int
    {
        return Payment::where('user_id', $userId)
            ->where('status', 'FAILED')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * Obtenir une recommandation selon le score de risque
     * 
     * @param int $riskScore
     * @return string
     */
    protected function getRecommendation(int $riskScore): string
    {
        if ($riskScore >= 100) {
            return 'BLOCK'; // Bloquer le paiement
        } elseif ($riskScore >= 50) {
            return 'REVIEW'; // Révision manuelle requise
        } elseif ($riskScore >= 20) {
            return 'MONITOR'; // Surveiller
        } else {
            return 'ALLOW'; // Autoriser
        }
    }

    /**
     * Ajouter un utilisateur à la blacklist
     * 
     * @param int $userId
     * @param string $reason
     * @return void
     */
    public function blacklistUser(int $userId, string $reason = ''): void
    {
        try {
            DB::table('fraud_blacklist')->insert([
                'user_id' => $userId,
                'reason' => $reason,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Si la table n'existe pas, utiliser le cache
            Cache::put("fraud_blacklist_user_{$userId}", true, 86400 * 30); // 30 jours
        }

        // Invalider le cache
        Cache::forget("fraud_blacklist_user_{$userId}");
    }

    /**
     * Ajouter une IP à la blacklist
     * 
     * @param string $ip
     * @param string $reason
     * @return void
     */
    public function blacklistIP(string $ip, string $reason = ''): void
    {
        Cache::put("fraud_blacklist_ip_{$ip}", true, 86400 * 7); // 7 jours
        
        try {
            DB::table('fraud_blacklist_ips')->insert([
                'ip' => $ip,
                'reason' => $reason,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Table n'existe pas, juste le cache
        }
    }
}

