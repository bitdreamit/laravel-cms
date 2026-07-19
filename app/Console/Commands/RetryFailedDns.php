<?php

namespace App\Console\Commands;

use App\Domain\Dns\Jobs\VerifyDomainDnsJob;
use App\Models\Central\DnsVerificationJob;
use Illuminate\Console\Command;

class RetryFailedDns extends Command
{
    protected $signature = 'dns:retry-failed';
    protected $description = 'Retry DNS verification for pending jobs.';

    public function handle(): int
    {
        $jobs = DnsVerificationJob::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
            })
            ->get();

        foreach ($jobs as $job) {
            VerifyDomainDnsJob::dispatch($job->id);
        }

        $this->info("Dispatched {$jobs->count()} DNS verification retries.");
        return self::SUCCESS;
    }
}
