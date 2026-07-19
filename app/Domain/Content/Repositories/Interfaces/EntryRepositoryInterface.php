<?php

namespace App\Domain\Content\Repositories\Interfaces;

use App\Models\Tenant\Entry;
use Illuminate\Pagination\LengthAwarePaginator;

interface EntryRepositoryInterface
{
    public function find(string $id): ?Entry;
    public function findBySlug(string $slug, string $collectionHandle = null): ?Entry;
    public function all(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): Entry;
    public function update(Entry $entry, array $data): Entry;
    public function delete(Entry $entry): bool;
    public function publish(Entry $entry): Entry;
    public function unpublish(Entry $entry): Entry;
    public function createRevision(Entry $entry): void;
    public function restoreRevision(Entry $entry, string $revisionId): Entry;
}
