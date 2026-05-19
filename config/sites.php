<?php

return [
    'default_site' => env('SITE_DEFAULT_KEY', 'main'),
    'fallback_locale' => env('SITE_FALLBACK_LOCALE', 'fr'),
    'ecosystem' => [
        'transport' => [
            'name' => env('SITE_TRANSPORT_NAME', 'Transport'),
            'url' => env('SITE_TRANSPORT_URL', '/transport/taxi'),
            'description' => env('SITE_TRANSPORT_DESCRIPTION', 'Reservation taxi depuis l espace transport dedie.'),
            'icon' => 'fa-car-side',
            'badge' => 'Disponible',
        ],
        'colis' => [
            'name' => env('SITE_COLIS_NAME', 'Mema'),
            'url' => env('SITE_COLIS_URL', '/suivi-colis'),
            'description' => env('SITE_COLIS_DESCRIPTION', 'Expedition et suivi de colis avec Mema.'),
            'icon' => 'fa-box-open',
            'badge' => 'Disponible',
        ],
        'salisa' => [
            'name' => env('SITE_SALISA_NAME', 'Salisa'),
            'url' => env('SITE_SALISA_URL', 'https://salisa.cg'),
            'description' => env('SITE_SALISA_DESCRIPTION', 'Freelance et missions, dans un espace separe.'),
            'icon' => 'fa-briefcase',
            'badge' => 'Bientot',
        ],
        'kosunga' => [
            'name' => env('SITE_KOSUNGA_NAME', 'Kosunga'),
            'url' => env('SITE_KOSUNGA_URL', 'https://kosunga.cg'),
            'description' => env('SITE_KOSUNGA_DESCRIPTION', 'Sante et teleconsultation dans un espace dedie.'),
            'icon' => 'fa-stethoscope',
            'badge' => 'Bientot',
        ],
    ],

    'sites' => [
        'main' => [
            'name' => env('SITE_MAIN_NAME', 'BantuDelice'),
            'domains' => array_values(array_filter([
                env('SITE_MAIN_DOMAIN', 'bantudelice.cg'),
                env('SITE_MAIN_DOMAIN_WWW', 'www.bantudelice.cg'),
                parse_url(env('APP_URL', 'https://bantudelice.cg'), PHP_URL_HOST),
                'localhost',
                '127.0.0.1',
            ])),
            'default_locale' => env('SITE_MAIN_LOCALE', 'fr'),
            'supported_locales' => [
                'fr' => 'Français',
                'en' => 'English',
            ],
            'theme' => 'modern',
            'active' => true,
        ],
        'market' => [
            'name' => env('SITE_MARKET_NAME', 'BantuDelice Market'),
            'domains' => array_values(array_filter([
                env('SITE_MARKET_DOMAIN', null),
                env('SITE_MARKET_DOMAIN_WWW', null),
            ])),
            'default_locale' => env('SITE_MARKET_LOCALE', 'fr'),
            'supported_locales' => [
                'fr' => 'Français',
                'en' => 'English',
            ],
            'theme' => 'market',
            'active' => filter_var(env('SITE_MARKET_ACTIVE', false), FILTER_VALIDATE_BOOL),
        ],
    ],
];
