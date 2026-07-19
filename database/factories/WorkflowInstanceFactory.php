<?php

namespace Database\Factories;

use App\Models\Tenant\WorkflowInstance;
use App\Models\Central\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkflowInstanceFactory extends Factory
{
    protected $model = WorkflowInstance::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'workflow_id' => Workflow::factory(),
            'entry_id' => Str::uuid(),
            'current_node_id' => 'start',
            'status' => 'running',
            'context' => [],
            'started_at' => now(),
        ];
    }
}
