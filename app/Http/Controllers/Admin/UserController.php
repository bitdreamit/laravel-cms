<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Central\User;
use App\Models\Central\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $tenantUserIds = TenantUser::where('tenant_id', tenant('id'))->pluck('user_id');
        $users = User::whereIn('id', $tenantUserIds)
            ->with(['tenantUsers' => fn($q) => $q->where('tenant_id', tenant('id'))])
            ->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:owner,admin,editor,author,contributor,viewer',
        ]);

        $user = User::create([
            'id' => Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        TenantUser::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'user_id' => $user->id,
            'role' => $data['role'],
            'invited_by' => auth()->id(),
            'joined_at' => now(),
        ]);

        return response()->json($user, 201);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);
        $tenantUser = TenantUser::where('tenant_id', tenant('id'))->where('user_id', $id)->firstOrFail();
        return response()->json(['user' => $user, 'tenant_user' => $tenantUser]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $tenantUser = TenantUser::where('tenant_id', tenant('id'))->where('user_id', $id)->firstOrFail();

        $data = $request->validate([
            'name' => 'string|max:200',
            'email' => 'email|unique:users,email,' . $id,
            'role' => 'in:owner,admin,editor,author,contributor,viewer',
            'is_active' => 'boolean',
        ]);

        $user->update($request->only(['name', 'email']));
        if (isset($data['is_active'])) {
            $user->update(['is_active' => $data['is_active']]);
        }
        if (isset($data['role'])) {
            $tenantUser->update(['role' => $data['role']]);
        }

        return response()->json($user);
    }

    public function destroy(string $id)
    {
        $tenantUser = TenantUser::where('tenant_id', tenant('id'))->where('user_id', $id)->firstOrFail();
        $tenantUser->delete();
        return response()->noContent();
    }
}
