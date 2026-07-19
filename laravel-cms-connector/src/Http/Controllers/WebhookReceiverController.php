<?php

namespace Platform\CmsConnector\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Platform\CmsConnector\Support\SignatureVerifier;

class WebhookReceiverController
{
    public function receive(Request $request, SignatureVerifier $verifier)
    {
        $signature = $request->header('X-Cms-Signature');
        $payload = $request->getContent();
        $eventType = $request->header('X-Cms-Event', 'unknown');
        $eventId = $request->header('X-Cms-Event-Id', \Illuminate\Support\Str::uuid()->toString());
        $secret = config('cms-connector.event_bus.signature_secret');
        $expected = $verifier->sign(json_decode($payload, true) ?? [], $secret);
        if (! hash_equals($expected, $signature)) return response()->json(['error' => 'Invalid signature.'], 401);

        $log = \Platform\CmsConnector\Models\CmsConnectorEventLog::firstOrCreate(['event_id' => $eventId], ['event_type' => $eventType, 'payload' => json_decode($payload, true), 'received_at' => now()]);
        if ($log->processed_at) return response()->json(['message' => 'Already processed.']);

        $subscriberClass = config("cms-connector.event_bus.subscriptions.{$eventType}");
        if ($subscriberClass && class_exists($subscriberClass)) { app($subscriberClass)->handle($eventType, json_decode($payload, true)); }
        $log->update(['processed_at' => now()]);
        return response()->json(['message' => 'Webhook received.']);
    }
}
