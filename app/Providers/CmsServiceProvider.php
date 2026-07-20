<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * Helpers MUST be loaded in register() not boot() so they are
     * available before other providers' boot() methods run.
     */
    public function register(): void
    {
        // Register helper functions (tenant_has_feature, current_domain, etc.)
        // Must be loaded BEFORE V4ServiceProvider::boot() which calls tenant_has_feature()
        require_once app_path('Support/Helpers/helpers.php');
    }

    public function boot(): void
    {
        //
    }
}
