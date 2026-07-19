<?php

use Stancl\Tenancy\Database\Models\Tenant;

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    */
    'tenant_model' => \App\Models\Central\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Domain Model
    |--------------------------------------------------------------------------
    */
    'domain_model' => \App\Models\Central\Domain::class,

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |
    | Domains that serve the central/platform-owner app (not tenant-scoped).
    | Requests to these domains do NOT initialize tenancy.
    |
    | Set via APP_CENTRAL_DOMAIN env var.
    */
    'central_domains' => [
        env('APP_CENTRAL_DOMAIN', 'platform.test'),
        'localhost',
        '127.0.0.1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Mode
    |
    | This project uses SINGLE-DATABASE mode — all tenants share one DB,
    | isolated by tenant_id column + BelongsToTenant trait.
    |
    | DO NOT change to 'database' or 'hybrid' without a full migration.
    */
    'database_mode' => 'single',

    /*
    |--------------------------------------------------------------------------
    | Identification Middleware
    |--------------------------------------------------------------------------
    */
    'identification' => [
        'mode' => 'domain',

        // Custom middleware for domain-based tenant identification
        'middleware' => [
            \App\Http\Middleware\InitializeTenancyByDomain::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenancy Bootstrappers
    |
    | These run when tenancy is initialized, configuring the app for the tenant.
    |--------------------------------------------------------------------------
    */
    'bootstrappers' => [
        // In single-DB mode, we don't switch databases — we just cache the tenant_id.
        // The BelongsToTenant trait + global scope handles query isolation.
        \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Tenant Awareness
    |
    | CRITICAL: Ensures queued jobs dispatched from tenant context are
    | re-initialized with the correct tenant when they run on the worker.
    |
    | Without this, DispatchWebhook and other tenant-scoped jobs would
    | run without tenant context, causing BelongsToTenant global scope
    | to filter out all records (returning null).
    |
    | Set to true to automatically tag queued jobs with tenant_id.
    */
    'queue' => [
        'tenant_aware' => true,

        // Jobs that should NOT be tenant-aware (run in central context)
        'not_tenant_aware' => [
            \App\Console\Commands\GenerateInvoices::class,
            \App\Console\Commands\SuspendOverdueTenants::class,
            \App\Console\Commands\ReactivatePaidTenants::class,
            \App\Console\Commands\SendBillingReminders::class,
            \App\Console\Commands\CmsBackup::class,
            \App\Console\Commands\VerifyAuditChain::class,
            \App\Console\Commands\RenewSslCertificates::class,
            \App\Console\Commands\RetryFailedDns::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database (central connection — used for central tables)
    |--------------------------------------------------------------------------
    */
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        // In single-DB mode, tenant connection = central connection
        'tenant_connection' => env('DB_CONNECTION', 'mysql'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Tenancy
    |--------------------------------------------------------------------------
    */
    'redis' => [
        'prefix_base' => 'tenant:',  // e.g. tenant:{uuid}:cache:...
        'prefixed_connections' => ['default', 'cache'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tenancy
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'tag_base' => 'tenant',  // Cache tag: tenant:{uuid}
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Tenancy
    |--------------------------------------------------------------------------
    */
    'filesystem' => [
        // In single-DB mode, we use tenant_id-prefixed paths, not separate disks
        'suffix_base' => 'tenant-',
        // Override root paths per tenant (set to null to use default disks)
        'disks' => [],
        // Root path for asset storage (assets/ prefix already tenant-scoped via DB)
        'asset_path' => 'assets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => true,  // Auto-load routes/tenant.php if exists

    /*
    |--------------------------------------------------------------------------
    | Migrations
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'path' => database_path('migrations/tenant'),
        'parameters' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Parameters
    |--------------------------------------------------------------------------
    */
    'parameters' => [
        'tenant_parameter_name' => 'tenant',  // Route parameter name
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |
    | Cross-cutting features that can be toggled.
    |--------------------------------------------------------------------------
    */
    'features' => [
        'test' => false,  // Enable test mode (skips some bootstrappers)
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'override_default' => false,  // Don't override default disk in single-DB mode
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Tenant
    |--------------------------------------------------------------------------
    */
    'default_tenant' => null,

];
