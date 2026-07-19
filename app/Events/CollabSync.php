<?php

namespace App\Events;

use App\Models\Tenant\CollabSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollabSync implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CollabSession $session,
        public string $update,
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
            'update' => $this->update,
            'user_id' => auth()->id(),
        ];
    }
}
