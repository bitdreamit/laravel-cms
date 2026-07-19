<?php

namespace App\Mail;

use App\Models\Central\Invoice;
use App\Models\Central\Payment;
use Illuminate\Mail\Mailable;

class InvoicePaidMail extends Mailable
{
    public function __construct(public Invoice $invoice, public Payment $payment) {}

    public function build(): self
    {
        return $this->subject("Invoice {$this->invoice->number} Paid")
            ->markdown('emails.invoice-paid', [
                'invoice' => $this->invoice,
                'payment' => $this->payment,
            ]);
    }
}
