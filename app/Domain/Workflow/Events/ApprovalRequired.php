<?php

namespace App\Domain\Workflow\Events;

use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowNodeExecution;
use Illuminate\Foundation\Events\Dispatchable;

class ApprovalRequired
{
    use Dispatchable;

    public function __construct(
        public WorkflowInstance $instance,
        public array $nodeDef,
        public WorkflowNodeExecution $execution,
    ) {}
}
