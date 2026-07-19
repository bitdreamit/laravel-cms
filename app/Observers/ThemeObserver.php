<?php

namespace App\Observers;

use App\Models\Central\Theme;
use Illuminate\Support\Facades\Cache;

class ThemeObserver
{
    public function updated(Theme $theme): void
    {
        // Clear all cache for tenants using this theme
        $tenants = Tenant::where('current_theme_id', $theme->id)->get();
        foreach ($tenants as $tenant) {
            Cache::forget("theme:{$tenant->id}:settings");
        }
    }

    public function deleted(Theme $theme): void
    {
        // Reset tenants using this theme to foundation
        Tenant::where('current_theme_id', $theme->id)->update(['current_theme_id' => null]);
    }
}
