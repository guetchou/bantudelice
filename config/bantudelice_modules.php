<?php

return [
    'food' => [
        'enabled' => env('MODULE_FOOD_ENABLED', true),
        'label' => 'Repas',
        'queue' => env('QUEUE_FOOD', 'food'),
        'tables' => [
            'orders',
            'deliveries',
            'payments',
        ],
    ],
    'colis' => [
        'enabled' => env('MODULE_COLIS_ENABLED', true),
        'label' => 'Mema',
        'queue' => env('QUEUE_COLIS', 'colis'),
        'tables' => [
            'shipments',
            'shipment_events',
        ],
    ],
    'transport' => [
        'enabled' => env('MODULE_TRANSPORT_ENABLED', true),
        'label' => 'Transport',
        'queue' => env('QUEUE_TRANSPORT', 'transport'),
        'tables' => [
            'transport_bookings',
        ],
    ],
];
