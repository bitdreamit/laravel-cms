<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Experiment\Services\ExperimentEngine;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Experiment;
use App\Models\Tenant\ExperimentVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExperimentController extends Controller
{
    public function __construct(protected ExperimentEngine $engine) {}

    public function index()
    {
        $experiments = Experiment::where('tenant_id', tenant('id'))
            ->with(['variants', 'winningVariant'])
            ->withCount('assignments')
            ->paginate(20);

        return response()->json($experiments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'description' => 'nullable|string',
            'experiment_type' => 'required|in:entry_variant,template_variant,cta_variant,headline_variant',
            'entry_id' => 'nullable|uuid',
            'collection_handle' => 'nullable|string',
            'traffic_allocation' => 'integer|min:0|max:100',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'goal_type' => 'required|in:conversion,bounce,time_on_page,scroll_depth,custom_event',
            'goal_config' => 'array',
            'min_sample_size' => 'integer|min:1',
            'confidence_threshold' => 'numeric|min:0.5|max:1',
            'variants' => 'required|array|min:2',
            'variants.*.name' => 'required|string',
            'variants.*.handle' => 'required|string',
            'variants.*.is_control' => 'boolean',
            'variants.*.weight' => 'integer|min:0|max:100',
            'variants.*.entry_id' => 'nullable|uuid',
            'variants.*.template_override' => 'nullable|string',
            'variants.*.field_overrides' => 'nullable|array',
        ]);

        $experimentData = $request->only([
            'name', 'handle', 'description', 'experiment_type', 'entry_id',
            'collection_handle', 'traffic_allocation', 'start_at', 'end_at',
            'goal_type', 'goal_config', 'min_sample_size', 'confidence_threshold',
        ]);
        $experimentData['tenant_id'] = tenant('id');
        $experimentData['created_by'] = auth()->id();
        $experimentData['status'] = 'draft';

        $experiment = Experiment::create($experimentData);

        // Create variants
        foreach ($request->input('variants') as $variantData) {
            $variantData['id'] = Str::uuid();
            $variantData['tenant_id'] = tenant('id');
            $variantData['experiment_id'] = $experiment->id;
            ExperimentVariant::create($variantData);
        }

        return response()->json($experiment->load('variants'), 201);
    }

    public function show(string $id)
    {
        $experiment = Experiment::where('tenant_id', tenant('id'))
            ->with(['variants.assignments', 'winningVariant'])
            ->findOrFail($id);

        $stats = $this->engine->calculateStatistics($experiment);

        return response()->json([
            'experiment' => $experiment,
            'statistics' => $stats,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $experiment = Experiment::where('tenant_id', tenant('id'))->findOrFail($id);

        $experiment->update($request->only([
            'name', 'description', 'traffic_allocation', 'status',
            'start_at', 'end_at', 'goal_type', 'goal_config',
            'min_sample_size', 'confidence_threshold',
        ]));

        return response()->json($experiment);
    }

    public function destroy(string $id)
    {
        $experiment = Experiment::where('tenant_id', tenant('id'))->findOrFail($id);
        $experiment->delete();
        return response()->noContent();
    }

    public function promoteWinner(string $id)
    {
        $experiment = Experiment::where('tenant_id', tenant('id'))->findOrFail($id);
        $stats = $this->engine->calculateStatistics($experiment);

        // Find the winning variant
        $winningStat = collect($stats)->firstWhere('is_significant', true);
        if (! $winningStat || $winningStat['lift_vs_control'] <= 0) {
            return response()->json(['error' => 'No statistically significant winning variant found.'], 422);
        }

        $winner = ExperimentVariant::find($winningStat['variant_id']);
        $experiment->update([
            'winning_variant_id' => $winner->id,
            'status' => 'completed',
            'end_at' => now(),
        ]);

        // For entry_variant: copy winner's entry data to control entry
        if ($experiment->experiment_type === 'entry_variant' && $winner->entry_id) {
            $control = $experiment->controlVariant();
            if ($control && $control->entry_id) {
                $controlEntry = \App\Models\Tenant\Entry::find($control->entry_id);
                $winnerEntry = \App\Models\Tenant\Entry::find($winner->entry_id);
                if ($controlEntry && $winnerEntry) {
                    $controlEntry->update([
                        'data' => $winnerEntry->data,
                        'title' => $winnerEntry->title,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Winner promoted.',
            'winning_variant' => $winner,
        ]);
    }
}
