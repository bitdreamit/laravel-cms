<?php

namespace App\Domain\Theme\Services;

use App\Models\Central\Domain;
use App\Models\Central\Theme;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\View;

class ThemeResolver
{
    public function resolve(?Tenant $tenant = null, ?Domain $domain = null): ?Theme
    {
        $tenant ??= tenant();
        $domain ??= app()->bound('current.domain') ? (app()->bound('current.domain') ? app('current.domain') : null) : null;

        // V4: Per-domain theme override takes precedence
        if ($domain && $domain->theme_id && tenant_has_feature('multi_domain')) {
            $theme = Theme::find($domain->theme_id);
            if ($theme) return $theme;
        }

        // Tenant default theme
        if ($tenant && $tenant->current_theme_id) {
            return Theme::find($tenant->current_theme_id);
        }

        // Platform default theme
        $defaultSlug = config('themes.default_theme', 'foundation');
        return Theme::where('slug', $defaultSlug)->first();
    }

    public function getViewCascade(?Theme $theme = null): array
    {
        $theme ??= $this->resolve();
        if (! $theme) {
            return [resource_path('views')];
        }

        $cascade = [$theme->path . '/views'];
        $current = $theme;

        while ($current->parent_id) {
            $parent = $current->parent;
            if (! $parent) break;
            $cascade[] = $parent->path . '/views';
            $current = $parent;
        }

        // Final fallback to platform views
        $cascade[] = resource_path('views');

        return $cascade;
    }

    public function resolveView(string $view): ?string
    {
        $cascade = $this->getViewCascade();

        foreach ($cascade as $path) {
            $fullPath = "{$path}/" . str_replace('.', '/', $view) . '.blade.php';
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    public function resolveAsset(string $path): ?string
    {
        $cascade = $this->getViewCascade();
        $theme = $this->resolve();

        foreach ($cascade as $viewPath) {
            $themePath = str_replace('/views', '', $viewPath);
            $fullPath = "{$themePath}/assets/{$path}";
            if (file_exists($fullPath)) {
                $slug = $theme?->slug ?? 'foundation';
                return "/themes/{$slug}/assets/{$path}";
            }
        }

        return null;
    }

    public function registerViewNamespaces(): void
    {
        $theme = $this->resolve();
        if (! $theme) return;

        View::addNamespace('theme', $theme->path . '/views');

        // Also register parent themes so @includeTheme falls through
        $cascade = $this->getViewCascade();
        foreach (array_slice($cascade, 1) as $path) {
            if (str_starts_with($path, base_path('themes/'))) {
                $themePath = str_replace('/views', '', $path);
                View::prependNamespace('theme', $themePath . '/views');
            }
        }
    }
}
