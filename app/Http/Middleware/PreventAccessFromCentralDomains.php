<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventAccessFromCentralDomains
{
    public function handle(Request $request, Closure $next): Response
    {
        // V3 stub: prevent tenant routes from being accessed on central domains
        return $next($request);
    }
}
