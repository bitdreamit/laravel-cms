<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Rag\Services\RagService;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Entry;
use App\Models\Tenant\RagQuery;
use Illuminate\Http\Request;

class RagController extends Controller
{
    public function __construct(protected RagService $ragService) {}

    public function playground()
    {
        return response()->json([
            'enabled' => tenant_has_feature('ai_rag'),
            'settings' => [
                'model' => config('ai.providers.' . config('ai.default_provider') . '.model'),
                'embedding_model' => config('ai.providers.' . config('ai.default_provider') . '.embedding_model'),
                'chunk_size' => config('rag.chunking.size'),
                'top_k' => config('rag.retrieval.top_k'),
            ],
        ]);
    }

    public function ask(Request $request)
    {
        $request->validate(['question' => 'required|string|max:2000']);

        $response = $this->ragService->ask(tenant('id'), $request->input('question'), auth()->id());

        return response()->json([
            'answer' => $response->answer,
            'citations' => $response->citations,
            'retrieved_chunks' => $response->retrievedChunks,
            'latency_ms' => $response->latencyMs,
        ]);
    }

    public function indexStatus()
    {
        $entries = Entry::where('tenant_id', tenant('id'))
            ->where('status', 'published')
            ->withCount(['ragDocuments'])
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'slug' => $e->slug,
                'chunk_count' => $e->rag_documents_count,
                'is_indexed' => $e->rag_documents_count > 0,
            ]);

        return response()->json($entries);
    }

    public function reindexEntry(string $entryId)
    {
        $entry = Entry::where('tenant_id', tenant('id'))->findOrFail($entryId);
        $this->ragService->reindexEntry($entry);

        return response()->json(['message' => 'Entry re-indexed.']);
    }

    public function queriesLog(Request $request)
    {
        $queries = RagQuery::where('tenant_id', tenant('id'))
            ->when($request->input('rating'), fn($q, $r) => $q->where('feedback_rating', $r))
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($queries);
    }
}
