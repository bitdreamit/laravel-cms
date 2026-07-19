<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class AssetContainer extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'name', 'handle', 'disk', 'title',
        'max_files', 'allowed_file_types', 'data',
    ];

    protected $casts = [
        'max_files' => 'integer',
        'allowed_file_types' => 'array',
        'data' => 'array',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'container_id');
    }
}
