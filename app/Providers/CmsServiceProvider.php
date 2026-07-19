<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register helper functions (tenant_has_feature, current_domain, etc.)
        require_once app_path('Support/Helpers/helpers.php');
    }
}
