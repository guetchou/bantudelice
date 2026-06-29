<?php

return [
    'workspaces' => [
        'bantudelice' => [
            'label' => 'BantuDelice',
            'short_label' => 'Food',
            'color' => '#009543',
            'dashboard_route' => 'admin.dashboard',
            'sections' => [
                [
                    'label' => 'Exploitation',
                    'items' => [
                        ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'icon' => 'fas fa-gauge-high', 'nav' => 'dashboard'],
                        ['label' => 'Commandes', 'route' => 'admin.all_orders', 'icon' => 'fas fa-bag-shopping', 'nav' => 'orders'],
                        ['label' => 'Restaurants', 'route' => 'restaurant.index', 'icon' => 'fas fa-utensils', 'nav' => 'restaurants'],
                        ['label' => 'Livreurs', 'route' => 'driver.index', 'icon' => 'fas fa-motorcycle', 'nav' => 'drivers'],
                        ['label' => 'Support', 'route' => 'admin.support-tickets.index', 'icon' => 'fas fa-headset', 'nav' => 'support'],
                    ],
                ],
                [
                    'label' => 'Finance',
                    'collapsible' => true,
                    'items' => [
                        ['label' => 'Paiements', 'route' => 'admin.payments.dashboard', 'icon' => 'fas fa-wallet', 'nav' => 'payments'],
                        ['label' => 'Reversements restaurants', 'route' => 'restaurant_payout', 'icon' => 'fas fa-store', 'nav' => 'payouts-restaurants'],
                        ['label' => 'Reversements livreurs', 'route' => 'driver_payout', 'icon' => 'fas fa-money-bill-transfer', 'nav' => 'payouts-drivers'],
                        ['label' => 'Analytique commerciale', 'route' => 'admin.commerce-analytics.index', 'icon' => 'fas fa-chart-line', 'nav' => 'commerce-analytics'],
                    ],
                ],
                [
                    'label' => 'Catalogue et contenu',
                    'collapsible' => true,
                    'items' => [
                        ['label' => 'Produits', 'route' => 'total.pro', 'icon' => 'fas fa-burger', 'nav' => 'products'],
                        ['label' => 'Cuisines', 'route' => 'cuisine.index', 'icon' => 'fas fa-tags', 'nav' => 'cuisine'],
                        ['label' => 'Promotions', 'route' => 'admin.promotions.index', 'icon' => 'fas fa-percent', 'nav' => 'promotions'],
                        ['label' => 'CMS', 'route' => 'admin.cms.dashboard', 'icon' => 'fas fa-newspaper', 'nav' => 'cms'],
                        ['label' => 'Actualités', 'route' => 'news.index', 'icon' => 'fas fa-bullhorn', 'nav' => 'news'],
                    ],
                ],
                [
                    'label' => 'Administration food',
                    'collapsible' => true,
                    'items' => [
                        ['label' => 'Utilisateurs', 'route' => 'user.index', 'icon' => 'fas fa-users', 'nav' => 'users'],
                        ['label' => 'Tarification et frais', 'route' => 'charge.index', 'icon' => 'fas fa-sliders', 'nav' => 'settings'],
                    ],
                ],
            ],
        ],
        'kende' => [
            'label' => 'Kende',
            'short_label' => 'Kende',
            'color' => '#f97316',
            'dashboard_route' => 'admin.transport.dashboard',
            'sections' => [
                [
                    'label' => 'Transport',
                    'items' => [
                        ['label' => 'Tableau de bord', 'route' => 'admin.transport.dashboard', 'icon' => 'fas fa-gauge-high', 'nav' => 'kende-dashboard'],
                        ['label' => 'Réservations', 'route' => 'admin.transport.bookings.index', 'icon' => 'fas fa-route', 'nav' => 'transport'],
                        ['label' => 'Véhicules', 'route' => 'admin.transport.vehicles.index', 'icon' => 'fas fa-car-side', 'nav' => 'vehicles'],
                        ['label' => 'Tarification', 'route' => 'admin.transport.pricing.index', 'icon' => 'fas fa-tags', 'nav' => 'transport-pricing'],
                    ],
                ],
            ],
        ],
        'mema' => [
            'label' => 'Mema',
            'short_label' => 'Mema',
            'color' => '#2563eb',
            'dashboard_route' => 'admin.colis.index',
            'sections' => [
                [
                    'label' => 'Logistique',
                    'items' => [
                        ['label' => 'Colis', 'route' => 'admin.colis.index', 'icon' => 'fas fa-box', 'nav' => 'colis'],
                        ['label' => 'Finance colis', 'route' => 'admin.colis.finance', 'icon' => 'fas fa-file-invoice-dollar', 'nav' => 'colis-finance'],
                        ['label' => 'Points relais', 'route' => 'admin.relay-points.index', 'icon' => 'fas fa-location-dot', 'nav' => 'relay-points'],
                    ],
                ],
            ],
        ],
    ],

    'platform' => [
        'label' => 'Plateforme',
        'collapsible' => true,
        'items' => [
            ['label' => 'Portail des applications', 'route' => 'admin.portal', 'icon' => 'fas fa-border-all', 'nav' => 'portal'],
            ['label' => 'Modules', 'route' => 'admin.modules.index', 'icon' => 'fas fa-puzzle-piece', 'nav' => 'modules'],
            ['label' => 'Métriques techniques', 'route' => 'admin.metrics', 'icon' => 'fas fa-chart-column', 'nav' => 'metrics'],
            ['label' => 'Configuration API', 'route' => 'admin.api.configuration', 'icon' => 'fas fa-plug', 'nav' => 'api-configuration'],
            ['label' => 'Journal d’audit', 'route' => 'admin.audit_trail', 'icon' => 'fas fa-shield-halved', 'nav' => 'audit-trail'],
        ],
    ],
];
