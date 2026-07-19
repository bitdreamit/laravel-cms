<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Blueprint extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'handle', 'title', 'type', 'icon',
        'tabs', 'created_by',
    ];

    protected $casts = [
        'tabs' => 'array',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(BlueprintField::class, 'blueprint_id');
    }
}
