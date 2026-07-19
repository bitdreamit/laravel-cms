@component('mail::message')
# Invoice Paid

Your invoice has been paid successfully.

**Invoice Number:** {{ $invoice->number }}
**Amount:** {{ $invoice->currency }} {{ $invoice->total }}
**Payment Method:** {{ $payment->gateway }}
**Transaction ID:** {{ $payment->gateway_transaction_id }}
**Paid At:** {{ $payment->processed_at?->format('M d, Y H:i') }}

@if($invoice->lineItems->isNotEmpty())
**Line Items:**
@foreach($invoice->lineItems as $item)
- {{ $item->description }}: {{ $invoice->currency }} {{ $item->total }}
@endforeach
@endif

Thank you for your business!
@endcomponent
