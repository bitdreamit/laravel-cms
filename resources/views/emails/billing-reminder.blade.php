@component('mail::message')
# Payment Reminder

{{ $type === 'overdue_1_day' ? 'Your invoice is now OVERDUE.' : 'This is a friendly reminder about your upcoming invoice.' }}

**Invoice Number:** {{ $invoice->number }}
**Amount Due:** {{ $invoice->currency }} {{ $invoice->total }}
**Due Date:** {{ $invoice->due_at->format('M d, Y') }}

@if($invoice->due_at->isPast())
Your invoice is past due. Please make payment immediately to avoid account suspension.
@else()
Your invoice is due in {{ $invoice->due_at->diffInDays(now()) }} day(s).
@endif

@component('mail::button', ['url' => url('/admin/billing')])
View & Pay Invoice
@endcomponent

If you have already paid, please disregard this message.

Thank you for your business!
@endcomponent
