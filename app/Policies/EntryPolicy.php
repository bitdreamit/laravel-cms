<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Tenant\Entry;

class EntryPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Entry $entry): bool { return true; }

    public function create(User $user): bool
    {
        return $user->tenantUsers()->where('tenant_id', tenant('id'))
            ->whereIn('role', ['owner', 'admin', 'editor', 'author', 'contributor'])->exists();
    }

    public function update(User $user, Entry $entry): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin', 'editor', 'author']);
    }

    public function publish(User $user, Entry $entry): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin', 'editor']);
    }

    public function delete(User $user, Entry $entry): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function restore(User $user, Entry $entry): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin', 'editor']);
    }
}
