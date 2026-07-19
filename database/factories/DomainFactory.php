<?php

namespace Database\Factories;

use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        return [
            'id' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'domain' => $this->faker->unique()->domainWord() . '.test',
            'is_primary' => false,
            'ssl_status' => 'pending',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
            'is_wildcard' => false,
        ];
    }
}
