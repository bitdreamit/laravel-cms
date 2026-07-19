<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Central seeders
        $this->call([
            Central\BillingPlanSeeder::class,
            Central\V4TenantSeeder::class,
        ]);

        // Per-tenant seeders must be run inside tenancy context
        // (See TenantDatabaseSeeder which initializes tenancy for each tenant)
        $this->call([
            TenantDatabaseSeeder::class,
        ]);
    }
}
