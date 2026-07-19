<?php

namespace App\Domain\Workflow\Services;

use App\Domain\Workflow\Services\NodeExecutors\NodeExecutorInterface;
use App\Models\Tenant\Workflow;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowNodeExecution;
use App\Models\Central\User;
use Illuminate\Support\Str;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;

class WorkflowEngine
{
    public function __construct(
        protected ConditionEvaluator $conditionEvaluator,
    ) {}

    /**
     * Start a new workflow instance for an entry.
     */
    public function start(Workflow $workflow, string $entryId, array $initialContext = []): WorkflowInstance
    {
        $instance = WorkflowInstance::create([
            'id' => Str::uuid(),
            'tenant_id' => $workflow->tenant_id,
            'workflow_id' => $workflow->id,
            'entry_id' => $entryId,
            'current_node_id' => $this->getStartNode($workflow)['id'],
            'status' => 'running',
            'context' => $initialContext,
            'started_at' => now(),
        ]);

        event(new \App\Domain\Workflow\Events\WorkflowStarted($instance));

        // Execute the start node immediately
        $this->executeCurrentNode($instance);

        return $instance;
    }

    /**
     * Advance the workflow by processing an action on the current node.
     */
    public function advance(WorkflowInstance $instance, string $action, ?string $userId = null, ?string $comment = null): WorkflowInstance
    {
        if (! $instance->isRunning()) {
            throw new \RuntimeException('Cannot advance a non-running workflow instance.');
        }

        $nodeDef = $this->getNodeDefinition($instance, $instance->current_node_id);
        $executor = $this->getNodeExecutor($nodeDef['type']);

        $executor->processAction($instance, $nodeDef, $action, $userId, $comment);

        // Determine next node
        $nextNodeId = $executor->getNextNode($nodeDef, $action, $instance);

        if ($nextNodeId === null) {
            $this->complete($instance, 'completed');
        } else {
            $instance->update(['current_node_id' => $nextNodeId]);
            $this->executeCurrentNode($instance);
        }

        return $instance->fresh();
    }

    /**
     * Cancel a running workflow instance.
     */
    public function cancel(WorkflowInstance $instance, string $userId, string $reason): void
    {
        $instance->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        event(new \App\Domain\Workflow\Events\WorkflowCompleted($instance, 'cancelled'));
    }

    /**
     * Execute the current node of the instance.
     */
    protected function executeCurrentNode(WorkflowInstance $instance): void
    {
        $nodeDef = $this->getNodeDefinition($instance, $instance->current_node_id);
        $executor = $this->getNodeExecutor($nodeDef['type']);

        // Create execution record
        $execution = WorkflowNodeExecution::create([
            'id' => Str::uuid(),
            'tenant_id' => $instance->tenant_id,
            'workflow_instance_id' => $instance->id,
            'node_id' => $nodeDef['id'],
            'node_type' => $nodeDef['type'],
            'started_at' => now(),
            'status' => 'pending',
        ]);

        // Execute the node
        $result = $executor->execute($instance, $nodeDef, $execution);

        // If the node auto-completed (e.g. condition, action), advance automatically
        if ($result->isAutoAdvance()) {
            $this->advance($instance, $result->action);
        } elseif ($result->isComplete()) {
            $this->complete($instance, $result->outcome ?? 'completed');
        }
    }

    /**
     * Mark a workflow instance as complete.
     */
    protected function complete(WorkflowInstance $instance, string $outcome): void
    {
        $instance->update([
            'status' => $outcome === 'cancelled' ? 'cancelled' : 'completed',
            'completed_at' => now(),
        ]);

        event(new \App\Domain\Workflow\Events\WorkflowCompleted($instance, $outcome));
    }

    /**
     * Get the start node from the workflow definition.
     */
    public function getStartNode(Workflow $workflow): array
    {
        $definition = $workflow->definition;
        foreach ($definition['nodes'] as $node) {
            if ($node['type'] === 'start') {
                return $node;
            }
        }
        throw new \RuntimeException('No start node found in workflow definition.');
    }

    /**
     * Get a node definition by ID.
     */
    public function getNodeDefinition(WorkflowInstance $instance, string $nodeId): array
    {
        $definition = $instance->workflow->definition;
        foreach ($definition['nodes'] as $node) {
            if ($node['id'] === $nodeId) {
                return $node;
            }
        }
        throw new \RuntimeException("Node {$nodeId} not found in workflow definition.");
    }

    /**
     * Get the executor for a node type.
     */
    protected function getNodeExecutor(string $nodeType): NodeExecutorInterface
    {
        $executorClass = config("workflow.node_types.{$nodeType}");

        if (! $executorClass || ! class_exists($executorClass)) {
            throw new \RuntimeException("No executor for node type: {$nodeType}");
        }

        return app($executorClass);
    }
}
