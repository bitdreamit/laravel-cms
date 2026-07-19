<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use Illuminate\Console\Command;

class CreateTenant extends Command
{
    protected $signature = 'cms:create-tenant {name} {slug} {--domain=} {--plan=} {--feature=*}';
    protected $description = 'Create a new tenant with optional domain and features.';

    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = $this->argument('slug');
        $domain = $this->option('domain');
        $planSlug = $this->option('plan');
        $features = $this->option('feature');

        if (Tenant::where('slug', $slug)->exists()) {
            $this->error("Tenant with slug '{$slug}' already exists.");
            return self::FAILURE;
        }

        $plan = $planSlug ? \App\Models\Central\BillingPlan::where('slug', $planSlug)->first() : null;

        $tenant = \App\Domain\Tenancy\Actions\CreateTenant::dispatchSync([
            'name' => $name,
            'slug' => $slug,
            'plan_id' => $plan?->id,
            'status' => 'active',
            'data' => ['features' => $features],
        ]);

        if ($domain) {
            \App\Domain\Tenancy\Actions\AddDomainToTenant::dispatchSync($tenant, $domain, true);
            $this->info("Domain '{$domain}' added as primary.");
        }

        $this->info("Tenant '{$name}' created with ID: {$tenant->id}");
        $this->info("Slug: {$tenant->slug}");
        if ($features) {
            $this->info("Features: " . implode(', ', $features));
        }

        return self::SUCCESS;
    }
}
