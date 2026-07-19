<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Segment extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'name', 'handle', 'description',
        'rules', 'is_dynamic', 'estimated_size',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_dynamic' => 'boolean',
        'estimated_size' => 'integer',
    ];

    public function visitors(): HasMany
    {
        return $this->hasMany(SegmentVisitor::class, 'segment_id');
    }

    public function personalizationRules(): HasMany
    {
        return $this->hasMany(PersonalizationRule::class, 'segment_id');
    }
}
