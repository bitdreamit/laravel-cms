<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Domain\Workflow\Services\WorkflowEngine start(\App\Models\Tenant\Workflow $workflow, string $entryId, array $initialContext = [])
 * @method static \App\Domain\Workflow\Services\WorkflowEngine advance(\App\Models\Tenant\WorkflowInstance $instance, string $action, ?string $userId = null, ?string $comment = null)
 */
class Workflow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Domain\Workflow\Services\WorkflowEngine::class;
    }
}
