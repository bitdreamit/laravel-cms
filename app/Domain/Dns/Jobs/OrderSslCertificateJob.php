<?php

namespace App\Domain\Dns\Jobs;

use App\Domain\Dns\Services\SslCertificateManager;
use App\Models\Central\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderSslCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $domainId,
    ) {}

    public function handle(SslCertificateManager $manager): void
    {
        $domain = Domain::find($this->domainId);

        if (! $domain) {
            return;
        }

        $manager->issueForDomain($domain);
    }
}
