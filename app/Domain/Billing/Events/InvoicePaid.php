<?php

namespace App\Domain\Billing\Events;

use App\Models\Central\Invoice;
use App\Models\Central\Payment;
use Illuminate\Foundation\Events\Dispatchable;

class InvoicePaid
{
    use Dispatchable;

    public function __construct(
        public Invoice $invoice,
        public Payment $payment,
    ) {}
}
