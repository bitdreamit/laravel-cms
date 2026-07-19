<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::where('team_id', tenant('id'))->with('permissions')->paginate(20);
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        $role = Role::create([
            'id' => Str::uuid(),
            'name' => $data['name'],
            'guard_name' => 'web',
            'team_id' => tenant('id'),
        ]);

        if (! empty($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function show(string $id)
    {
        $role = Role::where('team_id', tenant('id'))->where('id', $id)->with('permissions')->firstOrFail();
        return response()->json($role);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::where('team_id', tenant('id'))->where('id', $id)->firstOrFail();

        $data = $request->validate([
            'name' => 'string|max:100',
            'permissions' => 'array',
        ]);

        $role->update(['name' => $data['name'] ?? $role->name]);

        if (isset($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy(string $id)
    {
        $role = Role::where('team_id', tenant('id'))->where('id', $id)->firstOrFail();
        $role->delete();
        return response()->noContent();
    }

    public function permissions()
    {
        $permissions = Permission::where('team_id', tenant('id'))->orWhereNull('team_id')->get();
        return response()->json($permissions);
    }
}
