<?php

namespace App\Domain\Billing\Listeners;

use App\Domain\Billing\Events\InvoicePaid;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmail
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $tenant = $invoice->tenant;

        // Send email notification
        $adminEmail = data_get($tenant->data, 'billing_email', "admin@{$tenant->slug}.test");

        Mail::to($adminEmail)->send(new \App\Mail\InvoicePaidMail($invoice, $event->payment));
    }
}
