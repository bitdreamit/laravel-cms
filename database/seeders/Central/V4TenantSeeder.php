<?php

namespace Database\Seeders\Central;

use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use App\Models\Central\Theme;
use App\Models\Central\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class V4TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Get the enterprise plan for V4 demo tenants
        $enterprisePlan = \App\Models\Central\BillingPlan::where('slug', 'multi-domain-enterprise')->first();
        $proPlan = \App\Models\Central\BillingPlan::where('slug', 'multi-domain-pro')->first();

        // ============================================
        // V3 Test Tenants (AdvMedi, BitDreamIT)
        // ============================================

        $advmedi = Tenant::create([
            'id' => Str::uuid(),
            'name' => 'AdvMedi',
            'slug' => 'advmedi',
            'plan_id' => $proPlan?->id,
            'status' => 'active',
            'data' => ['features' => ['multi_domain', 'workflow_engine', 'ai_rag', 'personalization']],
        ]);

        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $advmedi->id,
            'domain' => 'advmedi.test',
            'is_primary' => true,
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
        ]);

        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $advmedi->id,
            'domain' => 'shop.advmedi.test',
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
            'default_collection_handle' => 'products',
        ]);

        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $advmedi->id,
            'domain' => 'blog.advmedi.test',
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
            'default_collection_handle' => 'blog',
        ]);

        $bitdream = Tenant::create([
            'id' => Str::uuid(),
            'name' => 'BitDreamIT',
            'slug' => 'bitdreamit',
            'plan_id' => $proPlan?->id,
            'status' => 'active',
            'data' => ['features' => ['multi_domain']],
        ]);

        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $bitdream->id,
            'domain' => 'bitdreamit.test',
            'is_primary' => true,
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
        ]);

        // ============================================
        // V4 Test Tenant: Shopland (Connector demo)
        // ============================================

        $shopland = Tenant::create([
            'id' => Str::uuid(),
            'name' => 'Shopland',
            'slug' => 'shopland',
            'plan_id' => $proPlan?->id,
            'status' => 'active',
            'data' => ['features' => ['connector', 'multi_domain']],
        ]);

        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $shopland->id,
            'domain' => 'shopland.test',
            'is_primary' => true,
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
        ]);

        // ============================================
        // V4 Test Tenant: EnterpriseCorp (SAML + SCIM + Audit)
        // ============================================

        $enterprise = Tenant::create([
            'id' => Str::uuid(),
            'name' => 'EnterpriseCorp',
            'slug' => 'enterprisecorp',
            'plan_id' => $enterprisePlan?->id,
            'status' => 'active',
            'data' => ['features' => ['saml_sso', 'scim_provisioning', 'audit_streaming', 'workflow_engine']],
        ]);

        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $enterprise->id,
            'domain' => 'enterprise.test',
            'is_primary' => true,
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
        ]);

        // ============================================
        // V4 Test Tenant: Multilingual Co. (Wildcard + Per-Domain Locale)
        // ============================================

        $multilingual = Tenant::create([
            'id' => Str::uuid(),
            'name' => 'Multilingual Co.',
            'slug' => 'multilingual',
            'plan_id' => $enterprisePlan?->id,
            'status' => 'active',
            'data' => ['features' => ['multi_domain', 'ai_rag', 'personalization']],
        ]);

        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $multilingual->id,
            'domain' => 'multilingual.fr',
            'is_primary' => true,
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
        ]);

        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $multilingual->id,
            'domain' => 'multilingual.de',
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
        ]);

        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $multilingual->id,
            'domain' => 'multilingual.bn',
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
        ]);

        // Wildcard domain
        Domain::create([
            'id' => Str::uuid(),
            'tenant_id' => $multilingual->id,
            'domain' => '*.multilingual.test',
            'is_wildcard' => true,
            'wildcard_parent' => 'multilingual.test',
            'ssl_status' => 'active',
            'dns_verification_status' => 'verified',
            'dns_verified_at' => now(),
            'status' => 'active',
        ]);

        // ============================================
        // Foundation Theme (default for all tenants)
        // ============================================

        $foundation = Theme::create([
            'id' => Str::uuid(),
            'name' => 'Foundation',
            'slug' => 'foundation',
            'version' => '1.0.0',
            'description' => 'Clean, modern starter theme for CMS Platform V4',
            'author' => 'Platform Team',
            'type' => 'system',
            'path' => 'themes/foundation',
            'is_active' => true,
            'settings_schema' => [
                'branding' => [
                    'type' => 'section',
                    'title' => 'Branding',
                    'settings' => [
                        'brand_color' => ['type' => 'color', 'label' => 'Primary Color', 'default' => '#2563eb'],
                    ],
                ],
            ],
            'supported_features' => ['mega_menu', 'hero_slider', 'blog', 'contact_form'],
            'tags' => ['corporate', 'minimal', 'responsive'],
            'installed_count' => 5,
        ]);

        // Assign foundation theme to all tenants
        Tenant::query()->update(['current_theme_id' => $foundation->id]);

        // ============================================
        // Platform Super Admin User
        // ============================================

        User::create([
            'id' => Str::uuid(),
            'name' => 'Platform Admin',
            'email' => 'admin@platform.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_platform_super_admin' => true,
            'is_active' => true,
        ]);

        // Tenant admin users
        foreach ([$advmedi, $bitdream, $shopland, $enterprise, $multilingual] as $tenant) {
            $user = User::create([
                'id' => Str::uuid(),
                'name' => $tenant->name . ' Admin',
                'email' => 'admin@' . $tenant->slug . '.test',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            \DB::table('tenant_users')->insert([
                'id' => Str::uuid(),
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);
        }
    }
}
