<?php

namespace Platform\CmsConnector\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ForwardEventToCmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        $signature = app(\Platform\CmsConnector\Support\SignatureVerifier::class)->sign($this->payload, config('cms-connector.event_bus.signature_secret'));
        Http::withHeaders(['X-Cms-Signature' => $signature, 'Content-Type' => 'application/json'])->withToken(config('cms-connector.api_token'))->post(config('cms-connector.cms_base_url') . '/api/v1/webhooks/incoming', $this->payload);
    }
}
