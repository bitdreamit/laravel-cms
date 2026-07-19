<?php

namespace App\Domain\Workflow\Services\NodeExecutors;

class ExecutionResult
{
    public function __construct(
        protected bool $autoAdvance = false,
        protected bool $complete = false,
        protected ?string $action = null,
        protected ?string $outcome = null,
        protected ?array $output = null,
    ) {}

    public function isAutoAdvance(): bool { return $this->autoAdvance; }
    public function isComplete(): bool { return $this->complete; }
    public function getAction(): ?string { return $this->action; }
    public function getOutcome(): ?string { return $this->outcome; }
    public function getOutput(): ?array { return $this->output; }

    public static function pending(): self
    {
        return new self(autoAdvance: false, complete: false);
    }

    public static function autoAdvance(string $action, ?array $output = null): self
    {
        return new self(autoAdvance: true, action: $action, output: $output);
    }

    public static function complete(string $outcome): self
    {
        return new self(complete: true, outcome: $outcome);
    }
}
