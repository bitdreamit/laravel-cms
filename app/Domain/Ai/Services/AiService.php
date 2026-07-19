<?php

namespace App\Domain\Ai\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AiService
{
    public function generate(string $prompt, array $options = []): array
    {
        $tenantId = tenant('id');
        $this->checkRateLimit($tenantId);

        $provider = config('ai.default_provider');
        $config = config("ai.providers.{$provider}");

        $model = $options['model'] ?? $config['model'] ?? 'gpt-4o';
        $maxTokens = $options['max_tokens'] ?? $config['max_tokens'] ?? 4096;
        $temperature = $options['temperature'] ?? 0.7;

        try {
            $response = Http::withToken($config['api_key'])
                ->timeout(120)
                ->post("{$config['base_url']}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $options['system_prompt'] ?? 'You are a helpful assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'content' => $response->json('choices.0.message.content', ''),
                    'model' => $model,
                    'prompt_tokens' => $response->json('usage.prompt_tokens', 0),
                    'completion_tokens' => $response->json('usage.completion_tokens', 0),
                ];
            }

            return ['success' => false, 'error' => $response->body(), 'content' => ''];
        } catch (\Throwable $e) {
            Log::error('AI generation failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'content' => ''];
        }
    }

    public function generateImage(string $prompt, array $options = []): array
    {
        $this->checkRateLimit(tenant('id'));
        $config = config('ai.providers.openai');

        try {
            $response = Http::withToken($config['api_key'])
                ->timeout(120)
                ->post("{$config['base_url']}/images/generations", [
                    'prompt' => $prompt,
                    'n' => $options['n'] ?? 1,
                    'size' => $options['size'] ?? '1024x1024',
                ]);

            if ($response->successful()) {
                return ['success' => true, 'images' => $response->json('data', [])];
            }
            return ['success' => false, 'error' => $response->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function checkRateLimit(?string $tenantId): void
    {
        if (! $tenantId) return;
        $key = "ai:generate:{$tenantId}";
        $limit = (int) config('ai.rate_limit_per_tenant', 100);
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw new \RuntimeException('AI rate limit exceeded. Try again in ' . RateLimiter::availableIn($key) . ' seconds.');
        }
        RateLimiter::hit($key, 3600);
    }
}
