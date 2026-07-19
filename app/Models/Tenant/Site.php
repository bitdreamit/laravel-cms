<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Site extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'name', 'handle', 'locale', 'is_default',
        'url', 'attributes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'attributes' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'site_id');
    }
}
