<?php

namespace App\Events;

use App\Models\Central\User;
use App\Models\Tenant\CollabSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollabForceLock implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CollabSession $session,
        public User $lockedBy,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("collab.{$this->session->tenant_id}.{$this->session->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'locked_by' => $this->lockedBy->name,
            'message' => "Session force-locked by {$this->lockedBy->name}. You can no longer edit this field.",
        ];
    }
}
