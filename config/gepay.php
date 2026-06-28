<?php

$envBool = static fn (string $key, bool $default = false): bool => filter_var(
    env($key, $default),
    FILTER_VALIDATE_BOOLEAN
);

return [
    'enabled' => $envBool('GEPAY_ENABLED', true),
    'signature_tolerance_seconds' => (int) env('GEPAY_SIGNATURE_TOLERANCE_SECONDS', 300),
    'submission_claim_timeout_seconds' => (int) env('GEPAY_SUBMISSION_CLAIM_TIMEOUT_SECONDS', 120),
    'default_provider' => env('GEPAY_DEFAULT_PROVIDER', 'mtn_momo'),

    'internal_client_uuid' => env('GEPAY_INTERNAL_CLIENT_UUID'),

    'bantudelice' => [
        'collections_enabled' => $envBool('GEPAY_BANTUDELICE_COLLECTIONS_ENABLED', false),
        'withdrawals_enabled' => $envBool('GEPAY_BANTUDELICE_WITHDRAWALS_ENABLED', false),
    ],

    'providers' => [
        'mtn_momo' => [
            'enabled' => $envBool('GEPAY_MTN_ENABLED', false),
            'environment' => env('GEPAY_MTN_ENVIRONMENT', 'sandbox'),
            'target_environment' => env(
                'GEPAY_MTN_TARGET_ENVIRONMENT',
                env('GEPAY_MTN_ENVIRONMENT', 'sandbox') === 'sandbox' ? 'sandbox' : 'mtncongo'
            ),
            'base_url' => [
                'sandbox' => env('GEPAY_MTN_SANDBOX_URL', 'https://sandbox.momodeveloper.mtn.com'),
                'production' => env('GEPAY_MTN_PRODUCTION_URL', 'https://proxy.momoapi.mtn.com'),
            ],
            'callback_url' => env('GEPAY_MTN_CALLBACK_URL'),
            'collections' => [
                'subscription_key' => env('GEPAY_MTN_COLLECTIONS_SUBSCRIPTION_KEY'),
                'api_user' => env('GEPAY_MTN_COLLECTIONS_API_USER'),
                'api_key' => env('GEPAY_MTN_COLLECTIONS_API_KEY'),
            ],
            'disbursements' => [
                'subscription_key' => env('GEPAY_MTN_DISBURSEMENTS_SUBSCRIPTION_KEY'),
                'api_user' => env('GEPAY_MTN_DISBURSEMENTS_API_USER'),
                'api_key' => env('GEPAY_MTN_DISBURSEMENTS_API_KEY'),
            ],
            'remittances' => [
                'enabled' => $envBool('GEPAY_MTN_REMITTANCES_ENABLED', false),
                'subscription_key' => env('GEPAY_MTN_REMITTANCES_SUBSCRIPTION_KEY'),
                'api_user' => env('GEPAY_MTN_REMITTANCES_API_USER'),
                'api_key' => env('GEPAY_MTN_REMITTANCES_API_KEY'),
                'token_path' => env('GEPAY_MTN_REMITTANCES_TOKEN_PATH'),
            ],
        ],
    ],
];
