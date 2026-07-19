<?php

namespace App\Observers;

use App\Domain\Content\Events\EntryDeleted;
use App\Domain\Rag\Services\RagService;
use App\Models\Tenant\Entry;
use Illuminate\Support\Facades\Cache;

class EntryObserver
{
    public function created(Entry $entry): void
    {
        Cache::forget("collection:entries:{$entry->collection_id}");
    }

    public function updated(Entry $entry): void
    {
        Cache::forget("entry:{$entry->tenant_id}:{$entry->id}");
        Cache::forget("entry:slug:{$entry->tenant_id}:{$entry->slug}");

        // V4: Re-index for RAG if entry is published
        if (tenant_has_feature('ai_rag') && $entry->status === 'published') {
            \App\Domain\Rag\Jobs\IndexEntry::dispatch($entry->id);
        }
    }

    public function deleting(Entry $entry): void
    {
        // V4: Remove from RAG index
        if (tenant_has_feature('ai_rag')) {
            app(RagService::class)->removeFromIndex($entry);
        }

        event(new EntryDeleted($entry));
    }
}
