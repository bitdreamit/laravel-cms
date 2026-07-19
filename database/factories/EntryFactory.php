<?php

namespace Database\Factories;

use App\Models\Central\BillingPlan;
use App\Models\Central\Tenant;
use App\Models\Tenant\Collection;
use App\Models\Tenant\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EntryFactory extends Factory
{
    protected $model = Entry::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'collection_id' => Collection::factory(),
            'title' => $this->faker->sentence(4),
            'slug' => $this->faker->unique()->slug(3),
            'status' => 'published',
            'data' => ['body' => $this->faker->paragraphs(3, true)],
            'published_at' => now()->subDays(rand(1, 30)),
        ];
    }
}
