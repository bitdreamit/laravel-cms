<?php

namespace App\Mail;

use App\Models\Central\Invoice;
use Illuminate\Mail\Mailable;

class BillingReminderMail extends Mailable
{
    public function __construct(public Invoice $invoice, public string $type) {}

    public function build(): self
    {
        $subject = match($this->type) {
            'reminder_7_days' => "Reminder: Invoice {$this->invoice->number} due in 7 days",
            'reminder_1_day' => "URGENT: Invoice {$this->invoice->number} due tomorrow",
            'overdue_1_day' => "OVERDUE: Invoice {$this->invoice->number} is 1 day past due",
            default => "Reminder: Invoice {$this->invoice->number}",
        };

        return $this->subject($subject)
            ->markdown('emails.billing-reminder', [
                'invoice' => $this->invoice,
                'type' => $this->type,
            ]);
    }
}
