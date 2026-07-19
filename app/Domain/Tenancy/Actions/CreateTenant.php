<?php

namespace App\Domain\Tenancy\Actions;

use App\Models\Central\Tenant;
use Illuminate\Support\Str;

class CreateTenant
{
    public function execute(array $data): Tenant
    {
        $tenant = Tenant::create([
            'id' => $data['id'] ?? Str::uuid(),
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'plan_id' => $data['plan_id'] ?? null,
            'status' => $data['status'] ?? 'trial',
            'trial_ends_at' => $data['trial_ends_at'] ?? now()->addDays(14),
            'data' => $data['data'] ?? ['features' => []],
        ]);

        event(new \App\Domain\Tenancy\Events\TenantCreated($tenant));

        return $tenant;
    }
}
