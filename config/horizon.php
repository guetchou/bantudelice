<?php

use Illuminate\Support\Str;

return [
    'name' => env('HORIZON_NAME', 'BantuDelice'),
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'bantudelice'), '_').'_horizon:'
    ),
    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 60,
        'redis:food' => 30,
        'redis:colis' => 60,
        'redis:transport' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],
    'silenced_tags' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => true,
    'memory_limit' => 64,

    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
        'supervisor-food' => [
            'connection' => 'redis',
            'queue' => ['food'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 90,
            'nice' => 0,
        ],
        'supervisor-colis' => [
            'connection' => 'redis',
            'queue' => ['colis'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
        'supervisor-transport' => [
            'connection' => 'redis',
            'queue' => ['transport'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => [
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-food' => [
                'minProcesses' => 1,
                'maxProcesses' => 5,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-colis' => [
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-transport' => [
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],

        'local' => [
            'supervisor-default' => ['maxProcesses' => 1],
            'supervisor-food' => ['maxProcesses' => 1],
            'supervisor-colis' => ['maxProcesses' => 1],
            'supervisor-transport' => ['maxProcesses' => 1],
        ],
    ],

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        'composer.json',
        '.env',
    ],
];
