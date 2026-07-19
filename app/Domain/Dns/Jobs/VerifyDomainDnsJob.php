<?php

namespace App\Domain\Dns\Jobs;

use App\Domain\Dns\Services\DnsVerificationService;
use App\Models\Central\DnsVerificationJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyDomainDnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 50;
    public int $backoff = 300;

    public function __construct(
        public string $jobId,
    ) {}

    public function handle(DnsVerificationService $service): void
    {
        $job = DnsVerificationJob::find($this->jobId);

        if (! $job || $job->status !== 'pending') {
            return;
        }

        if (! $job->canRetry()) {
            return;
        }

        if ($service->verify($job)) {
            return; // Verified!
        }

        // Not yet verified, schedule next attempt
        if ($job->canRetry()) {
            static::dispatch($job->id)->delay($job->next_attempt_at);
        }
    }
}
