<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Service centralisé pour récupérer les valeurs de configuration depuis la base de données
 * Évite les valeurs codées en dur dans le code
 * Lit depuis la table system_config si elle existe, sinon utilise des fallbacks
 */
class ConfigService
{
    const CACHE_DURATION = 3600; // 1 heure
    
    /**
     * Récupérer une valeur de configuration depuis system_config
     * 
     * @param string $key
     * @param mixed $defaultValue
     * @param string $type
     * @return mixed
     */
    private static function getConfigValue($key, $defaultValue, $type = 'string')
    {
        $cacheKey = 'config_' . $key;
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($key, $defaultValue, $type) {
            // Vérifier si la table system_config existe
            if (!Schema::hasTable('system_config')) {
                return $defaultValue;
            }
            
            $config = DB::table('system_config')->where('key', $key)->first();
            
            if ($config && $config->value !== null) {
                // Convertir selon le type
                switch ($type) {
                    case 'integer':
                        return (int)$config->value;
                    case 'float':
                        return (float)$config->value;
                    case 'boolean':
                        return filter_var($config->value, FILTER_VALIDATE_BOOLEAN);
                    default:
                        return $config->value;
                }
            }
            
            // Fallback uniquement si la table n'existe pas ou la clé n'existe pas
            return $defaultValue;
        });
    }
    
    /**
     * Récupérer les frais de livraison par défaut depuis system_config
     * Si system_config n'existe pas, lit depuis la table charges
     * 
     * @return float
     */
    public static function getDefaultDeliveryFee()
    {
        $value = self::getConfigValue('default_delivery_fee', null, 'float');
        
        // Si system_config n'a pas de valeur, essayer depuis charges
        if ($value === null) {
            $charge = DB::table('charges')->first();
            if ($charge) {
                $value = (float)($charge->delivery_fee ?? $charge->delivery_charges ?? 1500);
            } else {
                $value = 1500.0;
            }
        }
        
        return $value;
    }
    
    /**
     * Récupérer le temps de livraison par défaut (min)
     * 
     * @return int
     */
    public static function getDefaultDeliveryTimeMin()
    {
        return self::getConfigValue('default_delivery_time_min', 20, 'integer');
    }
    
    /**
     * Récupérer le temps de livraison par défaut (max)
     * 
     * @return int
     */
    public static function getDefaultDeliveryTimeMax()
    {
        return self::getConfigValue('default_delivery_time_max', 35, 'integer');
    }
    
    /**
     * Récupérer le rating par défaut (si aucun rating n'existe)
     * 
     * @return float
     */
    public static function getDefaultRating()
    {
        return self::getConfigValue('default_rating', 4.5, 'float');
    }
    
    /**
     * Récupérer le seuil de note pour le badge "Top noté"
     * 
     * @return float
     */
    public static function getTopRatedThreshold()
    {
        return self::getConfigValue('top_rated_threshold', 4.5, 'float');
    }
    
    /**
     * Récupérer le nombre minimum d'avis pour le badge "Top noté"
     * 
     * @return int
     */
    public static function getTopRatedMinReviews()
    {
        return self::getConfigValue('top_rated_min_reviews', 10, 'integer');
    }
    
    /**
     * Récupérer le format d'affichage du temps de livraison par défaut
     * 
     * @return string
     */
    public static function getDefaultDeliveryTimeDisplay()
    {
        $min = self::getDefaultDeliveryTimeMin();
        $max = self::getDefaultDeliveryTimeMax();
        return $min . '-' . $max . ' min';
    }
    
    /**
     * Récupérer le nom de l'entreprise
     * 
     * @return string
     */
    public static function getCompanyName()
    {
        return self::getConfigValue('company_name', 'BantuDelice', 'string');
    }
    
    /**
     * Récupérer l'email noreply du système
     * 
     * @return string
     */
    public static function getNoreplyEmail()
    {
        return self::getConfigValue('noreply_email', 'noreply@bantudelice.cg', 'string');
    }
    
    /**
     * Récupérer l'email admin officiel
     * 
     * @return string
     */
    public static function getAdminEmail()
    {
        return self::getConfigValue('admin_email', 'admin@bantudelice.cg', 'string');
    }
    
    /**
     * Récupérer l'email de contact
     * 
     * @return string
     */
    public static function getContactEmail()
    {
        return self::getConfigValue('contact_email', 'contact@bantudelice.cg', 'string');
    }
    
    /**
     * Récupérer le message d'inscription utilisateur
     * 
     * @return string
     */
    public static function getUserRegistrationMessage()
    {
        $companyName = self::getCompanyName();
        return self::getConfigValue('message_user_registration', 
            "Cher utilisateur, Votre inscription a été reçue avec succès. Nous vous remercions de votre confiance et vous souhaitons la bienvenue sur {$companyName}. Notre objectif est de traiter vos commandes avec le plus grand soin et de vous offrir un service client exceptionnel. N'hésitez pas à nous faire part de vos retours et suggestions d'amélioration. Merci de faire partie de la famille {$companyName}. Cordialement, L'équipe {$companyName}", 
            'string');
    }
    
    /**
     * Récupérer le message d'inscription livreur
     * 
     * @return string
     */
    public static function getDriverRegistrationMessage()
    {
        $companyName = self::getCompanyName();
        return self::getConfigValue('message_driver_registration', 
            "Cher livreur, Votre demande d'inscription a été reçue avec succès. Nous vous remercions de votre intérêt pour rejoindre l'équipe {$companyName}. Notre équipe examinera votre candidature et vous contactera dans les plus brefs délais. Merci de vouloir faire partie de la famille {$companyName}. Cordialement, L'équipe {$companyName}", 
            'string');
    }
    
    /**
     * Récupérer le message d'inscription restaurant
     * 
     * @return string
     */
    public static function getRestaurantRegistrationMessage()
    {
        $companyName = self::getCompanyName();
        return self::getConfigValue('message_restaurant_registration', 
            "Cher partenaire, Votre demande d'inscription a été reçue avec succès. Nous vous remercions de votre confiance et vous souhaitons la bienvenue sur {$companyName}. Notre équipe examinera votre demande et vous contactera dans les plus brefs délais. Merci de faire partie de la famille {$companyName}. Cordialement, L'équipe {$companyName}", 
            'string');
    }
    
    /**
     * Récupérer le sujet de l'email d'inscription
     * 
     * @return string
     */
    public static function getRegistrationEmailSubject()
    {
        $companyName = self::getCompanyName();
        return self::getConfigValue('email_subject_registration', 
            "{$companyName} | Confirmation d'inscription", 
            'string');
    }
    
    /**
     * Récupérer le User-Agent pour les requêtes HTTP
     * 
     * @return string
     */
    public static function getUserAgent()
    {
        $companyName = self::getCompanyName();
        $contactEmail = self::getContactEmail();
        return self::getConfigValue('http_user_agent', 
            "{$companyName}/1.0 ({$contactEmail})", 
            'string');
    }
    
    /**
     * Invalider le cache de configuration
     */
    public static function clearCache()
    {
        $keys = [
            'config_default_delivery_fee',
            'config_default_delivery_time_min',
            'config_default_delivery_time_max',
            'config_default_rating',
            'config_top_rated_threshold',
            'config_top_rated_min_reviews',
            'config_company_name',
            'config_noreply_email',
            'config_admin_email',
            'config_contact_email',
            'config_message_user_registration',
            'config_message_driver_registration',
            'config_message_restaurant_registration',
            'config_email_subject_registration',
            'config_http_user_agent',
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}

