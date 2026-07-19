<?php

namespace App\Domain\Search\Services;

use App\Models\Tenant\Entry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Full-text search across entries.
     * Uses MySQL FULLTEXT index or PostgreSQL tsvector.
     *
     * SECURITY: All queries use parameterized bindings — never raw string interpolation.
     */
    public function search(string $query, array $options = []): LengthAwarePaginator
    {
        $tenantId = tenant('id');
        if (! $tenantId) {
            return new LengthAwarePaginator([], 0, $options['per_page'] ?? 15);
        }

        $perPage = min((int) ($options['per_page'] ?? 15), config('cms.pagination.max_per_page', 100));
        $collectionHandle = $options['collection'] ?? null;
        $status = $options['status'] ?? 'published';
        $page = (int) request()->input('page', 1);

        $driver = DB::getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $builder = Entry::where('entries.tenant_id', $tenantId)
            ->where('entries.status', $status)
            ->select('entries.*');

        // Join collection if filtering by handle
        if ($collectionHandle) {
            $builder->join('collections', 'entries.collection_id', '=', 'collections.id')
                    ->where('collections.handle', $collectionHandle);
        }

        // Apply full-text search using parameterized raw queries
        switch ($driver) {
            case 'pgsql':
                $this->applyPostgresSearch($builder, $query);
                break;

            case 'mysql':
                $this->applyMysqlSearch($builder, $query);
                break;

            default:
                $this->applyLikeSearch($builder, $query);
                break;
        }

        $builder->orderByDesc('search_score');

        $total = $builder->count();
        $results = $builder->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * MySQL FULLTEXT search — parameterized via whereRaw with bindings.
     */
    protected function applyMysqlSearch($builder, string $query): void
    {
        $builder->selectRaw(
            'entries.*, MATCH(entries.title, entries.data) AGAINST(? IN NATURAL LANGUAGE MODE) as search_score',
            [$query]
        )
        ->whereRaw('MATCH(entries.title, entries.data) AGAINST(? IN NATURAL LANGUAGE MODE)', [$query]);
    }

    /**
     * PostgreSQL tsvector search — parameterized.
     */
    protected function applyPostgresSearch($builder, string $query): void
    {
        $builder->selectRaw(
            "entries.*, ts_rank(to_tsvector('english', entries.title || ' ' || entries.data::text), plainto_tsquery('english', ?)) as search_score",
            [$query]
        )
        ->whereRaw("to_tsvector('english', entries.title || ' ' || entries.data::text) @@ plainto_tsquery('english', ?)", [$query]);
    }

    /**
     * SQLite/other fallback — parameterized LIKE.
     */
    protected function applyLikeSearch($builder, string $query): void
    {
        $builder->selectRaw('entries.*, 1.0 as search_score')
               ->where(function ($q) use ($query) {
                   $q->where('entries.title', 'like', "%{$query}%")
                     ->orWhere('entries.data', 'like', "%{$query}%");
               });
    }
}
