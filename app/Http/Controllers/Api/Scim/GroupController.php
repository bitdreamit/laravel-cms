<?php

namespace App\Http\Controllers\Api\Scim;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = Role::where('team_id', tenant('id'))->get();

        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => $groups->count(),
            'itemsPerPage' => $groups->count(),
            'startIndex' => 1,
            'Resources' => $groups->map(fn($r) => $this->toScimGroup($r)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'displayName' => 'required|string',
        ]);

        $role = Role::create([
            'id' => Str::uuid(),
            'name' => $data['displayName'],
            'team_id' => tenant('id'),
            'guard_name' => 'web',
        ]);

        return response()->json($this->toScimGroup($role), 201);
    }

    public function show(string $id)
    {
        $role = $this->findRole($id);
        return response()->json($this->toScimGroup($role));
    }

    public function update(Request $request, string $id)
    {
        $role = $this->findRole($id);
        $role->update(['name' => $request->input('displayName', $role->name)]);
        return response()->json($this->toScimGroup($role));
    }

    public function patch(Request $request, string $id)
    {
        $role = $this->findRole($id);
        // Handle member add/remove operations
        $operations = $request->input('Operations', []);

        foreach ($operations as $op) {
            if (strtolower($op['op'] ?? '') === 'add' && str_starts_with($op['path'] ?? '', 'members')) {
                foreach ($op['value'] ?? [] as $member) {
                    $user = \App\Models\Central\User::find($member['value'] ?? null);
                    if ($user) $user->assignRole($role);
                }
            } elseif (strtolower($op['op'] ?? '') === 'remove' && str_starts_with($op['path'] ?? '', 'members')) {
                // Parse member ID from path
                if (preg_match('/members\[value eq "([^"]+)"\]/', $op['path'], $matches)) {
                    $user = \App\Models\Central\User::find($matches[1]);
                    if ($user) $user->removeRole($role);
                }
            }
        }

        return response()->json($this->toScimGroup($role));
    }

    public function destroy(string $id)
    {
        $role = $this->findRole($id);
        $role->delete();
        return response()->noContent();
    }

    protected function findRole(string $id): Role
    {
        $role = Role::where('id', $id)->where('team_id', tenant('id'))->first();
        if (! $role) abort(404);
        return $role;
    }

    protected function toScimGroup(Role $role): array
    {
        $members = $role->users()->wherePivot('team_id', tenant('id'))->get()->map(fn($u) => [
            'value' => $u->id,
            'display' => $u->name,
        ]);

        return [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:Group'],
            'id' => $role->id,
            'displayName' => $role->name,
            'members' => $members,
            'meta' => [
                'resourceType' => 'Group',
                'location' => url("/scim/v2/Groups/{$role->id}"),
            ],
        ];
    }
}
