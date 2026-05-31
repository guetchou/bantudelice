<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'release' => env('SENTRY_RELEASE', config('release.version', null)),

    'environment' => env('APP_ENV', 'production'),

    'breadcrumbs' => [
        'logs' => true,
        'cache' => true,
        'livewire' => false,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'command_info' => true,
    ],

    'tracing' => [
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_JOBS', false),
        'queue_jobs' => env('SENTRY_TRACE_QUEUE_JOBS', false),
        'sql_queries' => env('SENTRY_TRACE_SQL_QUERIES', true),
        'sql_origin' => env('SENTRY_TRACE_SQL_ORIGIN', false),
        'views' => env('SENTRY_TRACE_VIEWS', true),
        'livewire' => false,
        'http_client_requests' => env('SENTRY_TRACE_HTTP_CLIENT_REQUESTS', true),
        'redis_commands' => env('SENTRY_TRACE_REDIS_COMMANDS', false),
        'redis_origin' => env('SENTRY_TRACE_REDIS_ORIGIN', false),
    ],

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    'send_default_pii' => false,

    'spotlight' => env('SENTRY_SPOTLIGHT', false),

    'ignore_exceptions' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],
];
