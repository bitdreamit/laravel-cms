<?php

namespace Database\Factories;

use App\Models\Central\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->slug(2),
            'status' => 'active',
            'data' => ['features' => []],
        ];
    }
}
