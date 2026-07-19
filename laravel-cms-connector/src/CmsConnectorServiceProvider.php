<?php

namespace Platform\CmsConnector;

use Illuminate\Support\ServiceProvider;

class CmsConnectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cms-connector.php', 'cms-connector');

        $this->app->singleton(ConnectorManager::class, function ($app) {
            return new ConnectorManager($app->make(Support\CmsClient::class), $app->make(Support\SignatureVerifier::class), config('cms-connector'));
        });

        $this->app->singleton(Support\CmsClient::class, function ($app) {
            return new Support\CmsClient(
                config('cms-connector.cms_base_url'),
                config('cms-connector.api_token'),
                config('cms-connector.timeout_seconds', 30),
                $app->make(Support\CircuitBreaker::class),
                $app->make(Support\CacheFallback::class),
            );
        });

        $this->app->singleton(Support\SignatureVerifier::class);
        $this->app->singleton(Support\CircuitBreaker::class);
        $this->app->singleton(Support\CacheFallback::class);

        if (config('cms-connector.auth_bridge.enabled')) {
            $this->app->singleton(Bridges\AuthBridge::class);
        }
        if (config('cms-connector.model_sync.enabled')) {
            $this->app->singleton(Bridges\ModelSyncBridge::class);
        }
        if (config('cms-connector.event_bus.enabled')) {
            $this->app->singleton(Bridges\EventBusBridge::class);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Console\InstallCommand::class, Console\SyncModelsCommand::class, Console\StatusCommand::class]);
            $this->publishes([__DIR__ . '/../config/cms-connector.php' => config_path('cms-connector.php')], 'cms-connector-config');
            $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'cms-connector-migrations');
        }

        $this->registerRoutes();
        $this->registerEventListeners();
    }

    protected function registerRoutes(): void
    {
        if (config('cms-connector.auth_bridge.enabled')) {
            \Illuminate\Support\Facades\Route::group(['middleware' => ['web', 'auth'], 'prefix' => config('cms-connector.auth_bridge.route_prefix', 'cms-sso')], function () {
                \Illuminate\Support\Facades\Route::get('/redirect', [Http\Controllers\SsoRedirectController::class, 'redirect']);
            });
        }

        if (config('cms-connector.event_bus.enabled')) {
            \Illuminate\Support\Facades\Route::post(config('cms-connector.event_bus.webhook_path', '/cms-connector/webhook'), [Http\Controllers\WebhookReceiverController::class, 'receive'])->middleware('api');
        }
    }

    protected function registerEventListeners(): void
    {
        if (config('cms-connector.model_sync.enabled')) {
            $sync = $this->app->make(Bridges\ModelSyncBridge::class);
            foreach (config('cms-connector.model_sync.syncable_models', []) as $modelClass => $modelConfig) {
                foreach ($modelConfig['watch_events'] ?? ['created', 'updated', 'deleted'] as $event) {
                    $this->app['events']->listen("eloquent.{$event}: {$modelClass}", fn($model) => $sync->onModelEvent($model, $event));
                }
            }
        }

        if (config('cms-connector.event_bus.enabled')) {
            $bus = $this->app->make(Bridges\EventBusBridge::class);
            foreach (config('cms-connector.event_bus.publish', []) as $eventClass => $eventType) {
                $this->app['events']->listen($eventClass, fn($event) => $bus->forwardToCms($event, $eventType));
            }
        }
    }
}
