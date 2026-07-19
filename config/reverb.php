<?php

return [

    'apps' => [
        [
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
            'allowed_origins' => ['*'],
            'ping_interval' => env('REVERB_PING_INTERVAL', 60),
            'max_message_size' => env('REVERB_MAX_MESSAGE_SIZE', 10000),
        ],
    ],

    'development' => [
        'host' => env('REVERB_DEV_HOST', '127.0.0.1'),
        'port' => env('REVERB_PORT', 8080),
    ],

    'server' => [
        'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
        'port' => env('REVERB_SERVER_PORT', 8080),
        'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10000),
        'scaling' => [
            'enabled' => env('REVERB_SCALING_ENABLED', false),
            'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
            'connections' => env('REVERB_SCALING_CONNECTIONS', ''),
        ],
        'health_check' => [
            'enabled' => env('REVERB_HEALTH_CHECK_ENABLED', true),
            'path' => '/up',
        ],
    ],

];
