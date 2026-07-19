<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Navigation extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'name', 'handle', 'max_depth',
    ];

    protected $casts = [
        'max_depth' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(NavigationItem::class, 'navigation_id');
    }
}
