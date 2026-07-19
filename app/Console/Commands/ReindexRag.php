<?php

namespace App\Console\Commands;

use App\Models\Tenant\Entry;
use App\Domain\Rag\Services\RagService;
use Illuminate\Console\Command;

class ReindexRag extends Command
{
    protected $signature = 'rag:reindex-stale {--tenant= : Tenant ID}';
    protected $description = 'Reindex published entries that are stale in the RAG vector store.';

    public function handle(RagService $ragService): int
    {
        $query = Entry::where('status', 'published');
        if ($this->option('tenant')) {
            $query->where('tenant_id', $this->option('tenant'));
        }
        $entries = $query->get();

        $count = 0;
        foreach ($entries as $entry) {
            // Check if entry needs reindexing (no docs, or recently updated)
            $docCount = \App\Models\Tenant\RagDocument::where('entry_id', $entry->id)->count();
            $needsReindex = $docCount === 0 || $entry->updated_at > $entry->created_at;

            if ($needsReindex) {
                $ragService->reindexEntry($entry);
                $count++;
                $this->line("Reindexed: {$entry->title}");
            }
        }

        $this->info("Reindexed {$count} entries.");
        return self::SUCCESS;
    }
}
