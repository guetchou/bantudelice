<?php

namespace App\Services;

use App\Services\CmsHomeContentService;
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
    const HOME_CONTENT_CACHE_KEY_PREFIX = 'config_home_content_';
    
    /**
     * Récupérer une valeur de configuration depuis system_config
     * 
     * @param string $key
     * @param mixed $defaultValue
     * @param string $type
     * @return mixed
     */
    public static function getConfigValue($key, $defaultValue, $type = 'string')
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

    public static function clearHomeContentCache(?string $workspace = null): void
    {
        $workspaces = $workspace ? [self::resolveHomeWorkspace($workspace)] : ['bantudelice', 'kende', 'mema'];

        foreach ($workspaces as $workspaceKey) {
            Cache::forget(self::homeContentCacheKey($workspaceKey));
        }

        foreach ([
            'home_hero_badge',
            'home_hero_title_line_1',
            'home_hero_title_line_2',
            'home_hero_description',
            'home_hero_main_image',
            'home_hero_colis_image',
            'home_hero_transport_image',
            'home_restaurants_title',
            'home_restaurants_subtitle',
            'home_services_title',
            'home_services_subtitle',
            'home_service_food_image',
            'home_service_colis_image',
            'home_service_transport_image',
            'home_mosaic_cuisine_image',
            'home_mosaic_driver_image',
            'home_mosaic_restaurant_image',
            'home_support_title',
            'home_support_description',
            'home_support_cta_text',
            'home_popular_products_title',
            'home_popular_products_subtitle',
        ] as $key) {
            Cache::forget('config_' . $key);
        }
    }

    public static function getHomeContent(?string $workspace = null): array
    {
        $workspace = self::resolveHomeWorkspace($workspace);

        return Cache::remember(self::homeContentCacheKey($workspace), self::CACHE_DURATION, function () use ($workspace) {
            return array_merge(
                self::getHomeContentDefaults($workspace),
                app(CmsHomeContentService::class)->getHomeContent($workspace) ?? []
            );
        });
    }

    private static function homeContentCacheKey(string $workspace): string
    {
        return self::HOME_CONTENT_CACHE_KEY_PREFIX . $workspace;
    }

    private static function resolveHomeWorkspace(?string $workspace = null): string
    {
        $workspace = $workspace ?: request('workspace') ?: request('brand');

        return in_array($workspace, ['bantudelice', 'kende', 'mema'], true) ? $workspace : 'bantudelice';
    }

    public static function getLegacyHomeContent(): array
    {
        return self::workspaceHomeSeed('bantudelice');
    }

    public static function getHomeContentDefaults(?string $workspace = null): array
    {
        return self::workspaceHomeSeed(self::resolveHomeWorkspace($workspace));
    }

    private static function workspaceHomeSeed(string $workspace): array
    {
        return match ($workspace) {
            'kende' => [
                'hero_badge' => 'Brazzaville • Pointe-Noire • transport',
                'hero_title_line_1' => 'Reservez un trajet',
                'hero_title_line_2' => 'sans attente inutile.',
                'hero_description' => "Kende vous aide a estimer, confirmer et suivre vos trajets urbains avec un parcours clair, rapide et adapte au terrain congolais.",
                'hero_main_image' => 'images/home/service-transport.jpg',
                'hero_colis_image' => 'images/home/service-transport.jpg',
                'hero_transport_image' => 'images/home/service-transport.jpg',
                'restaurants_title' => 'Courses recentes',
                'restaurants_subtitle' => 'Demandes confirmees, zones actives et flux de reservations',
                'services_title' => 'Taxi, reservation et flotte',
                'services_subtitle' => "Kende se concentre sur l attribution rapide, la lisibilite tarifaire et la supervision des trajets a Brazzaville et Pointe-Noire.",
                'service_food_image' => 'images/home/service-restaurant.jpg',
                'service_colis_image' => 'images/home/service-transport.jpg',
                'service_transport_image' => 'images/home/service-transport.jpg',
                'support_title' => "Besoin d'un accompagnement trajet ?",
                'support_description' => "Le support Kende repond pour les reservations, les zones desservies, les tarifs et les incidents de prise en charge.",
                'support_cta_text' => 'Contacter le support Kende',
                'popular_products_title' => 'Formules disponibles',
                'popular_products_subtitle' => 'Eco, Confort et XL selon le besoin terrain',
                'testimonials_tag' => 'Retours clients',
                'testimonials_title' => 'Des trajets plus lisibles',
                'testimonials_subtitle' => 'Tarif affiche avant confirmation et suivi plus simple des courses.',
                'testimonial_1_tag' => 'Taxi urbain',
                'testimonial_1_quote' => 'Le tarif est clair avant validation et le chauffeur arrive avec un suivi compréhensible.',
                'testimonial_1_name' => 'Mireille T.',
                'testimonial_1_loc' => 'Moungali, Brazzaville',
                'testimonial_2_tag' => 'Course planifiee',
                'testimonial_2_quote' => 'Reservation rapide pour un trajet tot le matin, sans appels repetes ni confusion.',
                'testimonial_2_name' => 'Armel N.',
                'testimonial_2_loc' => 'Pointe-Noire',
                'testimonial_3_tag' => 'Trajet professionnel',
                'testimonial_3_quote' => 'Kende reste plus rassurant pour les deplacements prevus et les confirmations importantes.',
                'testimonial_3_name' => 'Grace B.',
                'testimonial_3_loc' => 'Centre-ville, Brazzaville',
                'opportunities_tag' => 'Opportunites transport',
                'opportunities_title' => 'Grandissez avec Kende',
                'opportunities_subtitle' => 'Developpez votre activite de conduite, de flotte ou d exploitation transport.',
                'opportunity_1_title' => 'Devenir chauffeur',
                'opportunity_1_body' => "Rejoignez Kende pour accepter des courses avec un cadre plus lisible et un suivi d activite simple.",
                'opportunity_1_cta' => 'Postuler',
                'opportunity_1_url' => route('driver'),
                'opportunity_1_image' => 'images/home/service-driver.jpg',
                'opportunity_2_title' => 'Gerer une flotte',
                'opportunity_2_body' => 'Ajoutez des vehicules, supervisez les disponibilites et structurez vos operations transport.',
                'opportunity_2_cta' => 'Devenir partenaire',
                'opportunity_2_url' => route('partner'),
                'opportunity_2_image' => 'images/home/service-transport.jpg',
                'opportunity_3_title' => 'Parler a l equipe',
                'opportunity_3_body' => 'Besoin d un cadrage sur une reservation, une zone ou un partenariat transport ?',
                'opportunity_3_cta' => 'Nous contacter',
                'opportunity_3_url' => route('contact.us', ['brand' => 'kende']),
                'opportunity_3_image' => 'images/home/service-transport.jpg',
            ],
            'mema' => [
                'hero_badge' => 'Brazzaville • Pointe-Noire • colis',
                'hero_title_line_1' => 'Expediez et suivez',
                'hero_title_line_2' => 'vos colis sans rupture.',
                'hero_description' => "Mema rend l expedition, le suivi et la reclamation plus clairs pour les colis du quotidien, avec une lecture simple de chaque etape.",
                'hero_main_image' => 'images/home/service-transport.jpg',
                'hero_colis_image' => 'images/home/service-transport.jpg',
                'hero_transport_image' => 'images/home/service-transport.jpg',
                'restaurants_title' => 'Expeditions recentes',
                'restaurants_subtitle' => 'Flux, remise et suivi terrain par zone active',
                'services_title' => 'Expedition, suivi et reclamation',
                'services_subtitle' => "Mema se concentre sur la prise en charge, la trace de remise et la resolution des incidents colis sans surcharge inutile.",
                'service_food_image' => 'images/home/service-restaurant.jpg',
                'service_colis_image' => 'images/home/service-transport.jpg',
                'service_transport_image' => 'images/home/service-transport.jpg',
                'support_title' => "Besoin d'aide sur un colis en cours ?",
                'support_description' => "Le support Mema vous accompagne pour le suivi, la remise, les anomalies de tracking et les reclamations colis.",
                'support_cta_text' => 'Contacter le support Mema',
                'popular_products_title' => 'Parcours utiles',
                'popular_products_subtitle' => 'Suivre, expedier et reclamer depuis une meme interface',
                'testimonials_tag' => 'Retours expediteurs',
                'testimonials_title' => 'Des envois mieux traces',
                'testimonials_subtitle' => 'Suivi plus clair, preuve de remise et traitement plus propre des reclamations.',
                'testimonial_1_tag' => 'Suivi colis',
                'testimonial_1_quote' => 'Je vois mieux ou se trouve mon envoi et je sais quoi faire en cas de blocage.',
                'testimonial_1_name' => 'Cedric N.',
                'testimonial_1_loc' => 'Poto-Poto, Brazzaville',
                'testimonial_2_tag' => 'Preuve de remise',
                'testimonial_2_quote' => 'La confirmation de remise rassure vraiment pour les envois a distance.',
                'testimonial_2_name' => 'Rita L.',
                'testimonial_2_loc' => 'Pointe-Noire',
                'testimonial_3_tag' => 'Support',
                'testimonial_3_quote' => 'La reclamation est plus rapide a ouvrir et le support comprend tout de suite le contexte du colis.',
                'testimonial_3_name' => 'Arsene K.',
                'testimonial_3_loc' => 'Bacongo, Brazzaville',
                'opportunities_tag' => 'Opportunites logistiques',
                'opportunities_title' => 'Grandissez avec Mema',
                'opportunities_subtitle' => 'Developpez vos operations de livraison, de relais ou de traitement colis.',
                'opportunity_1_title' => 'Devenir livreur colis',
                'opportunity_1_body' => "Rejoignez Mema pour prendre en charge des envois avec un suivi d etapes plus clair.",
                'opportunity_1_cta' => 'Postuler',
                'opportunity_1_url' => route('driver'),
                'opportunity_1_image' => 'images/home/service-driver.jpg',
                'opportunity_2_title' => 'Ouvrir un point relais',
                'opportunity_2_body' => 'Ajoutez un relais de depot ou de retrait pour fluidifier la logistique locale.',
                'opportunity_2_cta' => 'Devenir partenaire',
                'opportunity_2_url' => route('partner'),
                'opportunity_2_image' => 'images/home/service-transport.jpg',
                'opportunity_3_title' => 'Parler a l equipe',
                'opportunity_3_body' => 'Besoin d aide pour un incident, une integration ou une demande de couverture zone ?',
                'opportunity_3_cta' => 'Nous contacter',
                'opportunity_3_url' => route('contact.us', ['brand' => 'mema']),
                'opportunity_3_image' => 'images/home/service-transport.jpg',
            ],
            default => [
            'hero_badge' => self::getConfigValue('home_hero_badge', 'Brazzaville · Pointe-Noire'),
            'hero_title_line_1' => self::getConfigValue('home_hero_title_line_1', 'Vos repas préférés,'),
            'hero_title_line_2' => self::getConfigValue('home_hero_title_line_2', 'livrés à votre porte.'),
            'hero_description' => self::getConfigValue('home_hero_description', 'Commandez en quelques secondes auprès des meilleurs restaurants de votre quartier.'),
            'hero_main_image' => self::getConfigValue('home_hero_main_image', 'images/home/service-restaurant.jpg'),
            'hero_colis_image' => self::getConfigValue('home_hero_colis_image', 'images/home/service-transport.jpg'),
            'hero_transport_image' => self::getConfigValue('home_hero_transport_image', 'images/home/service-transport.jpg'),
            'restaurants_title' => self::getConfigValue('home_restaurants_title', 'Restaurants populaires'),
            'restaurants_subtitle' => self::getConfigValue('home_restaurants_subtitle', 'Les mieux notés par nos clients'),
            'services_title' => self::getConfigValue('home_services_title', 'La livraison de repas à Brazzaville'),
            'services_subtitle' => self::getConfigValue('home_services_subtitle', 'Les meilleurs restaurants, livrés chez vous en 20–40 minutes.'),
            'service_food_image' => self::getConfigValue('home_service_food_image', 'images/home/service-restaurant.jpg'),
            'service_colis_image' => self::getConfigValue('home_service_colis_image', 'images/home/service-transport.jpg'),
            'service_transport_image' => self::getConfigValue('home_service_transport_image', 'images/home/service-transport.jpg'),
            'support_title' => self::getConfigValue('home_support_title', "Besoin d'un accompagnement ?"),
            'support_description' => self::getConfigValue('home_support_description', "Notre équipe est disponible pour vous accompagner dans votre commande de repas."),
            'support_cta_text' => self::getConfigValue('home_support_cta_text', "Contacter l'équipe"),
            'popular_products_title' => self::getConfigValue('home_popular_products_title', 'Plats populaires'),
            'popular_products_subtitle' => self::getConfigValue('home_popular_products_subtitle', 'Les plus commandés du moment'),
            'opportunity_1_image' => self::getConfigValue('home_opportunity_1_image', 'images/home/service-driver.jpg'),
            'opportunity_2_image' => self::getConfigValue('home_opportunity_2_image', 'images/home/service-restaurant.jpg'),
            'opportunity_3_image' => self::getConfigValue('home_opportunity_3_image', 'images/home/service-transport.jpg'),
            'mosaic_cuisine_image'    => self::getConfigValue('home_mosaic_cuisine_image',    'images/home/service-cuisine.jpg'),
            'mosaic_driver_image'     => self::getConfigValue('home_mosaic_driver_image',     'images/home/service-driver.jpg'),
            'mosaic_restaurant_image' => self::getConfigValue('home_mosaic_restaurant_image', 'images/home/service-restaurant.jpg'),
            ],
        };
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
