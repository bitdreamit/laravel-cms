# Laravel Integration Kit — V4
## `platform/laravel-cms-connector` Composer Package Specification

**Version:** 4.0
**Package name:** `platform/laravel-cms-connector`
**Repository:** `github.com/your-org/laravel-cms-connector` (separate repo from main CMS platform)
**License:** MIT
**Supported Laravel versions:** 5.8 → 11.x (LTS strategy)
**PHP requirement:** ^7.4 (for Laravel 5.8/6.x compatibility) — OR — ^8.1 (recommended, for Laravel 10/11)

This document is the complete specification for the connector package. It is intended for the developer building the package itself (Phase 13 of `04-AI-BUILD-PROMPTS-V4.md`). For instructions on *using* the package in a host Laravel app, see `docs/v4/connector-guide.md` in the main CMS project.

---

## 1. Design Goals

1. **Zero host refactor** — installation is `composer require`, `php artisan cms-connector:install`, edit `.env`. No code changes to the host's models, controllers, routes, or views.
2. **Five independently-toggleable modes** — the host can enable any combination of: `auth_bridge`, `model_sync`, `event_bus`, `embedded`, `headless`. Each is a self-contained module.
3. **Respects existing auth** — if the host already has `App\Models\User`, the connector bridges to it. It does NOT replace the host's auth system. The host's users table is the source of truth for the host's auth.
4. **Tenant-aware** — one host app connects to exactly one tenant by default. Multi-tenant host apps can use `CmsConnector::forTenant($id)` to switch context per request.
5. **Failure-isolated** — if the CMS is unreachable, the host app continues serving its own routes. The connector uses circuit breaker + cache fallback (configurable per mode).
6. **Auditable** — every connector action is logged locally (Monolog channel `cms-connector`) and optionally forwarded to the CMS audit log via `X-Connector-Id` header attribution.
7. **Testable** — every bridge has a test double (`FakeBridge`) for host-app testing without a live CMS.
8. **Backward-compatible** — supports Laravel 5.8 through 11.x via version-specific service provider bindings where needed (primarily for middleware signature changes in 10.x).

---

## 2. Package Layout

```
platform/laravel-cms-connector/
├── composer.json
├── LICENSE
├── README.md
├── CHANGELOG.md
├── UPGRADE.md
├── .gitignore
├── .scrutinizer.yml
├── phpunit.xml
├── tests/
│   ├── Pest.php
│   ├── Unit/
│   │   ├── Support/
│   │   │   ├── CmsClientTest.php
│   │   │   ├── SignatureVerifierTest.php
│   │   │   └── CacheFallbackTest.php
│   │   ├── Bridges/
│   │   │   ├── AuthBridgeTest.php
│   │   │   ├── ModelSyncBridgeTest.php
│   │   │   ├── EventBusBridgeTest.php
│   │   │   └── HeadlessClientBridgeTest.php
│   │   └── Console/
│   │       ├── InstallCommandTest.php
│   │       └── StatusCommandTest.php
│   ├── Feature/
│   │   ├── AuthBridgeFlowTest.php
│   │   ├── ModelSyncBidirectionalTest.php
│   │   ├── EventBusWebhookReceivingTest.php
│   │   ├── EmbeddedModeRoutingTest.php
│   │   └── HeadlessClientCachingTest.php
│   └── TestApp/                       # minimal Laravel app for integration tests
│       ├── composer.json
│       ├── artisan
│       ├── app/Models/Product.php
│       ├── app/Models/User.php
│       ├── config/
│       ├── database/migrations/
│       └── routes/web.php
├── src/
│   ├── CmsConnectorServiceProvider.php
│   ├── ConnectorManager.php
│   ├── Facades/
│   │   └── CmsConnector.php
│   ├── Console/
│   │   ├── InstallCommand.php
│   │   ├── SyncModelsCommand.php
│   │   └── StatusCommand.php
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── ShareSessionWithCms.php
│   │   │   └── EmbeddedCmsRouting.php
│   │   └── Controllers/
│   │       ├── SsoRedirectController.php
│   │       ├── SsoCallbackController.php
│   │       └── WebhookReceiverController.php
│   ├── Bridges/
│   │   ├── AuthBridge.php
│   │   ├── ModelSyncBridge.php
│   │   ├── EventBusBridge.php
│   │   ├── EmbeddedBridge.php
│   │   └── HeadlessClientBridge.php
│   ├── Contracts/
│   │   ├── SyncableToCms.php
│   │   ├── CmsEventSubscriber.php
│   │   ├── AuthBridgeInterface.php
│   │   ├── BridgeInterface.php
│   │   └── WorkflowActionInterface.php    # for forwarding host actions to CMS workflows
│   ├── Jobs/
│   │   ├── SyncModelToCmsJob.php
│   │   ├── ProcessIncomingWebhookJob.php
│   │   ├── ForwardEventToCmsJob.php
│   │   └── PersistYjsDocumentJob.php      # only if collab is enabled in host
│   ├── Listeners/
│   │   ├── AutoSyncEloquentModels.php     # listens to model events
│   │   ├── ForwardHostEventsToCms.php     # listens to configured host events
│   │   └── ReceiveCmsEvents.php           # dispatched by WebhookReceiverController
│   ├── Support/
│   │   ├── CmsClient.php                  # HTTP client wrapper
│   │   ├── SignatureVerifier.php
│   │   ├── CacheFallback.php
│   │   ├── CircuitBreaker.php
│   │   ├── CollectionQueryBuilder.php
│   │   ├── VisitorIdCookie.php
│   │   └── YjsClient.php                  # for embedded collab (optional)
│   ├── Config/
│   │   └── config.php                     # published as config/cms-connector.php
│   ├── Exceptions/
│   │   ├── ConnectorNotConfiguredException.php
│   │   ├── CmsUnreachableException.php
│   │   ├── SyncConflictException.php
│   │   ├── InvalidSignatureException.php
│   │   └── CircuitOpenException.php
│   ├── Models/
│   │   ├── CmsConnectorSyncState.php      # morphMap: syncable
│   │   └── CmsConnectorEventLog.php
│   ├── Support/
│   │   └── FakeBridge.php                 # for testing
│   └── resources/
│       ├── config/
│       │   └── cms-connector.php          # default config
│       ├── views/
│       │   ├── embedded-layout.blade.php
│       │   └── sso-redirect.blade.php
│       ├── migrations/
│       │   ├── 2024_01_01_000001_create_cms_connector_sync_state_table.php
│       │   └── 2024_01_01_000002_create_cms_connector_event_log_table.php
│       └── lang/
│           └── en/
│               └── cms-connector.php
├── config/
│   └── cms-connector.php                  # full default config (see section 5)
└── docs/
    ├── installation.md
    ├── auth-bridge.md
    ├── model-sync.md
    ├── event-bus.md
    ├── embedded-mode.md
    ├── headless-client.md
    ├── testing.md
    ├── troubleshooting.md
    └── upgrade-guide.md
```

---

## 3. `composer.json`

```json
{
    "name": "platform/laravel-cms-connector",
    "description": "Connect any existing Laravel app to the CMS Platform (V4) — SSO bridge, model sync, event bus, embedded mode, headless API client.",
    "type": "library",
    "license": "MIT",
    "keywords": ["laravel", "cms", "multi-tenant", "sso", "sync", "headless"],
    "authors": [
        {"name": "Platform Team", "email": "platform@example.com"}
    ],
    "require": {
        "php": "^8.1",
        "illuminate/contracts": "^10.0|^11.0",
        "illuminate/support": "^10.0|^11.0",
        "illuminate/http": "^10.0|^11.0",
        "illuminate/queue": "^10.0|^11.0",
        "guzzlehttp/guzzle": "^7.5",
        "firebase/php-jwt": "^6.10"
    },
    "require-dev": {
        "orchestra/testbench": "^8.0|^9.0",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0",
        "phpstan/phpstan": "^1.10"
    },
    "autoload": {
        "psr-4": {
            "Platform\\CmsConnector\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Platform\\CmsConnector\\Tests\\": "tests/",
            "Platform\\CmsConnector\\Tests\\TestApp\\": "tests/TestApp/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Platform\\CmsConnector\\CmsConnectorServiceProvider"
            ],
            "aliases": {
                "CmsConnector": "Platform\\CmsConnector\\Facades\\CmsConnector"
            }
        }
    },
    "config": {
        "sort-packages": true
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## 4. Service Provider

`src/CmsConnectorServiceProvider.php` is the entry point. It:

1. **Merges config** — `cms-connector.php` defaults are available even before publish.
2. **Binds `ConnectorManager` as singleton** — the facade resolves to this instance.
3. **Registers bridges** based on which modes are enabled in config:
   - `auth_bridge.enabled` → registers `AuthBridge`, `SsoRedirectController` routes, `ShareSessionWithCms` middleware alias
   - `model_sync.enabled` → registers `ModelSyncBridge`, registers `AutoSyncEloquentModels` listener for each configured model's events
   - `event_bus.enabled` → registers `EventBusBridge`, `ForwardHostEventsToCms` listener for configured publish events, `WebhookReceiverController` route
   - `embedded.enabled` → registers `EmbeddedCmsRouting` middleware, prepends to web middleware stack
   - `headless.enabled` → registers `HeadlessClientBridge` (mostly just exposes `ConnectorManager::collection()` and `graphql()`)
4. **Loads migrations** from `resources/migrations/`.
5. **Loads views** from `resources/views/` under the `cms-connector` namespace.
6. **Loads translations** from `resources/lang/`.
7. **Publishes config** on `php artisan vendor:publish --tag=cms-connector-config`.
8. **Publishes migrations** on `php artisan vendor:publish --tag=cms-connector-migrations`.
9. **Registers console commands** — `InstallCommand`, `SyncModelsCommand`, `StatusCommand`.
10. **Registers queue connections** — if `cms-connector.queue` config is set, ensures the queue exists.

Skeleton:

```php
namespace Platform\CmsConnector;

use Illuminate\Support\ServiceProvider;
use Platform\CmsConnector\Bridges\AuthBridge;
use Platform\CmsConnector\Bridges\ModelSyncBridge;
use Platform\CmsConnector\Bridges\EventBusBridge;
use Platform\CmsConnector\Bridges\EmbeddedBridge;
use Platform\CmsConnector\Bridges\HeadlessClientBridge;
use Platform\CmsConnector\Console\InstallCommand;
use Platform\CmsConnector\Console\SyncModelsCommand;
use Platform\CmsConnector\Console\StatusCommand;
use Platform\CmsConnector\Listeners\AutoSyncEloquentModels;
use Platform\CmsConnector\Listeners\ForwardHostEventsToCms;
use Platform\CmsConnector\Support\CmsClient;
use Platform\CmsConnector\Support\SignatureVerifier;
use Platform\CmsConnector\Support\CacheFallback;
use Platform\CmsConnector\Support\CircuitBreaker;

class CmsConnectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../resources/config/cms-connector.php', 'cms-connector');

        $this->app->singleton(CmsClient::class, function ($app) {
            return new CmsClient(
                config('cms-connector.cms_base_url'),
                config('cms-connector.api_token'),
                config('cms-connector.timeout_seconds'),
                $app->make(CircuitBreaker::class),
                $app->make(CacheFallback::class),
            );
        });

        $this->app->singleton(ConnectorManager::class, function ($app) {
            return new ConnectorManager(
                $app->make(CmsClient::class),
                $app->make(SignatureVerifier::class),
                config('cms-connector'),
            );
        });

        // Register each bridge only if its mode is enabled
        if (config('cms-connector.auth_bridge.enabled')) {
            $this->app->singleton(AuthBridge::class);
        }
        if (config('cms-connector.model_sync.enabled')) {
            $this->app->singleton(ModelSyncBridge::class);
        }
        if (config('cms-connector.event_bus.enabled')) {
            $this->app->singleton(EventBusBridge::class);
        }
        if (config('cms-connector.embedded.enabled')) {
            $this->app->singleton(EmbeddedBridge::class);
        }
        if (config('cms-connector.headless.enabled')) {
            $this->app->singleton(HeadlessClientBridge::class);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../resources/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cms-connector');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'cms-connector');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SyncModelsCommand::class,
                StatusCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../resources/config/cms-connector.php' => config_path('cms-connector.php'),
            ], 'cms-connector-config');

            $this->publishes([
                __DIR__.'/../resources/migrations' => database_path('migrations'),
            ], 'cms-connector-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/cms-connector'),
            ], 'cms-connector-views');
        }

        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerEventListeners();
    }

    protected function registerRoutes(): void
    {
        if (config('cms-connector.auth_bridge.enabled')) {
            Route::group([
                'middleware' => ['web', 'auth'],
                'prefix' => config('cms-connector.auth_bridge.route_prefix', 'cms-sso'),
            ], function () {
                Route::get('/redirect', [SsoRedirectController::class, 'redirect']);
                Route::get('/callback', [SsoCallbackController::class, 'callback']);
            });
        }

        if (config('cms-connector.event_bus.enabled')) {
            Route::post(
                config('cms-connector.event_bus.webhook_path', '/cms-connector/webhook'),
                [WebhookReceiverController::class, 'receive']
            )->middleware(['api']);
        }
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('cms-connector.embedded', \Platform\CmsConnector\Http\Middleware\EmbeddedCmsRouting::class);
        $router->aliasMiddleware('cms-connector.share-session', \Platform\CmsConnector\Http\Middleware\ShareSessionWithCms::class);
    }

    protected function registerEventListeners(): void
    {
        if (config('cms-connector.model_sync.enabled')) {
            $sync = $this->app->make(ModelSyncBridge::class);
            foreach (config('cms-connector.model_sync.syncable_models', []) as $modelClass => $config) {
                foreach ($config['watch_events'] as $event) {
                    $this->app['events']->listen(
                        "eloquent.{$event}: {$modelClass}",
                        fn($model) => $sync->onModelEvent($model, $event)
                    );
                }
            }
        }

        if (config('cms-connector.event_bus.enabled')) {
            $bus = $this->app->make(EventBusBridge::class);
            foreach (config('cms-connector.event_bus.publish', []) as $eventClass => $eventType) {
                $this->app['events']->listen($eventClass, fn($event) => $bus->forwardToCms($event, $eventType));
            }
        }
    }
}
```

---

## 5. Full Default Config (`resources/config/cms-connector.php`)

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CMS Connection
    |--------------------------------------------------------------------------
    */
    'cms_base_url' => env('CMS_BASE_URL', 'https://cms.example.com'),
    'tenant_id' => env('CMS_TENANT_ID'),
    'api_token' => env('CMS_API_TOKEN'),
    'shared_secret' => env('CMS_SHARED_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */
    'timeout_seconds' => env('CMS_TIMEOUT', 30),
    'retry_attempts' => env('CMS_RETRY_ATTEMPTS', 3),
    'circuit_breaker' => [
        'enabled' => env('CMS_CIRCUIT_BREAKER_ENABLED', true),
        'failure_threshold' => env('CMS_CB_FAILURE_THRESHOLD', 5),
        'reset_seconds' => env('CMS_CB_RESET_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('CMS_CACHE_ENABLED', true),
        'ttl_seconds' => env('CMS_CACHE_TTL', 300),
        'stale_while_revalidate' => env('CMS_SWR', true),
        'store' => env('CMS_CACHE_STORE', env('CACHE_STORE', 'database')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mode 1: Auth Bridge (SSO)
    |--------------------------------------------------------------------------
    */
    'auth_bridge' => [
        'enabled' => env('CMS_AUTH_BRIDGE_ENABLED', false),
        'route_prefix' => 'cms-sso',
        'shared_secret' => env('CMS_AUTH_BRIDGE_SECRET'),  // distinct from main shared_secret
        'cms_sso_url' => env('CMS_SSO_URL'),               // e.g. https://cms.example.com/sso/bridge
        'token_ttl_seconds' => 60,
        'auto_create_users' => true,
        'default_role' => 'editor',
        'user_model' => env('CMS_USER_MODEL', \App\Models\User::class),
        'user_field_map' => [
            'email' => 'email',
            'name' => 'name',
            'avatar' => 'avatar_url',
        ],
        'sign_out_together' => false,
        'logout_redirect' => '/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mode 2: Model Sync
    |--------------------------------------------------------------------------
    */
    'model_sync' => [
        'enabled' => env('CMS_MODEL_SYNC_ENABLED', false),
        'direction' => env('CMS_SYNC_DIRECTION', 'bidirectional'),  // host_to_cms | cms_to_host | bidirectional
        'syncable_models' => [
            // \App\Models\Product::class => [
            //     'collection_handle' => 'products',
            //     'watch_events' => ['created', 'updated', 'deleted'],
            //     'debounce_seconds' => 5,
            // ],
        ],
        'conflict_resolution' => env('CMS_CONFLICT_RESOLUTION', 'newest_wins'),  // host_wins | cms_wins | newest_wins | manual
        'queue' => 'cms-sync',
        'retry_attempts' => 3,
        'batch_size' => 50,  // for SyncModelsCommand bulk sync
    ],

    /*
    |--------------------------------------------------------------------------
    | Mode 3: Event Bus
    |--------------------------------------------------------------------------
    */
    'event_bus' => [
        'enabled' => env('CMS_EVENT_BUS_ENABLED', false),
        'webhook_path' => '/cms-connector/webhook',
        'subscriptions' => [
            // 'entry.published' => \App\Listeners\CmsEntryPublishedListener::class,
        ],
        'publish' => [
            // \App\Events\OrderPlaced::class => 'order.placed',
        ],
        'signature_secret' => env('CMS_EVENT_BUS_SECRET'),
        'retry_queue' => 'cms-events',
        'dedup_ttl_seconds' => 86400,  // 24h dedup window for incoming webhooks
    ],

    /*
    |--------------------------------------------------------------------------
    | Mode 4: Embedded Mode
    |--------------------------------------------------------------------------
    */
    'embedded' => [
        'enabled' => env('CMS_EMBEDDED_ENABLED', false),
        'route_prefix' => 'cms',
        'layout' => 'layouts.app',
        'middleware' => ['web', 'auth'],
        'assets_inherit' => true,  // include host's Vite-built assets in embedded CMS views
    ],

    /*
    |--------------------------------------------------------------------------
    | Mode 5: Headless API Client
    |--------------------------------------------------------------------------
    */
    'headless' => [
        'enabled' => env('CMS_HEADLESS_ENABLED', false),
        'default_cache_ttl' => env('CMS_HEADLESS_CACHE_TTL', 300),
        'collections' => [
            // 'blog' => ['cache_ttl' => 600],
            // 'products' => ['cache_ttl' => 60],
        ],
        'auto_webp' => true,  // request WebP variants from CMS if supported
        'image_transform_params' => ['w' => 1200, 'q' => 80],  // default image transforms
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channel' => env('CMS_LOG_CHANNEL', 'stack'),
        'level' => env('CMS_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */
    'queue' => env('CMS_QUEUE_NAME', 'cms-connector'),
    'queue_connection' => env('CMS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),

    /*
    |--------------------------------------------------------------------------
    | Optional: Real-time Collab (Yjs) — only enable if the host wants to embed
    | a collab-editable CMS field directly in host app views. Rarely needed.
    |--------------------------------------------------------------------------
    */
    'collab' => [
        'enabled' => env('CMS_COLLAB_ENABLED', false),
        'websocket_url' => env('CMS_REVERB_URL'),
        'websocket_port' => env('CMS_REVERB_PORT', 8080),
    ],
];
```

---

## 6. Core Classes

### 6.1 `ConnectorManager` (singleton + facade)

The user-facing API. Host controllers and views interact with the CMS exclusively through this class.

```php
namespace Platform\CmsConnector;

use Platform\CmsConnector\Support\CmsClient;
use Platform\CmsConnector\Support\CollectionQueryBuilder;
use Platform\CmsConnector\Support\SignatureVerifier;

class ConnectorManager
{
    public function __construct(
        protected CmsClient $client,
        protected SignatureVerifier $signer,
        protected array $config,
    ) {}

    /**
     * Start a query against a CMS collection.
     */
    public function collection(string $handle): CollectionQueryBuilder
    {
        return new CollectionQueryBuilder($this->client, $handle, $this->config['headless']['collections'][$handle] ?? []);
    }

    /**
     * Execute a GraphQL query against the CMS.
     */
    public function graphql(string $query, array $variables = []): array
    {
        return $this->client->post('/api/v1/graphql', [
            'query' => $query,
            'variables' => $variables,
        ]);
    }

    /**
     * Switch to a different tenant context (for multi-tenant host apps).
     * Returns a NEW instance — the original is unchanged.
     */
    public function forTenant(string $tenantId): static
    {
        $clone = clone $this;
        $clone->client = $this->client->forTenant($tenantId);
        return $clone;
    }

    /**
     * Check if the CMS is reachable.
     */
    public function health(): array
    {
        return $this->client->get('/api/v1/connector/status');
    }

    /**
     * Get the registered connector ID (cached after first call).
     */
    public function getConnectorId(): ?string
    {
        return cache()->remember('cms-connector.id', 3600, function () {
            try {
                return $this->health()['connector_id'] ?? null;
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * Manually trigger a model sync (used by SyncModelsCommand).
     */
    public function syncModel(object $model): void
    {
        if (! $model instanceof \Platform\CmsConnector\Contracts\SyncableToCms) {
            throw new \InvalidArgumentException('Model must implement SyncableToCms');
        }
        $data = $model->toCmsEntryData();
        $this->client->put(
            "/api/v1/collections/{$data['collection_handle']}/entries/{$data['slug']}",
            $data
        );
    }

    /**
     * Generate an SSO redirect URL (used by AuthBridge).
     */
    public function ssoRedirectUrl(): string
    {
        $payload = [
            'host_user_id' => auth()->id(),
            'email' => auth()->user()->email,
            'name' => auth()->user()->name,
            'exp' => time() + $this->config['auth_bridge']['token_ttl_seconds'],
        ];
        $token = $this->signer->signJwt($payload, $this->config['auth_bridge']['shared_secret']);
        return $this->config['auth_bridge']['cms_sso_url'] . '?token=' . $token;
    }
}
```

### 6.2 `CmsClient` (HTTP wrapper)

Wraps Guzzle with retry, circuit breaker, cache, and HMAC signing.

```php
namespace Platform\CmsConnector\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Platform\CmsConnector\Exceptions\CmsUnreachableException;
use Platform\CmsConnector\Exceptions\CircuitOpenException;

class CmsClient
{
    protected ?string $tenantIdOverride = null;

    public function __construct(
        protected string $baseUrl,
        protected ?string $apiToken,
        protected int $timeout,
        protected CircuitBreaker $breaker,
        protected CacheFallback $cache,
    ) {
        $this->httpClient = new Client([
            'base_uri' => $baseUrl,
            'timeout' => $timeout,
            'headers' => [
                'Authorization' => "Bearer {$apiToken}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function forTenant(string $tenantId): static
    {
        $clone = clone $this;
        $clone->tenantIdOverride = $tenantId;
        return $clone;
    }

    public function get(string $url, array $query = [], ?int $cacheTtl = null): array
    {
        $cacheKey = $this->cacheKey('GET', $url, $query);

        if ($cacheTtl !== null) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $response = $this->request('GET', $url, ['query' => $query]);

        if ($cacheTtl !== null) {
            $this->cache->put($cacheKey, $response, $cacheTtl);
        }

        return $response;
    }

    public function post(string $url, array $data = []): array
    {
        return $this->request('POST', $url, ['json' => $data]);
    }

    public function put(string $url, array $data = []): array
    {
        return $this->request('PUT', $url, ['json' => $data]);
    }

    public function delete(string $url): array
    {
        return $this->request('DELETE', $url);
    }

    protected function request(string $method, string $url, array $options = []): array
    {
        if ($this->breaker->isOpen()) {
            throw new CircuitOpenException("Circuit breaker open — CMS unavailable");
        }

        $headers = [];
        if ($this->tenantIdOverride) {
            $headers['X-Tenant-Id'] = $this->tenantIdOverride;
        }
        // X-Connector-Id is added via cached lookup in ConnectorManager
        $options['headers'] = array_merge($options['headers'] ?? [], $headers);

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $this->breaker->recordSuccess();
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->breaker->recordFailure();

            if ($e->hasResponse() && $e->getResponse()?->getStatusCode() >= 500) {
                // retry on 5xx
                $retries = 0;
                while ($retries < 3) {
                    try {
                        $response = $this->httpClient->request($method, $url, $options);
                        $this->breaker->recordSuccess();
                        return json_decode($response->getBody()->getContents(), true);
                    } catch (RequestException $retry) {
                        $retries++;
                        usleep(pow(2, $retries) * 100000);  // 200ms, 400ms, 800ms
                    }
                }
            }

            Log::channel('cms-connector')->error('CMS request failed', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new CmsUnreachableException("CMS request failed: {$e->getMessage()}", 0, $e);
        }
    }

    protected function cacheKey(string $method, string $url, array $query = []): string
    {
        return 'cms-connector:' . md5($method . $url . serialize($query) . ($this->tenantIdOverride ?? ''));
    }
}
```

### 6.3 `SignatureVerifier` (HMAC + JWT)

```php
namespace Platform\CmsConnector\Support;

use Firebase\JWT\JWT;

class SignatureVerifier
{
    public function sign(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
    }

    public function verify(array $payload, string $signature, string $secret): bool
    {
        $expected = $this->sign($payload, $secret);
        return hash_equals($expected, $signature);  // constant-time
    }

    public function signJwt(array $payload, string $secret): string
    {
        return JWT::encode($payload, $secret, 'HS256');
    }

    public function verifyJwt(string $token, string $secret): object
    {
        return JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));
    }
}
```

### 6.4 `CollectionQueryBuilder` (fluent headless API)

```php
namespace Platform\CmsConnector\Support;

class CollectionQueryBuilder
{
    protected array $wheres = [];
    protected array $orders = [];
    protected ?int $perPage = null;
    protected ?int $page = null;
    protected array $with = [];

    public function __construct(
        protected CmsClient $client,
        protected string $handle,
        protected array $config = [],
    ) {}

    public function where(string $field, $operator, $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = compact('field', 'operator', 'value');
        return $this;
    }

    public function orderBy(string $field, string $direction = 'asc'): static
    {
        $this->orders[] = compact('field', 'direction');
        return $this;
    }

    public function with(string $relation): static
    {
        $this->with[] = $relation;
        return $this;
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $this->perPage = $perPage;
        $this->page = $page;
        return $this->execute('paginate');
    }

    public function find(string $id): ?array
    {
        $response = $this->client->get("/api/v1/collections/{$this->handle}/entries/{$id}", [], $this->cacheTtl());
        return $response['data'] ?? null;
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->find($slug);
    }

    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    public function get(): array
    {
        return $this->execute('get');
    }

    public function limit(int $limit): static
    {
        $this->perPage = $limit;
        return $this;
    }

    protected function execute(string $mode): array
    {
        $query = [
            'filter' => $this->buildFilter(),
            'sort' => $this->buildSort(),
        ];
        if ($this->perPage) {
            $query['per_page'] = $this->perPage;
        }
        if ($this->page) {
            $query['page'] = $this->page;
        }
        if ($this->with) {
            $query['include'] = implode(',', $this->with);
        }

        $response = $this->client->get("/api/v1/collections/{$this->handle}/entries", $query, $this->cacheTtl());

        return $mode === 'paginate' ? $response : ($response['data'] ?? []);
    }

    protected function buildFilter(): string
    {
        // CMS API expects JSON:API-style filter: filter[status]=published
        // For multiple: filter[and][0][status]=published&filter[and][0][field][gt]=10
        $parts = [];
        foreach ($this->wheres as $where) {
            if ($where['operator'] === '=') {
                $parts[] = "{$where['field']}:{$where['value']}";
            } else {
                $parts[] = "{$where['field']}{$where['operator']}{$where['value']}";
            }
        }
        return implode(';', $parts);
    }

    protected function buildSort(): string
    {
        $parts = [];
        foreach ($this->orders as $order) {
            $parts[] = $order['direction'] === 'desc' ? '-' . $order['field'] : $order['field'];
        }
        return implode(',', $parts);
    }

    protected function cacheTtl(): ?int
    {
        return $this->config['cache_ttl'] ?? null;
    }
}
```

---

## 7. Bridge Implementations

### 7.1 `AuthBridge` (Mode 1)

The full SSO bridge flow:

```php
namespace Platform\CmsConnector\Bridges;

use Illuminate\Http\RedirectResponse;
use Platform\CmsConnector\ConnectorManager;
use Platform\CmsConnector\Contracts\AuthBridgeInterface;

class AuthBridge implements AuthBridgeInterface
{
    public function __construct(protected ConnectorManager $manager) {}

    public function redirect(): RedirectResponse
    {
        if (! auth()->check()) {
            abort(403, 'Must be authenticated on host to use SSO bridge.');
        }
        return redirect($this->manager->ssoRedirectUrl());
    }
}
```

The `SsoRedirectController` simply calls `AuthBridge::redirect()`.

The CMS side handles the JWT: verifies signature, looks up user by email, creates if `auto_create_users`, logs in, sets session cookie, redirects to relay_state (default: `/admin`).

### 7.2 `ModelSyncBridge` (Mode 2)

```php
namespace Platform\CmsConnector\Bridges;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Platform\CmsConnector\ConnectorManager;
use Platform\CmsConnector\Contracts\SyncableToCms;
use Platform\CmsConnector\Jobs\SyncModelToCmsJob;
use Platform\CmsConnector\Models\CmsConnectorSyncState;
use Platform\CmsConnector\Exceptions\SyncConflictException;

class ModelSyncBridge
{
    public function __construct(protected ConnectorManager $manager) {}

    public function onModelEvent(Model $model, string $event): void
    {
        if (! $model instanceof SyncableToCms) {
            return;
        }

        // Debounce: wait 5 seconds for further updates, then sync once
        $debounceKey = "cms-sync-debounce:" . get_class($model) . ":" . $model->getKey();
        Cache::put($debounceKey, true, now()->addSeconds(
            config('cms-connector.model_sync.syncable_models.' . get_class($model) . '.debounce_seconds', 5)
        ));

        SyncModelToCmsJob::dispatch(get_class($model), $model->getKey(), $event)
            ->delay(now()->addSeconds(
                config('cms-connector.model_sync.syncable_models.' . get_class($model) . '.debounce_seconds', 5)
            ))
            ->onQueue(config('cms-connector.model_sync.queue'));
    }

    public function sync(Model $model): void
    {
        if (! $model instanceof SyncableToCms) {
            throw new \InvalidArgumentException('Model must implement SyncableToCms');
        }

        $data = $model->toCmsEntryData();

        try {
            $response = $this->manager->getClient()->put(
                "/api/v1/collections/{$data['collection_handle']}/entries/{$data['slug']}",
                $data
            );

            CmsConnectorSyncState::updateOrCreate(
                [
                    'syncable_type' => get_class($model),
                    'syncable_id' => $model->getKey(),
                ],
                [
                    'cms_entry_id' => $response['data']['id'] ?? null,
                    'cms_entry_slug' => $data['slug'],
                    'last_synced_at' => now(),
                    'last_sync_direction' => 'host_to_cms',
                    'last_sync_status' => 'success',
                ]
            );
        } catch (\Throwable $e) {
            CmsConnectorSyncState::updateOrCreate(
                [
                    'syncable_type' => get_class($model),
                    'syncable_id' => $model->getKey(),
                ],
                [
                    'last_synced_at' => now(),
                    'last_sync_direction' => 'host_to_cms',
                    'last_sync_status' => 'failed',
                ]
            );
            throw $e;
        }
    }

    public function syncFromCms(array $entryData, string $modelClass): void
    {
        if (! is_subclass_of($modelClass, SyncableToCms::class)) {
            return;
        }

        $existing = CmsConnectorSyncState::where('cms_entry_slug', $entryData['slug'])
            ->where('syncable_type', $modelClass)
            ->first();

        if ($existing && $existing->last_synced_at && $existing->last_synced_at->gt(now()->subSeconds(10))) {
            // We just synced this to the CMS; ignore the echo
            return;
        }

        $model = $modelClass::fromCmsEntryData($entryData);

        CmsConnectorSyncState::updateOrCreate(
            [
                'syncable_type' => $modelClass,
                'syncable_id' => $model->getKey(),
            ],
            [
                'cms_entry_id' => $entryData['id'],
                'cms_entry_slug' => $entryData['slug'],
                'last_synced_at' => now(),
                'last_sync_direction' => 'cms_to_host',
                'last_sync_status' => 'success',
            ]
        );
    }
}
```

### 7.3 `EventBusBridge` (Mode 3)

```php
namespace Platform\CmsConnector\Bridges;

use Platform\CmsConnector\Jobs\ForwardEventToCmsJob;
use Platform\CmsConnector\Support\SignatureVerifier;

class EventBusBridge
{
    public function __construct(protected SignatureVerifier $signer) {}

    public function forwardToCms(object $event, string $eventType): void
    {
        $payload = [
            'event' => $eventType,
            'data' => $this->extractEventData($event),
            'source' => 'host:' . config('app.name'),
            'timestamp' => now()->toIso8601String(),
            'event_id' => $this->generateEventId($event),
        ];

        ForwardEventToCmsJob::dispatch($payload)
            ->onQueue(config('cms-connector.event_bus.retry_queue'));
    }

    public function signOutgoing(array $payload): string
    {
        return $this->signer->sign($payload, config('cms-connector.event_bus.signature_secret'));
    }

    protected function extractEventData(object $event): array
    {
        if (method_exists($event, 'toCmsPayload')) {
            return $event->toCmsPayload();
        }
        return json_decode(json_encode($event), true) ?? [];
    }

    protected function generateEventId(object $event): string
    {
        return \Illuminate\Support\Str::uuid()->toString();
    }
}
```

### 7.4 `EmbeddedBridge` (Mode 4)

The `EmbeddedCmsRouting` middleware intercepts requests to `/cms/*` and proxies them to the CMS internally:

```php
namespace Platform\CmsConnector\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Platform\CmsConnector\Support\CmsClient;

class EmbeddedCmsRouting
{
    public function __construct(protected CmsClient $client) {}

    public function handle(Request $request, Closure $next)
    {
        $prefix = config('cms-connector.embedded.route_prefix', 'cms');

        if (! $request->is($prefix . '/*') && ! $request->is($prefix)) {
            return $next($request);
        }

        // Strip prefix from URL
        $path = substr($request->path(), strlen($prefix) + 1);  // +1 for the /
        if ($path === false) $path = '';

        // Forward to CMS as internal HTTP request
        $response = $this->client->request(
            $request->method(),
            '/' . $path . ($request->getQueryString() ? '?' . $request->getQueryString() : ''),
            [
                'json' => $request->all(),
                'headers' => [
                    'X-Tenant-Id' => config('cms-connector.tenant_id'),
                    'X-Embedded-Mode' => 'true',
                    'X-Host-User-Id' => auth()->id(),
                ],
            ]
        );

        // Wrap response in host's layout if it's HTML
        $contentType = $response['headers']['Content-Type'][0] ?? '';
        if (str_contains($contentType, 'text/html')) {
            return response()->view('cms-connector::embedded-layout', [
                'cms_content' => $response['body'],
            ]);
        }

        return response($response['body'], $response['status'])
            ->withHeaders($response['headers']);
    }
}
```

### 7.5 `HeadlessClientBridge` (Mode 5)

The simplest bridge — it just exposes `ConnectorManager::collection()` and `graphql()` to the host. No additional class is strictly required; the `ConnectorManager` IS the headless client. This bridge class exists for symmetry and for testing isolation (it can be faked).

---

## 8. Console Commands

### 8.1 `cms-connector:install`

```
$ php artisan cms-connector:install

  CMS Connector Installer
  =======================

  This will publish config and run migrations. Continue? (yes/no) [yes]:
  > yes

  Config published: config/cms-connector.php

  Migrations run: 2 tables created (cms_connector_sync_state, cms_connector_event_log)

  Now let's configure your connection.

  CMS Base URL [https://cms.example.com]:
  > https://cms.shopland.test

  Tenant ID:
  > shopland

  API Token (from CMS admin → Connectors → Create):
  > 1|abc123def456...

  Shared Secret (HMAC, from CMS admin → Connectors → Create):
  > xyz789...

  SSO Bridge Secret (different from shared secret):
  > sso-secret-abc...

  Auth Bridge enabled? (true/false) [false]:
  > true

  Model Sync enabled? (true/false) [false]:
  > true

  Event Bus enabled? (true/false) [false]:
  > true

  Embedded Mode enabled? (true/false) [false]:
  > false

  Headless Client enabled? (true/false) [true]:
  > true

  .env updated with CMS_* variables.

  Testing connection...
  ✓ CMS reachable at https://cms.shopland.test
  ✓ Connector registered as ID: uuid-here
  ✓ All enabled modes responded to health check

  ✓ Installation complete! See config/cms-connector.php for advanced options.
```

### 8.2 `cms-connector:sync`

```
$ php artisan cms-connector:sync {model?} {--force}

  Without model argument: syncs ALL configured models.
  With model argument: syncs only that model class.

  --force  Re-sync even if last_synced_at is recent.

  Examples:
    php artisan cms-connector:sync
    php artisan cms-connector:sync App\\Models\\Product
    php artisan cms-connector:sync App\\Models\\Product --force
```

### 8.3 `cms-connector:status`

```
$ php artisan cms-connector:status

  CMS Connector Status
  ====================

  CMS Base URL:        https://cms.shopland.test
  Tenant ID:           shopland
  Connector ID:        uuid-here

  CMS Reachable:       ✓ (response: 87ms)
  Authenticated:       ✓ (token valid)

  Modes:
    auth_bridge:       ✓ enabled
      last SSO redirect: 2 hours ago
    model_sync:        ✓ enabled
      syncable models: App\Models\Product (3,432 records)
                       App\Models\BlogPost (87 records)
      last sync: 5 minutes ago (success)
      pending queue: 0 jobs
    event_bus:         ✓ enabled
      last event sent: 12 minutes ago
      last event received: 3 hours ago
      dedup table size: 1,234 rows
    embedded:          ✗ disabled
    headless:          ✓ enabled
      cache hit rate: 89.4%
      last API call: 2 seconds ago

  Circuit Breaker:
    state: closed
    failure count: 0

  ✓ All systems operational.
```

---

## 9. Database Tables (in host app)

### 9.1 `cms_connector_sync_state`

```sql
CREATE TABLE cms_connector_sync_state (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    syncable_type VARCHAR(255) NOT NULL,
    syncable_id BIGINT UNSIGNED NOT NULL,
    cms_entry_id CHAR(36) NULL,
    cms_entry_slug VARCHAR(255) NULL,
    last_synced_at TIMESTAMP NULL,
    last_sync_direction ENUM('host_to_cms', 'cms_to_host') NULL,
    last_sync_status ENUM('success', 'failed', 'conflict') NULL,
    conflict_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY cms_sync_unique (syncable_type, syncable_id),
    INDEX cms_sync_cms_entry (cms_entry_slug),
    INDEX cms_sync_status (last_sync_status)
);
```

### 9.2 `cms_connector_event_log`

```sql
CREATE TABLE cms_connector_event_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    received_at TIMESTAMP NOT NULL,
    processed_at TIMESTAMP NULL,
    processing_error TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY cms_event_unique (event_id),
    INDEX cms_event_type (event_type),
    INDEX cms_event_processed (processed_at)
);
```

---

## 10. Contracts (Interfaces)

### 10.1 `SyncableToCms`

```php
namespace Platform\CmsConnector\Contracts;

interface SyncableToCms
{
    /**
     * Convert this model to CMS entry data.
     * Must include: collection_handle, slug, status, data (array of field values).
     * Optionally: taxonomy_terms (array keyed by taxonomy handle).
     */
    public function toCmsEntryData(): array;

    /**
     * Reverse: apply CMS entry data to this model (static constructor or updater).
     * Called when an entry is updated in the CMS.
     */
    public static function fromCmsEntryData(array $data): static;
}
```

### 10.2 `CmsEventSubscriber`

```php
namespace Platform\CmsConnector\Contracts;

interface CmsEventSubscriber
{
    /**
     * Handle a CMS event received via webhook.
     */
    public function handle(string $eventType, array $payload): void;
}
```

### 10.3 `BridgeInterface`

```php
namespace Platform\CmsConnector\Contracts;

interface BridgeInterface
{
    /**
     * Whether this bridge is enabled in the host's config.
     */
    public function isEnabled(): bool;

    /**
     * Health check for this bridge (used by StatusCommand).
     */
    public function healthCheck(): array;
}
```

---

## 11. Jobs

### 11.1 `SyncModelToCmsJob`

```php
namespace Platform\CmsConnector\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncModelToCmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $modelClass,
        public int $modelId,
        public string $event,
    ) {}

    public function handle(\Platform\CmsConnector\Bridges\ModelSyncBridge $bridge): void
    {
        $model = $this->modelClass::find($this->modelId);

        if (! $model) {
            // Model was deleted before job ran; treat as delete on CMS side
            if ($this->event === 'deleted') {
                $bridge->deleteFromCms($this->modelClass, $this->modelId);
            }
            return;
        }

        $bridge->sync($model);
    }

    public function failed(\Throwable $exception): void
    {
        // Log failure, update sync_state, optionally alert
    }
}
```

### 11.2 `ForwardEventToCmsJob`

```php
namespace Platform\CmsConnector\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ForwardEventToCmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        $signature = app(\Platform\CmsConnector\Support\SignatureVerifier::class)
            ->sign($this->payload, config('cms-connector.event_bus.signature_secret'));

        Http::withHeaders([
            'X-Cms-Signature' => $signature,
            'Content-Type' => 'application/json',
        ])
        ->withToken(config('cms-connector.api_token'))
        ->post(
            config('cms-connector.cms_base_url') . '/api/v1/webhooks/incoming',
            $this->payload
        );
    }

    public $tries = 3;
    public $backoff = [10, 30, 60];
}
```

### 11.3 `ProcessIncomingWebhookJob`

```php
namespace Platform\CmsConnector\Jobs;

use Illuminate\Bus\Queueable;
// ... standard ShouldQueue traits
use Platform\CmsConnector\Models\CmsConnectorEventLog;

class ProcessIncomingWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $payload,
        public string $eventType,
        public string $eventId,
    ) {}

    public function handle(): void
    {
        // Dedup check
        if (CmsConnectorEventLog::where('event_id', $this->eventId)->whereNotNull('processed_at')->exists()) {
            return;
        }

        $log = CmsConnectorEventLog::firstOrCreate(
            ['event_id' => $this->eventId],
            [
                'event_type' => $this->eventType,
                'payload' => $this->payload,
                'received_at' => now(),
            ]
        );

        try {
            $subscriberClass = config("cms-connector.event_bus.subscriptions.{$this->eventType}");
            if ($subscriberClass && class_exists($subscriberClass)) {
                $subscriber = app($subscriberClass);
                $subscriber->handle($this->eventType, $this->payload);
            }

            $log->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            $log->update(['processing_error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

---

## 12. Testing Tools

### 12.1 `FakeBridge`

For host apps that want to test their connector integration without a live CMS:

```php
namespace Platform\CmsConnector\Support;

use Platform\CmsConnector\Contracts\BridgeInterface;

class FakeBridge implements BridgeInterface
{
    public array $syncedModels = [];
    public array $forwardedEvents = [];
    public array $receivedEvents = [];

    public function isEnabled(): bool { return true; }
    public function healthCheck(): array { return ['status' => 'ok']; }

    public function fakeSync(object $model): void
    {
        $this->syncedModels[] = $model;
    }

    public function fakeForwardEvent(array $payload): void
    {
        $this->forwardedEvents[] = $payload;
    }

    public function fakeReceiveEvent(string $eventType, array $payload): void
    {
        $this->receivedEvents[] = compact('eventType', 'payload');
    }
}
```

### 12.2 Pest Testing Helpers

```php
// tests/Pest.php (host app)
use Platform\CmsConnector\Support\FakeBridge;

function fakeCmsConnector(): FakeBridge
{
    $fake = new FakeBridge();
    app()->instance(\Platform\CmsConnector\Bridges\ModelSyncBridge::class, $fake);
    app()->instance(\Platform\CmsConnector\Bridges\EventBusBridge::class, $fake);
    return $fake;
}
```

Usage in host tests:

```php
it('syncs product to CMS on create', function () {
    $fake = fakeCmsConnector();

    \App\Models\Product::create([
        'name' => 'Test Product',
        'price' => 99,
    ]);

    expect($fake->syncedModels)->toHaveCount(1);
    expect($fake->syncedModels[0]->name)->toBe('Test Product');
});
```

---

## 13. Security Considerations

1. **Shared secrets** — `CMS_SHARED_SECRET`, `CMS_AUTH_BRIDGE_SECRET`, `CMS_EVENT_BUS_SECRET` MUST be 32+ character random strings. They MUST be different from each other. Never commit them to git.
2. **JWT TTL** — SSO JWTs expire in 60 seconds. Replay attacks are mitigated.
3. **HMAC signature** — all incoming webhooks MUST be HMAC-verified. Reject with 401 if signature is invalid. Use constant-time comparison (`hash_equals`).
4. **Circuit breaker** — protects the host from CMS downtime. After 5 consecutive failures, the circuit opens for 60 seconds. During this time, all CMS calls throw `CircuitOpenException` immediately without HTTP round-trip.
5. **Cache fallback** — if `stale_while_revalidate` is enabled, cached responses are served when the CMS is unreachable. The host continues to function (with potentially stale data) until the CMS recovers.
6. **X-Connector-Id header** — every CMS-bound request includes this header for audit attribution. CMS logs all connector activity with this ID.
7. **No plaintext secrets in DB** — `CmsConnectorSyncState` and `CmsConnectorEventLog` tables store no secrets. The CMS API token is in `.env` only.
8. **TLS required in production** — `CMS_BASE_URL` MUST be HTTPS in production. The package refuses to start in `embedded` mode on non-HTTPS URLs in production.
9. **Rate limiting** — the CMS applies rate limits per connector token. The host should respect `429 Too Many Requests` responses by backing off (handled automatically by `CmsClient` retry logic).
10. **Tenant isolation** — every CMS API call from the connector is scoped to the configured `tenant_id`. The connector CANNOT access other tenants' data, even accidentally.

---

## 14. Versioning & Release Strategy

- **Semantic versioning** — `MAJOR.MINOR.PATCH`.
- **MAJOR** — breaking config changes, dropped Laravel version support.
- **MINOR** — new features, new bridge modes, backward-compatible.
- **PATCH** — bug fixes only.
- **Laravel version support** — current LTS + previous LTS. As of 2026: Laravel 10.x, 11.x. Drop support when each reaches EOL.
- **PHP version support** — `^8.1` (the minimum for Laravel 10). Drop PHP 7.x support entirely.

---

## 15. Release Checklist

For each release:

- [ ] All Pest tests pass on Laravel 10.x and 11.x
- [ ] PHPStan level 6 passes
- [ ] CHANGELOG.md updated
- [ ] UPGRADE.md updated if breaking changes
- [ ] README.md updated with new features
- [ ] docs/ updated for any new modes or config options
- [ ] Tagged release on GitHub
- [ ] Tag pushed to Packagist (`composer require platform/laravel-cms-connector:^x.y`)
- [ ] Test install in a fresh Laravel app to verify the install command works

---

## 16. Common Host App Integration Patterns

### Pattern A: Host is a SaaS app with its own users, CMS powers the marketing site

- Use modes: `auth_bridge` (so host admins can edit CMS content), `headless` (so host app can pull CMS content for embedding in SaaS UI)
- Don't use: `model_sync` (host data is private SaaS data, not for CMS), `event_bus` (no need)

### Pattern B: Host is an e-commerce app, CMS powers blog + product descriptions

- Use modes: `auth_bridge`, `model_sync` (sync Product model to CMS products collection so editors can manage product copy), `headless` (so host app can render CMS blog posts on the shop)
- Optional: `event_bus` (forward OrderPlaced events to CMS for analytics)

### Pattern C: Host is a CRM, CMS powers the customer portal docs

- Use modes: `auth_bridge` (CRM agents can edit docs), `headless` (customer portal renders CMS docs)
- Don't use: `model_sync`, `event_bus`

### Pattern D: Host wants the full CMS admin at /admin/cms inside their existing app

- Use mode: `embedded` only
- All other modes can be added later if needed

### Pattern E: Host is headless Jamstack site, CMS is the only admin

- Use mode: `headless` only (via Next.js/Nuxt server-side API routes that use this PHP package, OR via the CMS's REST/GraphQL API directly)

---

## 17. Troubleshooting Guide

| Symptom | Likely Cause | Fix |
|---|---|---|
| `CmsUnreachableException` on every call | CMS is down OR base URL is wrong | Check `CMS_BASE_URL`, verify CMS is up |
| Circuit breaker always open | 5+ consecutive failures | Run `cms-connector:status`, check error log, fix root cause, wait 60s for reset |
| SSO redirect fails with 401 | JWT secret mismatch | Verify `CMS_AUTH_BRIDGE_SECRET` matches CMS-side config |
| Webhooks received but not processed | HMAC signature mismatch | Verify `CMS_EVENT_BUS_SECRET` matches CMS-side config |
| Model sync one-way only | `direction` config is `host_to_cms` or `cms_to_host` | Set to `bidirectional` |
| Model sync creates duplicates | Echo loop (CMS → host → CMS) | Verify `last_synced_at` check in `syncFromCms` |
| Embedded mode 404s | CMS doesn't have `tenancy_identification_mode=path` for this tenant | Configure CMS tenant for path-based identification |
| Headless API returns stale data | Cache TTL too high | Lower `cache_ttl` for the collection in config |
| `circuit_open` exception in queue workers | Workers don't share circuit state | Use Redis cache store for circuit breaker (already default) |

---

## 18. Future Roadmap (V5+)

- **WordPress connector** — same package structure for connecting existing WordPress sites
- **Shopify connector** — for Shopify stores wanting CMS-managed blog/pages
- **Custom webhooks UI** — host can configure webhook subscriptions from a UI instead of config file
- **Bidirectional collab editing** — host app's text fields use the same Yjs sync as CMS
- **Auto-discovery of syncable models** — scan host's `app/Models/` for `SyncableToCms` implementations, suggest config
- **Real-time sync dashboard** — WebSocket-based live view of sync state
- **Diff viewer for sync conflicts** — UI for resolving manual conflicts

---

*End of Laravel Integration Kit V4 Specification. Companion files: `04-FIELD-STRUCTURE-SPEC-V4.md`, `04-AI-BUILD-PROMPTS-V4.md`, `04-V3-TO-V4-MIGRATION-GUIDE.md`.*
