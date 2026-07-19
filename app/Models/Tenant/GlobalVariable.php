<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class GlobalVariable extends Model
{
    use BelongsToTenant;

    protected $table = 'globals';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'name', 'handle', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
