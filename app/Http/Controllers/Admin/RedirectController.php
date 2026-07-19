<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RedirectController extends Controller
{
    public function index()
    {
        $redirects = Redirect::where('tenant_id', tenant('id'))->paginate(20);
        return response()->json($redirects);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_url' => 'required|string|max:255',
            'destination_url' => 'required|string|max:255',
            'status_code' => 'integer|in:301,302,303,307,308',
            'is_active' => 'boolean',
        ]);

        $redirect = Redirect::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'status_code' => $data['status_code'] ?? 301,
            'is_active' => $data['is_active'] ?? true,
        ]));

        return response()->json($redirect, 201);
    }

    public function show(string $id)
    {
        $redirect = Redirect::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($redirect);
    }

    public function update(Request $request, string $id)
    {
        $redirect = Redirect::where('tenant_id', tenant('id'))->findOrFail($id);
        $redirect->update($request->only(['source_url', 'destination_url', 'status_code', 'is_active']));
        return response()->json($redirect);
    }

    public function destroy(string $id)
    {
        $redirect = Redirect::where('tenant_id', tenant('id'))->findOrFail($id);
        $redirect->delete();
        return response()->noContent();
    }
}
