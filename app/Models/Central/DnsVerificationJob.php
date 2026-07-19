<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class DnsVerificationJob extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'domain_id', 'verification_type', 'record_name', 'record_value',
        'attempts', 'max_attempts', 'next_attempt_at',
        'verified_at', 'failed_at', 'status',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'next_attempt_at' => 'datetime',
        'verified_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    public function canRetry(): bool
    {
        return $this->attempts < $this->max_attempts
            && $this->status === 'pending'
            && (! $this->next_attempt_at || $this->next_attempt_at->isPast());
    }

    public function markVerified(): void
    {
        $this->update([
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);
    }

    public function incrementAttempt(): void
    {
        $this->increment('attempts');
        $this->update([
            'next_attempt_at' => now()->addSeconds((int) config('ssl.dns_verification.attempt_interval_seconds', 300)),
        ]);

        if ($this->attempts >= $this->max_attempts) {
            $this->markFailed();
        }
    }
}
