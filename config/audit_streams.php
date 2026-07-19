<?php

return [
    'enabled' => env('AUDIT_STREAMING_ENABLED', true),

    'chain' => [
        'enabled' => env('AUDIT_CHAIN_ENABLED', true),
        'hash_algorithm' => 'sha256',
        'verify_cron' => 'weekly',
    ],

    'delivery' => [
        'queue' => 'audit-streaming',
        'retry_attempts' => env('AUDIT_RETRY_ATTEMPTS', 5),
        'backoff_seconds' => [10, 30, 60, 300, 900],
        'timeout_seconds' => env('AUDIT_TIMEOUT', 30),
    ],

    'destinations' => [
        'splunk_hec' => \App\Domain\Audit\Services\Destinations\SplunkHecDestination::class,
        'datadog_logs' => \App\Domain\Audit\Services\Destinations\DatadogLogsDestination::class,
        'elastic' => \App\Domain\Audit\Services\Destinations\ElasticDestination::class,
        'logtail' => \App\Domain\Audit\Services\Destinations\LogtailDestination::class,
        'http_webhook' => \App\Domain\Audit\Services\Destinations\HttpWebhookDestination::class,
        'syslog' => \App\Domain\Audit\Services\Destinations\SyslogDestination::class,
    ],

    'severity_map' => [
        'info' => LOG_INFO,
        'notice' => LOG_NOTICE,
        'warning' => LOG_WARNING,
        'error' => LOG_ERR,
        'critical' => LOG_CRIT,
        'alert' => LOG_ALERT,
        'emergency' => LOG_EMERG,
    ],

    'cleanup_deliveries_days' => env('AUDIT_CLEANUP_DAYS', 30),
    'max_concurrent_streams_per_tenant' => env('AUDIT_MAX_STREAMS', 10),
];
