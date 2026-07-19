<?php

namespace App\Domain\Workflow\Actions\Builtin;

use App\Models\Tenant\WorkflowInstance;

class SetField implements WorkflowActionInterface
{
    public function execute(WorkflowInstance $instance, array $config): array
    {
        $entry = \App\Models\Tenant\Entry::find($instance->entry_id);

        if (! $entry) {
            return ['error' => 'Entry not found'];
        }

        $field = $config['field'] ?? null;
        $value = $config['value'] ?? null;

        if (! $field) {
            return ['error' => 'No field specified'];
        }

        $data = $entry->data ?? [];
        $data[$field] = $value;
        $entry->update(['data' => $data]);

        return ['field' => $field, 'value' => $value];
    }
}
