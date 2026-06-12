<?php

return [
    'enabled' => env('MOBILE_MONEY_BRIDGE_ENABLED', true),
    'tolerance_seconds' => (int) env('MOBILE_MONEY_BRIDGE_TOLERANCE_SECONDS', 300),
    'clients' => array_filter([
        env('MOBILE_MONEY_BRIDGE_CLIENT_KEY', 'bridge-default') => [
            'name' => env('MOBILE_MONEY_BRIDGE_CLIENT_NAME', 'Default Bridge Client'),
            'secret' => env('MOBILE_MONEY_BRIDGE_CLIENT_SECRET'),
        ],
    ], function ($client) {
        return !empty($client['secret']);
    }),
    'service_user' => [
        'name' => env('MOBILE_MONEY_BRIDGE_SERVICE_NAME', 'BantuDelice Payments Bridge'),
        'email' => env('MOBILE_MONEY_BRIDGE_SERVICE_EMAIL', 'payments-bridge@bantudelice.cg'),
        'phone' => env('MOBILE_MONEY_BRIDGE_SERVICE_PHONE', '060000000'),
    ],
];
