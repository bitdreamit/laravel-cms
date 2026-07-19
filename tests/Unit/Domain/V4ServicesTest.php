<?php

use App\Domain\Rag\Services\Chunker;
use App\Domain\Rag\Services\EmbeddingService;
use App\Domain\Rag\Services\VectorSearch;
use App\Domain\Workflow\Services\ConditionEvaluator;
use App\Domain\Experiment\Services\ExperimentEngine;

it('chunks text into properly sized pieces', function () {
    $chunker = new Chunker();

    $text = str_repeat('This is a test sentence. ', 200); // ~5000 chars
    $chunks = $chunker->chunk($text, 500, 50);

    expect($chunks)->not->toBeEmpty();
    foreach ($chunks as $chunk) {
        expect(strlen($chunk['text']))->toBeLessThan(2500); // rough upper bound
    }
});

it('embeds text and returns a vector', function () {
    $service = new EmbeddingService();
    $embedding = $service->embed('Hello world');

    expect($embedding)->toBeArray();
    expect(count($embedding))->toBeGreaterThan(0);
});

it('computes cosine similarity correctly', function () {
    $search = new VectorSearch(new EmbeddingService());

    $a = [1.0, 0.0, 0.0];
    $b = [1.0, 0.0, 0.0];
    expect($search->cosineSimilarity($a, $b))->toBe(1.0);

    $c = [0.0, 1.0, 0.0];
    expect($search->cosineSimilarity($a, $c))->toBe(0.0);

    $d = [-1.0, 0.0, 0.0];
    expect($search->cosineSimilarity($a, $d))->toBe(-1.0);
});

it('evaluates simple twig condition', function () {
    $evaluator = app(ConditionEvaluator::class);

    $instance = new \App\Models\Tenant\WorkflowInstance([
        'context' => ['value' => 15000],
    ]);

    // We need to mock the entry lookup
    \App\Models\Tenant\Entry::shouldReceive('find')->andReturn(new \App\Models\Tenant\Entry([
        'data' => ['estimated_value' => 15000],
    ]));

    $result = $evaluator->evaluate('entry.data.estimated_value > 10000', $instance);
    expect($result)->toBeTrue();
});
