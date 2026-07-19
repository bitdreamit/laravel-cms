<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Central\RegisteredConnector;

class ConnectorPolicy
{
    public function viewAny(User $user): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function view(User $user, RegisteredConnector $connector): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function update(User $user, RegisteredConnector $connector): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, RegisteredConnector $connector): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return $role === 'owner';
    }
}
