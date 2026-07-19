<?php

namespace App\Domain\Workflow\Jobs;

use App\Domain\Workflow\Services\WorkflowEngine;
use App\Models\Tenant\WorkflowInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResumeWorkflowAfterWait implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $instanceId) {}

    public function handle(WorkflowEngine $engine): void
    {
        $instance = WorkflowInstance::find($this->instanceId);
        if (! $instance || ! $instance->isRunning()) {
            return;
        }

        // Advance past the wait node
        $engine->advance($instance, 'done');
    }
}
