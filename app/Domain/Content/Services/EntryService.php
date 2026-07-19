<?php

namespace App\Domain\Content\Services;

use App\Domain\Content\Repositories\Interfaces\EntryRepositoryInterface;
use App\Models\Tenant\Entry;
use Illuminate\Pagination\LengthAwarePaginator;

class EntryService
{
    public function __construct(protected EntryRepositoryInterface $entries) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->entries->all($filters, $perPage);
    }

    public function get(string $id): ?Entry
    {
        return $this->entries->find($id);
    }

    public function getBySlug(string $slug, ?string $collectionHandle = null): ?Entry
    {
        return $this->entries->findBySlug($slug, $collectionHandle);
    }

    public function create(array $data): Entry
    {
        return app(\App\Domain\Content\Actions\CreateEntry::class)->execute($data);
    }

    public function update(Entry $entry, array $data): Entry
    {
        return app(\App\Domain\Content\Actions\UpdateEntry::class)->execute($entry, $data);
    }

    public function publish(Entry $entry): Entry
    {
        return app(\App\Domain\Content\Actions\PublishEntry::class)->execute($entry);
    }

    public function schedule(Entry $entry, $scheduledAt): Entry
    {
        return app(\App\Domain\Content\Actions\ScheduleEntry::class)->execute($entry, $scheduledAt);
    }

    public function restoreRevision(Entry $entry, string $revisionId): Entry
    {
        return app(\App\Domain\Content\Actions\RestoreRevision::class)->execute($entry, $revisionId);
    }

    public function duplicate(Entry $entry): Entry
    {
        return app(\App\Domain\Content\Actions\DuplicateEntry::class)->execute($entry);
    }

    public function delete(Entry $entry): bool
    {
        return $this->entries->delete($entry);
    }
}
