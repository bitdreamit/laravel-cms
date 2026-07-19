<?php

namespace App\Console\Commands;

use App\Models\Central\Invoice;
use App\Models\Central\Tenant;
use Illuminate\Console\Command;

class ReactivatePaidTenants extends Command
{
    protected $signature = 'billing:reactivate-paid';
    protected $description = 'Reactivate tenants whose overdue invoices have been paid.';

    public function handle(): int
    {
        $this->info('Checking for tenants to reactivate...');

        $suspendedTenants = Tenant::where('status', 'suspended')->get();
        $reactivated = 0;

        foreach ($suspendedTenants as $tenant) {
            // Check if tenant has any unpaid, non-void invoices past due
            $unpaidOverdue = Invoice::where('tenant_id', $tenant->id)
                ->where('status', '!=', 'paid')
                ->where('status', '!=', 'void')
                ->whereNull('paid_at')
                ->where('due_at', '<', now())
                ->exists();

            if (! $unpaidOverdue) {
                // All invoices are paid or void — reactivate
                $tenant->update(['status' => 'active']);
                $reactivated++;

                activity()
                    ->performedOn($tenant)
                    ->log('tenant_reactivated_after_payment');

                $this->line("  Reactivated {$tenant->name}");
            }
        }

        $this->info("✓ Reactivated {$reactivated} tenant(s).");
        return self::SUCCESS;
    }
}
