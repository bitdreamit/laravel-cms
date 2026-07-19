<?php

namespace App\Domain\Audit\Services\Destinations;

use Illuminate\Support\Facades\Http;

class SplunkHecDestination implements DestinationInterface
{
    public function send(array $config, array $payload): array
    {
        $url = rtrim($config['url'], '/') . '/services/collector';
        $token = $config['token'];
        $index = $config['index'] ?? null;
        $source = $config['source'] ?? 'cms-platform';
        $sourcetype = $config['sourcetype'] ?? 'cms:activity';

        $body = [
            'time' => time(),
            'host' => $payload['tenant_id'] ?? 'unknown',
            'source' => $source,
            'sourcetype' => $sourcetype,
            'event' => $payload,
        ];

        if ($index) {
            $body['index'] = $index;
        }

        $response = Http::withToken("Splunk {$token}")
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($url, $body);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }
}
