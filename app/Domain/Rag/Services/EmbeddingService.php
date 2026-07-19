<?php

namespace App\Domain\Rag\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    public function embed(string $text): array
    {
        $provider = config('ai.default_provider');
        $config = config("ai.providers.{$provider}");

        return match ($provider) {
            'openai' => $this->embedWithOpenAI($text, $config),
            'anthropic' => $this->embedWithAnthropic($text, $config),
            'local' => $this->embedWithLocal($text, $config),
            default => $this->fallbackEmbedding($text, $config),
        };
    }

    public function embedBatch(array $texts): array
    {
        // For simplicity, embed individually. For production, batch API call.
        return array_map(fn($text) => $this->embed($text), $texts);
    }

    protected function embedWithOpenAI(string $text, array $config): array
    {
        $model = $config['embedding_model'] ?? 'text-embedding-3-small';

        $response = Http::withToken($config['api_key'])
            ->timeout(60)
            ->post("{$config['base_url']}/embeddings", [
                'model' => $model,
                'input' => $text,
            ]);

        if (! $response->successful()) {
            Log::error('OpenAI embedding failed', ['error' => $response->body()]);
            return $this->fallbackEmbedding($text, $config);
        }

        return $response->json('data.0.embedding');
    }

    protected function embedWithAnthropic(string $text, array $config): array
    {
        // Anthropic doesn't currently offer an embeddings API.
        // Fall back to local hashing or OpenAI.
        return $this->fallbackEmbedding($text, $config);
    }

    protected function embedWithLocal(string $text, array $config): array
    {
        $response = Http::timeout(120)
            ->post("{$config['base_url']}/api/embeddings", [
                'model' => $config['model'],
                'prompt' => $text,
            ]);

        if (! $response->successful()) {
            return $this->fallbackEmbedding($text, $config);
        }

        return $response->json('embedding', []);
    }

    /**
     * Fallback: deterministic hash-based embedding.
     * Not as good as real embeddings, but ensures RAG works without an API key.
     */
    protected function fallbackEmbedding(string $text, array $config): array
    {
        $dimensions = $config['embedding_dimensions'] ?? 1536;
        $embedding = array_fill(0, $dimensions, 0.0);

        // Bag-of-words hash embedding
        $words = explode(' ', strtolower($text));
        foreach ($words as $word) {
            $hash = crc32($word);
            $index = abs($hash) % $dimensions;
            $embedding[$index] += 1.0;
        }

        // Normalize
        $magnitude = sqrt(array_sum(array_map(fn($x) => $x * $x, $embedding)));
        if ($magnitude > 0) {
            $embedding = array_map(fn($x) => $x / $magnitude, $embedding);
        }

        return $embedding;
    }
}
