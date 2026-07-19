<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Force HTTPS redirect if domain config requires it.
 */
class EnforceHttps
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

        if (! $domain) {
            return $next($request);
        }

        $forceHttps = $domain->getConfigValue('force_https', false);

        if ($forceHttps && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
