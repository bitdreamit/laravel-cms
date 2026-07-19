<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class RagDocument extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'entry_id', 'field_handle', 'chunk_index',
        'chunk_text', 'embedding', 'metadata',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'metadata' => 'array',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}
