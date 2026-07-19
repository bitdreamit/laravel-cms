<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Experiment extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'name', 'handle', 'description',
        'experiment_type', 'entry_id', 'collection_handle', 'status',
        'traffic_allocation', 'winning_variant_id',
        'start_at', 'end_at', 'goal_type', 'goal_config',
        'min_sample_size', 'confidence_threshold', 'created_by',
    ];

    protected $casts = [
        'traffic_allocation' => 'integer',
        'goal_config' => 'array',
        'min_sample_size' => 'integer',
        'confidence_threshold' => 'decimal:3',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ExperimentVariant::class, 'experiment_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExperimentAssignment::class, 'experiment_id');
    }

    public function winningVariant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class, 'winning_variant_id');
    }

    public function controlVariant(): ?ExperimentVariant
    {
        return $this->variants()->where('is_control', true)->first();
    }

    public function isRunning(): bool
    {
        return $this->status === 'running'
            && ($this->start_at === null || $this->start_at->isPast())
            && ($this->end_at === null || $this->end_at->isFuture());
    }
}
