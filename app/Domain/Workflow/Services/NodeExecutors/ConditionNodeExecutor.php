<?php

namespace App\Domain\Workflow\Services\NodeExecutors;

use App\Domain\Workflow\Services\ConditionEvaluator;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowNodeExecution;

class ConditionNodeExecutor implements NodeExecutorInterface
{
    public function __construct(protected ConditionEvaluator $evaluator) {}

    public function execute(WorkflowInstance $instance, array $nodeDef, WorkflowNodeExecution $execution): ExecutionResult
    {
        $result = $this->evaluator->evaluate($nodeDef['condition'], $instance);

        $execution->update([
            'status' => 'completed',
            'completed_at' => now(),
            'output' => ['branch' => $result ? 'true' : 'false'],
        ]);

        return ExecutionResult::autoAdvance($result ? 'true' : 'false');
    }

    public function processAction(WorkflowInstance $instance, array $nodeDef, string $action, ?string $userId, ?string $comment): void
    {
        // Conditions don't require user action
    }

    public function getNextNode(array $nodeDef, string $action, WorkflowInstance $instance): ?string
    {
        return $action === 'true'
            ? ($nodeDef['on_true'] ?? null)
            : ($nodeDef['on_false'] ?? null);
    }
}
