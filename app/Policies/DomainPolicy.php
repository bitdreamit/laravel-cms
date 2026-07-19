<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Central\Domain;

class DomainPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Domain $domain): bool { return true; }

    public function create(User $user): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function update(User $user, Domain $domain): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function delete(User $user, Domain $domain): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return $role === 'owner';
    }

    public function manageSsl(User $user, Domain $domain): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function manageDns(User $user, Domain $domain): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function manageConfig(User $user, Domain $domain): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return $role === 'owner';
    }
}
