<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Repositories\Interfaces\EntryRepositoryInterface;

class RestoreRevision
{
    public function __construct(protected EntryRepositoryInterface $entries) {}

    public function execute(\App\Models\Tenant\Entry $entry, string $revisionId): \App\Models\Tenant\Entry
    {
        return $this->entries->restoreRevision($entry, $revisionId);
    }
}
