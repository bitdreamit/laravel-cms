<?php

namespace App\Domain\Content\Listeners;

use App\Jobs\DispatchWebhook;
use App\Models\Tenant\Webhook;

class DispatchWebhooks
{
    public function handle($event): void
    {
        $entry = $event->entry ?? null;
        if (! $entry) return;

        $eventType = (new \ReflectionClass($event))->getShortName();
        $webhookEvent = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $eventType));

        $webhooks = Webhook::where('tenant_id', $entry->tenant_id)
            ->where('is_active', true)
            ->whereJsonContains('subscribed_events', $webhookEvent)
            ->get();

        foreach ($webhooks as $webhook) {
            // Capture tenant_id at dispatch time so the queued job can
            // re-initialize tenancy on the worker.
            DispatchWebhook::dispatch(
                $webhook->id,
                $webhookEvent,
                [
                    'entry_id' => $entry->id,
                    'entry_slug' => $entry->slug,
                    'entry_title' => $entry->title,
                    'collection_id' => $entry->collection_id,
                    'tenant_id' => $entry->tenant_id,
                ],
                $entry->tenant_id,  // ← explicit tenant_id for queue worker
            );
        }
    }
}
