<?php

namespace App\Domain\Audit\Services\Destinations;

use Illuminate\Support\Facades\Http;

class ElasticDestination implements DestinationInterface
{
    public function send(array $config, array $payload): array
    {
        $url = rtrim($config['url'], '/');
        $index = $config['index'] ?? 'cms-activity-' . date('Y.m.d');
        $apiKey = $config['api_key'] ?? null;
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        $headers = ['Content-Type' => 'application/x-ndjson'];

        $auth = null;
        if ($apiKey) {
            $headers['Authorization'] = 'ApiKey ' . $apiKey;
        } elseif ($username) {
            $auth = [$username, $password];
        }

        // NDJSON bulk format
        $action = json_encode(['index' => ['_index' => $index]]);
        $doc = json_encode(array_merge($payload, ['@timestamp' => now()->toIso8601String()]));
        $body = $action . "\n" . $doc . "\n";

        $request = Http::withHeaders($headers)->timeout(30);
        if ($auth) $request = $request->withBasicAuth($auth[0], $auth[1]);

        $response = $request->post("{$url}/_bulk", $body);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }
}
