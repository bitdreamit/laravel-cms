<?php

namespace App\Domain\Workflow\Services;

use App\Models\Tenant\WorkflowInstance;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\Extension\SandboxExtension;
use Twig\Sandbox\SecurityPolicy;

/**
 * Evaluates workflow condition expressions using a sandboxed Twig environment.
 */
class ConditionEvaluator
{
    public function evaluate(string $expression, WorkflowInstance $instance): bool
    {
        $loader = new ArrayLoader([
            'condition' => "{{ {$expression} ? 'true' : 'false' }}",
        ]);

        $twig = new Environment($loader, ['autoescape' => false]);

        // Apply sandboxing
        $policy = new SecurityPolicy(
            allowedTags: config('workflow.twig.allowed_tags', ['if']),
            allowedFilters: config('workflow.twig.allowed_filters', ['default']),
            allowedMethods: [],
            allowedProperties: [],
            allowedFunctions: [],
        );
        $twig->addExtension(new SandboxExtension($policy, true));

        // Build context
        $entry = \App\Models\Tenant\Entry::find($instance->entry_id);
        $context = [
            'entry' => $entry?->toArray(),
            'context' => $instance->context ?? [],
            'tenant' => tenant()?->toArray(),
        ];

        try {
            $result = $twig->render('condition', $context);
            return trim($result) === 'true';
        } catch (\Throwable $e) {
            \Log::warning('Workflow condition evaluation failed', [
                'expression' => $expression,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
