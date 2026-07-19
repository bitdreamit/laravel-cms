<?php

namespace App\Domain\Tenancy\Actions;

use App\Models\Central\Tenant;

class SuspendTenant
{
    public function execute(Tenant $tenant, ?string $reason = null): Tenant
    {
        $tenant->update(['status' => 'suspended']);

        event(new \App\Domain\Tenancy\Events\TenantSuspended($tenant, $reason));

        return $tenant->fresh();
    }
}
