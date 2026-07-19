<?php

namespace App\Console\Commands;

use App\Domain\Billing\Actions\GenerateInvoice;
use App\Domain\Billing\Services\BillingService;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use Illuminate\Console\Command;

class GenerateInvoices extends Command
{
    protected $signature = 'billing:generate-invoices';
    protected $description = 'Generate recurring invoices for active subscriptions entering a new billing period.';

    public function handle(BillingService $billingService): int
    {
        $this->info('Checking for subscriptions needing new invoices...');

        $subscriptions = Subscription::where('status', 'active')
            ->where('current_period_end', '<=', now()->addDays(3))
            ->with(['tenant', 'plan'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions need invoicing.');
            return self::SUCCESS;
        }

        $generated = 0;
        foreach ($subscriptions as $subscription) {
            // Skip if an invoice already exists for this period
            $existingInvoice = \App\Models\Central\Invoice::where('tenant_id', $subscription->tenant_id)
                ->where('issued_at', '>=', $subscription->current_period_end->copy()->subMonth())
                ->whereHas('lineItems', fn($q) => $q->where('description', 'like', '%Subscription%'))
                ->exists();

            if ($existingInvoice) {
                $this->line("  Skipping {$subscription->tenant->name} — invoice already exists for this period.");
                continue;
            }

            $invoice = $billingService->generateInvoice($subscription->tenant, [
                [
                    'description' => "{$subscription->plan->name} subscription ({$subscription->plan->billing_cycle})",
                    'quantity' => 1,
                    'unit_price' => $subscription->plan->price_monthly,
                    'metadata' => ['subscription_id' => $subscription->id, 'plan_id' => $subscription->plan_id],
                ],
            ], notes: "Auto-generated invoice for subscription period ending {$subscription->current_period_end->format('Y-m-d')}");

            // Advance the subscription period
            $subscription->update([
                'current_period_end' => $subscription->current_period_end->addMonth(),
            ]);

            $this->info("  Generated invoice {$invoice->number} for {$subscription->tenant->name}");
            $generated++;
        }

        $this->info("✓ Generated {$generated} invoice(s).");
        return self::SUCCESS;
    }
}
