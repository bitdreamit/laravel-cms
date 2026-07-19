<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class AuditStreamDelivery extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'audit_stream_id', 'activity_log_id',
        'payload', 'response_status', 'response_body',
        'attempts', 'status', 'next_attempt_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_status' => 'integer',
        'attempts' => 'integer',
        'next_attempt_at' => 'datetime',
    ];

    public function auditStream(): BelongsTo
    {
        return $this->belongsTo(AuditStream::class, 'audit_stream_id');
    }

    public function canRetry(): bool
    {
        return $this->status === 'pending'
            && $this->attempts < (int) config('audit_streams.delivery.retry_attempts', 5)
            && (! $this->next_attempt_at || $this->next_attempt_at->isPast());
    }

    public function markDelivered(int $responseStatus, ?string $responseBody = null): void
    {
        $this->update([
            'status' => 'delivered',
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'next_attempt_at' => null,
        ]);
    }

    public function markFailed(int $responseStatus, ?string $responseBody = null): void
    {
        $this->update([
            'status' => 'failed',
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'next_attempt_at' => null,
        ]);
    }

    public function scheduleRetry(): void
    {
        $backoff = config('audit_streams.delivery.backoff_seconds', [10, 30, 60, 300, 900]);
        $delay = $backoff[min($this->attempts, count($backoff) - 1)] ?? 900;

        $this->increment('attempts');
        $this->update([
            'status' => 'pending',
            'next_attempt_at' => now()->addSeconds($delay),
        ]);
    }
}
