<?php

namespace App\Domain\Content\Repositories\Eloquent;

use App\Domain\Content\Repositories\Interfaces\EntryRepositoryInterface;
use App\Models\Tenant\Entry;
use App\Models\Tenant\EntryRevision;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class EntryRepository implements EntryRepositoryInterface
{
    public function find(string $id): ?Entry
    {
        return Entry::where('tenant_id', tenant('id'))->where('id', $id)->first();
    }

    public function findBySlug(string $slug, string $collectionHandle = null): ?Entry
    {
        $query = Entry::where('tenant_id', tenant('id'))->where('slug', $slug);
        if ($collectionHandle) {
            $query->whereHas('collection', fn($q) => $q->where('handle', $collectionHandle));
        }
        return $query->first();
    }

    public function all(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Entry::where('tenant_id', tenant('id'))
            ->with(['collection', 'site']);

        if (! empty($filters['collection_id'])) {
            $query->where('collection_id', $filters['collection_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $query->orderBy($sort, $direction);

        return $query->paginate($perPage);
    }

    public function create(array $data): Entry
    {
        $data['id'] = $data['id'] ?? Str::uuid();
        $data['tenant_id'] = tenant('id');
        $data['created_by'] = auth()->id();

        return Entry::create($data);
    }

    public function update(Entry $entry, array $data): Entry
    {
        $this->createRevision($entry);
        $entry->update($data);
        return $entry->fresh();
    }

    public function delete(Entry $entry): bool
    {
        return $entry->delete();
    }

    public function publish(Entry $entry): Entry
    {
        $entry->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        return $entry->fresh();
    }

    public function unpublish(Entry $entry): Entry
    {
        $entry->update(['status' => 'draft']);
        return $entry->fresh();
    }

    public function createRevision(Entry $entry): void
    {
        $revisionCount = EntryRevision::where('entry_id', $entry->id)->count();

        EntryRevision::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'entry_id' => $entry->id,
            'revision_number' => $revisionCount + 1,
            'data' => $entry->data,
            'user_id' => auth()->id(),
            'action' => 'update',
            'summary' => 'Auto revision before update',
        ]);
    }

    public function restoreRevision(Entry $entry, string $revisionId): Entry
    {
        $revision = EntryRevision::where('entry_id', $entry->id)
            ->where('id', $revisionId)
            ->firstOrFail();

        $this->createRevision($entry);

        $entry->update(['data' => $revision->data]);
        return $entry->fresh();
    }
}
