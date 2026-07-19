<?php

namespace App\Domain\Rag\Services;

use App\Models\Tenant\RagDocument;
use Illuminate\Support\Facades\DB;

class VectorSearch
{
    public function __construct(protected EmbeddingService $embeddingService) {}

    /**
     * Search for similar documents to a query embedding.
     *
     * @return array<int, array{document: RagDocument, similarity: float}>
     */
    public function search(string $tenantId, array $queryEmbedding, int $k = 5, float $minSimilarity = 0.7): array
    {
        $driver = DB::getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            return $this->searchWithPgVector($tenantId, $queryEmbedding, $k, $minSimilarity);
        }

        return $this->searchWithJson($tenantId, $queryEmbedding, $k, $minSimilarity);
    }

    /**
     * Postgres + pgvector search (fast, index-backed).
     */
    protected function searchWithPgVector(string $tenantId, array $queryEmbedding, int $k, float $minSimilarity): array
    {
        $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';

        $results = DB::table('rag_documents')
            ->select('id', 'entry_id', 'field_handle', 'chunk_index', 'chunk_text', 'metadata')
            ->selectRaw("1 - (embedding <=> ?::vector) AS similarity", [$embeddingStr])
            ->where('tenant_id', $tenantId)
            ->whereRaw("1 - (embedding <=> ?::vector) >= ?", [$embeddingStr, $minSimilarity])
            ->orderByRaw('embedding <=> ?::vector', [$embeddingStr])
            ->limit($k)
            ->get();

        return $results->map(function ($row) {
            return [
                'document' => new RagDocument((array) $row),
                'similarity' => $row->similarity,
            ];
        })->all();
    }

    /**
     * MySQL/SQLite JSON search (brute-force cosine similarity in PHP).
     * Works for <50k documents. Slow for larger corpora.
     */
    protected function searchWithJson(string $tenantId, array $queryEmbedding, int $k, float $minSimilarity): array
    {
        $documents = DB::table('rag_documents')
            ->where('tenant_id', $tenantId)
            ->get();

        $scored = [];
        foreach ($documents as $doc) {
            $embedding = json_decode($doc->embedding ?? '[]', true);
            if (empty($embedding)) continue;

            $similarity = $this->cosineSimilarity($queryEmbedding, $embedding);
            if ($similarity >= $minSimilarity) {
                $scored[] = [
                    'document' => new RagDocument((array) $doc),
                    'similarity' => $similarity,
                ];
            }
        }

        usort($scored, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($scored, 0, $k);
    }

    /**
     * Compute cosine similarity between two vectors.
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        $dot = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $magA += $a[$i] * $a[$i];
            $magB += $b[$i] * $b[$i];
        }

        if ($magA === 0.0 || $magB === 0.0) return 0.0;
        return $dot / (sqrt($magA) * sqrt($magB));
    }
}
