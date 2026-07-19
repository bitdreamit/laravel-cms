<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * V3: Initialize tenancy by domain (multi-tenancy entry point).
 * Wraps stancl/tenancy's InitializeTenancyByDomain middleware.
 */
class InitializeTenancyByDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Tenancy $tenancy */
        $tenancy = app(Tenancy::class);

        if ($tenancy->initialized) {
            return $next($request);
        }

        $host = $request->getHost();

        // Skip tenancy for central/platform domain
        $centralDomains = config('tenancy.central_domains', []);
        if (in_array($host, $centralDomains)) {
            return $next($request);
        }

        // Look up domain (exact match only — wildcards handled by ResolveWildcardDomain)
        $domain = \App\Models\Central\Domain::where('domain', $host)
            ->where('dns_verification_status', 'verified')
            ->first();

        if ($domain) {
            $tenant = $domain->tenant;

            if (! $tenant || $tenant->status !== 'active') {
                abort(404, 'Tenant not found or inactive.');
            }

            $tenancy->initialize($tenant);
            app()->instance('current.domain', $domain);
        }

        return $next($request);
    }
}
