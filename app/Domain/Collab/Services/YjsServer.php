<?php

namespace App\Domain\Collab\Services;

use App\Models\Tenant\CollabSession;
use App\Models\Tenant\CollabPresence;
use Illuminate\Support\Str;
use React\Promise\PromiseInterface;

/**
 * Yjs sync protocol server.
 * Handles WebSocket connections for real-time collaborative editing.
 *
 * Implements the Yjs sync protocol:
 * - Step1: Request state vector from peer
 * - Step2: Send update to peer
 * - Awareness: Broadcast cursor/selection/presence
 */
class YjsServer
{
    protected array $connections = [];
    protected array $sessionDocuments = [];

    public function onConnect(string $sessionId, string $userId): array
    {
        $session = CollabSession::where('tenant_id', tenant('id'))
            ->where('id', $sessionId)
            ->firstOrFail();

        // Initialize or restore Yjs document state
        if (! isset($this->sessionDocuments[$sessionId])) {
            $this->sessionDocuments[$sessionId] = $session->yjs_document_state ?? '';
        }

        // Create presence record
        $presence = CollabPresence::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'collab_session_id' => $session->id,
            'user_id' => $userId,
            'cursor_position' => null,
            'selection_color' => $this->getUserColor($userId),
            'last_heartbeat_at' => now(),
        ]);

        return [
            'session_id' => $session->id,
            'document_state' => base64_encode($this->sessionDocuments[$sessionId]),
            'presence_id' => $presence->id,
            'active_users' => $this->getActiveUsers($session),
        ];
    }

    public function onMessage(string $sessionId, string $userId, array $message): ?array
    {
        $type = $message['type'] ?? '';

        switch ($type) {
            case 'sync-step1':
                return $this->handleSyncStep1($sessionId, $message);

            case 'sync-step2':
                $this->handleSyncStep2($sessionId, $message);
                return null;

            case 'update':
                $this->applyUpdate($sessionId, $message['update'] ?? '');
                $this->broadcastUpdate($sessionId, $userId, $message['update']);
                return null;

            case 'awareness':
                $this->updatePresence($sessionId, $userId, $message);
                $this->broadcastAwareness($sessionId, $userId, $message);
                return null;

            case 'heartbeat':
                $this->updateHeartbeat($sessionId, $userId);
                return null;

            default:
                return null;
        }
    }

    public function onDisconnect(string $sessionId, string $userId): void
    {
        CollabPresence::where('collab_session_id', $sessionId)
            ->where('user_id', $userId)
            ->delete();

        $session = CollabSession::find($sessionId);
        if ($session) {
            // If no more active users, persist final document state
            $activeCount = CollabPresence::where('collab_session_id', $sessionId)->count();
            if ($activeCount === 0) {
                $this->persistDocument($session);
            }
        }
    }

    protected function handleSyncStep1(string $sessionId, array $message): array
    {
        $stateVector = $this->getStateVector($sessionId);
        return [
            'type' => 'sync-step2',
            'state_vector' => base64_encode($stateVector),
            'document_state' => base64_encode($this->sessionDocuments[$sessionId] ?? ''),
        ];
    }

    protected function handleSyncStep2(string $sessionId, array $message): void
    {
        $update = base64_decode($message['update'] ?? '');
        $this->applyUpdate($sessionId, $update);
    }

    protected function applyUpdate(string $sessionId, string $update): void
    {
        $current = $this->sessionDocuments[$sessionId] ?? '';
        // In a real Yjs implementation, this would merge the update into the document
        // using the Yjs library's merge function
        $this->sessionDocuments[$sessionId] = $current . $update;
    }

    protected function getStateVector(string $sessionId): string
    {
        // In a real implementation, this would compute the Yjs state vector
        return $this->sessionDocuments[$sessionId] ?? '';
    }

    protected function broadcastUpdate(string $sessionId, string $fromUserId, string $update): void
    {
        // Broadcast via Reverb WebSocket
        broadcast(new \App\Events\CollabSync(
            CollabSession::find($sessionId),
            $update
        ))->toOthers();
    }

    protected function broadcastAwareness(string $sessionId, string $userId, array $message): void
    {
        // Broadcast presence update via WebSocket
        $session = CollabSession::find($sessionId);
        if ($session) {
            broadcast(new \App\Events\CollabPresenceUpdate($session, $userId, $message))->toOthers();
        }
    }

    protected function updatePresence(string $sessionId, string $userId, array $message): void
    {
        CollabPresence::where('collab_session_id', $sessionId)
            ->where('user_id', $userId)
            ->update([
                'cursor_position' => $message['cursor'] ?? null,
                'last_heartbeat_at' => now(),
            ]);
    }

    protected function updateHeartbeat(string $sessionId, string $userId): void
    {
        CollabPresence::where('collab_session_id', $sessionId)
            ->where('user_id', $userId)
            ->update(['last_heartbeat_at' => now()]);
    }

    protected function getActiveUsers(CollabSession $session): array
    {
        $timeout = now()->subSeconds((int) config('collab.presence.timeout_seconds', 30));
        return $session->presence()
            ->where('last_heartbeat_at', '>', $timeout)
            ->with('user:id,name,email,avatar')
            ->get()
            ->map(fn($p) => [
                'user_id' => $p->user_id,
                'name' => $p->user?->name,
                'avatar' => $p->user?->avatar,
                'color' => $p->selection_color,
                'cursor' => $p->cursor_position,
            ])
            ->toArray();
    }

    protected function getUserColor(string $userId): string
    {
        $colors = config('collab.colors', ['#3b82f6']);
        $index = crc32($userId) % count($colors);
        return $colors[$index];
    }

    protected function persistDocument(CollabSession $session): void
    {
        $documentState = $this->sessionDocuments[$session->id] ?? '';

        $session->update([
            'yjs_document_state' => $documentState,
            'last_active_at' => now(),
        ]);

        // Convert Yjs document to field value and save to entry
        app(DocumentPersister::class)->persist($session);
    }
}
