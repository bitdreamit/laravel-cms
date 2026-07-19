<?php

namespace App\Console\Commands;

use App\Models\Tenant\WorkflowInstance;
use Illuminate\Console\Command;

class CheckWorkflowSla extends Command
{
    protected $signature = 'workflow:check-sla-breaches';
    protected $description = 'Check for workflow approval nodes past their SLA.';

    public function handle(): int
    {
        $breached = 0;

        \Stancl\Tenancy\Tenancy::runForMultiple(\App\Models\Central\Tenant::where('status', 'active')->get(), function () use (&$breached) {
            $instances = WorkflowInstance::where('tenant_id', tenant('id'))
                ->where('status', 'running')
                ->get();

            foreach ($instances as $instance) {
                $nodeDef = app(\App\Domain\Workflow\Services\WorkflowEngine::class)
                    ->getNodeDefinition($instance, $instance->current_node_id);

                if (($nodeDef['type'] ?? '') !== 'approval') continue;
                $slaHours = $nodeDef['sla_hours'] ?? config('workflow.default_sla_hours', 48);

                $execution = $instance->nodeExecutions()
                    ->where('node_id', $instance->current_node_id)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();

                if ($execution && $execution->started_at->diffInHours(now()) > $slaHours) {
                    $breached++;
                    $this->warn("SLA breach: workflow instance {$instance->id} (node: {$nodeDef['label']})");

                    // Notify admins
                    event(new \App\Domain\Workflow\Events\ApprovalRequired($instance, $nodeDef, $execution));
                }
            }
        });

        $this->info("Found {$breached} SLA breaches.");
        return self::SUCCESS;
    }
}
