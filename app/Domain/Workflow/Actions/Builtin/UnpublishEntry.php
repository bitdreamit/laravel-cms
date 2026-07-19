<?php

namespace App\Domain\Workflow\Actions\Builtin;

use App\Models\Tenant\WorkflowInstance;

class UnpublishEntry implements WorkflowActionInterface
{
    public function execute(WorkflowInstance $instance, array $config): array
    {
        $entry = \App\Models\Tenant\Entry::find($instance->entry_id);

        if (! $entry) {
            return ['error' => 'Entry not found'];
        }

        $entry->update(['status' => 'draft']);
        return ['entry_id' => $entry->id];
    }
}
