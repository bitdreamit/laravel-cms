<?php

namespace App\Console\Commands;

use App\Domain\Billing\Services\BillingService;
use Illuminate\Console\Command;

class SuspendOverdueTenants extends Command
{
    protected $signature = 'billing:suspend-overdue';
    protected $description = 'Auto-suspend tenants whose invoices are past the grace period.';

    public function handle(BillingService $billingService): int
    {
        $this->info('Checking for overdue tenants...');

        $count = $billingService->suspendOverdueTenants();

        $this->info("✓ Suspended {$count} overdue tenant(s).");
        return self::SUCCESS;
    }
}
