<?php

namespace App\Jobs;

use App\Models\Central\Tenant;
use App\Models\Tenant\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Stancl\Tenancy\Tenancy;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $webhookId,
        public string $eventType,
        public array $data,
        /**
         * Tenant ID is explicitly captured at dispatch time so the queued
         * job can re-initialize tenancy on the worker, regardless of
         * whether stancl/tenancy's QueueTenancyBootstrapper is active.
         */
        public ?string $tenantId = null,
    ) {}

    public function handle(Tenancy $tenancy): void
    {
        // Bootstrap tenant context if not already initialized.
        // This is critical — without it, Webhook::find() would be filtered
        // by BelongsToTenant's global scope against the wrong (or no) tenant.
        if (! $tenancy->initialized) {
            $tenant = Tenant::find($this->tenantId);
            if (! $tenant) {
                throw new \RuntimeException("Cannot dispatch webhook: tenant {$this->tenantId} not found.");
            }
            $tenancy->initialize($tenant);
        }

        // Use withoutGlobalScope to be extra safe — the tenant_id is already
        // captured, so we want to find the webhook by ID regardless of scope.
        $webhook = Webhook::withoutGlobalScope('tenant')->find($this->webhookId);

        if (! $webhook) {
            return; // Webhook was deleted between dispatch and execution
        }

        // Verify the webhook belongs to the tenant we're acting as.
        if ($webhook->tenant_id !== $this->tenantId) {
            throw new \RuntimeException("Webhook {$this->webhookId} does not belong to tenant {$this->tenantId}.");
        }

        if (! $webhook->is_active) {
            return;
        }

        $payload = [
            'event' => $this->eventType,
            'data' => $this->data,
            'tenant_id' => $webhook->tenant_id,
            'timestamp' => now()->toIso8601String(),
        ];

        $signature = hash_hmac('sha256', json_encode($payload), $webhook->secret);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Cms-Signature' => $signature,
                'X-Cms-Event' => $this->eventType,
            ])->timeout(30)->post($webhook->url, $payload);

            $webhook->update(['last_triggered_at' => now()]);

            if (! $response->successful()) {
                $webhook->increment('failure_count');
                throw new \RuntimeException("Webhook returned {$response->status()}");
            }

            $webhook->update(['failure_count' => 0]);
        } catch (\Throwable $e) {
            $webhook->increment('failure_count');
            throw $e;
        }
    }
}
