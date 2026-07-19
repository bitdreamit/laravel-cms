<?php

use Laravel\Telescope\Http\Middleware\Authorize;

return [
    'domain' => env('TELESCOPE_DOMAIN'),
    'path' => env('TELESCOPE_PATH', 'telescope'),
    'driver' => env('TELESCOPE_DRIVER', 'database'),
    'storage' => [
        'database' => ['connection' => env('TELESCOPE_DB_CONNECTION', null)],
        'redis' => ['connection' => env('TELESCOPE_REDIS_CONNECTION', null)],
    ],
    'enabled' => env('TELESCOPE_ENABLED', true),
    'middleware' => ['web', Authorize::class],
    'only_paths' => [],
    'ignore_paths' => ['api/v1/connector/*'],
    'ignore_commands' => [],
    'watchers' => [
        Laravel\Telescope\Watchers\BatchWatcher::class => env('TELESCOPE_BATCH_WATCHER', true),
        Laravel\Telescope\Watchers\CacheWatcher::class => env('TELESCOPE_CACHE_WATCHER', true),
        Laravel\Telescope\Watchers\ClientRequestWatcher::class => env('TELESCOPE_CLIENT_REQUEST_WATCHER', true),
        Laravel\Telescope\Watchers\CommandWatcher::class => env('TELESCOPE_COMMAND_WATCHER', true),
        Laravel\Telescope\Watchers\DumpWatcher::class => env('TELESCOPE_DUMP_WATCHER', true),
        Laravel\Telescope\Watchers\EventWatcher::class => env('TELESCOPE_EVENT_WATCHER', true),
        Laravel\Telescope\Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),
        Laravel\Telescope\Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),
        Laravel\Telescope\Watchers\LogWatcher::class => env('TELESCOPE_LOG_WATCHER', true),
        Laravel\Telescope\Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),
        Laravel\Telescope\Watchers\ModelWatcher::class => env('TELESCOPE_MODEL_WATCHER', true),
        Laravel\Telescope\Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),
        Laravel\Telescope\Watchers\QueryWatcher::class => env('TELESCOPE_QUERY_WATCHER', true),
        Laravel\Telescope\Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),
        Laravel\Telescope\Watchers\RequestWatcher::class => env('TELESCOPE_REQUEST_WATCHER', true),
        Laravel\Telescope\Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),
        Laravel\Telescope\Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
    ],
];
