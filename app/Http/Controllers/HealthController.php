<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    /**
     * Comprehensive health check endpoint.
     */
    public function check(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        // V4 feature checks (only if enabled)
        if (config('collab.enabled')) {
            $checks['reverb'] = $this->checkReverb();
        }
        if (config('ai.providers.openai.api_key')) {
            $checks['ai_provider'] = $this->checkAiProvider();
        }
        if (config('ssl.dns_providers.cloudflare.api_token')) {
            $checks['dns_provider'] = $this->checkDnsProvider();
        }

        $allHealthy = collect($checks)->every(fn($c) => $c['status'] === 'healthy');
        $statusCode = $allHealthy ? 200 : 503;

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('cms.version', '4.0.0'),
            'checks' => $checks,
        ], $statusCode);
    }

    protected function checkDatabase(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'healthy', 'connection' => config('database.default')];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    protected function checkRedis(): array
    {
        try {
            \Redis::ping();
            return ['status' => 'healthy'];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => 'Redis not reachable'];
        }
    }

    protected function checkStorage(): array
    {
        $path = storage_path('app');
        return is_writable($path)
            ? ['status' => 'healthy', 'path' => $path]
            : ['status' => 'unhealthy', 'error' => 'Storage not writable'];
    }

    protected function checkQueue(): array
    {
        $failedCount = \DB::table('failed_jobs')->count();
        return [
            'status' => $failedCount < 100 ? 'healthy' : 'degraded',
            'failed_jobs' => $failedCount,
        ];
    }

    protected function checkReverb(): array
    {
        try {
            $host = config('reverb.apps.0.options.host', '127.0.0.1');
            $port = config('reverb.apps.0.options.port', 8080);
            $connection = @fsockopen($host, $port, $errno, $errstr, 2);
            if ($connection) { fclose($connection); return ['status' => 'healthy']; }
            return ['status' => 'unhealthy', 'error' => 'Cannot connect to Reverb'];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    protected function checkAiProvider(): array
    {
        $provider = config('ai.default_provider');
        $key = config("ai.providers.{$provider}.api_key");
        return $key
            ? ['status' => 'healthy', 'provider' => $provider]
            : ['status' => 'unhealthy', 'error' => "No API key for {$provider}"];
    }

    protected function checkDnsProvider(): array
    {
        $provider = data_get(tenant()?->data, 'dns_provider_config.provider', 'cloudflare');
        return ['status' => 'healthy', 'provider' => $provider];
    }
}
