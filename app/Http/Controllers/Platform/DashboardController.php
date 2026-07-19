<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'trial_tenants' => Tenant::where('status', 'trial')->count(),
            'suspended_tenants' => Tenant::where('status', 'suspended')->count(),
            'mrr' => \DB::table('subscriptions')
                ->join('billing_plans', 'subscriptions.plan_id', '=', 'billing_plans.id')
                ->where('subscriptions.status', 'active')
                ->sum('billing_plans.price_monthly'),
            'overdue_invoices_total' => \DB::table('invoices')
                ->where('status', '!=', 'paid')
                ->where('due_at', '<', now())
                ->sum('total'),
        ];

        $recentTenants = Tenant::orderByDesc('created_at')->take(5)->get();
        $upcomingRenewals = \DB::table('subscriptions')
            ->where('status', 'active')
            ->where('current_period_end', '<=', now()->addDays(7))
            ->join('tenants', 'subscriptions.tenant_id', '=', 'tenants.id')
            ->select('tenants.name', 'subscriptions.current_period_end')
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_tenants' => $recentTenants,
            'upcoming_renewals' => $upcomingRenewals,
        ]);
    }
}
