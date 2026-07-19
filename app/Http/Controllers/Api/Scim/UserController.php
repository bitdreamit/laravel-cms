<?php

namespace App\Http\Controllers\Api\Scim;

use App\Http\Controllers\Controller;
use App\Models\Central\User;
use App\Models\Central\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tmilos\ScimFilterParser\Parser;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = tenant('id');

        $query = User::whereHas('tenantUsers', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });

        // SCIM filter parsing
        if ($filter = $request->input('filter')) {
            $this->applyFilter($query, $filter);
        }

        $count = min((int) $request->input('count', 100), (int) config('scim.pagination.max_count', 1000));
        $startIndex = max((int) $request->input('startIndex', 1), 1);

        $total = $query->count();
        $users = $query->skip($startIndex - 1)->take($count)->get();

        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => $total,
            'startIndex' => $startIndex,
            'itemsPerPage' => $count,
            'Resources' => $users->map(fn($u) => $this->toScimUser($u)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'userName' => 'required|email',
            'displayName' => 'nullable|string',
            'active' => 'boolean',
            'emails' => 'array',
            'emails.*.value' => 'email',
            'emails.*.primary' => 'boolean',
        ]);

        $user = User::create([
            'id' => Str::uuid(),
            'name' => $data['displayName'] ?? $data['userName'],
            'email' => $data['userName'],
            'password' => bcrypt(Str::random(32)),
            'email_verified_at' => now(),
            'is_active' => $data['active'] ?? true,
        ]);

        TenantUser::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'user_id' => $user->id,
            'role' => 'viewer',
            'joined_at' => now(),
        ]);

        return response()->json($this->toScimUser($user), 201);
    }

    public function show(string $id)
    {
        $user = $this->findUser($id);
        return response()->json($this->toScimUser($user));
    }

    public function update(Request $request, string $id)
    {
        $user = $this->findUser($id);
        $data = $request->all();

        $user->update([
            'name' => $data['displayName'] ?? $user->name,
            'email' => $data['userName'] ?? $user->email,
            'is_active' => $data['active'] ?? $user->is_active ?? true,
        ]);

        return response()->json($this->toScimUser($user));
    }

    public function patch(Request $request, string $id)
    {
        $user = $this->findUser($id);
        $operations = $request->input('Operations', $request->input('operations', []));

        foreach ($operations as $op) {
            $path = $op['path'] ?? '';
            $value = $op['value'] ?? null;
            $opType = strtolower($op['op'] ?? '');

            if ($opType === 'replace') {
                if ($path === 'userName' || $path === 'emails[type eq "work"].value') {
                    $user->update(['email' => $value]);
                } elseif ($path === 'displayName') {
                    $user->update(['name' => $value]);
                } elseif ($path === 'active') {
                    $user->update(['is_active' => (bool) $value]);
                }
            }
        }

        return response()->json($this->toScimUser($user));
    }

    public function destroy(string $id)
    {
        $user = $this->findUser($id);

        if (config('scim.user.deactivate_on_delete', true)) {
            $user->update(['is_active' => false]);
        } else {
            $user->delete();
        }

        return response()->noContent();
    }

    protected function findUser(string $id): User
    {
        $user = User::where('id', $id)
            ->whereHas('tenantUsers', function ($q) {
                $q->where('tenant_id', tenant('id'));
            })
            ->first();

        if (! $user) abort(404);
        return $user;
    }

    protected function toScimUser(User $user): array
    {
        return [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'id' => $user->id,
            'userName' => $user->email,
            'displayName' => $user->name,
            'active' => $user->is_active ?? true,
            'emails' => [
                ['value' => $user->email, 'primary' => true],
            ],
            'meta' => [
                'resourceType' => 'User',
                'created' => $user->created_at?->toIso8601String(),
                'lastModified' => $user->updated_at?->toIso8601String(),
                'location' => url("/scim/v2/Users/{$user->id}"),
            ],
        ];
    }

    protected function applyFilter($query, string $filter): void
    {
        // Simple filter parser — handles "userName eq \"value\"" pattern
        if (preg_match('/(\w+)\s+(eq|co|sw|ew)\s+"([^"]+)"/', $filter, $matches)) {
            $field = $matches[1];
            $op = $matches[2];
            $value = $matches[3];

            $column = match ($field) {
                'userName' => 'email',
                'displayName' => 'name',
                default => $field,
            };

            $queryMethod = match ($op) {
                'eq' => 'where',
                'co' => 'where',
                'sw' => 'where',
                'ew' => 'where',
                default => 'where',
            };

            if ($op === 'eq') $query->where($column, $value);
            elseif ($op === 'co') $query->where($column, 'like', "%{$value}%");
            elseif ($op === 'sw') $query->where($column, 'like', "{$value}%");
            elseif ($op === 'ew') $query->where($column, 'like', "%{$value}");
        }
    }
}
