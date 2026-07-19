<?php

namespace App\Http\Controllers\Api;

use App\Domain\Connector\Services\ConnectorManager;
use App\Http\Controllers\Controller;
use App\Models\Central\RegisteredConnector;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(protected ConnectorManager $manager) {}

    /**
     * Receive an incoming webhook from a host app.
     */
    public function receive(Request $request)
    {
        $signature = $request->header('X-Cms-Signature');
        $payload = $request->getContent();
        $eventType = $request->header('X-Cms-Event', 'unknown');
        $eventId = $request->header('X-Cms-Event-Id', \Illuminate\Support\Str::uuid()->toString());

        // Find the connector for this tenant
        $connector = RegisteredConnector::where('tenant_id', tenant('id'))
            ->where('is_active', true)
            ->first();

        if (! $connector) {
            return response()->json(['error' => 'No connector registered.'], 404);
        }

        // Verify signature
        if (! $this->manager->verifyWebhookSignature($payload, $signature, $connector)) {
            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $data = json_decode($payload, true);

        // Dispatch to configured listeners via event bus
        // The ConnectorManager or EventBusBridge handles routing to subscribers
        event(new \App\Domain\Connector\Events\IncomingWebhookReceived(
            $connector,
            $eventType,
            $data,
            $eventId,
        ));

        return response()->json(['message' => 'Webhook received.']);
    }

    public function subscriptions(Request $request)
    {
        $connector = app('connector');
        return response()->json([
            'subscriptions' => $connector?->subscribed_events ?? [],
        ]);
    }

    public function subscribe(Request $request)
    {
        $connector = app('connector');
        $events = array_merge($connector->subscribed_events ?? [], $request->input('events', []));
        $connector->update(['subscribed_events' => array_unique($events)]);
        return response()->json($connector);
    }

    public function unsubscribe(Request $request, string $id)
    {
        $connector = app('connector');
        $events = array_diff($connector->subscribed_events ?? [], [$id]);
        $connector->update(['subscribed_events' => array_values($events)]);
        return response()->noContent();
    }
}
