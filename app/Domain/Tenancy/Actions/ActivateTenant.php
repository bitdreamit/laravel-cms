<?php

namespace App\Domain\Tenancy\Actions;

use App\Models\Central\Tenant;

class ActivateTenant
{
    public function execute(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => 'active']);
        return $tenant->fresh();
    }
}
