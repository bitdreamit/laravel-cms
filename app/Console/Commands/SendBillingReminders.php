<?php

namespace App\Console\Commands;

use App\Domain\Billing\Services\BillingService;
use App\Models\Central\Invoice;
use App\Models\Central\Tenant;
use Illuminate\Console\Command;

class SendBillingReminders extends Command
{
    protected $signature = 'billing:send-reminders';
    protected $description = 'Send payment reminder emails for upcoming/overdue invoices.';

    public function handle(): int
    {
        $this->info('Sending payment reminders...');

        $sent = 0;

        // T-7 reminder: invoices due in 7 days
        $t7Invoices = Invoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'void')
            ->whereNull('paid_at')
            ->whereDate('due_at', now()->addDays(7)->toDateString())
            ->get();

        foreach ($t7Invoices as $invoice) {
            $this->sendReminder($invoice, 'reminder_7_days');
            $sent++;
        }

        // T-1 reminder: invoices due tomorrow
        $t1Invoices = Invoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'void')
            ->whereNull('paid_at')
            ->whereDate('due_at', now()->addDay()->toDateString())
            ->get();

        foreach ($t1Invoices as $invoice) {
            $this->sendReminder($invoice, 'reminder_1_day');
            $sent++;
        }

        // T+1 reminder: invoices 1 day overdue
        $overdue1Invoices = Invoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'void')
            ->whereNull('paid_at')
            ->whereDate('due_at', now()->subDay()->toDateString())
            ->get();

        foreach ($overdue1Invoices as $invoice) {
            $this->sendReminder($invoice, 'overdue_1_day');
            $sent++;
        }

        $this->info("✓ Sent {$sent} reminder(s).");
        return self::SUCCESS;
    }

    protected function sendReminder(Invoice $invoice, string $type): void
    {
        $tenant = $invoice->tenant;
        $email = data_get($tenant->data, 'billing_email', "admin@{$tenant->slug}.test");

        \Illuminate\Support\Facades\Mail::to($email)->send(
            new \App\Mail\BillingReminderMail($invoice, $type)
        );

        activity()
            ->performedOn($invoice)
            ->withProperties(['type' => $type, 'sent_to' => $email])
            ->log('billing_reminder_sent');

        $this->line("  Sent {$type} reminder for invoice {$invoice->number}");
    }
}
