<?php

namespace App\Domain\Workflow\Events;

use App\Models\Tenant\WorkflowInstance;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowCompleted
{
    use Dispatchable;

    public function __construct(
        public WorkflowInstance $instance,
        public string $outcome,
    ) {}
}
