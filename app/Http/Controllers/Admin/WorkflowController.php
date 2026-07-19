<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Workflow\Services\WorkflowEngine;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWorkflowRequest;
use App\Models\Tenant\Workflow;
use App\Models\Tenant\WorkflowInstance;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(protected WorkflowEngine $engine) {}

    public function index()
    {
        $workflows = Workflow::where('tenant_id', tenant('id'))
            ->withCount(['instances' => function ($q) {
                $q->where('status', 'running');
            }])
            ->paginate(20);

        return response()->json($workflows);
    }

    public function store(StoreWorkflowRequest $request)
    {
        $data = $request->validated();
        $data['tenant_id'] = tenant('id');
        $data['created_by'] = auth()->id();

        $workflow = Workflow::create($data);

        return response()->json($workflow, 201);
    }

    public function show(string $id)
    {
        $workflow = Workflow::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($workflow);
    }

    public function update(Request $request, string $id)
    {
        $workflow = Workflow::where('tenant_id', tenant('id'))->findOrFail($id);
        $workflow->update($request->only(['name', 'handle', 'description', 'definition', 'is_active', 'trigger_event', 'trigger_collections']));

        return response()->json($workflow);
    }

    public function destroy(string $id)
    {
        $workflow = Workflow::where('tenant_id', tenant('id'))->findOrFail($id);
        $workflow->delete();
        return response()->noContent();
    }

    public function start(Request $request, string $id)
    {
        $workflow = Workflow::where('tenant_id', tenant('id'))->findOrFail($id);

        $request->validate([
            'entry_id' => 'required|uuid',
            'initial_context' => 'array',
        ]);

        $instance = $this->engine->start($workflow, $request->input('entry_id'), $request->input('initial_context', []));

        return response()->json($instance, 201);
    }

    public function advance(Request $request, string $instanceId)
    {
        $instance = WorkflowInstance::where('tenant_id', tenant('id'))->findOrFail($instanceId);

        $request->validate([
            'action' => 'required|string|in:approve,reject,request_changes,start,done,true,false',
            'comment' => 'nullable|string',
        ]);

        $instance = $this->engine->advance($instance, $request->input('action'), auth()->id(), $request->input('comment'));

        return response()->json($instance);
    }

    public function cancel(Request $request, string $instanceId)
    {
        $instance = WorkflowInstance::where('tenant_id', tenant('id'))->findOrFail($instanceId);
        $this->engine->cancel($instance, auth()->id(), $request->input('reason', 'Cancelled by user.'));

        return response()->json($instance);
    }
}
