<?php

namespace App\Domain\Workflow\Services\NodeExecutors;

use App\Domain\Workflow\Events\ApprovalRequired;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowNodeExecution;

class ApprovalNodeExecutor implements NodeExecutorInterface
{
    public function execute(WorkflowInstance $instance, array $nodeDef, WorkflowNodeExecution $execution): ExecutionResult
    {
        // Mark as pending approval
        $execution->update(['status' => 'pending']);

        // Fire event to notify approvers
        event(new ApprovalRequired($instance, $nodeDef, $execution));

        return ExecutionResult::pending();
    }

    public function processAction(WorkflowInstance $instance, array $nodeDef, string $action, ?string $userId, ?string $comment): void
    {
        $execution = $instance->nodeExecutions()
            ->where('node_id', $nodeDef['id'])
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($execution) {
            $execution->update([
                'executed_by' => $userId,
                'completed_at' => now(),
                'status' => $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'skipped'),
                'comment' => $comment,
            ]);
        }
    }

    public function getNextNode(array $nodeDef, string $action, WorkflowInstance $instance): ?string
    {
        return match ($action) {
            'approve' => $nodeDef['on_approve'] ?? null,
            'reject' => $nodeDef['on_reject'] ?? null,
            'request_changes' => $nodeDef['on_request_changes'] ?? null,
            default => null,
        };
    }
}
