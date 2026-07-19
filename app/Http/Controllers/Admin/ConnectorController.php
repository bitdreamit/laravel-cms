<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Connector\Services\ConnectorManager;
use App\Http\Controllers\Controller;
use App\Models\Central\RegisteredConnector;
use Illuminate\Http\Request;

class ConnectorController extends Controller
{
    public function __construct(protected ConnectorManager $manager) {}

    public function index()
    {
        $connectors = RegisteredConnector::where('tenant_id', tenant('id'))
            ->orderByDesc('created_at')
            ->get();

        return response()->json($connectors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'base_url' => 'required|url',
            'subscribed_events' => 'array',
            'syncable_collections' => 'array',
            'webhook_url' => 'nullable|url',
        ]);

        $result = $this->manager->register(
            tenant('id'),
            $request->input('name'),
            $request->input('base_url'),
            $request->input('subscribed_events', []),
            $request->input('syncable_collections', []),
        );

        // Update webhook_url if provided
        if ($request->input('webhook_url')) {
            $result['connector']->update(['webhook_url' => $request->input('webhook_url')]);
        }

        return response()->json([
            'connector' => $result['connector'],
            'api_token' => $result['api_token'],
            'webhook_secret' => $result['webhook_secret'],
            'message' => 'Connector registered. Save the API token — it will not be shown again.',
        ], 201);
    }

    public function show(string $id)
    {
        $connector = RegisteredConnector::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($connector);
    }

    public function update(Request $request, string $id)
    {
        $connector = RegisteredConnector::where('tenant_id', tenant('id'))->findOrFail($id);
        $connector->update($request->only(['name', 'base_url', 'subscribed_events', 'syncable_collections', 'webhook_url', 'is_active']));

        return response()->json($connector);
    }

    public function revoke(string $id)
    {
        $connector = RegisteredConnector::where('tenant_id', tenant('id'))->findOrFail($id);
        $this->manager->revoke($connector);

        return response()->json(['message' => 'Connector revoked.']);
    }
}
