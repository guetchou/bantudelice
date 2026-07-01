<?php

return [
    'enabled' => env('PAYMENT_FINANCIAL_CORE_ENABLED', false),
    'shadow_mode' => env('PAYMENT_FINANCIAL_CORE_SHADOW_MODE', true),
    'currency' => env('PAYMENT_FINANCIAL_CORE_CURRENCY', 'XAF'),

    'allocation' => [
        'strict' => env('PAYMENT_FINANCIAL_CORE_STRICT_ALLOCATION', true),
    ],

    'reconciliation' => [
        'stale_after_seconds' => (int) env('PAYMENT_RECONCILIATION_STALE_AFTER_SECONDS', 120),
        'manual_review_statuses' => ['unknown', 'reversed', 'disputed'],
    ],

    'rollout' => [
        'write_journal' => env('PAYMENT_FINANCIAL_CORE_WRITE_JOURNAL', false),
        'read_dashboard_from_journal' => env('PAYMENT_FINANCIAL_CORE_READ_DASHBOARD', false),
        'block_unallocated_confirmation' => env('PAYMENT_FINANCIAL_CORE_BLOCK_UNALLOCATED', false),
    ],
];
