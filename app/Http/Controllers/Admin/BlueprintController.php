<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Services\BlueprintService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlueprintRequest;
use App\Http\Requests\Admin\UpdateBlueprintRequest;
use App\Http\Resources\Api\BlueprintResource;
use App\Models\Tenant\Blueprint;
use Illuminate\Http\Request;

class BlueprintController extends Controller
{
    public function __construct(protected BlueprintService $blueprintService) {}

    public function index()
    {
        $blueprints = Blueprint::where('tenant_id', tenant('id'))->with('fields')->paginate(20);
        return BlueprintResource::collection($blueprints);
    }

    public function store(StoreBlueprintRequest $request)
    {
        $blueprint = $this->blueprintService->createBlueprint($request->validated());
        return new BlueprintResource($blueprint);
    }

    public function show(string $id)
    {
        $blueprint = Blueprint::where('tenant_id', tenant('id'))->with('fields')->findOrFail($id);
        return new BlueprintResource($blueprint);
    }

    public function update(UpdateBlueprintRequest $request, string $id)
    {
        $blueprint = Blueprint::where('tenant_id', tenant('id'))->findOrFail($id);
        $blueprint->update($request->validated());
        return new BlueprintResource($blueprint->fresh('fields'));
    }

    public function destroy(string $id)
    {
        $blueprint = Blueprint::where('tenant_id', tenant('id'))->findOrFail($id);
        $blueprint->delete();
        return response()->noContent();
    }

    public function addField(Request $request, string $id)
    {
        $blueprint = Blueprint::where('tenant_id', tenant('id'))->findOrFail($id);
        $field = $this->blueprintService->addField($blueprint, $request->all());
        return response()->json($field, 201);
    }

    public function validateData(Request $request, string $id)
    {
        $blueprint = Blueprint::where('tenant_id', tenant('id'))->findOrFail($id);
        $errors = $this->blueprintService->validateDataAgainstBlueprint($blueprint, $request->input('data', []));
        return response()->json(['valid' => empty($errors), 'errors' => $errors]);
    }
}
