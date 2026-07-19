<?php

namespace App\Domain\Workflow\Services\NodeExecutors;

use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowNodeExecution;

class EndNodeExecutor implements NodeExecutorInterface
{
    public function execute(WorkflowInstance $instance, array $nodeDef, WorkflowNodeExecution $execution): ExecutionResult
    {
        $execution->update(['status' => 'completed', 'completed_at' => now()]);
        return ExecutionResult::complete($nodeDef['outcome'] ?? 'completed');
    }

    public function processAction(WorkflowInstance $instance, array $nodeDef, string $action, ?string $userId, ?string $comment): void {}

    public function getNextNode(array $nodeDef, string $action, WorkflowInstance $instance): ?string
    {
        return null;
    }
}
