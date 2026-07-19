<?php

namespace App\Domain\Content\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InvalidateEntryCache
{
    public function handle($event): void
    {
        $entry = $event->entry ?? null;
        if (! $entry) return;

        // Clear entry-specific cache
        Cache::forget("entry:{$entry->tenant_id}:{$entry->id}");
        Cache::forget("entry:slug:{$entry->tenant_id}:{$entry->slug}");

        // Clear collection listing cache
        if ($entry->collection_id) {
            Cache::forget("collection:entries:{$entry->collection_id}");
        }

        // Clear tenant's full-page cache (V3 caching strategy)
        Cache::flush(); // In production, use tags or prefix-based flush
    }
}
