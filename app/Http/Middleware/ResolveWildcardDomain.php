<?php

namespace App\Http\Middleware;

use App\Models\Central\Domain;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Resolve wildcard domains (e.g. *.example.com).
 *
 * Runs AFTER InitializeTenancyByDomain. If the standard middleware failed to
 * resolve a tenant (because the domain is a wildcard), this middleware looks
 * up wildcard domains for the host pattern and resolves the tenant.
 */
class ResolveWildcardDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenant_has_feature('multi_domain')) {
            return $next($request);
        }

        // If tenancy was already initialized by V3 middleware, do nothing
        if (tenancy()->initialized) {
            return $next($request);
        }

        $host = $request->getHost();

        // Look up wildcard domains
        $wildcardDomains = Domain::where('is_wildcard', true)
            ->where('dns_verification_status', 'verified')
            ->get();

        foreach ($wildcardDomains as $domain) {
            if ($domain->matchesHost($host)) {
                // Found a wildcard match — initialize tenancy manually
                $tenant = $domain->tenant;

                if (! $tenant || $tenant->status !== 'active') {
                    abort(404, 'Tenant not found or inactive.');
                }

                tenancy()->initialize($tenant);

                // Store the wildcard segment in request attributes
                $segment = $domain->extractWildcardSegment($host);
                $request->attributes->set('wildcard_segment', $segment);
                $request->attributes->set('wildcard_domain', $domain);

                // Also store current domain globally
                app()->instance('current.domain', $domain);

                return $next($request);
            }
        }

        return $next($request);
    }
}
