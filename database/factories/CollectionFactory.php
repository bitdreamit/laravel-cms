<?php

namespace Database\Factories;

use App\Models\Tenant\Collection;
use App\Models\Central\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        return [
            'id' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => ucfirst($name),
            'handle' => Str::slug($name),
            'route_pattern' => '/{slug}',
            'template' => 'default',
            'structure_mode' => 'flat',
            'default_status' => 'draft',
            'is_searchable' => true,
        ];
    }
}
