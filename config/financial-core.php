<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Canonical financial engine
    |--------------------------------------------------------------------------
    |
    | This setting documents which financial core is allowed to become the
    | production source of truth. It does not activate a ledger by itself.
    |
    */
    'engine' => env('FINANCIAL_CORE_ENGINE', 'legacy'),

    'allowed_engines' => [
        'legacy',
        'partner_ledger_v2',
        'payment_domain_foundation',
        'payment_financial_core',
        'payment_business_release',
    ],

    /*
    | Refuse a deployment when multiple migrations create the same business
    | table. Keep enabled in CI and production deployment pipelines.
    */
    'fail_on_migration_conflict' => filter_var(
        env('FINANCIAL_CORE_FAIL_ON_CONFLICT', true),
        FILTER_VALIDATE_BOOLEAN
    ),
];
