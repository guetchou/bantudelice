<?php

return [
    'currency' => env('COMMERCE_CURRENCY', 'FCFA'),
    'max_promo_discount_ratio' => (float) env('COMMERCE_MAX_PROMO_DISCOUNT_RATIO', 0.2),
    'risk' => [
        'high_threshold' => (float) env('COMMERCE_RISK_HIGH_THRESHOLD', 0.7),
        'block_threshold' => (float) env('COMMERCE_RISK_BLOCK_THRESHOLD', 0.9),
    ],
    'search' => [
        'candidate_limit' => (int) env('COMMERCE_SEARCH_CANDIDATE_LIMIT', 50),
        'restaurant_limit' => (int) env('COMMERCE_SEARCH_RESTAURANT_LIMIT', 12),
        'product_limit' => (int) env('COMMERCE_SEARCH_PRODUCT_LIMIT', 12),
        'product_recommendation_limit' => (int) env('COMMERCE_SEARCH_PRODUCT_RECOMMENDATION_LIMIT', 8),
        'result_limit' => (int) env('COMMERCE_SEARCH_RESULT_LIMIT', 12),
        'weights' => [
            'query_name' => (float) env('COMMERCE_SEARCH_WEIGHT_QUERY_NAME', 8.0),
            'query_address' => (float) env('COMMERCE_SEARCH_WEIGHT_QUERY_ADDRESS', 2.5),
            'query_city' => (float) env('COMMERCE_SEARCH_WEIGHT_QUERY_CITY', 1.2),
            'query_cuisine' => (float) env('COMMERCE_SEARCH_WEIGHT_QUERY_CUISINE', 4.0),
            'query_product' => (float) env('COMMERCE_SEARCH_WEIGHT_QUERY_PRODUCT', 5.0),
            'featured' => (float) env('COMMERCE_SEARCH_WEIGHT_FEATURED', 2.5),
            'rating' => (float) env('COMMERCE_SEARCH_WEIGHT_RATING', 1.8),
            'rating_count' => (float) env('COMMERCE_SEARCH_WEIGHT_RATING_COUNT', 1.4),
            'favorite' => (float) env('COMMERCE_SEARCH_WEIGHT_FAVORITE', 1.8),
            'delivery_fee' => (float) env('COMMERCE_SEARCH_WEIGHT_DELIVERY_FEE', 1.0),
            'distance' => (float) env('COMMERCE_SEARCH_WEIGHT_DISTANCE', 2.0),
            'product_discount' => (float) env('COMMERCE_SEARCH_WEIGHT_PRODUCT_DISCOUNT', 1.5),
            'product_relevance' => (float) env('COMMERCE_SEARCH_WEIGHT_PRODUCT_RELEVANCE', 2.0),
            'product_featured' => (float) env('COMMERCE_SEARCH_WEIGHT_PRODUCT_FEATURED', 2.5),
            'product_recent' => (float) env('COMMERCE_SEARCH_WEIGHT_PRODUCT_RECENT', 1.0),
        ],
    ],
    'recommendations' => [
        'history_limit' => (int) env('COMMERCE_RECOMMENDATION_HISTORY_LIMIT', 24),
        'history_window_days' => (int) env('COMMERCE_RECOMMENDATION_HISTORY_WINDOW_DAYS', 120),
        'max_per_restaurant' => (int) env('COMMERCE_RECOMMENDATION_MAX_PER_RESTAURANT', 2),
        'weights' => [
            'history_restaurant' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_HISTORY_RESTAURANT', 3.2),
            'history_cuisine' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_HISTORY_CUISINE', 2.4),
            'history_category' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_HISTORY_CATEGORY', 2.0),
            'favorite_restaurant' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_FAVORITE_RESTAURANT', 4.0),
            'recent_repeat' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_RECENT_REPEAT', 1.2),
            'price_band' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_PRICE_BAND', 1.2),
            'daypart' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_DAYPART', 1.4),
            'city' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_CITY', 1.0),
            'distance' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_DISTANCE', 2.2),
            'freshness' => (float) env('COMMERCE_RECOMMENDATION_WEIGHT_FRESHNESS', 1.1),
        ],
    ],
    'refunds' => [
        'auto_mode' => env('COMMERCE_REFUND_AUTO_MODE', 'automatic'),
        'manual_fallback' => (bool) env('COMMERCE_REFUND_MANUAL_FALLBACK', true),
        'provider_timeout' => (int) env('COMMERCE_REFUND_PROVIDER_TIMEOUT', 20),
        'providers' => [
            'cash' => [
                'refund_endpoint' => env('COMMERCE_REFUND_ENDPOINT_CASH'),
            ],
            'momo' => [
                'refund_endpoint' => env('COMMERCE_REFUND_ENDPOINT_MOMO'),
            ],
            'paypal' => [
                'refund_endpoint' => env('COMMERCE_REFUND_ENDPOINT_PAYPAL'),
            ],
            'airtel_money' => [
                'refund_endpoint' => env('COMMERCE_REFUND_ENDPOINT_AIRTEL'),
            ],
        ],
        'supported_providers' => [
            'cash',
            'momo',
            'paypal',
            'airtel_money',
        ],
    ],
    'support' => [
        'auto_ticket_on_incident' => (bool) env('COMMERCE_SUPPORT_AUTO_TICKET', true),
        'auto_ticket_on_refund' => (bool) env('COMMERCE_SUPPORT_AUTO_TICKET_REFUND', true),
        'auto_ticket_on_risk' => (bool) env('COMMERCE_SUPPORT_AUTO_TICKET_RISK', true),
        'escalation_threshold_minutes' => (int) env('COMMERCE_SUPPORT_ESCALATION_MINUTES', 30),
        'open_statuses' => ['open', 'pending_review', 'pending_refund', 'pending_redelivery'],
    ],
    'analytics' => [
        'window_days' => (int) env('COMMERCE_ANALYTICS_WINDOW_DAYS', 7),
        'top_limit' => (int) env('COMMERCE_ANALYTICS_TOP_LIMIT', 10),
        'slas' => [
            'restaurant_accept_minutes' => (int) env('COMMERCE_SLA_RESTAURANT_ACCEPT_MINUTES', 3),
            'delivery_assign_minutes' => (int) env('COMMERCE_SLA_DELIVERY_ASSIGN_MINUTES', 2),
            'delivery_complete_minutes' => (int) env('COMMERCE_SLA_DELIVERY_COMPLETE_MINUTES', 45),
        ],
    ],
    'retention_days' => (int) env('COMMERCE_SIGNAL_RETENTION_DAYS', 365),
    'integrations' => [
        'maps' => [
            'enabled' => (bool) env('COMMERCE_INTEGRATION_MAPS_ENABLED', true),
            'driver' => env('COMMERCE_INTEGRATION_MAPS_DRIVER', 'mapbox'),
        ],
        'payment' => [
            'enabled' => (bool) env('COMMERCE_INTEGRATION_PAYMENT_ENABLED', false),
            'driver' => env('COMMERCE_INTEGRATION_PAYMENT_DRIVER', 'manual'),
        ],
        'sms' => [
            'enabled' => (bool) env('COMMERCE_INTEGRATION_SMS_ENABLED', false),
            'driver' => env('COMMERCE_INTEGRATION_SMS_DRIVER', 'manual'),
        ],
        'mail' => [
            'enabled' => (bool) env('COMMERCE_INTEGRATION_MAIL_ENABLED', true),
            'driver' => env('COMMERCE_INTEGRATION_MAIL_DRIVER', 'smtp'),
        ],
    ],
];
