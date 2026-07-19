<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'name' => env('APP_NAME', 'Laravel CMS V4'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(
            array_map(strval(...), array_keys(array_fill_keys(explode(',', env('APP_PREVIOUS_KEYS', '')), true)))
        ),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
    'providers' => ServiceProvider::defaultProviders()->merge([
        // V3
        \Stancl\Tenancy\TenancyServiceProvider::class,
        \Spatie\Permission\PermissionServiceProvider::class,
        \Spatie\MediaLibrary\MediaLibraryServiceProvider::class,
        \Spatie\Activitylog\ActivitylogServiceProvider::class,
        \Laravel\Fortify\FortifyServiceProvider::class,
        \Laravel\Sanctum\SanctumServiceProvider::class,
        \Laravel\Telescope\TelescopeServiceProvider::class,
        \Inertia\ServiceProvider::class,
        // V4 (SAML auto-registers via package discovery from scaler-tech/laravel-saml2)
        \App\Providers\V4ServiceProvider::class,
        \App\Providers\CmsServiceProvider::class,
        \App\Providers\TenancyServiceProvider::class,
        \App\Providers\EventServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        'Acme' => \App\Support\Facades\Acme::class,
        'CmsConnector' => \App\Support\Facades\CmsConnector::class,
        'Theme' => \App\Support\Facades\Theme::class,
        'Rag' => \App\Support\Facades\Rag::class,
        'Workflow' => \App\Support\Facades\Workflow::class,
        'Experiment' => \App\Support\Facades\Experiment::class,
        'Audit' => \App\Support\Facades\Audit::class,
    ])->toArray(),
];
