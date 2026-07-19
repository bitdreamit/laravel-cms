<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Require elevated session (password re-entry within last 15 minutes).
 * Used for sensitive operations like changing billing, deleting users, editing theme files.
 */
class RequireElevatedSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $elevatedAt = session('elevated_session_at');

        if (! $elevatedAt || now()->diffInMinutes($elevatedAt) > 15) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Elevated session required'], 423);
            }
            return redirect()->route('admin.elevated-session.show', ['redirect' => $request->fullUrl()]);
        }

        return $next($request);
    }
}
