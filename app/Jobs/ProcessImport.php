<?php

namespace App\Jobs;

use App\Domain\Import\Services\ImportService;
use App\Models\Tenant\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public string $jobId) {}

    public function handle(ImportService $importService): void
    {
        $job = ImportJob::find($this->jobId);
        if (! $job) return;

        match ($job->source_type) {
            'wordpress' => $importService->importWordPress($job),
            'csv' => $importService->importCsv($job),
            'json' => $importService->importJson($job),
            default => $job->update(['status' => 'failed', 'error_log' => ["Unknown source type: {$job->source_type}"]]),
        };
    }
}
