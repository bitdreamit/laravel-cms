<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ThemeCustomization extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'theme_id', 'settings',
        'custom_css', 'custom_js', 'custom_code_position',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
}
