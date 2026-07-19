<?php

namespace App\Domain\Content\Actions;

use App\Models\Tenant\Entry;

class ScheduleEntry
{
    public function execute(Entry $entry, $scheduledAt): Entry
    {
        $entry->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);

        return $entry->fresh();
    }
}
