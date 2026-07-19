<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Segment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SegmentController extends Controller
{
    public function index()
    {
        $segments = Segment::where('tenant_id', tenant('id'))->paginate(20);
        return response()->json($segments);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'description' => 'nullable|string',
            'rules' => 'required|array',
            'is_dynamic' => 'boolean',
        ]);

        $segment = Segment::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));

        return response()->json($segment, 201);
    }

    public function show(string $id)
    {
        $segment = Segment::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($segment);
    }

    public function update(Request $request, string $id)
    {
        $segment = Segment::where('tenant_id', tenant('id'))->findOrFail($id);
        $segment->update($request->only(['name', 'description', 'rules', 'is_dynamic']));
        return response()->json($segment);
    }

    public function destroy(string $id)
    {
        $segment = Segment::where('tenant_id', tenant('id'))->findOrFail($id);
        $segment->delete();
        return response()->noContent();
    }
}
