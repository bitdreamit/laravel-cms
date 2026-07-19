<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class PersonalizationRule extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'name', 'handle', 'segment_id',
        'target_type', 'target_config', 'priority',
        'is_active', 'start_at', 'end_at',
    ];

    protected $casts = [
        'target_config' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) return false;
        $now = now();
        if ($this->start_at && $this->start_at->isFuture()) return false;
        if ($this->end_at && $this->end_at->isPast()) return false;
        return true;
    }
}
