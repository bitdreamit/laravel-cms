<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = tenant('id');

        // Create permissions (team-scoped to tenant_id)
        foreach (config('permissions.permissions', []) as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web', 'team_id' => $tenantId],
                ['id' => Str::uuid()]
            );
        }

        // Create roles and assign permissions
        foreach (config('permissions.default_roles', []) as $roleName => $permissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web', 'team_id' => $tenantId],
                ['id' => Str::uuid()]
            );

            if (in_array('*', $permissions)) {
                $allPermissions = Permission::where('team_id', $tenantId)->orWhereNull('team_id')->get();
                $role->syncPermissions($allPermissions);
            } else {
                $permModels = Permission::whereIn('name', $permissions)
                    ->where(function ($q) use ($tenantId) {
                        $q->where('team_id', $tenantId)->orWhereNull('team_id');
                    })->get();
                $role->syncPermissions($permModels);
            }
        }

        // Assign 'owner' role to the tenant's admin user
        $ownerTenantUser = \DB::table('tenant_users')->where('tenant_id', $tenantId)->first();
        if ($ownerTenantUser) {
            $user = \App\Models\Central\User::find($ownerTenantUser->user_id);
            $ownerRole = Role::where('name', 'owner')->where('team_id', $tenantId)->first();
            if ($user && $ownerRole) {
                $user->assignRole($ownerRole);
            }
        }
    }
}
