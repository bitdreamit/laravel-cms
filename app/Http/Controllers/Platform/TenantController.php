<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Models\Central\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with(['plan', 'currentTheme']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
        }

        $tenants = $query->orderByDesc('created_at')->paginate(20);
        return response()->json($tenants);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'slug' => 'required|string|max:100|unique:tenants,slug',
            'plan_id' => 'nullable|uuid|exists:billing_plans,id',
            'status' => 'in:active,trial,suspended,cancelled',
            'domain' => 'nullable|string|unique:domains,domain',
        ]);

        $tenant = Tenant::create([
            'id' => Str::uuid(),
            'name' => $data['name'],
            'slug' => $data['slug'],
            'plan_id' => $data['plan_id'] ?? null,
            'status' => $data['status'] ?? 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        if (! empty($data['domain'])) {
            Domain::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenant->id,
                'domain' => $data['domain'],
                'is_primary' => true,
                'dns_verification_status' => 'verified',
                'dns_verified_at' => now(),
                'status' => 'active',
            ]);
        }

        return response()->json($tenant, 201);
    }

    public function show(string $id)
    {
        $tenant = Tenant::with(['plan', 'currentTheme', 'domains'])->findOrFail($id);
        return response()->json($tenant);
    }

    public function update(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update($request->only(['name', 'plan_id', 'status', 'data']));
        return response()->json($tenant);
    }

    public function destroy(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'cancelled']);
        return response()->noContent();
    }

    public function suspend(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'suspended']);
        return response()->json(['message' => 'Tenant suspended.']);
    }

    public function reactivate(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'active']);
        return response()->json(['message' => 'Tenant reactivated.']);
    }
}
