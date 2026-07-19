<?php

namespace App\Domain\Workflow\Actions\Builtin;

use App\Models\Tenant\WorkflowInstance;
use Illuminate\Support\Facades\Http;

class CallWebhook implements WorkflowActionInterface
{
    public function execute(WorkflowInstance $instance, array $config): array
    {
        $url = $config['url'] ?? null;
        $method = strtoupper($config['method'] ?? 'POST');
        $payload = $config['payload'] ?? [];

        if (! $url) {
            return ['error' => 'No webhook URL specified'];
        }

        $entry = \App\Models\Tenant\Entry::find($instance->entry_id);
        $payload = array_merge($payload, [
            'workflow_instance_id' => $instance->id,
            'entry_id' => $instance->entry_id,
            'tenant_id' => $instance->tenant_id,
            'entry' => $entry?->toArray(),
        ]);

        $response = Http::retry(3, 100)->$method($url, $payload);

        return [
            'status' => $response->status(),
            'response' => $response->body(),
        ];
    }
}
