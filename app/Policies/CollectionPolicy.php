<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Tenant\Collection;

class CollectionPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Collection $collection): bool { return true; }

    public function create(User $user): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function update(User $user, Collection $collection): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function delete(User $user, Collection $collection): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return $role === 'owner';
    }
}
