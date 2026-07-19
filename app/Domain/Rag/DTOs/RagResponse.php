<?php

namespace App\Domain\Rag\DTOs;

class RagResponse
{
    public function __construct(
        public string $answer,
        public array $citations = [],
        public array $retrievedChunks = [],
        public ?string $modelUsed = null,
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $latencyMs = 0,
    ) {}
}
