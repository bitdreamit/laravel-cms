<?php

namespace App\Http\Middleware;

use App\Models\Tenant\ScimToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Authenticate SCIM requests via bearer token.
 */
class RequireScimToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenant_has_feature('scim_provisioning')) {
            abort(404);
        }

        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                'detail' => 'Bearer token required',
                'status' => 401,
            ], 401);
        }

        $tokenHash = hash('sha256', $token);
        $scimToken = ScimToken::where('token_hash', $tokenHash)
            ->where('is_active', true)
            ->first();

        if (! $scimToken || $scimToken->isExpired()) {
            return response()->json([
                'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                'detail' => 'Invalid or expired token',
                'status' => 401,
            ], 401);
        }

        $scimToken->touchLastUsed();

        // Set SCIM token in request for controllers to access
        $request->attributes->set('scim_token', $scimToken);

        return $next($request);
    }
}
