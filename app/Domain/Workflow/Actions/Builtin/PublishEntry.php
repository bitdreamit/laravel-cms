<?php

namespace App\Domain\Workflow\Actions\Builtin;

use App\Models\Tenant\WorkflowInstance;

class PublishEntry implements WorkflowActionInterface
{
    public function execute(WorkflowInstance $instance, array $config): array
    {
        $entry = \App\Models\Tenant\Entry::find($instance->entry_id);

        if (! $entry) {
            return ['error' => 'Entry not found'];
        }

        $entry->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        event(new \App\Domain\Content\Events\EntryPublished($entry));

        return ['entry_id' => $entry->id, 'published_at' => now()->toIso8601String()];
    }
}
