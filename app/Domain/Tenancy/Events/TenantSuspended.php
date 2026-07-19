<?php

namespace App\Domain\Tenancy\Events;

use App\Models\Central\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

class TenantSuspended
{
    use Dispatchable;

    public function __construct(public Tenant $tenant, public ?string $reason = null) {}
}
