<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CollabPresence extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'collab_session_id', 'user_id',
        'cursor_position', 'selection_color', 'last_heartbeat_at',
    ];

    protected $casts = [
        'cursor_position' => 'array',
        'last_heartbeat_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CollabSession::class, 'collab_session_id');
    }

    public function isStale(): bool
    {
        $timeout = (int) config('collab.presence.timeout_seconds', 30);
        return $this->last_heartbeat_at->diffInSeconds(now()) > $timeout;
    }
}
