<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Navigation;
use App\Models\Tenant\NavigationItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = tenant('id');

        $mainNav = Navigation::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Main Menu',
            'handle' => 'main_menu',
            'max_depth' => 2,
        ]);

        $items = [
            ['title' => 'Home', 'url' => '/', 'sort_order' => 0],
            ['title' => 'About', 'url' => '/about', 'sort_order' => 1],
            ['title' => 'Contact', 'url' => '/contact', 'sort_order' => 2],
        ];

        foreach ($items as $item) {
            NavigationItem::create(array_merge([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'navigation_id' => $mainNav->id,
            ], $item));
        }

        $footerNav = Navigation::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Footer',
            'handle' => 'footer',
            'max_depth' => 1,
        ]);

        NavigationItem::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'navigation_id' => $footerNav->id,
            'title' => 'Privacy Policy',
            'url' => '/privacy',
            'sort_order' => 0,
        ]);
    }
}
