<?php

namespace Database\Seeders;

use App\Models\Central\Tenant;
use Illuminate\Database\Seeder;
use Stancl\Tenancy\Tenancy;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            app(Tenancy::class)->run($tenant, function () use ($tenant) {
                $this->command->info("Seeding tenant: {$tenant->name}");

                // V3 baseline seeders
                $this->call([
                    \Database\Seeders\Tenant\RolePermissionSeeder::class,
                    \Database\Seeders\Tenant\BlueprintSeeder::class,
                    \Database\Seeders\Tenant\TaxonomySeeder::class,
                    \Database\Seeders\Tenant\NavigationSeeder::class,
                    \Database\Seeders\Tenant\FormSeeder::class,
                    \Database\Seeders\Tenant\GlobalSeeder::class,
                    \Database\Seeders\Tenant\AssetContainerSeeder::class,
                ]);

                // Create sites (locales) per tenant
                \App\Models\Tenant\Site::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'name' => 'Default',
                    'handle' => 'default',
                    'locale' => 'en',
                    'is_default' => true,
                ]);

                // For Multilingual Co., create additional locale sites
                if ($tenant->slug === 'multilingual') {
                    \App\Models\Tenant\Site::create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'tenant_id' => tenant('id'),
                        'name' => 'French',
                        'handle' => 'fr',
                        'locale' => 'fr-FR',
                        'is_default' => false,
                    ]);

                    \App\Models\Tenant\Site::create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'tenant_id' => tenant('id'),
                        'name' => 'German',
                        'handle' => 'de',
                        'locale' => 'de-DE',
                        'is_default' => false,
                    ]);

                    \App\Models\Tenant\Site::create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'tenant_id' => tenant('id'),
                        'name' => 'Bengali',
                        'handle' => 'bn',
                        'locale' => 'bn-BD',
                        'is_default' => false,
                    ]);
                }

                // Create a sample collection
                $collectionHandle = match ($tenant->slug) {
                    'shopland' => 'products',
                    'advmedi' => 'services',
                    'multilingual' => 'articles',
                    'enterprisecorp' => 'pages',
                    default => 'blog',
                };

                $collection = \App\Models\Tenant\Collection::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'name' => ucfirst($collectionHandle),
                    'handle' => $collectionHandle,
                    'route_pattern' => '/{slug}',
                    'template' => 'default',
                    'structure_mode' => 'flat',
                    'default_status' => 'draft',
                    'is_searchable' => true,
                ]);

                // Create sample entries
                for ($i = 1; $i <= 5; $i++) {
                    \App\Models\Tenant\Entry::create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'tenant_id' => tenant('id'),
                        'collection_id' => $collection->id,
                        'title' => "Sample Entry {$i} for {$tenant->name}",
                        'slug' => "sample-{$i}-" . \Illuminate\Support\Str::slug($tenant->slug),
                        'status' => 'published',
                        'data' => [
                            'body' => "This is sample content for entry {$i} of {$tenant->name}. " .
                                      "It demonstrates the V4 multi-tenant CMS capabilities with full content management.",
                        ],
                        'published_at' => now()->subDays($i),
                    ]);
                }
            });
        }
    }
}
