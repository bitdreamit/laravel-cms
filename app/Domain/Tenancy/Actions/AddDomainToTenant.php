<?php

namespace App\Domain\Tenancy\Actions;

use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use Illuminate\Support\Str;

class AddDomainToTenant
{
    public function execute(Tenant $tenant, string $domainName, bool $isPrimary = false): Domain
    {
        $domain = Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'domain' => $domainName,
            'is_primary' => $isPrimary,
            'ssl_status' => 'pending',
            'dns_verification_status' => 'unverified',
            'status' => 'active',
        ]);

        if ($isPrimary) {
            Domain::where('tenant_id', $tenant->id)
                ->where('id', '!=', $domain->id)
                ->update(['is_primary' => false]);
        }

        return $domain;
    }
}
