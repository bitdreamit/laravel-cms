<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Central\BillingPlan;
use App\Models\Central\Invoice;
use App\Models\Central\Subscription;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function currentPlan()
    {
        $tenant = tenant();
        $plan = $tenant->plan;
        $subscription = Subscription::where('tenant_id', $tenant->id)->where('status', 'active')->first();

        return response()->json([
            'plan' => $plan,
            'subscription' => $subscription,
            'usage' => [
                'domains' => $tenant->domains()->count(),
                'max_domains' => $plan?->max_domains,
                'users' => \DB::table('tenant_users')->where('tenant_id', $tenant->id)->count(),
                'max_admin_users' => $plan?->max_admin_users,
                'themes_installed' => 1,
                'max_themes' => $plan?->max_themes,
            ],
        ]);
    }

    public function invoices()
    {
        $invoices = Invoice::where('tenant_id', tenant('id'))->orderByDesc('issued_at')->paginate(20);
        return response()->json($invoices);
    }

    public function showInvoice(string $id)
    {
        $invoice = Invoice::where('tenant_id', tenant('id'))->with(['lineItems', 'payments'])->findOrFail($id);
        return response()->json($invoice);
    }

    public function downloadInvoice(string $id)
    {
        $invoice = Invoice::where('tenant_id', tenant('id'))->with(['lineItems', 'tenant'])->findOrFail($id);

        // Generate PDF invoice (in production, use barryvdh/laravel-dompdf or laravel-snappy)
        $html = view('admin.billing.invoice', ['invoice' => $invoice])->render();
        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    public function paymentMethods()
    {
        // Return saved payment methods (in production, integrate with Stripe customer object)
        return response()->json(['methods' => []]);
    }

    public function plans()
    {
        $plans = BillingPlan::where('is_active', true)->orderBy('sort_order')->get();
        return response()->json($plans);
    }

    public function changePlan(Request $request)
    {
        $data = $request->validate(['plan_id' => 'required|uuid|exists:billing_plans,id']);
        $tenant = tenant();
        $tenant->update(['plan_id' => $data['plan_id']]);
        return response()->json(['message' => 'Plan changed successfully.']);
    }
}
