<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Central\Theme;

class ThemePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Theme $theme): bool { return true; }

    public function customize(User $user, Theme $theme): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function editFiles(User $user, Theme $theme): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return $role === 'owner';
    }

    public function upload(User $user): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function activate(User $user, Theme $theme): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function uninstall(User $user, Theme $theme): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return $role === 'owner';
    }
}
