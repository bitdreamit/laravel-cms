<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class RegisteredConnector extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'name', 'connector_type', 'base_url',
        'api_token_id', 'webhook_secret', 'webhook_url',
        'subscribed_events', 'syncable_collections',
        'last_seen_at', 'is_active',
    ];

    protected $hidden = ['webhook_secret'];

    protected $casts = [
        'subscribed_events' => 'array',
        'syncable_collections' => 'array',
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function isSubscribedTo(string $eventType): bool
    {
        $events = $this->subscribed_events ?? [];
        if (in_array('*', $events)) return true;
        // Support wildcard patterns like entry.*
        foreach ($events as $pattern) {
            if (fnmatch($pattern, $eventType)) return true;
        }
        return in_array($eventType, $events);
    }

    public function canSyncCollection(string $handle): bool
    {
        $collections = $this->syncable_collections ?? [];
        if (empty($collections)) return false;
        return in_array($handle, $collections) || in_array('*', $collections);
    }

    public function touchLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
