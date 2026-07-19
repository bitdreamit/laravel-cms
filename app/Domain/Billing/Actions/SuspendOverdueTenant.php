<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Services\BillingService;

class SuspendOverdueTenant
{
    public function __construct(protected BillingService $billingService) {}

    public function execute(): int
    {
        return $this->billingService->suspendOverdueTenants();
    }
}
