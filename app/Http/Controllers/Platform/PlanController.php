<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\BillingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        $plans = BillingPlan::orderBy('sort_order')->paginate(20);
        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'slug' => 'required|string|max:100|unique:billing_plans,slug',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'currency' => 'string|size:3',
            'max_domains' => 'nullable|integer',
            'max_admin_users' => 'integer|min:1',
            'max_storage_mb' => 'integer|min:1',
            'max_themes' => 'integer|min:1',
            'theme_marketplace_access' => 'boolean',
            'white_label_allowed' => 'boolean',
            'custom_css_allowed' => 'boolean',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $plan = BillingPlan::create(array_merge($data, ['id' => Str::uuid()]));
        return response()->json($plan, 201);
    }

    public function show(string $id)
    {
        $plan = BillingPlan::findOrFail($id);
        return response()->json($plan);
    }

    public function update(Request $request, string $id)
    {
        $plan = BillingPlan::findOrFail($id);
        $plan->update($request->all());
        return response()->json($plan);
    }

    public function destroy(string $id)
    {
        $plan = BillingPlan::findOrFail($id);
        $plan->update(['is_active' => false]);
        return response()->noContent();
    }
}
