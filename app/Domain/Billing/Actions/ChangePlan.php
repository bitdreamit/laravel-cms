<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Services\BillingService;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;

class ChangePlan
{
    public function __construct(protected BillingService $billingService) {}

    public function execute(Tenant $tenant, string $newPlanId, string $gateway = 'stripe'): Subscription
    {
        // Cancel existing subscription if any
        $existing = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            $existing->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        }

        return $this->billingService->createSubscription($tenant, $newPlanId, $gateway);
    }
}
