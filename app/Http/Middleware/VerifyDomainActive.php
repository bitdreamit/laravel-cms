<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Verify domain is active (not parked, not redirect-only).
 */
class VerifyDomainActive
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

        // Parked domains return 503
        if ($domain->status === 'parked') {
            return response()->view('errors.parked', [], 503);
        }

        // Redirect-only domains 301 to redirect_target
        if ($domain->status === 'redirect_only' && $domain->redirect_target) {
            $target = $domain->redirect_target;
            if (! str_starts_with($target, 'http')) {
                $target = 'https://' . $target;
            }
            return redirect()->away($target, 301);
        }

        return $next($request);
    }
}
