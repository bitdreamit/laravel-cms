<?php

namespace App\Console\Commands;

use App\Domain\Audit\Jobs\DeliverAuditEvent;
use App\Models\Tenant\AuditStreamDelivery;
use Illuminate\Console\Command;

class RetryFailedAuditDeliveries extends Command
{
    protected $signature = 'audit:retry-failed-deliveries';
    protected $description = 'Retry pending/failed audit stream deliveries.';

    public function handle(): int
    {
        $count = 0;

        \Stancl\Tenancy\Tenancy::runForMultiple(\App\Models\Central\Tenant::where('status', 'active')->get(), function () use (&$count) {
            $deliveries = AuditStreamDelivery::where('tenant_id', tenant('id'))
                ->where('status', 'pending')
                ->where(function ($q) {
                    $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                })
                ->get();

            foreach ($deliveries as $delivery) {
                if ($delivery->canRetry()) {
                    DeliverAuditEvent::dispatch($delivery->id);
                    $count++;
                }
            }
        });

        $this->info("Dispatched {$count} audit delivery retries.");
        return self::SUCCESS;
    }
}
