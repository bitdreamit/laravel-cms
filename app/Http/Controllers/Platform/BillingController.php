<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Invoice;
use App\Models\Central\Payment;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('tenant')->orderByDesc('issued_at')->paginate(20);
        return response()->json($invoices);
    }

    public function createInvoice(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'required|uuid|exists:tenants,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'numeric|min:0',
            'items.*.unit_price' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        $tax = $subtotal * 0.0; // Adjust based on tenant's tax profile
        $total = $subtotal + $tax;

        $invoice = Invoice::create([
            'id' => Str::uuid(),
            'tenant_id' => $data['tenant_id'],
            'number' => 'INV-' . date('Y') . '-' . str_pad(Invoice::count() + 1, 6, '0', STR_PAD_LEFT),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'currency' => 'USD',
            'status' => 'sent',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            $invoice->lineItems()->create([
                'id' => Str::uuid(),
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return response()->json($invoice->load('lineItems'), 201);
    }

    public function recordPayment(Request $request, string $invoiceId)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'gateway' => 'required|string',
            'gateway_transaction_id' => 'nullable|string',
            'status' => 'required|in:succeeded,failed,pending',
        ]);

        $invoice = Invoice::findOrFail($invoiceId);

        $payment = Payment::create([
            'id' => Str::uuid(),
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'amount' => $data['amount'],
            'currency' => $invoice->currency,
            'gateway' => $data['gateway'],
            'gateway_transaction_id' => $data['gateway_transaction_id'] ?? null,
            'status' => $data['status'],
            'processed_at' => now(),
        ]);

        if ($data['status'] === 'succeeded') {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        }

        return response()->json($payment, 201);
    }

    public function revenue()
    {
        $monthlyRevenue = \DB::table('payments')
            ->where('status', 'succeeded')
            ->where('processed_at', '>=', now()->subYear())
            ->selectRaw('YEAR(processed_at) as year, MONTH(processed_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return response()->json(['monthly_revenue' => $monthlyRevenue]);
    }
}
