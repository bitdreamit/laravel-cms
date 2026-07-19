<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Authenticate connector API requests.
 * Connector endpoints accept Sanctum tokens issued via /api/v1/connector/register.
 */
class RequireConnectorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'error' => 'Bearer token required',
            ], 401);
        }

        // Sanctum will handle the actual token validation via its guard.
        // This middleware just ensures the request has a token.
        // Configure 'connector-auth' => 'sanctum' in config/auth.php for the actual guard.

        return $next($request);
    }
}
