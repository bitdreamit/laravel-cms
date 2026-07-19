<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ImportJob extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'source_type', 'source_file', 'collection_handle',
        'status', 'total_items', 'processed_items', 'failed_items',
        'error_log', 'started_at', 'completed_at', 'user_id', 'config',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'failed_items' => 'integer',
        'error_log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'config' => 'array',
    ];

    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    public function progressPercent(): float
    {
        if ($this->total_items === 0) return 0;
        return ($this->processed_items / $this->total_items) * 100;
    }
}
