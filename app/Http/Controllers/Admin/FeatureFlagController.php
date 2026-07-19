<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    protected array $v4Features = [
        'multi_domain' => 'Multi-Domain & Subdomain Layer',
        'connector' => 'External Laravel Connector',
        'workflow_engine' => 'Workflow Engine',
        'ab_testing' => 'A/B Testing',
        'collab_editing' => 'Real-time Collaborative Editing',
        'ai_rag' => 'AI RAG (Retrieval-Augmented Generation)',
        'personalization' => 'Personalization & Segments',
        'saml_sso' => 'SAML 2.0 SSO',
        'scim_provisioning' => 'SCIM 2.0 User Provisioning',
        'audit_streaming' => 'Audit Log Streaming to SIEM',
        'form_analytics' => 'Form Analytics & Lead Scoring',
    ];

    public function index()
    {
        $tenant = tenant();
        $enabledFeatures = data_get($tenant->data, 'features', []);

        $features = collect($this->v4Features)->map(function ($label, $key) use ($enabledFeatures) {
            return [
                'key' => $key,
                'label' => $label,
                'enabled' => in_array($key, $enabledFeatures),
            ];
        })->values();

        return response()->json($features);
    }

    public function update(Request $request)
    {
        $request->validate(['features' => 'array']);
        $features = $request->input('features', []);

        // Validate feature keys
        $validKeys = array_keys($this->v4Features);
        $features = array_values(array_intersect($features, $validKeys));

        $tenant = tenant();
        $data = $tenant->data ?? [];
        $data['features'] = $features;
        $tenant->data = $data;
        $tenant->save();

        // Clear feature cache
        \Illuminate\Support\Facades\Cache::forget("tenant:{$tenant->id}:features");

        return response()->json([
            'message' => 'Feature flags updated.',
            'features' => $features,
        ]);
    }
}
