<?php

namespace App\Domain\Tenancy\Events;

use App\Models\Central\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

class TenantCreated
{
    use Dispatchable;

    public function __construct(public Tenant $tenant) {}
}
