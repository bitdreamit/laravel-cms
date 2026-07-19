<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Simple GraphQL controller.
 * In production, use nuwave/lighthouse for full schema auto-generation from blueprints.
 */
class GraphQLController extends Controller
{
    public function handle(Request $request)
    {
        $query = $request->input('query');
        $variables = $request->input('variables', []);

        // Simple implementation — in production, delegate to nuwave/lighthouse
        // For now, return a basic introspection response
        return response()->json([
            'data' => [
                'entries' => [
                    'edges' => [],
                ],
            ],
        ]);
    }
}
