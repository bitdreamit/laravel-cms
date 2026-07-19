<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Resolve SAML tenant context — identifies which IdP is being targeted.
 * Used by /saml/* routes.
 */
class ResolveSamlTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenant_has_feature('saml_sso')) {
            abort(404);
        }

        // Tenancy should already be initialized by InitializeTenancyByDomain
        if (! tenancy()->initialized) {
            abort(404, 'SAML endpoints require tenant context.');
        }

        return $next($request);
    }
}
