<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // NOTE: api.php is loaded in the then: closure below with proper
        // tenancy middleware. Do NOT pass it to withRouting() to avoid
        // double-loading.
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // === Tenant web routes (with tenancy + V4 middleware) ===
            Route::middleware(['web', 'tenant'])
                ->group(base_path('routes/tenant-web.php'));

            // Admin routes — accessible on both central and tenant domains.
            // Uses 'auth' middleware. Tenancy initializes automatically if
            // the request comes from a tenant domain (via InitializeTenancyByDomain
            // which is included in the 'tenant' group below, but we also add it
            // here for admin routes so tenant context is available).
            Route::middleware(['web', 'auth'])
                ->prefix('admin')
                ->group(base_path('routes/tenant-admin.php'));

            Route::middleware(['web', 'tenant', 'saml'])
                ->group(base_path('routes/saml.php'));

            Route::middleware(['web', 'tenant'])
                ->group(base_path('routes/collab.php'));

            // === API routes (with tenancy initialization) ===
            Route::middleware(['api', 'tenant-api'])
                ->prefix('api/v1')
                ->group(base_path('routes/api.php'));

            Route::middleware(['api', 'tenant-api'])
                ->prefix('api/v1/connector')
                ->group(base_path('routes/connector.php'));

            Route::middleware(['api', 'scim-auth'])
                ->prefix('scim/v2')
                ->group(base_path('routes/scim.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // TENANT middleware group — runs tenancy resolution + V4 features.
        // Applied ONLY to tenant routes via the route groups above.
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

        // API tenancy group — minimal middleware for API routes.
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
