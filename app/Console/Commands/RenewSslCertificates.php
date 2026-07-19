<?php

namespace App\Console\Commands;

use App\Domain\Dns\Jobs\RenewSslCertificateJob;
use App\Models\Central\SslCertificate;
use Illuminate\Console\Command;

class RenewSslCertificates extends Command
{
    protected $signature = 'ssl:renew';
    protected $description = 'Renew SSL certificates expiring within the renewal window.';

    public function handle(): int
    {
        $window = (int) config('ssl.renewal_window_days', 30);
        $certs = SslCertificate::where('auto_renew', true)
            ->where('status', 'active')
            ->where('expires_at', '<=', now()->addDays($window))
            ->get();

        if ($certs->isEmpty()) {
            $this->info('No certificates need renewal.');
            return self::SUCCESS;
        }

        $this->info("Found {$certs->count()} certificate(s) to renew.");

        foreach ($certs as $cert) {
            if ($cert->renewal_failure_count >= (int) config('ssl.max_renewal_failures', 5)) {
                $this->error("Skipping {$cert->common_name} — max failures reached.");
                continue;
            }

            RenewSslCertificateJob::dispatch($cert->id);
            $this->line("Queued renewal for {$cert->common_name}");
        }

        return self::SUCCESS;
    }
}
