<?php

namespace App\Policies;

use App\Models\Central\User;
use App\Models\Tenant\Workflow;

class WorkflowPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Workflow $workflow): bool { return true; }

    public function create(User $user): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function update(User $user, Workflow $workflow): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return $role === 'owner';
    }

    public function cancel(User $user, $instance): bool
    {
        $role = $user->tenantUsers()->where('tenant_id', tenant('id'))->value('role');
        return in_array($role, ['owner', 'admin']);
    }
}
