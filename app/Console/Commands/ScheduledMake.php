<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScheduledMake extends Command
{
    protected $signature = 'scheduled:make';
    protected $description = 'Publish scheduled entries whose time has arrived.';

    public function handle(): int
    {
        $count = 0;

        \Stancl\Tenancy\Tenancy::runForMultiple(\App\Models\Central\Tenant::where('status', 'active')->get(), function () use (&$count) {
            $entries = \App\Models\Tenant\Entry::where('tenant_id', tenant('id'))
                ->where('status', 'scheduled')
                ->where('scheduled_at', '<=', now())
                ->get();

            foreach ($entries as $entry) {
                $entry->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                event(new \App\Domain\Content\Events\EntryPublished($entry));
                $count++;
            }
        });

        $this->info("Published {$count} scheduled entries.");
        return self::SUCCESS;
    }
}
