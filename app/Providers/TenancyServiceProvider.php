<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Tenancy;

class TenancyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register central + tenant migration paths
        $this->app->afterResolving('migrator', function ($migrator) {
            $migrator->path(database_path('migrations/central'));
            $migrator->path(database_path('migrations/tenant'));
        });
    }

    public function register(): void
    {
        // Configure stancl/tenancy for single-DB mode
        config([
            'tenancy.tenant_model' => \App\Models\Central\Tenant::class,
            'tenancy.domain_model' => \App\Models\Central\Domain::class,
            'tenancy.central_domains' => [config('app.central_domain', 'platform.test')],
            'tenancy.database_mode' => 'single',
        ]);
    }
}
