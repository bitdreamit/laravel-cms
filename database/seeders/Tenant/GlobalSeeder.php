<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\GlobalVariable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GlobalSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = tenant('id');

        GlobalVariable::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Site Settings',
            'handle' => 'site_settings',
            'data' => [
                'site_name' => tenant()->name,
                'tagline' => 'Welcome to our website',
                'footer_text' => '© ' . date('Y') . ' ' . tenant()->name,
                'social_links' => [
                    'facebook' => 'https://facebook.com/',
                    'twitter' => 'https://twitter.com/',
                    'linkedin' => 'https://linkedin.com/',
                ],
            ],
        ]);

        GlobalVariable::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Contact Info',
            'handle' => 'contact_info',
            'data' => [
                'email' => 'info@' . tenant('slug') . '.test',
                'phone' => '+1 (555) 123-4567',
                'address' => '123 Main St, City, Country',
            ],
        ]);
    }
}
