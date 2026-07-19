<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Repositories\Interfaces\EntryRepositoryInterface;
use Illuminate\Support\Str;

class DuplicateEntry
{
    public function __construct(protected EntryRepositoryInterface $entries) {}

    public function execute(\App\Models\Tenant\Entry $entry): \App\Models\Tenant\Entry
    {
        return $this->entries->create([
            'collection_id' => $entry->collection_id,
            'blueprint_id' => $entry->blueprint_id,
            'site_id' => $entry->site_id,
            'title' => $entry->title . ' (Copy)',
            'slug' => Str::slug($entry->slug . '-copy-' . Str::random(4)),
            'status' => 'draft',
            'data' => $entry->data,
            'template' => $entry->template,
            'created_by' => auth()->id(),
        ]);
    }
}
