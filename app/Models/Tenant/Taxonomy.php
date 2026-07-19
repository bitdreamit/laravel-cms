<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Taxonomy extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'name', 'handle', 'description',
        'is_hierarchical', 'max_levels',
    ];

    protected $casts = [
        'is_hierarchical' => 'boolean',
        'max_levels' => 'integer',
    ];

    public function terms()
    {
        return $this->hasMany(Term::class, 'taxonomy_id');
    }
}
