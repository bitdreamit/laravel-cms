<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Collection extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'name', 'handle', 'description',
        'route_pattern', 'template', 'structure_mode', 'max_depth',
        'default_status', 'seo_settings', 'sort_order', 'is_searchable',
    ];

    protected $casts = [
        'seo_settings' => 'array',
        'sort_order' => 'integer',
        'max_depth' => 'integer',
        'is_searchable' => 'boolean',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'collection_id');
    }

    public function blueprints(): BelongsToMany
    {
        return $this->belongsToMany(Blueprint::class, 'collection_blueprints', 'collection_id', 'blueprint_id')
            ->withPivot(['tenant_id', 'is_primary']);
    }
}
