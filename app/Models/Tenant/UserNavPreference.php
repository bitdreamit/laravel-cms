<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class UserNavPreference extends Model
{
    use BelongsToTenant;

    protected $table = 'user_nav_preferences';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'user_id', 'nav_items', 'pinned_items', 'hidden_items',
    ];

    protected $casts = [
        'nav_items' => 'array',
        'pinned_items' => 'array',
        'hidden_items' => 'array',
    ];
}
