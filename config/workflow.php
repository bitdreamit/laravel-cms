<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Engine Configuration
    |--------------------------------------------------------------------------
    */
    'enabled' => env('WORKFLOW_ENABLED', true),
    'default_sla_hours' => env('WORKFLOW_DEFAULT_SLA', 48),
    'max_nodes_per_workflow' => env('WORKFLOW_MAX_NODES', 100),
    'cleanup_completed_instances_days' => env('WORKFLOW_CLEANUP_DAYS', 90),

    'node_types' => [
        'start' => \App\Domain\Workflow\Services\NodeExecutors\StartNodeExecutor::class,
        'approval' => \App\Domain\Workflow\Services\NodeExecutors\ApprovalNodeExecutor::class,
        'condition' => \App\Domain\Workflow\Services\NodeExecutors\ConditionNodeExecutor::class,
        'action' => \App\Domain\Workflow\Services\NodeExecutors\ActionNodeExecutor::class,
        'parallel' => \App\Domain\Workflow\Services\NodeExecutors\ParallelNodeExecutor::class,
        'wait' => \App\Domain\Workflow\Services\NodeExecutors\WaitNodeExecutor::class,
        'end' => \App\Domain\Workflow\Services\NodeExecutors\EndNodeExecutor::class,
    ],

    'actions' => [
        'publish_entry' => \App\Domain\Workflow\Actions\Builtin\PublishEntry::class,
        'unpublish_entry' => \App\Domain\Workflow\Actions\Builtin\UnpublishEntry::class,
        'send_email' => \App\Domain\Workflow\Actions\Builtin\SendEmail::class,
        'call_webhook' => \App\Domain\Workflow\Actions\Builtin\CallWebhook::class,
        'set_field' => \App\Domain\Workflow\Actions\Builtin\SetField::class,
        'add_tag' => \App\Domain\Workflow\Actions\Builtin\AddTag::class,
        'ask_rag' => \App\Domain\Workflow\Actions\Builtin\AskRag::class,
    ],

    'twig' => [
        'sandbox' => true,
        'allowed_tags' => ['if', 'for', 'set', 'filter'],
        'allowed_filters' => ['upper', 'lower', 'capitalize', 'trim', 'length', 'default'],
    ],
];
