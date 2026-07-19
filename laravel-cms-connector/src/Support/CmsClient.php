<?php

namespace Platform\CmsConnector\Support;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Platform\CmsConnector\Exceptions\CmsUnreachableException;
use Platform\CmsConnector\Exceptions\CircuitOpenException;

class CmsClient
{
    protected ?string $tenantIdOverride = null;
    protected Client $httpClient;

    public function __construct(
        protected string $baseUrl,
        protected ?string $apiToken,
        protected int $timeout,
        protected CircuitBreaker $breaker,
        protected CacheFallback $cache,
    ) {
        $this->httpClient = new Client(['base_uri' => $baseUrl, 'timeout' => $timeout, 'headers' => ['Authorization' => "Bearer {$apiToken}", 'Accept' => 'application/json', 'Content-Type' => 'application/json']]);
    }

    public function forTenant(string $tenantId): static { $clone = clone $this; $clone->tenantIdOverride = $tenantId; return $clone; }

    public function get(string $url, array $query = [], ?int $cacheTtl = null): array
    {
        $cacheKey = $this->cacheKey('GET', $url, $query);
        if ($cacheTtl !== null) { $cached = $this->cache->get($cacheKey); if ($cached !== null) return $cached; }
        $response = $this->request('GET', $url, ['query' => $query]);
        if ($cacheTtl !== null) $this->cache->put($cacheKey, $response, $cacheTtl);
        return $response;
    }

    public function post(string $url, array $data = []): array { return $this->request('POST', $url, ['json' => $data]); }
    public function put(string $url, array $data = []): array { return $this->request('PUT', $url, ['json' => $data]); }
    public function delete(string $url): array { return $this->request('DELETE', $url); }

    protected function request(string $method, string $url, array $options = []): array
    {
        if ($this->breaker->isOpen()) throw new CircuitOpenException("Circuit breaker open — CMS unavailable");
        $headers = [];
        if ($this->tenantIdOverride) $headers['X-Tenant-Id'] = $this->tenantIdOverride;
        $options['headers'] = array_merge($options['headers'] ?? [], $headers);

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $this->breaker->recordSuccess();
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->breaker->recordFailure();
            if ($e->hasResponse() && $e->getResponse()?->getStatusCode() >= 500) {
                $retries = 0;
                while ($retries < 3) {
                    try { $r = $this->httpClient->request($method, $url, $options); $this->breaker->recordSuccess(); return json_decode($r->getBody()->getContents(), true) ?? []; } catch (\GuzzleHttp\Exception\RequestException) { $retries++; usleep(pow(2, $retries) * 100000); }
                }
            }
            Log::error('CMS request failed', ['method' => $method, 'url' => $url, 'error' => $e->getMessage()]);
            throw new CmsUnreachableException("CMS request failed: {$e->getMessage()}", 0, $e);
        }
    }

    protected function cacheKey(string $method, string $url, array $query = []): string { return 'cms-connector:' . md5($method . $url . serialize($query) . ($this->tenantIdOverride ?? '')); }
}
