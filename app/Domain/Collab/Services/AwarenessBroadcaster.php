<?php

namespace App\Domain\Collab\Services;

use App\Models\Tenant\CollabPresence;
use App\Models\Tenant\CollabSession;
use Illuminate\Support\Facades\Cache;

class AwarenessBroadcaster
{
    public function updatePresence(string $sessionId, string $userId, array $presence): void
    {
        CollabPresence::where('collab_session_id', $sessionId)
            ->where('user_id', $userId)
            ->update([
                'cursor_position' => $presence['cursor'] ?? null,
                'last_heartbeat_at' => now(),
            ]);

        $this->broadcast($sessionId, [
            'type' => 'awareness-update',
            'user_id' => $userId,
            'cursor' => $presence['cursor'] ?? null,
        ]);
    }

    public function getActivePresence(string $sessionId): array
    {
        $timeout = now()->subSeconds((int) config('collab.presence.timeout_seconds', 30));
        return CollabPresence::where('collab_session_id', $sessionId)
            ->where('last_heartbeat_at', '>', $timeout)
            ->with('user')
            ->get()
            ->map(fn($p) => [
                'user_id' => $p->user_id,
                'name' => $p->user?->name,
                'color' => $p->selection_color,
                'cursor' => $p->cursor_position,
            ])
            ->toArray();
    }

    public function cleanupStale(string $sessionId): int
    {
        $timeout = now()->subSeconds((int) config('collab.presence.timeout_seconds', 30));
        return CollabPresence::where('collab_session_id', $sessionId)
            ->where('last_heartbeat_at', '<', $timeout)
            ->delete();
    }

    protected function broadcast(string $sessionId, array $data): void
    {
        $session = CollabSession::find($sessionId);
        if ($session) {
            broadcast(new \App\Events\CollabPresenceUpdate($session, $data['user_id'] ?? '', $data))->toOthers();
        }
    }
}
