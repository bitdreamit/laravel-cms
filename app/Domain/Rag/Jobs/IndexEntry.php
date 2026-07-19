<?php

namespace App\Domain\Rag\Jobs;

use App\Domain\Rag\Services\RagService;
use App\Models\Tenant\Entry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(public string $entryId) {}

    public function handle(RagService $ragService): void
    {
        $entry = Entry::find($this->entryId);
        if (! $entry) return;

        $ragService->indexEntry($entry);
    }
}
