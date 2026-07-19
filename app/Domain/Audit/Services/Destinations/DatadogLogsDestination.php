<?php

namespace App\Domain\Audit\Services\Destinations;

use Illuminate\Support\Facades\Http;

class DatadogLogsDestination implements DestinationInterface
{
    public function send(array $config, array $payload): array
    {
        $url = $config['url'] ?? 'https://http-intake.logs.datadoghq.com/v1/input';
        $apiKey = $config['api_key'];

        $body = [
            'ddsource' => 'cms',
            'service' => 'laravel-cms',
            'ddtags' => implode(',', [
                "tenant:{$payload['tenant_id']}",
                "event_type:{$payload['event_type']}",
                "severity:{$payload['severity']}",
            ]),
            'message' => $payload['description'] ?? $payload['event_type'],
            'timestamp' => $payload['timestamp'] ?? now()->timestamp,
            'host' => config('app.url'),
            'cms_payload' => $payload,
        ];

        $response = Http::withToken($apiKey)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($url, $body);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }
}
