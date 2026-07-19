<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class UserColumnPreference extends Model
{
    use BelongsToTenant;

    protected $table = 'user_column_preferences';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'user_id', 'collection_handle',
        'columns', 'sort_order',
    ];

    protected $casts = [
        'columns' => 'array',
        'sort_order' => 'array',
    ];
}
