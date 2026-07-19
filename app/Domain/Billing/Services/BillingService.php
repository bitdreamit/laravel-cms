<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Gateways\GatewayInterface;
use App\Models\Central\Invoice;
use App\Models\Central\Payment;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use Illuminate\Support\Str;

class BillingService
{
    public function __construct(protected GatewayManager $gatewayManager) {}

    public function generateInvoice(Tenant $tenant, array $items, ?string $notes = null): Invoice
    {
        $subtotal = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
        $tax = $subtotal * 0.0; // Adjust per tenant's tax profile
        $total = $subtotal + $tax;

        $invoice = Invoice::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'number' => 'INV-' . date('Y') . '-' . str_pad(Invoice::whereYear('created_at', date('Y'))->count() + 1, 6, '0', STR_PAD_LEFT),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'currency' => 'USD',
            'status' => 'sent',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'notes' => $notes,
        ]);

        foreach ($items as $item) {
            $invoice->lineItems()->create([
                'id' => Str::uuid(),
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
                'metadata' => $item['metadata'] ?? null,
            ]);
        }

        return $invoice->load('lineItems');
    }

    public function processPayment(Invoice $invoice, array $paymentData): Payment
    {
        $gateway = $this->gatewayManager->gateway($paymentData['gateway'] ?? config('billing.default_gateway'));
        $result = $gateway->charge((float) $invoice->total, $invoice->currency, $paymentData);

        $payment = Payment::create([
            'id' => Str::uuid(),
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'gateway' => $paymentData['gateway'],
            'gateway_transaction_id' => $result['transaction_id'],
            'status' => $result['success'] ? 'succeeded' : 'failed',
            'raw_response' => $result['raw'],
            'processed_at' => now(),
        ]);

        if ($result['success']) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            event(new \App\Domain\Billing\Events\InvoicePaid($invoice, $payment));
        }

        return $payment;
    }

    public function createSubscription(Tenant $tenant, string $planId, string $gateway = 'stripe'): Subscription
    {
        $subscription = Subscription::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'plan_id' => $planId,
            'status' => 'active',
            'started_at' => now(),
            'current_period_end' => now()->addMonth(),
            'gateway' => $gateway,
        ]);

        $tenant->update(['plan_id' => $planId]);

        return $subscription;
    }

    /**
     * Suspend overdue tenants.
     *
     * FIX: Previously hardcoded a 7-day grace period for all tenants.
     * Now reads per-plan grace_period_days, and excludes void/cancelled invoices.
     */
    public function suspendOverdueTenants(): int
    {
        $defaultGracePeriod = (int) config('billing.invoices.grace_period_days', 7);

        // Find overdue invoices, excluding paid and void
        $overdueInvoices = Invoice::whereNotIn('status', ['paid', 'void', 'draft'])
            ->whereNull('paid_at')
            ->where('due_at', '<', now())
            ->with(['tenant.plan'])
            ->get();

        $suspendedCount = 0;
        $processedTenants = [];

        foreach ($overdueInvoices as $invoice) {
            $tenant = $invoice->tenant;
            if (! $tenant || $tenant->status !== 'active') continue;
            if (in_array($tenant->id, $processedTenants)) continue;

            // Get grace period from tenant's plan, falling back to default
            $gracePeriod = $tenant->plan?->grace_period_days ?? $defaultGracePeriod;

            // Check if invoice is past the grace period
            $overdueDays = $invoice->due_at->diffInDays(now());
            if ($overdueDays < $gracePeriod) continue;

            // Suspend the tenant
            $tenant->update(['status' => 'suspended']);
            $processedTenants[] = $tenant->id;
            $suspendedCount++;

            activity()
                ->performedOn($tenant)
                ->withProperties([
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'overdue_days' => $overdueDays,
                    'grace_period' => $gracePeriod,
                    'plan' => $tenant->plan?->name,
                ])
                ->log('tenant_suspended_overdue');

            // Fire event for notifications
            event(new \App\Domain\Tenancy\Events\TenantSuspended($tenant, "Invoice {$invoice->number} overdue by {$overdueDays} days"));
        }

        return $suspendedCount;
    }
}
