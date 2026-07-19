<?php

namespace App\Domain\Dns\Providers;

use Illuminate\Support\Facades\Http;

class CloudflareProvider implements DnsProviderInterface
{
    protected string $apiToken;
    protected string $apiBase = 'https://api.cloudflare.com/client/v4';

    public function __construct()
    {
        $this->apiToken = config('ssl.dns_providers.cloudflare.api_token', env('CLOUDFLARE_API_TOKEN'));
    }

    public function publishTxtRecord(string $name, string $value, int $ttl = 300): void
    {
        $zoneId = $this->getZoneId($name);

        Http::withToken($this->apiToken)
            ->post("{$this->apiBase}/zones/{$zoneId}/dns_records", [
                'type' => 'TXT',
                'name' => $name,
                'content' => $value,
                'ttl' => $ttl,
            ]);
    }

    public function deleteTxtRecord(string $name, string $value): void
    {
        $zoneId = $this->getZoneId($name);
        $records = $this->listTxtRecords($name);

        foreach ($records as $record) {
            if ($record['value'] === $value) {
                Http::withToken($this->apiToken)
                    ->delete("{$this->apiBase}/zones/{$zoneId}/dns_records/{$record['id']}");
            }
        }
    }

    public function listTxtRecords(string $name): array
    {
        $zoneId = $this->getZoneId($name);

        $response = Http::withToken($this->apiToken)
            ->get("{$this->apiBase}/zones/{$zoneId}/dns_records", [
                'type' => 'TXT',
                'name' => $name,
            ]);

        if (! $response->successful()) return [];

        return collect($response->json('result', []))
            ->map(fn($record) => [
                'id' => $record['id'],
                'name' => $record['name'],
                'value' => trim($record['content'], '"'),
                'ttl' => $record['ttl'],
            ])
            ->all();
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiToken);
    }

    /**
     * Get the Cloudflare zone ID for a domain.
     */
    protected function getZoneId(string $recordName): string
    {
        // Strip the subdomain prefix to get the root domain
        $parts = explode('.', $recordName);
        $rootDomain = implode('.', array_slice($parts, -2));

        $response = Http::withToken($this->apiToken)
            ->get("{$this->apiBase}/zones", ['name' => $rootDomain]);

        if (! $response->successful() || empty($response->json('result'))) {
            throw new \RuntimeException("Cloudflare zone not found for {$rootDomain}");
        }

        return $response->json('result.0.id');
    }
}
