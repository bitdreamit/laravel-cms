<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class SavedFilter extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'user_id', 'name', 'collection_handle',
        'filters', 'is_shared',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_shared' => 'boolean',
    ];
}
