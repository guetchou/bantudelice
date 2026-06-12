<?php

return [
    'default_connection' => env('DEFAULT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
    'default_queue' => env('DEFAULT_QUEUE', 'default'),

    'modules' => [
        'food' => [
            'connection' => env('MODULE_QUEUE_CONNECTION_FOOD', 'database_food'),
            'queue' => env('MODULE_QUEUE_FOOD', env('QUEUE_FOOD', 'food')),
            'worker_command' => 'php artisan worker:food',
        ],
        'colis' => [
            'connection' => env('MODULE_QUEUE_CONNECTION_COLIS', 'database_colis'),
            'queue' => env('MODULE_QUEUE_COLIS', env('QUEUE_COLIS', 'colis')),
            'worker_command' => 'php artisan worker:colis',
        ],
        'transport' => [
            'connection' => env('MODULE_QUEUE_CONNECTION_TRANSPORT', 'database_transport'),
            'queue' => env('MODULE_QUEUE_TRANSPORT', env('QUEUE_TRANSPORT', 'transport')),
            'worker_command' => 'php artisan worker:transport',
        ],
    ],

    'jobs' => [
        'food' => [
            'process_order' => \App\Jobs\ProcessOrderJob::class,
            'auto_assign_delivery' => \App\Jobs\AutoAssignDeliveryJob::class,
            'send_order_notifications' => \App\Jobs\SendOrderNotificationsJob::class,
            'retry_payment_callback' => \App\Jobs\RetryPaymentCallbackJob::class,
        ],
        'colis' => [
            'send_shipment_status_notification' => \App\Jobs\SendShipmentStatusNotificationJob::class,
            'generate_shipment_delivery_otp' => \App\Jobs\GenerateShipmentDeliveryOtpJob::class,
            'finalize_shipment_cod_collection' => \App\Jobs\FinalizeShipmentCodCollectionJob::class,
            'handle_shipment_payment_callback' => \App\Jobs\HandleShipmentPaymentCallbackJob::class,
        ],
        'transport' => [
            'send_transport_status_notification' => \App\Jobs\SendTransportStatusNotificationJob::class,
            'handle_transport_payment_callback' => \App\Jobs\HandleTransportPaymentCallbackJob::class,
        ],
    ],
];
