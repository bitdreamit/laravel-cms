<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Repositories\Interfaces\EntryRepositoryInterface;
use App\Domain\Content\Events\EntryPublished;

class PublishEntry
{
    public function __construct(protected EntryRepositoryInterface $entries) {}

    public function execute(\App\Models\Tenant\Entry $entry): \App\Models\Tenant\Entry
    {
        $entry = $this->entries->publish($entry);
        event(new EntryPublished($entry));
        return $entry;
    }
}
