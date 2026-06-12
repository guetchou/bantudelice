<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthBrandingService
{
    public function resolve(?Request $request = null): array
    {
        $request = $request ?: request();
        $intentPath = $this->resolveIntentPath($request);
        $brandKey = $this->resolveBrandKey($intentPath, $request);

        return $this->brandConfig($brandKey);
    }

    protected function resolveIntentPath(Request $request): string
    {
        $candidates = [
            (string) $request->query('redirect', ''),
            (string) $request->session()->get('url.intended', ''),
            '/' . ltrim((string) $request->path(), '/'),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (Str::startsWith($candidate, ['http://', 'https://'])) {
                $candidate = (string) parse_url($candidate, PHP_URL_PATH);
            }

            if (Str::startsWith($candidate, ['/'])) {
                return '/' . ltrim($candidate, '/');
            }
        }

        return '/';
    }

    protected function resolveBrandKey(string $intentPath, Request $request): string
    {
        $queryBrand = strtolower(trim((string) $request->query('brand', '')));
        if (in_array($queryBrand, ['bantudelice', 'mema', 'kende'], true)) {
            return $queryBrand;
        }

        if ($request->hasSession()) {
            $sessionBrand = strtolower(trim((string) $request->session()->get('frontend_brand', '')));
            if (in_array($sessionBrand, ['bantudelice', 'mema', 'kende'], true)) {
                return $sessionBrand;
            }
        }

        $paths = [
            strtolower($intentPath),
            '/' . ltrim(strtolower((string) $request->path()), '/'),
        ];

        foreach ($paths as $candidate) {
            if ($candidate === '/' || $candidate === '') {
                continue;
            }

            if (Str::startsWith($candidate, ['/transport', '/driver/transport'])) {
                return 'kende';
            }

            if (Str::startsWith($candidate, ['/livraison-colis', '/suivi-colis', '/mes-colis', '/colis'])) {
                return 'mema';
            }
        }

        return 'bantudelice';
    }

    protected function brandConfig(string $brandKey): array
    {
        $brands = [
            'bantudelice' => [
                'key' => 'bantudelice',
                'name' => 'BantuDelice',
                'label' => 'Food delivery',
                'primary' => '#009543',
                'primary_dark' => '#007836',
                'primary_soft' => 'rgba(0, 149, 67, 0.12)',
                'secondary' => '#22c55e',
                'surface' => '#f0fdf4',
                'hero_title' => 'Commandez vos plats préférés',
                'hero_title_emphasis' => 'préférés',
                'hero_description' => 'Retrouvez vos restaurants favoris, suivez vos commandes et profitez d\'une expérience food pensée pour BantuDelice.',
                'login_title' => 'Connexion | BantuDelice',
                'login_description' => 'Connectez-vous à votre compte BantuDelice pour commander vos plats préférés.',
                'signup_title' => 'Inscription | BantuDelice',
                'signup_description' => 'Créez votre compte BantuDelice et commencez à commander vos plats préférés.',
                'forgot_title' => 'Mot de passe oublié | BantuDelice',
                'forgot_description' => 'Réinitialisez votre mot de passe BantuDelice.',
                'features' => [
                    ['icon' => 'fa-bolt', 'title' => 'Livraison express', 'description' => 'Vos plats livrés en 30 min en moyenne'],
                    ['icon' => 'fa-map-marker-alt', 'title' => 'Suivi en temps réel', 'description' => 'Suivez votre commande sur la carte GPS'],
                    ['icon' => 'fa-percent', 'title' => 'Offres exclusives', 'description' => 'Profitez de réductions membres'],
                    ['icon' => 'fa-shield-alt', 'title' => 'Paiement sécurisé', 'description' => 'Vos données sont protégées'],
                ],
                'signup_features' => [
                    ['icon' => 'fa-gift', 'title' => 'Bonus de bienvenue', 'description' => '10% de réduction sur votre première commande'],
                    ['icon' => 'fa-history', 'title' => 'Historique des commandes', 'description' => 'Retrouvez et recommandez facilement'],
                    ['icon' => 'fa-heart', 'title' => 'Restaurants favoris', 'description' => 'Sauvegardez vos adresses préférées'],
                    ['icon' => 'fa-bell', 'title' => 'Notifications', 'description' => 'Restez informé des offres et promotions'],
                ],
                'forgot_hint' => 'Pour votre sécurité, vous devez fournir l\'email et le numéro de téléphone associés à votre compte.',
                'help_label' => 'Centre d\'aide',
                'support_intro' => 'Aide pour les commandes, livraisons, paiements et gestion du compte BantuDelice.',
                'privacy_intro' => 'Protection des données personnelles liées aux commandes, paiements et comptes BantuDelice.',
                'legal_intro' => 'Informations juridiques, techniques et d\'hébergement de la plateforme BantuDelice.',
                'cookies_intro' => 'Cookies et technologies similaires utilisés sur les parcours BantuDelice.',
                'data_deletion_intro' => 'Procédure de suppression et d\'anonymisation des données pour les comptes BantuDelice.',
                'contact_intro' => 'Contactez l\'équipe BantuDelice pour une commande, un compte ou un besoin commercial.',
                'support_email' => 'contact@bantudelice.cg',
                'support_phone' => '+242 06 400 00 00',
                'support_whatsapp' => 'https://wa.me/242064000000',
                'socials' => [
                    'facebook' => 'https://www.facebook.com/BantuDelice',
                    'instagram' => 'https://www.instagram.com/bantudelice.cg/',
                    'tiktok' => 'https://www.tiktok.com/@bantudelice',
                ],
            ],
            'kende' => [
                'key' => 'kende',
                'name' => 'Kende',
                'label' => 'Transport',
                'primary' => '#FF6B00',
                'primary_dark' => '#D95700',
                'primary_soft' => 'rgba(255, 107, 0, 0.12)',
                'secondary' => '#ff9b52',
                'surface' => '#fff7ed',
                'hero_title' => 'Réservez vos trajets avec Kende',
                'hero_title_emphasis' => 'Kende',
                'hero_description' => 'Connectez-vous pour retrouver vos courses, réserver un taxi ou suivre vos déplacements dans l\'univers transport.',
                'login_title' => 'Connexion | Kende',
                'login_description' => 'Connectez-vous à votre compte Kende pour gérer vos réservations et vos trajets.',
                'signup_title' => 'Inscription | Kende',
                'signup_description' => 'Créez votre compte Kende et commencez à réserver vos trajets.',
                'forgot_title' => 'Mot de passe oublié | Kende',
                'forgot_description' => 'Réinitialisez votre mot de passe Kende.',
                'features' => [
                    ['icon' => 'fa-taxi', 'title' => 'Réservation rapide', 'description' => 'Taxi, covoiturage et location depuis un seul espace'],
                    ['icon' => 'fa-route', 'title' => 'Suivi de course', 'description' => 'Gardez la main sur vos trajets et estimations'],
                    ['icon' => 'fa-car-side', 'title' => 'Chauffeurs vérifiés', 'description' => 'Des courses plus lisibles et plus fiables'],
                    ['icon' => 'fa-clock', 'title' => 'Historique transport', 'description' => 'Retrouvez vos réservations et vos reçus'],
                ],
                'signup_features' => [
                    ['icon' => 'fa-taxi', 'title' => 'Taxi à la demande', 'description' => 'Réservez rapidement selon votre besoin'],
                    ['icon' => 'fa-map-location-dot', 'title' => 'Points de départ clairs', 'description' => 'Adresse de départ et destination bien suivies'],
                    ['icon' => 'fa-wallet', 'title' => 'Paiement plus fluide', 'description' => 'Confirmez vos réservations avec moins de friction'],
                    ['icon' => 'fa-bell', 'title' => 'Notifications de trajet', 'description' => 'Soyez alerté des mises à jour importantes'],
                ],
                'forgot_hint' => 'Renseignez les informations associées à votre compte Kende pour récupérer l\'accès à vos réservations.',
                'help_label' => 'Support Kende',
                'support_intro' => 'Aide pour les réservations, trajets, paiements et incidents transport Kende.',
                'privacy_intro' => 'Protection des données liées aux réservations, trajets, géolocalisation et paiements Kende.',
                'legal_intro' => 'Informations juridiques et d\'exploitation de l\'espace transport Kende.',
                'cookies_intro' => 'Cookies et technologies similaires utilisés sur les parcours Kende.',
                'data_deletion_intro' => 'Procédure de suppression et d\'anonymisation des données pour les comptes Kende.',
                'contact_intro' => 'Contactez l\'équipe Kende pour un trajet, une réclamation ou un partenariat transport.',
                'support_email' => 'transport@kende.cg',
                'support_phone' => '+242 06 400 00 01',
                'support_whatsapp' => 'https://wa.me/242064000001',
                'socials' => [
                    'facebook' => null,
                    'instagram' => null,
                    'tiktok' => null,
                ],
            ],
            'mema' => [
                'key' => 'mema',
                'name' => 'Mema',
                'label' => 'Colis',
                'primary' => '#2448FF',
                'primary_dark' => '#1836C7',
                'primary_soft' => 'rgba(36, 72, 255, 0.12)',
                'secondary' => '#00a86b',
                'surface' => '#eef4ff',
                'hero_title' => 'Expédiez et suivez avec Mema',
                'hero_title_emphasis' => 'Mema',
                'hero_description' => 'Connectez-vous pour créer un envoi, suivre vos colis et centraliser vos expéditions sans repasser par l\'univers food.',
                'login_title' => 'Connexion | Mema',
                'login_description' => 'Connectez-vous à votre compte Mema pour gérer vos colis et vos expéditions.',
                'signup_title' => 'Inscription | Mema',
                'signup_description' => 'Créez votre compte Mema et commencez à expédier vos colis.',
                'forgot_title' => 'Mot de passe oublié | Mema',
                'forgot_description' => 'Réinitialisez votre mot de passe Mema.',
                'features' => [
                    ['icon' => 'fa-box-open', 'title' => 'Expédition simplifiée', 'description' => 'Créez vos envois avec les bonnes informations dès le départ'],
                    ['icon' => 'fa-location-dot', 'title' => 'Suivi clair', 'description' => 'Consultez l\'état et la progression de chaque colis'],
                    ['icon' => 'fa-file-invoice', 'title' => 'Historique centralisé', 'description' => 'Retrouvez vos envois, paiements et confirmations'],
                    ['icon' => 'fa-shield-alt', 'title' => 'Données sécurisées', 'description' => 'Vos informations de livraison restent protégées'],
                ],
                'signup_features' => [
                    ['icon' => 'fa-box', 'title' => 'Création d\'envoi rapide', 'description' => 'Préparez un colis en quelques étapes'],
                    ['icon' => 'fa-truck-fast', 'title' => 'Suivi opérationnel', 'description' => 'Gardez une vue claire sur chaque remise'],
                    ['icon' => 'fa-user-check', 'title' => 'Compte client dédié', 'description' => 'Séparez vos expéditions du reste de la plateforme'],
                    ['icon' => 'fa-bell', 'title' => 'Alertes utiles', 'description' => 'Recevez les mises à jour importantes de vos colis'],
                ],
                'forgot_hint' => 'Renseignez les informations de votre compte Mema pour retrouver l\'accès à vos expéditions et suivis.',
                'help_label' => 'Support Mema',
                'support_intro' => 'Aide pour les expéditions, suivis, remises, paiements et réclamations Mema.',
                'privacy_intro' => 'Protection des données liées aux colis, destinataires, suivi et paiements Mema.',
                'legal_intro' => 'Informations juridiques et d\'exploitation de l\'espace colis Mema.',
                'cookies_intro' => 'Cookies et technologies similaires utilisés sur les parcours Mema.',
                'data_deletion_intro' => 'Procédure de suppression et d\'anonymisation des données pour les comptes Mema.',
                'contact_intro' => 'Contactez l\'équipe Mema pour un envoi, un suivi ou une réclamation colis.',
                'support_email' => 'support@mema.cg',
                'support_phone' => '+242 06 400 00 02',
                'support_whatsapp' => 'https://wa.me/242064000002',
                'socials' => [
                    'facebook' => null,
                    'instagram' => null,
                    'tiktok' => null,
                ],
            ],
        ];

        return $brands[$brandKey] ?? $brands['bantudelice'];
    }
}
