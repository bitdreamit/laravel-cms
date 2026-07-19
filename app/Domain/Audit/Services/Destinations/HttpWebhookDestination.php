<?php

namespace App\Domain\Audit\Services\Destinations;

use Illuminate\Support\Facades\Http;

class HttpWebhookDestination implements DestinationInterface
{
    public function send(array $config, array $payload): array
    {
        $url = $config['url'];
        $secret = $config['secret'] ?? null;

        $signature = '';
        if ($secret) {
            $signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
        }

        $response = Http::withHeaders(array_filter([
            'Content-Type' => 'application/json',
            'X-Cms-Signature' => $signature,
        ]))
            ->timeout(30)
            ->post($url, $payload);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }
}
