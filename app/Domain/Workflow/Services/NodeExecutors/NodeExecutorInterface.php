<?php

namespace App\Domain\Workflow\Services\NodeExecutors;

use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowNodeExecution;

interface NodeExecutorInterface
{
    /**
     * Execute the node (called when the node becomes active).
     * Returns an ExecutionResult indicating what to do next.
     */
    public function execute(WorkflowInstance $instance, array $nodeDef, WorkflowNodeExecution $execution): ExecutionResult;

    /**
     * Process an action from a user (for approval nodes).
     */
    public function processAction(WorkflowInstance $instance, array $nodeDef, string $action, ?string $userId, ?string $comment): void;

    /**
     * Determine the next node based on the action taken.
     */
    public function getNextNode(array $nodeDef, string $action, WorkflowInstance $instance): ?string;
}
