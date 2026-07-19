<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ExperimentAssignment extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'experiment_id', 'variant_id', 'visitor_id',
        'user_id', 'assigned_at', 'converted_at', 'conversion_value',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'converted_at' => 'datetime',
        'conversion_value' => 'decimal:2',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class, 'variant_id');
    }

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }
}
