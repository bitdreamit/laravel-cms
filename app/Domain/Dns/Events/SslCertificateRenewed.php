<?php

namespace App\Domain\Dns\Events;

use App\Models\Central\SslCertificate;
use Illuminate\Foundation\Events\Dispatchable;

class SslCertificateRenewed
{
    use Dispatchable;

    public function __construct(public SslCertificate $certificate) {}
}
