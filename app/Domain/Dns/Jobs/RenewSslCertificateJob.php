<?php

namespace App\Domain\Dns\Jobs;

use App\Domain\Dns\Services\SslCertificateManager;
use App\Models\Central\SslCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RenewSslCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $certificateId,
    ) {}

    public function handle(SslCertificateManager $manager): void
    {
        $cert = SslCertificate::find($this->certificateId);

        if (! $cert || ! $cert->shouldRenew()) {
            return;
        }

        $manager->renew($cert);
    }
}
