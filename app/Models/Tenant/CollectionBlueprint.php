<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CollectionBlueprint extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'tenant_id', 'collection_id', 'blueprint_id', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function blueprint()
    {
        return $this->belongsTo(Blueprint::class, 'blueprint_id');
    }
}
