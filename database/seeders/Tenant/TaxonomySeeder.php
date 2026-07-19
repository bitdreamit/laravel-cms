<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Taxonomy;
use App\Models\Tenant\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = tenant('id');

        $categories = Taxonomy::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Categories',
            'handle' => 'categories',
            'description' => 'Content categories',
            'is_hierarchical' => true,
            'max_levels' => 3,
        ]);

        $tags = Taxonomy::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Tags',
            'handle' => 'tags',
            'description' => 'Content tags',
            'is_hierarchical' => false,
        ]);

        // Sample categories
        $sampleCategories = ['News', 'Tutorials', 'Case Studies', 'Announcements'];
        foreach ($sampleCategories as $name) {
            Term::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'taxonomy_id' => $categories->id,
                'title' => $name,
                'slug' => Str::slug($name),
                'sort_order' => 0,
            ]);
        }

        // Sample tags
        $sampleTags = ['php', 'laravel', 'cms', 'multi-tenant', 'vue', 'api', 'design'];
        foreach ($sampleTags as $tag) {
            Term::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'taxonomy_id' => $tags->id,
                'title' => $tag,
                'slug' => $tag,
                'sort_order' => 0,
            ]);
        }
    }
}
