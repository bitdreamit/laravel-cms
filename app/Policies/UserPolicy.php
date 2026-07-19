<?php

namespace App\Policies;

use App\Models\Central\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function view(User $user, User $targetUser): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function update(User $user, User $targetUser): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        if ($user->id === $targetUser->id) return true;
        return in_array($role, ['owner', 'admin']);
    }

    public function delete(User $user, User $targetUser): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        if ($user->id === $targetUser->id) return false; // Can't delete self
        return $role === 'owner';
    }
}
