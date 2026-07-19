<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Repositories\Interfaces\EntryRepositoryInterface;
use App\Domain\Content\Events\EntryUpdated;

class UpdateEntry
{
    public function __construct(protected EntryRepositoryInterface $entries) {}

    public function execute(\App\Models\Tenant\Entry $entry, array $data): \App\Models\Tenant\Entry
    {
        $entry = $this->entries->update($entry, $data);
        event(new EntryUpdated($entry));
        return $entry;
    }
}
