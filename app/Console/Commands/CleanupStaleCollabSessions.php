<?php

namespace App\Console\Commands;

use App\Models\Tenant\CollabSession;
use App\Models\Tenant\CollabPresence;
use Illuminate\Console\Command;

class CleanupStaleCollabSessions extends Command
{
    protected $signature = 'collab:cleanup-stale-sessions';
    protected $description = 'Remove stale collab sessions and presence records.';

    public function handle(): int
    {
        $cleanupMinutes = (int) config('collab.persistence.cleanup_stale_sessions_minutes', 30);
        $threshold = now()->subMinutes($cleanupMinutes);

        $sessions = 0;
        $presence = 0;

        \Stancl\Tenancy\Tenancy::runForMultiple(\App\Models\Central\Tenant::where('status', 'active')->get(), function () use (&$sessions, &$presence, $threshold) {
            // Delete stale presence
            $presence += CollabPresence::where('tenant_id', tenant('id'))
                ->where('last_heartbeat_at', '<', $threshold)
                ->delete();

            // Delete sessions with no active presence
            $sessions += CollabSession::where('tenant_id', tenant('id'))
                ->where('last_active_at', '<', $threshold)
                ->whereDoesntHave('activePresence')
                ->delete();
        });

        $this->info("Cleaned up {$sessions} stale sessions and {$presence} stale presence records.");
        return self::SUCCESS;
    }
}
