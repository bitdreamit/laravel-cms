<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Services\BillingService;
use App\Models\Central\Invoice;
use App\Models\Central\Tenant;

class GenerateInvoice
{
    public function __construct(protected BillingService $billingService) {}

    public function execute(Tenant $tenant, array $items, ?string $notes = null): Invoice
    {
        return $this->billingService->generateInvoice($tenant, $items, $notes);
    }
}
