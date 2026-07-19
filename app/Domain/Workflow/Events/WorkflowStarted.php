<?php

namespace App\Domain\Workflow\Events;

use App\Models\Tenant\WorkflowInstance;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowStarted
{
    use Dispatchable;

    public function __construct(public WorkflowInstance $instance) {}
}
