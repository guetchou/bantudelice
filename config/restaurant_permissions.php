<?php

return [
    'roles' => [
        'owner' => ['all'],
        'manager' => [
            'dashboard.view', 'orders.view', 'orders.manage', 'kitchen.manage',
            'cash.collect', 'catalog.manage', 'promotions.manage', 'finance.view',
            'staff.manage', 'settings.manage',
        ],
        'kitchen' => ['dashboard.view', 'orders.view', 'orders.manage', 'kitchen.manage'],
        'cashier' => ['dashboard.view', 'orders.view', 'cash.collect', 'finance.view'],
        'catalog' => ['dashboard.view', 'catalog.manage', 'promotions.manage'],
        'viewer' => ['dashboard.view', 'orders.view'],
    ],
];
