<?php

namespace Database\Seeders\Central;

use App\Models\Central\BillingPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BillingPlanSeeder extends Seeder
{
    public function run(): void
    {
        BillingPlan::create([
            'id' => Str::uuid(),
            'name' => 'Single Domain Standard',
            'slug' => 'single-domain-standard',
            'description' => 'For small sites with one domain.',
            'price_monthly' => 29.00,
            'price_yearly' => 290.00,
            'currency' => 'USD',
            'max_domains' => 1,
            'max_admin_users' => 3,
            'max_storage_mb' => 5120,
            'max_themes' => 1,
            'theme_marketplace_access' => false,
            'white_label_allowed' => false,
            'custom_css_allowed' => false,
            'grace_period_days' => 7,
            'billing_cycle' => 'monthly',
            'features' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        BillingPlan::create([
            'id' => Str::uuid(),
            'name' => 'Multi Domain Professional',
            'slug' => 'multi-domain-pro',
            'description' => 'For agencies managing multiple domains per client.',
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'currency' => 'USD',
            'max_domains' => 10,
            'max_admin_users' => 15,
            'max_storage_mb' => 51200,
            'max_themes' => 5,
            'theme_marketplace_access' => true,
            'white_label_allowed' => false,
            'custom_css_allowed' => true,
            'grace_period_days' => 14,  // Pro plan: longer grace period
            'billing_cycle' => 'monthly',
            'features' => [],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        BillingPlan::create([
            'id' => Str::uuid(),
            'name' => 'Multi Domain Enterprise',
            'slug' => 'multi-domain-enterprise',
            'description' => 'Unlimited domains, white-label, SAML SSO, SCIM, audit streaming.',
            'price_monthly' => 299.00,
            'price_yearly' => 2990.00,
            'currency' => 'USD',
            'max_domains' => null,  // unlimited
            'max_admin_users' => 100,
            'max_storage_mb' => 512000,
            'max_themes' => 20,
            'theme_marketplace_access' => true,
            'white_label_allowed' => true,
            'custom_css_allowed' => true,
            'grace_period_days' => 30,  // Enterprise: longest grace period
            'billing_cycle' => 'monthly',
            'features' => [],
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }
}
