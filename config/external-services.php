<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Services de Paiement
    |--------------------------------------------------------------------------
    */

    'payments' => [
        // Mobile Money - MTN
        'mtn_momo' => [
            'enabled' => env('MTN_MOMO_ENABLED', false),
            'api_key' => env('MTN_MOMO_API_KEY'),
            'api_user' => env('MTN_MOMO_API_USER'),
            'api_secret' => env('MTN_MOMO_API_SECRET'),
            'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
            'environment' => env('MTN_MOMO_ENVIRONMENT', 'sandbox'), // sandbox | production
            'callback_url' => env('MTN_MOMO_CALLBACK_URL'),
            'base_url' => [
                'sandbox' => 'https://sandbox.momodeveloper.mtn.com',
                'production' => 'https://momodeveloper.mtn.com',
            ],
            'currency' => 'XAF',
        ],

        // Mobile Money - Airtel
        'airtel_money' => [
            'enabled' => env('AIRTEL_MONEY_ENABLED', false),
            'client_id' => env('AIRTEL_MONEY_CLIENT_ID'),
            'client_secret' => env('AIRTEL_MONEY_CLIENT_SECRET'),
            'environment' => env('AIRTEL_MONEY_ENVIRONMENT', 'sandbox'),
            'callback_url' => env('AIRTEL_MONEY_CALLBACK_URL'),
            'base_url' => [
                'sandbox' => 'https://openapiuat.airtel.africa',
                'production' => 'https://openapi.airtel.africa',
            ],
            'country' => 'CG',
            'currency' => 'XAF',
        ],

        // Stripe
        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'currency' => 'xaf',
        ],

        // PayPal
        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', false),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
            'currency' => 'XAF',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Services de Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        // Firebase Cloud Messaging
        'fcm' => [
            'enabled' => env('FCM_ENABLED', true),
            'server_key' => env('FCM_SERVER_KEY'),
            'sender_id' => env('FCM_SENDER_ID'),
            'project_id' => env('FCM_PROJECT_ID'),
            // Clés par type d'utilisateur (optionnel, pour apps séparées)
            'user_key' => env('FCM_USER_KEY'),
            'restaurant_key' => env('FCM_RESTAURANT_KEY'),
            'driver_key' => env('FCM_DRIVER_KEY'),
        ],

        // SMS via Twilio
        'twilio' => [
            'enabled' => env('TWILIO_ENABLED', false),
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
            'verify_sid' => env('TWILIO_VERIFY_SID'), // Pour OTP
        ],

        // SMS via Africa's Talking
        'africastalking' => [
            'enabled' => env('AFRICASTALKING_ENABLED', false),
            'username' => env('AFRICASTALKING_USERNAME'),
            'api_key' => env('AFRICASTALKING_API_KEY'),
            'from' => env('AFRICASTALKING_FROM', 'BantuDelice'),
        ],

        // SMS Local (Congo)
        'sms_local' => [
            'enabled' => env('SMS_LOCAL_ENABLED', false),
            'api_key' => env('SMS_LOCAL_API_KEY'),
            'sender_id' => env('SMS_LOCAL_SENDER_ID', 'BANTUDELICE'),
            'api_url' => env('SMS_LOCAL_API_URL'),
        ],

        // SMS via BulkGate
        'bulkgate' => [
            'enabled' => env('BULKGATE_ENABLED', false),
            'application_id' => env('BULKGATE_APPLICATION_ID'),
            'api_key' => env('BULKGATE_API_KEY'),
            'sender_id' => env('BULKGATE_SENDER_ID', 'BantuDelice'),
            'api_url' => 'https://portal.bulkgate.com/api/1.0/simple/transactional',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Services de Géolocalisation
    |--------------------------------------------------------------------------
    */

    'geolocation' => [
        'google_maps' => [
            'enabled' => env('GOOGLE_MAPS_ENABLED', true),
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
            'geocoding_api_key' => env('GOOGLE_GEOCODING_API_KEY'),
            'directions_api_key' => env('GOOGLE_DIRECTIONS_API_KEY'),
            'distance_matrix_api_key' => env('GOOGLE_DISTANCE_MATRIX_API_KEY'),
        ],

        // Alternative: OpenStreetMap/Nominatim (gratuit)
        'openstreetmap' => [
            'enabled' => env('OSM_ENABLED', false),
            'nominatim_url' => 'https://nominatim.openstreetmap.org',
            'osrm_url' => env('OSRM_URL', 'https://router.project-osrm.org'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Services d'Authentification Sociale
    |--------------------------------------------------------------------------
    */

    'social_auth' => [
        'google' => [
            'enabled' => env('GOOGLE_AUTH_ENABLED', false),
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
        ],

        'facebook' => [
            'enabled' => env('FACEBOOK_AUTH_ENABLED', false),
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
        ],

        'apple' => [
            'enabled' => env('APPLE_AUTH_ENABLED', false),
            'client_id' => env('APPLE_CLIENT_ID'),
            'client_secret' => env('APPLE_CLIENT_SECRET'),
            'redirect' => env('APPLE_REDIRECT_URI', '/auth/apple/callback'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Services d'Email
    |--------------------------------------------------------------------------
    */

    'email' => [
        'sendgrid' => [
            'enabled' => env('SENDGRID_ENABLED', false),
            'api_key' => env('SENDGRID_API_KEY'),
        ],

        'mailgun' => [
            'enabled' => env('MAILGUN_ENABLED', false),
            'domain' => env('MAILGUN_DOMAIN'),
            'secret' => env('MAILGUN_SECRET'),
            'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Services de Stockage
    |--------------------------------------------------------------------------
    */

    'storage' => [
        'cloudinary' => [
            'enabled' => env('CLOUDINARY_ENABLED', false),
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET'),
        ],

        'aws_s3' => [
            'enabled' => env('AWS_S3_ENABLED', false),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'eu-west-3'),
            'bucket' => env('AWS_BUCKET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Services d'Analytics
    |--------------------------------------------------------------------------
    */

    'analytics' => [
        'google_analytics' => [
            'enabled' => env('GA_ENABLED', false),
            'tracking_id' => env('GA_TRACKING_ID'),
            'measurement_id' => env('GA_MEASUREMENT_ID'), // GA4
        ],

        'mixpanel' => [
            'enabled' => env('MIXPANEL_ENABLED', false),
            'token' => env('MIXPANEL_TOKEN'),
        ],
    ],

];

