<?php

namespace App\Domain\Audit\Services\Destinations;

use Illuminate\Support\Facades\Http;

class LogtailDestination implements DestinationInterface
{
    public function send(array $config, array $payload): array
    {
        $sourceToken = $config['source_token'];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$sourceToken}",
        ])
            ->timeout(30)
            ->post('https://in.logtail.com', $payload);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }
}
