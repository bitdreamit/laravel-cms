<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register helper autoload
        require_once app_path('Support/Helpers/helpers.php');

        // Configure Sanctum for API tokens
        config(['sanctum.middleware.encrypt_cookies' => \App\Http\Middleware\EncryptCookies::class]);
    }
}
