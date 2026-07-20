<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V3: Resolve the active theme for the current tenant (or domain override in V4).
 */
class ResolveTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenancy()->initialized) {
            return $next($request);
        }

        $domain = app()->bound('current.domain') ? (app()->bound('current.domain') ? app('current.domain') : null) : null;
        $themeId = null;

        // V4: Per-domain theme override takes precedence
        if ($domain && $domain->theme_id && tenant_has_feature('multi_domain')) {
            $themeId = $domain->theme_id;
        } elseif (tenant() && tenant()->current_theme_id) {
            $themeId = tenant()->current_theme_id;
        }

        if ($themeId) {
            $theme = \App\Models\Central\Theme::find($themeId);
            if ($theme) {
                app()->instance('current.theme', $theme);
            }
        }

        return $next($request);
    }
}
