<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AuditStream;
use App\Models\Tenant\AuditStreamDelivery;
use Illuminate\Http\Request;

class AuditStreamController extends Controller
{
    public function index()
    {
        $streams = AuditStream::where('tenant_id', tenant('id'))->paginate(20);
        return response()->json($streams);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'destination_type' => 'required|in:splunk_hec,datadog_logs,elastic,logtail,http_webhook,syslog',
            'destination_config' => 'required|array',
            'event_filter' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $stream = AuditStream::create(array_merge($request->validated(), [
            'id' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));

        return response()->json($stream, 201);
    }

    public function show(string $id)
    {
        $stream = AuditStream::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($stream);
    }

    public function update(Request $request, string $id)
    {
        $stream = AuditStream::where('tenant_id', tenant('id'))->findOrFail($id);
        $stream->update($request->only(['name', 'destination_config', 'event_filter', 'is_active']));
        return response()->json($stream);
    }

    public function destroy(string $id)
    {
        $stream = AuditStream::where('tenant_id', tenant('id'))->findOrFail($id);
        $stream->delete();
        return response()->noContent();
    }

    public function testConnection(string $id)
    {
        $stream = AuditStream::where('tenant_id', tenant('id'))->findOrFail($id);

        $destinationClass = config("audit_streams.destinations.{$stream->destination_type}");
        if (! $destinationClass) {
            return response()->json(['error' => 'Unknown destination type'], 422);
        }

        $destination = app($destinationClass);
        $testPayload = [
            'tenant_id' => tenant('id'),
            'event_type' => 'test',
            'description' => 'Test connection from CMS',
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $result = $destination->send($stream->destination_config, $testPayload);
            return response()->json([
                'success' => $result['status'] >= 200 && $result['status'] < 300,
                'status' => $result['status'],
                'response' => $result['body'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function retryFailed(string $id)
    {
        $stream = AuditStream::where('tenant_id', tenant('id'))->findOrFail($id);

        $failedDeliveries = AuditStreamDelivery::where('audit_stream_id', $stream->id)
            ->where('status', 'failed')
            ->get();

        foreach ($failedDeliveries as $delivery) {
            $delivery->update(['status' => 'pending', 'attempts' => 0, 'next_attempt_at' => now()]);
            \App\Domain\Audit\Jobs\DeliverAuditEvent::dispatch($delivery->id);
        }

        return response()->json([
            'message' => "Retrying {$failedDeliveries->count()} failed deliveries.",
        ]);
    }
}
