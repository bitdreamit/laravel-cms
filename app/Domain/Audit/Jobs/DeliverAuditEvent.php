<?php

namespace App\Domain\Audit\Jobs;

use App\Domain\Audit\Services\Destinations\DestinationInterface;
use App\Models\Tenant\AuditStreamDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliverAuditEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public string $deliveryId) {}

    public function handle(): void
    {
        $delivery = AuditStreamDelivery::find($this->deliveryId);

        if (! $delivery || $delivery->status === 'delivered') {
            return;
        }

        $stream = $delivery->auditStream;

        if (! $stream || ! $stream->is_active) {
            $delivery->markFailed(0, 'Stream inactive or not found');
            return;
        }

        $destinationClass = config("audit_streams.destinations.{$stream->destination_type}");

        if (! $destinationClass || ! class_exists($destinationClass)) {
            $delivery->markFailed(0, "Unknown destination type: {$stream->destination_type}");
            return;
        }

        /** @var DestinationInterface $destination */
        $destination = app($destinationClass);
        $config = $stream->destination_config;

        try {
            $result = $destination->send($config, $delivery->payload);

            if ($result['status'] >= 200 && $result['status'] < 300) {
                $delivery->markDelivered($result['status'], $result['body']);
                $stream->markDeliverySuccess();
            } elseif ($result['status'] >= 400 && $result['status'] < 500) {
                // Permanent error — don't retry
                $delivery->markFailed($result['status'], $result['body']);
                $stream->markDeliveryFailed("HTTP {$result['status']}");
            } else {
                // 5xx — retry
                $delivery->scheduleRetry();
                $stream->markDeliveryFailed("HTTP {$result['status']} — will retry");
            }
        } catch (\Throwable $e) {
            Log::error('Audit stream delivery failed', [
                'delivery_id' => $this->deliveryId,
                'error' => $e->getMessage(),
            ]);
            $delivery->scheduleRetry();
        }
    }

    public function backoff(): array
    {
        return config('audit_streams.delivery.backoff_seconds', [10, 30, 60, 300, 900]);
    }
}
