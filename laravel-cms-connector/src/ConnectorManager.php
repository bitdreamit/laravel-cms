<?php

namespace Platform\CmsConnector;

use Platform\CmsConnector\Support\CmsClient;
use Platform\CmsConnector\Support\CollectionQueryBuilder;
use Platform\CmsConnector\Support\SignatureVerifier;

class ConnectorManager
{
    public function __construct(
        protected CmsClient $client,
        protected SignatureVerifier $signer,
        protected array $config,
    ) {}

    public function collection(string $handle): CollectionQueryBuilder
    {
        return new CollectionQueryBuilder($this->client, $handle, $this->config['headless']['collections'][$handle] ?? []);
    }

    public function graphql(string $query, array $variables = []): array
    {
        return $this->client->post('/api/v1/graphql', ['query' => $query, 'variables' => $variables]);
    }

    public function forTenant(string $tenantId): static
    {
        $clone = clone $this;
        $clone->client = $this->client->forTenant($tenantId);
        return $clone;
    }

    public function health(): array
    {
        return $this->client->get('/api/v1/connector/status');
    }

    public function getConnectorId(): ?string
    {
        return \Illuminate\Support\Facades\Cache::remember('cms-connector.id', 3600, function () {
            try { return $this->health()['connector_id'] ?? null; } catch (\Throwable) { return null; }
        });
    }

    public function syncModel(object $model): void
    {
        if (! $model instanceof Contracts\SyncableToCms) throw new \InvalidArgumentException('Model must implement SyncableToCms');
        $data = $model->toCmsEntryData();
        $this->client->put("/api/v1/collections/{$data['collection_handle']}/entries/{$data['slug']}", $data);
    }

    public function ssoRedirectUrl(): string
    {
        $payload = ['host_user_id' => auth()->id(), 'email' => auth()->user()->email, 'name' => auth()->user()->name, 'exp' => time() + ($this->config['auth_bridge']['token_ttl_seconds'] ?? 60)];
        $token = $this->signer->signJwt($payload, $this->config['auth_bridge']['shared_secret']);
        return $this->config['auth_bridge']['cms_sso_url'] . '?token=' . $token;
    }
}
