<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PersonalizationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PersonalizationRuleController extends Controller
{
    public function index()
    {
        $rules = PersonalizationRule::where('tenant_id', tenant('id'))
            ->with('segment')
            ->orderBy('priority')
            ->paginate(20);
        return response()->json($rules);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'segment_id' => 'required|uuid',
            'target_type' => 'required|in:entry_field_override,template_override,block_visibility,redirect',
            'target_config' => 'required|array',
            'priority' => 'integer|min:0|max:999',
            'is_active' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
        ]);

        $rule = PersonalizationRule::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));

        return response()->json($rule, 201);
    }

    public function show(string $id)
    {
        $rule = PersonalizationRule::where('tenant_id', tenant('id'))->with('segment')->findOrFail($id);
        return response()->json($rule);
    }

    public function update(Request $request, string $id)
    {
        $rule = PersonalizationRule::where('tenant_id', tenant('id'))->findOrFail($id);
        $rule->update($request->only(['name', 'segment_id', 'target_type', 'target_config', 'priority', 'is_active', 'start_at', 'end_at']));
        return response()->json($rule);
    }

    public function destroy(string $id)
    {
        $rule = PersonalizationRule::where('tenant_id', tenant('id'))->findOrFail($id);
        $rule->delete();
        return response()->noContent();
    }
}
