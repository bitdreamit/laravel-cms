<?php

namespace App\Http\Controllers\Collab;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CollabPresence;
use App\Models\Tenant\CollabSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollabController extends Controller
{
    public function connect(Request $request, string $sessionId)
    {
        $session = CollabSession::where('tenant_id', tenant('id'))
            ->where('id', $sessionId)
            ->firstOrFail();

        // In a real implementation, this would upgrade to a WebSocket connection
        // via Laravel Reverb. For HTTP fallback, return the session state.
        return response()->json([
            'session_id' => $session->id,
            'yjs_state' => base64_encode($session->yjs_document_state ?? ''),
            'active_users' => $session->activePresence()->count(),
        ]);
    }

    public function sync(Request $request, string $sessionId)
    {
        $session = CollabSession::where('tenant_id', tenant('id'))
            ->where('id', $sessionId)
            ->firstOrFail();

        $update = $request->input('update');
        if ($update) {
            // Apply Yjs update — in real impl this is handled via WebSocket
            $currentState = $session->yjs_document_state ?? '';
            $session->update([
                'yjs_document_state' => base64_decode($update) . $currentState,
                'last_active_at' => now(),
            ]);

            // Broadcast to other session members via Reverb
            broadcast(new \App\Events\CollabSync($session, $update))->toOthers();
        }

        return response()->json(['synced' => true]);
    }

    public function presence(Request $request, string $sessionId)
    {
        $session = CollabSession::where('tenant_id', tenant('id'))
            ->where('id', $sessionId)
            ->firstOrFail();

        $presence = CollabPresence::updateOrCreate(
            [
                'tenant_id' => tenant('id'),
                'collab_session_id' => $session->id,
                'user_id' => auth()->id(),
            ],
            [
                'id' => Str::uuid(),
                'cursor_position' => $request->input('cursor'),
                'selection_color' => $request->input('color', $this->getUserColor()),
                'last_heartbeat_at' => now(),
            ]
        );

        return response()->json([
            'active_users' => $session->activePresence()->with('user')->get(),
        ]);
    }

    public function forceLock(Request $request, string $sessionId)
    {
        if (! auth()->user()?->hasRole('owner')) {
            abort(403, 'Only Owner can force-lock a collab session.');
        }

        $session = CollabSession::where('tenant_id', tenant('id'))
            ->where('id', $sessionId)
            ->firstOrFail();

        // Disconnect all other users
        CollabPresence::where('collab_session_id', $session->id)
            ->where('user_id', '!=', auth()->id())
            ->delete();

        broadcast(new \App\Events\CollabForceLock($session, auth()->user()))->toOthers();

        return response()->json(['locked' => true]);
    }

    public function disconnect(Request $request, string $sessionId)
    {
        CollabPresence::where('collab_session_id', $sessionId)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->noContent();
    }

    protected function getUserColor(): string
    {
        $colors = config('collab.colors', ['#3b82f6']);
        $index = crc32(auth()->user()?->email ?? 'anonymous') % count($colors);
        return $colors[$index];
    }
}
