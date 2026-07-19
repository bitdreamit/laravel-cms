<?php

return [
    'enabled' => env('COLLAB_ENABLED', true),
    'websocket' => [
        'host' => env('REVERB_HOST', '127.0.0.1'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
        'app_id' => env('REVERB_APP_ID'),
        'app_key' => env('REVERB_APP_KEY'),
        'app_secret' => env('REVERB_APP_SECRET'),
    ],
    'persistence' => [
        'interval_seconds' => env('COLLAB_PERSIST_INTERVAL', 5),
        'cleanup_stale_sessions_minutes' => env('COLLAB_CLEANUP_MINUTES', 30),
    ],
    'presence' => [
        'heartbeat_seconds' => env('COLLAB_HEARTBEAT', 15),
        'timeout_seconds' => env('COLLAB_TIMEOUT', 30),
    ],
    'colors' => [
        '#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981',
        '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899',
    ],
    'force_lock_timeout_minutes' => env('COLLAB_FORCE_LOCK_TIMEOUT', 30),
];
