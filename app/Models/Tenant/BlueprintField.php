<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class BlueprintField extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'blueprint_id', 'tenant_id', 'handle', 'display_label',
        'instructions', 'fieldtype', 'config', 'validation_rules',
        'is_localizable', 'is_listable', 'is_sortable',
        'conditional_logic', 'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'conditional_logic' => 'array',
        'is_localizable' => 'boolean',
        'is_listable' => 'boolean',
        'is_sortable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class, 'blueprint_id');
    }
}
