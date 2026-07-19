<?php

return [
    'cms_base_url' => env('CMS_BASE_URL', 'https://cms.example.com'),
    'tenant_id' => env('CMS_TENANT_ID'),
    'api_token' => env('CMS_API_TOKEN'),
    'shared_secret' => env('CMS_SHARED_SECRET'),
    'timeout_seconds' => env('CMS_TIMEOUT', 30),
    'retry_attempts' => env('CMS_RETRY_ATTEMPTS', 3),
    'circuit_breaker' => ['enabled' => env('CMS_CIRCUIT_BREAKER_ENABLED', true), 'failure_threshold' => env('CMS_CB_FAILURE_THRESHOLD', 5), 'reset_seconds' => env('CMS_CB_RESET_SECONDS', 60)],
    'cache' => ['enabled' => env('CMS_CACHE_ENABLED', true), 'ttl_seconds' => env('CMS_CACHE_TTL', 300), 'stale_while_revalidate' => env('CMS_SWR', true), 'store' => env('CMS_CACHE_STORE', env('CACHE_STORE', 'database'))],
    'auth_bridge' => ['enabled' => env('CMS_AUTH_BRIDGE_ENABLED', false), 'route_prefix' => 'cms-sso', 'shared_secret' => env('CMS_AUTH_BRIDGE_SECRET'), 'cms_sso_url' => env('CMS_SSO_URL'), 'token_ttl_seconds' => 60, 'auto_create_users' => true, 'default_role' => 'editor', 'user_model' => env('CMS_USER_MODEL', \App\Models\User::class), 'sign_out_together' => false],
    'model_sync' => ['enabled' => env('CMS_MODEL_SYNC_ENABLED', false), 'direction' => env('CMS_SYNC_DIRECTION', 'bidirectional'), 'syncable_models' => [], 'conflict_resolution' => env('CMS_CONFLICT_RESOLUTION', 'newest_wins'), 'queue' => 'cms-sync', 'retry_attempts' => 3, 'batch_size' => 50],
    'event_bus' => ['enabled' => env('CMS_EVENT_BUS_ENABLED', false), 'webhook_path' => '/cms-connector/webhook', 'subscriptions' => [], 'publish' => [], 'signature_secret' => env('CMS_EVENT_BUS_SECRET'), 'retry_queue' => 'cms-events', 'dedup_ttl_seconds' => 86400],
    'embedded' => ['enabled' => env('CMS_EMBEDDED_ENABLED', false), 'route_prefix' => 'cms', 'layout' => 'layouts.app', 'middleware' => ['web', 'auth'], 'assets_inherit' => true],
    'headless' => ['enabled' => env('CMS_HEADLESS_ENABLED', true), 'default_cache_ttl' => env('CMS_HEADLESS_CACHE_TTL', 300), 'collections' => []],
    'logging' => ['channel' => env('CMS_LOG_CHANNEL', 'stack'), 'level' => env('CMS_LOG_LEVEL', 'info')],
    'queue' => env('CMS_QUEUE_NAME', 'cms-connector'),
    'queue_connection' => env('CMS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
];
