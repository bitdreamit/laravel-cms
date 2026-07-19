<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Blueprint;
use App\Models\Tenant\BlueprintField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlueprintSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = tenant('id');

        $bp = Blueprint::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'handle' => 'default',
            'title' => 'Default',
            'type' => 'collection',
            'icon' => 'document',
            'tabs' => ['Main' => ['title', 'body', 'excerpt']],
            'created_by' => null,
        ]);

        $fields = [
            ['handle' => 'title', 'fieldtype' => 'text', 'display_label' => 'Title', 'is_listable' => true, 'is_sortable' => true, 'sort_order' => 0],
            ['handle' => 'slug', 'fieldtype' => 'slug', 'display_label' => 'Slug', 'is_listable' => true, 'is_sortable' => false, 'sort_order' => 1, 'config' => ['from' => 'title']],
            ['handle' => 'body', 'fieldtype' => 'bard', 'display_label' => 'Body', 'is_listable' => false, 'is_sortable' => false, 'sort_order' => 2],
            ['handle' => 'excerpt', 'fieldtype' => 'textarea', 'display_label' => 'Excerpt', 'is_listable' => true, 'is_sortable' => false, 'sort_order' => 3],
            ['handle' => 'featured_image', 'fieldtype' => 'assets', 'display_label' => 'Featured Image', 'is_listable' => false, 'is_sortable' => false, 'sort_order' => 4, 'config' => ['max_files' => 1]],
            ['handle' => 'seo', 'fieldtype' => 'seo_pro', 'display_label' => 'SEO Settings', 'is_listable' => false, 'is_sortable' => false, 'sort_order' => 99],
        ];

        foreach ($fields as $field) {
            BlueprintField::create(array_merge([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'blueprint_id' => $bp->id,
            ], $field));
        }
    }
}
