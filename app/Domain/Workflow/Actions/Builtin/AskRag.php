<?php

namespace App\Domain\Workflow\Actions\Builtin;

use App\Models\Tenant\WorkflowInstance;

class AskRag implements WorkflowActionInterface
{
    public function execute(WorkflowInstance $instance, array $config): array
    {
        if (! tenant_has_feature('ai_rag')) {
            return ['error' => 'AI RAG feature not enabled'];
        }

        $question = $config['question'] ?? null;
        if (! $question) {
            return ['error' => 'No question specified'];
        }

        $ragService = app(\App\Domain\Rag\Services\RagService::class);
        $response = $ragService->ask($instance->tenant_id, $question);

        // Store the answer in workflow context for subsequent nodes
        $context = $instance->context ?? [];
        $context['rag_answer'] = $response->answer;
        $context['rag_citations'] = $response->citations;
        $instance->update(['context' => $context]);

        return [
            'answer' => $response->answer,
            'citations' => $response->citations,
        ];
    }
}
