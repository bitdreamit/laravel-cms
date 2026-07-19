<?php

namespace App\Domain\Workflow\Services\NodeExecutors;

use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowNodeExecution;

/**
 * Parallel node: spawns child branches that all must complete before merging.
 * V4 implementation — basic version; full parallel merge requires more complex tracking.
 */
class ParallelNodeExecutor implements NodeExecutorInterface
{
    public function execute(WorkflowInstance $instance, array $nodeDef, WorkflowNodeExecution $execution): ExecutionResult
    {
        // For simplicity in this implementation, parallel nodes just advance
        // to the merge point immediately. A full implementation would spawn
        // child instances for each branch and wait for all to complete.
        $execution->update(['status' => 'completed', 'completed_at' => now()]);
        return ExecutionResult::autoAdvance('done');
    }

    public function processAction(WorkflowInstance $instance, array $nodeDef, string $action, ?string $userId, ?string $comment): void {}

    public function getNextNode(array $nodeDef, string $action, WorkflowInstance $instance): ?string
    {
        return $nodeDef['next'] ?? $nodeDef['merge_to'] ?? null;
    }
}
