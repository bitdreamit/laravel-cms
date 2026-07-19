<?php

namespace Database\Factories;

use App\Models\Tenant\Workflow;
use App\Models\Central\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->words(3, true),
            'handle' => Str::slug($this->faker->words(3, true)),
            'trigger_event' => 'entry.submitted_for_review',
            'trigger_collections' => ['blog'],
            'definition' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start', 'next' => 'end'],
                    ['id' => 'end', 'type' => 'end', 'outcome' => 'approved'],
                ],
            ],
            'is_active' => true,
        ];
    }
}
