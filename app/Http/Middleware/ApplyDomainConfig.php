<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Apply domain config (custom headers, robots.txt override, favicon, og_image).
 */
class ApplyDomainConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenant_has_feature('multi_domain')) {
            return $next($request);
        }

        if (! tenancy()->initialized) {
            return $next($request);
        }

        $domain = app()->bound('current.domain') ? (app()->bound('current.domain') ? app('current.domain') : null) : null;

        if (! $domain || ! $domain->config) {
            return $next($request);
        }

        /** @var Response $response */
        $response = $next($request);

        // Apply custom headers
        $customHeaders = $domain->getConfigValue('custom_headers', []);
        foreach ($customHeaders as $key => $value) {
            $response->headers->set($key, $value);
        }

        // Apply www redirect if configured
        $wwwRedirect = $domain->getConfigValue('redirect_www');
        if ($wwwRedirect && $response->isSuccessful()) {
            $host = $request->getHost();
            if ($wwwRedirect === 'non-www' && str_starts_with($host, 'www.')) {
                $newUrl = preg_replace('/^www\./', '', $request->fullUrl());
                return redirect()->away($newUrl, 301);
            }
            if ($wwwRedirect === 'www' && ! str_starts_with($host, 'www.')) {
                $newUrl = preg_replace('/^https?:\/\//', '', $request->fullUrl());
                $newUrl = 'https://www.' . $newUrl;
                return redirect()->away($newUrl, 301);
            }
        }

        return $response;
    }
}
