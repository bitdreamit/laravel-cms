<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class EntryRevision extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'entry_id', 'revision_number', 'data',
        'user_id', 'action', 'summary',
    ];

    protected $casts = [
        'data' => 'array',
        'revision_number' => 'integer',
    ];
}
