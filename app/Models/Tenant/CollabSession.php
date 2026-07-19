<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CollabSession extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'entry_id', 'field_handle',
        'yjs_document_state', 'last_active_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    public function presence(): HasMany
    {
        return $this->hasMany(CollabPresence::class, 'collab_session_id');
    }

    public function activePresence(): HasMany
    {
        $timeout = now()->subSeconds((int) config('collab.presence.timeout_seconds', 30));
        return $this->hasMany(CollabPresence::class, 'collab_session_id')
            ->where('last_heartbeat_at', '>', $timeout);
    }
}
