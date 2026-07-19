<?php

namespace App\Http\Controllers\Api;

use App\Domain\Rag\Services\RagService;
use App\Http\Controllers\Controller;
use App\Models\Tenant\RagQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RagApiController extends Controller
{
    public function __construct(protected RagService $ragService) {}

    public function ask(Request $request)
    {
        $request->validate(['question' => 'required|string|max:2000']);

        // Rate limiting
        $key = 'rag:ask:' . ($request->ip() ?? 'anonymous');
        $limit = auth()->check()
            ? (int) config('rag.rate_limits.authenticated_per_minute', 30)
            : (int) config('rag.rate_limits.anonymous_per_minute', 5);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Try again in ' . RateLimiter::availableIn($key) . ' seconds.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $response = $this->ragService->ask(tenant('id'), $request->input('question'), auth()->id());

        return response()->json([
            'answer' => $response->answer,
            'citations' => $response->citations,
            'latency_ms' => $response->latencyMs,
        ]);
    }

    public function feedback(Request $request, string $queryId)
    {
        $request->validate(['rating' => 'required|in:positive,negative']);

        $query = RagQuery::where('tenant_id', tenant('id'))->findOrFail($queryId);
        $query->update(['feedback_rating' => $request->input('rating')]);

        return response()->json(['message' => 'Feedback recorded.']);
    }
}
