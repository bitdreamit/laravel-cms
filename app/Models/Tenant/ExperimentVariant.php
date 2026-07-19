<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ExperimentVariant extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'experiment_id', 'name', 'handle',
        'is_control', 'weight', 'entry_id',
        'template_override', 'field_overrides',
    ];

    protected $casts = [
        'is_control' => 'boolean',
        'weight' => 'integer',
        'field_overrides' => 'array',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExperimentAssignment::class, 'variant_id');
    }

    public function conversionRate(): float
    {
        $total = $this->assignments()->count();
        if ($total === 0) return 0.0;
        $converted = $this->assignments()->whereNotNull('converted_at')->count();
        return ($converted / $total) * 100;
    }
}
