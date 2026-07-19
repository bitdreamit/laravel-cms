<?php

namespace App\Http\Controllers\Api;

use App\Domain\Connector\Services\AuthBridgeService;
use App\Domain\Connector\Services\ConnectorManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConnectorController extends Controller
{
    public function __construct(
        protected ConnectorManager $connectorManager,
        protected AuthBridgeService $authBridge,
    ) {}

    /**
     * Register a new connector.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'base_url' => 'required|url',
            'subscribed_events' => 'array',
            'syncable_collections' => 'array',
            'webhook_url' => 'nullable|url',
        ]);

        $result = $this->connectorManager->register(
            tenant('id'),
            $request->input('name'),
            $request->input('base_url'),
            $request->input('subscribed_events', []),
            $request->input('syncable_collections', []),
        );

        if ($request->input('webhook_url')) {
            $result['connector']->update(['webhook_url' => $request->input('webhook_url')]);
        }

        return response()->json([
            'connector_id' => $result['connector']->id,
            'api_token' => $result['api_token'],
            'webhook_secret' => $result['webhook_secret'],
        ], 201);
    }

    /**
     * SSO Bridge — verify JWT, log in user.
     */
    public function ssoBridge(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $token = $request->input('token');

        // Find the connector for this tenant
        $connector = \App\Models\Central\RegisteredConnector::where('tenant_id', tenant('id'))->first();

        if (! $connector) {
            return response()->json(['error' => 'No connector registered for this tenant.'], 404);
        }

        $payload = $this->authBridge->verifySsoToken($token, $connector->webhook_secret);

        if (! $payload) {
            return response()->json(['error' => 'Invalid or expired token.'], 401);
        }

        $user = $this->authBridge->findOrCreateUser($payload);

        auth()->login($user);

        return response()->json([
            'message' => 'SSO bridge successful.',
            'user_id' => $user->id,
            'redirect' => '/admin',
        ]);
    }

    /**
     * Connector health check.
     */
    public function status()
    {
        $connector = app('connector');

        if (! $connector) {
            return response()->json(['error' => 'No connector authenticated.'], 401);
        }

        $connector->touchLastSeen();

        return response()->json([
            'status' => 'ok',
            'connector_id' => $connector->id,
            'tenant_id' => $connector->tenant_id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
