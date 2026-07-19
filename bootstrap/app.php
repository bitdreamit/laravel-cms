<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Central/platform routes — NO tenancy middleware
            // (handled by route groups in each route file)

            // V3 + V4 tenant route files — each defines its own middleware group
            Route::middleware(['web'])
                ->group(base_path('routes/tenant-web.php'));
            Route::middleware(['web', 'auth'])
                ->prefix('admin')
                ->group(base_path('routes/tenant-admin.php'));
            Route::middleware(['web', 'saml'])
                ->group(base_path('routes/saml.php'));
            Route::middleware(['api', 'scim-auth'])
                ->group(base_path('routes/scim.php'));
            Route::middleware(['api'])
                ->prefix('api/v1/connector')
                ->group(base_path('routes/connector.php'));
            Route::middleware(['web'])
                ->group(base_path('routes/collab.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // TENANT middleware group — runs tenancy resolution + V4 features.
        // Applied ONLY to tenant routes (tenant-web, tenant-admin, saml, collab),
        // NOT to central/platform-owner routes.
        $middleware->group('tenant', [
            \App\Http\Middleware\InitializeTenancyByDomain::class,
            \App\Http\Middleware\ResolveWildcardDomain::class,
            \App\Http\Middleware\PreventAccessFromCentralDomains::class,
            \App\Http\Middleware\VerifyDomainActive::class,
            \App\Http\Middleware\EnforceHttps::class,
            \App\Http\Middleware\TenantActiveGate::class,
            \App\Http\Middleware\ResolveTheme::class,
            \App\Http\Middleware\ResolveSite::class,
            \App\Http\Middleware\ApplyDomainConfig::class,
            \App\Http\Middleware\AssignExperimentVariant::class,
            \App\Http\Middleware\ApplyPersonalization::class,
        ]);

        // CENTRAL middleware group — for platform-owner console only.
        // NO tenancy middleware — these routes run in central context.
        $middleware->group('central', [
            // Add platform-specific middleware here if needed (e.g. ip-whitelist)
        ]);

        // API middleware group — for public + connector APIs.
        // Tenancy is initialized via InitializeTenancyByDomain (which works
        // for both web and API routes since it reads the Host header).
        $middleware->group('tenant-api', [
            \App\Http\Middleware\InitializeTenancyByDomain::class,
            \App\Http\Middleware\ResolveWildcardDomain::class,
            \App\Http\Middleware\TenantActiveGate::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'tenant' => \App\Http\Middleware\InitializeTenancyByDomain::class,
            'theme' => \App\Http\Middleware\ResolveTheme::class,
            'connector-auth' => \App\Http\Middleware\RequireConnectorAuth::class,
            'scim-auth' => \App\Http\Middleware\RequireScimToken::class,
            'saml' => \App\Http\Middleware\ResolveSamlTenant::class,
            'elevated' => \App\Http\Middleware\RequireElevatedSession::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
