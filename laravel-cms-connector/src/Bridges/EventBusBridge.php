<?php

namespace Platform\CmsConnector\Bridges;

use Platform\CmsConnector\Jobs\ForwardEventToCmsJob;
use Platform\CmsConnector\Support\SignatureVerifier;

class EventBusBridge
{
    public function __construct(protected SignatureVerifier $signer) {}

    public function forwardToCms(object $event, string $eventType): void
    {
        $payload = ['event' => $eventType, 'data' => $this->extractEventData($event), 'source' => 'host:' . config('app.name'), 'timestamp' => now()->toIso8601String(), 'event_id' => \Illuminate\Support\Str::uuid()->toString()];
        ForwardEventToCmsJob::dispatch($payload)->onQueue(config('cms-connector.event_bus.retry_queue'));
    }

    public function signOutgoing(array $payload): string { return $this->signer->sign($payload, config('cms-connector.event_bus.signature_secret')); }

    protected function extractEventData(object $event): array
    {
        if (method_exists($event, 'toCmsPayload')) return $event->toCmsPayload();
        return json_decode(json_encode($event), true) ?? [];
    }
}
