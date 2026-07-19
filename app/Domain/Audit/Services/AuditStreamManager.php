<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Services\Destinations\DestinationInterface;
use App\Models\Tenant\AuditStream;
use App\Models\Tenant\AuditStreamDelivery;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class AuditStreamManager
{
    public function __construct(protected ChainHasher $chainHasher) {}

    /**
     * Called when an activity is logged. Finds matching streams and dispatches delivery jobs.
     */
    public function onActivityLogged(Activity $activity): void
    {
        $tenantId = data_get($activity->properties, 'tenant_id');
        if (! $tenantId) return;

        $eventType = $activity->log_name ?? 'activity';
        $severity = data_get($activity->properties, 'severity', 'info');

        $streams = AuditStream::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($streams as $stream) {
            if (! $stream->matchesEvent($eventType, $severity)) {
                continue;
            }

            $payload = [
                'id' => $activity->id,
                'tenant_id' => $tenantId,
                'event_type' => $eventType,
                'description' => $activity->description,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'causer_type' => $activity->causer_type,
                'causer_id' => $activity->causer_id,
                'properties' => $activity->properties,
                'severity' => $severity,
                'timestamp' => $activity->created_at?->toIso8601String(),
                'previous_hash' => $activity->getAttribute('previous_hash'),
                'current_hash' => $activity->getAttribute('current_hash'),
            ];

            $delivery = AuditStreamDelivery::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'audit_stream_id' => $stream->id,
                'activity_log_id' => $activity->id,
                'payload' => $payload,
                'attempts' => 0,
                'status' => 'pending',
            ]);

            \App\Domain\Audit\Jobs\DeliverAuditEvent::dispatch($delivery->id)
                ->onQueue(config('audit_streams.delivery.queue', 'audit-streaming'));
        }
    }
}
