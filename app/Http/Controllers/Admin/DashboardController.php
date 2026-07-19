<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $tenantId = tenant('id');

        $stats = Cache::remember("dashboard:{$tenantId}", now()->addMinutes(5), function () use ($tenantId) {
            return [
                'entries' => \DB::table('entries')->where('tenant_id', $tenantId)->count(),
                'published_entries' => \DB::table('entries')->where('tenant_id', $tenantId)->where('status', 'published')->count(),
                'draft_entries' => \DB::table('entries')->where('tenant_id', $tenantId)->where('status', 'draft')->count(),
                'collections' => \DB::table('collections')->where('tenant_id', $tenantId)->count(),
                'users' => \DB::table('tenant_users')->where('tenant_id', $tenantId)->count(),
                'forms' => \DB::table('forms')->where('tenant_id', $tenantId)->count(),
                'form_submissions' => \DB::table('form_submissions')->where('tenant_id', $tenantId)->count(),
                'assets' => \DB::table('assets')->where('tenant_id', $tenantId)->count(),
                'storage_mb' => round(\DB::table('assets')->where('tenant_id', $tenantId)->sum('size') / 1024 / 1024, 2),
                'domains' => \DB::table('domains')->where('tenant_id', $tenantId)->count(),
            ];
        });

        $recentEntries = \App\Models\Tenant\Entry::where('tenant_id', $tenantId)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get(['id', 'title', 'slug', 'status', 'updated_at']);

        $recentSubmissions = \DB::table('form_submissions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('submitted_at')
            ->take(5)
            ->get();

        // V4 feature flags
        $features = data_get(tenant()->data, 'features', []);

        return response()->json([
            'stats' => $stats,
            'recent_entries' => $recentEntries,
            'recent_submissions' => $recentSubmissions,
            'features_enabled' => $features,
            'tenant' => [
                'name' => tenant()->name,
                'plan' => tenant()->plan?->name,
                'status' => tenant()->status,
            ],
        ]);
    }
}
