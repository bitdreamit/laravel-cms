<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantActiveGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (tenancy()->initialized) {
            $tenant = tenant();
            if ($tenant && $tenant->status === 'suspended') {
                abort(403, 'Tenant suspended.');
            }
        }
        return $next($request);
    }
}
