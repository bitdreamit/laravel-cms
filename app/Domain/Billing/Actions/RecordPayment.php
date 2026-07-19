<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Services\BillingService;
use App\Models\Central\Invoice;
use App\Models\Central\Payment;

class RecordPayment
{
    public function __construct(protected BillingService $billingService) {}

    public function execute(Invoice $invoice, array $paymentData): Payment
    {
        return $this->billingService->processPayment($invoice, $paymentData);
    }
}
