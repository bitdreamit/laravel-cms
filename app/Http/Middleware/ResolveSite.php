<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Resolve the active Site (locale) for the request.
 * If domain.site_id is set, use it; otherwise use tenant default site.
 */
class ResolveSite
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenant_has_feature('multi_domain')) {
            return $next($request);
        }

        if (! tenancy()->initialized) {
            return $next($request);
        }

        $domain = app('current.domain');
        $site = null;

        if ($domain && $domain->site_id) {
            $site = \App\Models\Tenant\Site::find($domain->site_id);
        }

        if (! $site) {
            // Fall back to tenant's default site
            $site = \App\Models\Tenant\Site::where('is_default', true)->first();
        }

        if ($site) {
            app()->instance('current.site', $site);
            app()->setLocale($site->locale ?? config('app.locale'));
        }

        return $next($request);
    }
}
