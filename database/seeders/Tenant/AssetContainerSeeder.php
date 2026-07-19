<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\AssetContainer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssetContainerSeeder extends Seeder
{
    public function run(): void
    {
        AssetContainer::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'name' => 'Main Assets',
            'handle' => 'main',
            'disk' => 'public',
            'title' => 'Main asset container',
            'max_files' => 0,
            'allowed_file_types' => ['image/*', 'application/pdf', 'application/zip'],
        ]);
    }
}
