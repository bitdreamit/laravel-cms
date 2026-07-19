<?php

return [
    'dashboard' => env('HORIZON_DASHBOARD_PATH', 'horizon'),
    'domain' => env('HORIZON_DOMAIN'),
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', 'horizon:'),
    'middleware' => ['web', 'auth'],
    'waits' => [
        'redis:default' => 60,
        'redis:cms-sync' => 300,
        'redis:cms-events' => 300,
        'redis:audit-streaming' => 60,
    ],
    'notifications' => [
        'email' => env('HORIZON_EMAIL'),
    ],
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['default', 'cms-sync', 'cms-events', 'audit-streaming'],
        'balance' => 'simple',
        'maxProcesses' => 5,
        'maxTime' => 0,
        'maxJobs' => 0,
        'memory' => 128,
        'tries' => 3,
        'timeout' => 60,
        'nice' => 0,
    ],
];
