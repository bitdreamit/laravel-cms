<?php

namespace App\Domain\Workflow\Services\NodeExecutors;

use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowNodeExecution;

class WaitNodeExecutor implements NodeExecutorInterface
{
    public function execute(WorkflowInstance $instance, array $nodeDef, WorkflowNodeExecution $execution): ExecutionResult
    {
        // For wait nodes with a duration, schedule a job to resume
        if (isset($nodeDef['duration_minutes'])) {
            \App\Domain\Workflow\Jobs\ResumeWorkflowAfterWait::dispatch($instance->id)
                ->delay(now()->addMinutes((int) $nodeDef['duration_minutes']));

            $execution->update(['status' => 'pending']);
            return ExecutionResult::pending();
        }

        // For wait nodes with a specific datetime, schedule at that time
        if (isset($nodeDef['until'])) {
            $until = \Carbon\Carbon::parse($nodeDef['until']);
            \App\Domain\Workflow\Jobs\ResumeWorkflowAfterWait::dispatch($instance->id)->delay($until);

            $execution->update(['status' => 'pending']);
            return ExecutionResult::pending();
        }

        // No wait condition — skip through
        $execution->update(['status' => 'completed', 'completed_at' => now()]);
        return ExecutionResult::autoAdvance('done');
    }

    public function processAction(WorkflowInstance $instance, array $nodeDef, string $action, ?string $userId, ?string $comment): void {}

    public function getNextNode(array $nodeDef, string $action, WorkflowInstance $instance): ?string
    {
        return $nodeDef['next'] ?? null;
    }
}
