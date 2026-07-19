<?php

namespace App\Domain\Rag\Services;

use App\Domain\Rag\DTOs\RagResponse;
use App\Models\Tenant\Entry;
use App\Models\Tenant\RagDocument;
use App\Models\Tenant\RagQuery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RagService
{
    public function __construct(
        protected Chunker $chunker,
        protected EmbeddingService $embeddingService,
        protected VectorSearch $vectorSearch,
        protected CitationFormatter $citationFormatter,
    ) {}

    /**
     * Index an entry's text fields into the RAG vector store.
     */
    public function indexEntry(Entry $entry): void
    {
        // Remove existing index for this entry
        $this->removeFromIndex($entry);

        // Determine which fields to index
        $fieldsToIndex = $this->getIndexableFields($entry);
        if (empty($fieldsToIndex)) {
            return;
        }

        foreach ($fieldsToIndex as $fieldHandle => $text) {
            if (empty(trim($text))) continue;

            $chunks = $this->chunker->chunk(
                $text,
                (int) config('rag.chunking.size', 500),
                (int) config('rag.chunking.overlap', 50),
                [
                    'entry_id' => $entry->id,
                    'field_handle' => $fieldHandle,
                    'page_url' => '/entries/' . $entry->slug,
                    'language' => $entry->site ?? 'default',
                ],
            );

            foreach ($chunks as $chunk) {
                $embedding = $this->embeddingService->embed($chunk['text']);

                RagDocument::create([
                    'id' => Str::uuid(),
                    'tenant_id' => $entry->tenant_id,
                    'entry_id' => $entry->id,
                    'field_handle' => $fieldHandle,
                    'chunk_index' => $chunk['chunk_index'],
                    'chunk_text' => $chunk['text'],
                    'embedding' => $embedding,
                    'metadata' => $chunk['metadata'],
                ]);
            }
        }

        event(new \App\Domain\Rag\Events\EntryIndexed($entry));
    }

    /**
     * Remove an entry's documents from the RAG index.
     */
    public function removeFromIndex(Entry $entry): void
    {
        RagDocument::where('entry_id', $entry->id)->delete();
    }

    /**
     * Reindex an entry (remove + index).
     */
    public function reindexEntry(Entry $entry): void
    {
        $this->removeFromIndex($entry);
        $this->indexEntry($entry);
    }

    /**
     * Answer a question using RAG (Retrieval-Augmented Generation).
     */
    public function ask(string $tenantId, string $question, ?string $userId = null): RagResponse
    {
        $startTime = microtime(true);

        // 1. Embed the question
        $queryEmbedding = $this->embeddingService->embed($question);

        // 2. Vector search for relevant chunks
        $topK = (int) config('rag.retrieval.top_k', 5);
        $minSim = (float) config('rag.retrieval.min_similarity', 0.7);
        $retrieved = $this->vectorSearch->search($tenantId, $queryEmbedding, $topK, $minSim);

        if (empty($retrieved)) {
            return $this->noResultsResponse($question, $userId, $startTime);
        }

        // 3. Build the prompt
        $context = $this->buildContext($retrieved);
        $prompt = $this->buildPrompt($question, $context);

        // 4. Call the AI provider
        [$answer, $modelUsed, $promptTokens, $completionTokens] = $this->callAIProvider($prompt);

        // 5. Format citations
        $answer = $this->citationFormatter->format($answer, $retrieved);
        $citations = $this->buildCitations($retrieved);

        $latency = (int) ((microtime(true) - $startTime) * 1000);

        // 6. Log the query
        $query = RagQuery::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'query_text' => $question,
            'retrieved_document_ids' => array_map(fn($r) => $r['document']->id, $retrieved),
            'answer_text' => $answer,
            'model_used' => $modelUsed,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'latency_ms' => $latency,
        ]);

        return new RagResponse(
            answer: $answer,
            citations: $citations,
            retrievedChunks: array_map(fn($r) => [
                'id' => $r['document']->id,
                'text' => $r['document']->chunk_text,
                'similarity' => $r['similarity'],
            ], $retrieved),
            modelUsed: $modelUsed,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            latencyMs: $latency,
        );
    }

    protected function getIndexableFields(Entry $entry): array
    {
        $fields = [];
        $data = $entry->data ?? [];

        // Always index title and main content fields if present
        if (! empty($entry->title)) {
            $fields['title'] = $entry->title;
        }
        foreach (['body', 'content', 'description', 'excerpt', 'summary'] as $field) {
            if (! empty($data[$field]) && is_string($data[$field])) {
                $fields[$field] = $data[$field];
            }
        }

        return $fields;
    }

    protected function buildContext(array $retrieved): string
    {
        $context = '';
        foreach ($retrieved as $i => $result) {
            $chunk = $result['document']->chunk_text;
            $context .= "[Context " . ($i + 1) . "]\n{$chunk}\n\n";
        }
        return trim($context);
    }

    protected function buildPrompt(string $question, string $context): array
    {
        return [
            'system' => config('rag.generation.system_prompt'),
            'user' => "Context:\n{$context}\n\nQuestion: {$question}\n\nAnswer based on the context above. Cite sources by referencing the entry titles when possible.",
        ];
    }

    protected function callAIProvider(array $prompt): array
    {
        $provider = config('ai.default_provider');
        $config = config("ai.providers.{$provider}");

        try {
            if ($provider === 'openai') {
                $response = Http::withToken($config['api_key'])
                    ->timeout(120)
                    ->post("{$config['base_url']}/chat/completions", [
                        'model' => $config['model'],
                        'messages' => [
                            ['role' => 'system', 'content' => $prompt['system']],
                            ['role' => 'user', 'content' => $prompt['user']],
                        ],
                        'max_tokens' => (int) config('rag.generation.max_tokens', 2000),
                        'temperature' => (float) config('rag.generation.temperature', 0.3),
                    ]);

                return [
                    $response->json('choices.0.message.content', "I couldn't generate a response."),
                    $config['model'],
                    $response->json('usage.prompt_tokens', 0),
                    $response->json('usage.completion_tokens', 0),
                ];
            }
        } catch (\Throwable $e) {
            Log::error('AI provider call failed', ['error' => $e->getMessage()]);
        }

        // Fallback: return the retrieved context as the answer
        return [
            "Based on the available context: " . substr($prompt['user'], strpos($prompt['user'], "Context:") + 9, 500),
            'fallback',
            0,
            0,
        ];
    }

    protected function buildCitations(array $retrieved): array
    {
        $citations = [];
        foreach ($retrieved as $result) {
            $doc = $result['document'];
            $entry = Entry::find($doc->entry_id);
            $citations[] = [
                'entry_id' => $doc->entry_id,
                'entry_title' => $entry?->title ?? 'Unknown',
                'url' => '/entries/' . ($entry?->slug ?? $doc->entry_id),
                'similarity' => $result['similarity'],
            ];
        }
        return $citations;
    }

    protected function noResultsResponse(string $question, ?string $userId, float $startTime): RagResponse
    {
        $latency = (int) ((microtime(true) - $startTime) * 1000);

        RagQuery::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'user_id' => $userId,
            'query_text' => $question,
            'answer_text' => "I don't have enough information to answer that.",
        ]);

        return new RagResponse(
            answer: "I don't have enough information to answer that.",
            latencyMs: $latency,
        );
    }
}
