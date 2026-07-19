<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class NavigationItem extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'navigation_id', 'parent_id', 'title',
        'url', 'entry_id', 'target', 'sort_order', 'data',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'data' => 'array',
    ];
}
