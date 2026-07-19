<?php

namespace Platform\CmsConnector\Support;

class CollectionQueryBuilder
{
    protected array $wheres = [];
    protected array $orders = [];
    protected ?int $perPage = null;
    protected ?int $page = null;
    protected array $with = [];

    public function __construct(protected CmsClient $client, protected string $handle, protected array $config = []) {}

    public function where(string $field, $operator, $value = null): static { if (func_num_args() === 2) { $value = $operator; $operator = '='; } $this->wheres[] = compact('field', 'operator', 'value'); return $this; }
    public function orderBy(string $field, string $direction = 'asc'): static { $this->orders[] = compact('field', 'direction'); return $this; }
    public function with(string $relation): static { $this->with[] = $relation; return $this; }
    public function paginate(int $perPage = 15, int $page = 1): array { $this->perPage = $perPage; $this->page = $page; return $this->execute('paginate'); }
    public function find(string $id): ?array { return $this->client->get("/api/v1/collections/{$this->handle}/entries/{$id}", [], $this->cacheTtl())['data'] ?? null; }
    public function findBySlug(string $slug): ?array { return $this->find($slug); }
    public function first(): ?array { $results = $this->limit(1)->get(); return $results[0] ?? null; }
    public function get(): array { return $this->execute('get'); }
    public function limit(int $limit): static { $this->perPage = $limit; return $this; }

    protected function execute(string $mode): array
    {
        $query = ['filter' => $this->buildFilter(), 'sort' => $this->buildSort()];
        if ($this->perPage) $query['per_page'] = $this->perPage;
        if ($this->page) $query['page'] = $this->page;
        if ($this->with) $query['include'] = implode(',', $this->with);
        $response = $this->client->get("/api/v1/collections/{$this->handle}/entries", $query, $this->cacheTtl());
        return $mode === 'paginate' ? $response : ($response['data'] ?? []);
    }

    protected function buildFilter(): string { $parts = []; foreach ($this->wheres as $w) { $parts[] = $w['operator'] === '=' ? "{$w['field']}:{$w['value']}" : "{$w['field']}{$w['operator']}{$w['value']}"; } return implode(';', $parts); }
    protected function buildSort(): string { $parts = []; foreach ($this->orders as $o) { $parts[] = $o['direction'] === 'desc' ? '-' . $o['field'] : $o['field']; } return implode(',', $parts); }
    protected function cacheTtl(): ?int { return $this->config['cache_ttl'] ?? null; }
}
