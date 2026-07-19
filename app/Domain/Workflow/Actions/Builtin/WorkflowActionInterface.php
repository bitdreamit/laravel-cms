<?php

namespace App\Domain\Workflow\Actions\Builtin;

use App\Models\Tenant\WorkflowInstance;

interface WorkflowActionInterface
{
    /**
     * Execute the action.
     *
     * @return array Output data stored on the node execution.
     */
    public function execute(WorkflowInstance $instance, array $config): array;
}
