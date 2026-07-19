<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class AuditStream extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'name', 'destination_type', 'destination_config',
        'event_filter', 'is_active', 'last_delivery_at',
        'last_delivery_status', 'last_delivery_error',
    ];

    protected $hidden = ['destination_config'];

    protected $casts = [
        'destination_config' => 'array',
        'event_filter' => 'array',
        'is_active' => 'boolean',
        'last_delivery_at' => 'datetime',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(AuditStreamDelivery::class, 'audit_stream_id');
    }

    public function matchesEvent(string $eventType, string $severity = 'info'): bool
    {
        $filter = $this->event_filter;
        if (! $filter) return true;

        if (isset($filter['types'])) {
            $matched = false;
            foreach ($filter['types'] as $pattern) {
                if (fnmatch($pattern, $eventType)) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) return false;
        }

        if (isset($filter['severity'])) {
            $severityMap = ['info' => 0, 'notice' => 1, 'warning' => 2, 'error' => 3, 'critical' => 4, 'alert' => 5, 'emergency' => 6];
            $minSeverity = $severityMap[$filter['severity']] ?? 0;
            $actualSeverity = $severityMap[$severity] ?? 0;
            if ($actualSeverity < $minSeverity) return false;
        }

        return true;
    }

    public function markDeliverySuccess(): void
    {
        $this->update([
            'last_delivery_at' => now(),
            'last_delivery_status' => 'success',
            'last_delivery_error' => null,
        ]);
    }

    public function markDeliveryFailed(string $error): void
    {
        $this->update([
            'last_delivery_at' => now(),
            'last_delivery_status' => 'failed',
            'last_delivery_error' => $error,
        ]);
    }
}
