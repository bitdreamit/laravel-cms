<?php

namespace App\Domain\Workflow\Services\NodeExecutors;

use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowNodeExecution;

class ActionNodeExecutor implements NodeExecutorInterface
{
    public function execute(WorkflowInstance $instance, array $nodeDef, WorkflowNodeExecution $execution): ExecutionResult
    {
        $actionClass = config("workflow.actions.{$nodeDef['action']}");

        if (! $actionClass || ! class_exists($actionClass)) {
            $execution->update([
                'status' => 'error',
                'completed_at' => now(),
                'output' => ['error' => "Action class not found: {$nodeDef['action']}"],
            ]);
            throw new \RuntimeException("Action class not found: {$nodeDef['action']}");
        }

        $action = app($actionClass);
        $output = $action->execute($instance, $nodeDef['config'] ?? []);

        $execution->update([
            'status' => 'completed',
            'completed_at' => now(),
            'output' => $output,
        ]);

        return ExecutionResult::autoAdvance('done');
    }

    public function processAction(WorkflowInstance $instance, array $nodeDef, string $action, ?string $userId, ?string $comment): void
    {
        // Actions don't require user input
    }

    public function getNextNode(array $nodeDef, string $action, WorkflowInstance $instance): ?string
    {
        return $nodeDef['next'] ?? null;
    }
}
