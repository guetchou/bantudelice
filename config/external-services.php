<?php

$momoEnvironment = env('MOMO_ENVIRONMENT', 'sandbox');
$momoTargetEnvironment = env('MOMO_TARGET_ENVIRONMENT', $momoEnvironment === 'sandbox' ? 'sandbox' : 'mtncongo');
$resolveMomoSubscriptionKey = static function (string $prefix): ?string {
    return env("{$prefix}_SUBSCRIPTION_KEY")
        ?: env("{$prefix}_PRIMARY_KEY")
        ?: env("{$prefix}_SECONDARY_KEY");
};
$collectionsSubscriptionKey   = $resolveMomoSubscriptionKey('MOMO_COLLECTIONS');
$disbursementsSubscriptionKey = $resolveMomoSubscriptionKey('MOMO_DISBURSEMENTS');
$remittancesSubscriptionKey   = $resolveMomoSubscriptionKey('MOMO_REMITTANCES');

return [

    /*
    |--------------------------------------------------------------------------
    | Services de Paiement
    |--------------------------------------------------------------------------
    */

    'payments' => [
        // Mobile Money - MTN MoMo
        'mtn_momo' => [
            'enabled' => !empty($collectionsSubscriptionKey)
                && !empty(env('MOMO_COLLECTIONS_API_USER'))
                && !empty(env('MOMO_COLLECTIONS_API_KEY')),
            'collections' => [
                'primary_key' => env('MOMO_COLLECTIONS_PRIMARY_KEY'),
                'secondary_key' => env('MOMO_COLLECTIONS_SECONDARY_KEY'),
                'subscription_key' => $collectionsSubscriptionKey,
                'api_user' => env('MOMO_COLLECTIONS_API_USER'),
                'api_key' => env('MOMO_COLLECTIONS_API_KEY'),
                'configured' => !empty($collectionsSubscriptionKey)
                    && !empty(env('MOMO_COLLECTIONS_API_USER'))
                    && !empty(env('MOMO_COLLECTIONS_API_KEY')),
            ],
            'disbursements' => [
                'primary_key' => env('MOMO_DISBURSEMENTS_PRIMARY_KEY'),
                'secondary_key' => env('MOMO_DISBURSEMENTS_SECONDARY_KEY'),
                'subscription_key' => $disbursementsSubscriptionKey,
                'api_user' => env('MOMO_DISBURSEMENTS_API_USER'),
                'api_key' => env('MOMO_DISBURSEMENTS_API_KEY'),
                'configured' => !empty($disbursementsSubscriptionKey)
                    && !empty(env('MOMO_DISBURSEMENTS_API_USER'))
                    && !empty(env('MOMO_DISBURSEMENTS_API_KEY')),
            ],
            'disbursement_proxy' => [
                'enabled' => filter_var(env('MOMO_DISBURSEMENT_PROXY_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
                'url' => env('MOMO_DISBURSEMENT_PROXY_URL'),
                'status_url' => env('MOMO_DISBURSEMENT_PROXY_STATUS_URL'),
                'token' => env('MOMO_DISBURSEMENT_PROXY_TOKEN'),
                'timeout' => (int) env('MOMO_DISBURSEMENT_PROXY_TIMEOUT', 90),
                'source_ip' => env('MOMO_DISBURSEMENT_PROXY_SOURCE_IP'),
            ],
            'environment' => $momoEnvironment, // sandbox | production
            'target_environment' => $momoTargetEnvironment,
            'callback_url' => env('MOMO_CALLBACK_URL'),
            'use_callback_header' => filter_var(
                env('MOMO_USE_CALLBACK_HEADER', true),
                FILTER_VALIDATE_BOOLEAN
            ),
            'base_url' => [
                'sandbox' => 'https://sandbox.momodeveloper.mtn.com',
                'production' => 'https://proxy.momoapi.mtn.com',
            ],
            'currency' => 'XAF',
            // Remittances — non utilisé en production BantuDelice pour l'instant.
            // Configurer uniquement si MTN Congo active ce produit.
            'remittances' => [
                'primary_key'      => env('MOMO_REMITTANCES_PRIMARY_KEY'),
                'secondary_key'    => env('MOMO_REMITTANCES_SECONDARY_KEY'),
                'subscription_key' => $remittancesSubscriptionKey,
                'api_user'         => env('MOMO_REMITTANCES_API_USER'),
                'api_key'          => env('MOMO_REMITTANCES_API_KEY'),
                'configured'       => false,
            ],
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
            'project_id' => env('FIREBASE_PROJECT_ID', env('FCM_PROJECT_ID')),
            'credentials_path' => env('FIREBASE_CREDENTIALS'),
            // Clés par type d'utilisateur (optionnel, pour apps séparées)
            'user_key' => env('FCM_USER_KEY'),
            'restaurant_key' => env('FCM_RESTAURANT_KEY'),
            'driver_key' => env('FCM_DRIVER_KEY'),
        ],

        // SMS via Twilio
        'twilio' => [
            'enabled'        => env('TWILIO_ENABLED', false),
            'sid'            => env('TWILIO_SID'),
            'token'          => env('TWILIO_TOKEN'),
            'from'           => env('TWILIO_FROM'),
            'verify_sid'     => env('TWILIO_VERIFY_SID'),
            'whatsapp_from'  => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
        ],

        // SMS via MTN Congo (SMS v3 API)
        'mtn_sms' => [
            'enabled'         => env('MTN_SMS_ENABLED', false),
            'consumer_key'    => env('MTN_SMS_CONSUMER_KEY'),
            'consumer_secret' => env('MTN_SMS_CONSUMER_SECRET'),
            'subscription_key'=> env('MTN_SMS_SUBSCRIPTION_KEY'),
            'sender_id'       => env('MTN_SMS_SENDER_ID', 'BantuDelice'),
            'environment'     => env('MTN_SMS_ENVIRONMENT', 'production'),
            'base_url'        => env('MTN_SMS_BASE_URL', 'https://api.mtn.com'),
            'token_url'       => env('MTN_SMS_TOKEN_URL', 'https://api.mtn.com/v1/oauth/access_token'),
            'send_url'        => env('MTN_SMS_SEND_URL', 'https://api.mtn.com/v3/sms/messages/sms/outbound'),
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
