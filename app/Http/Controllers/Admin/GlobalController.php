<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\GlobalVariable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GlobalController extends Controller
{
    public function index()
    {
        $globals = GlobalVariable::where('tenant_id', tenant('id'))->paginate(20);
        return response()->json($globals);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'data' => 'nullable|array',
        ]);

        $global = GlobalVariable::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));

        return response()->json($global, 201);
    }

    public function show(string $id)
    {
        $global = GlobalVariable::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($global);
    }

    public function update(Request $request, string $id)
    {
        $global = GlobalVariable::where('tenant_id', tenant('id'))->findOrFail($id);
        $global->update($request->only(['name', 'data']));
        return response()->json($global);
    }

    public function destroy(string $id)
    {
        $global = GlobalVariable::where('tenant_id', tenant('id'))->findOrFail($id);
        $global->delete();
        return response()->noContent();
    }
}
