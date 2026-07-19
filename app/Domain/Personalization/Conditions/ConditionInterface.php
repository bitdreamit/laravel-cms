<?php

namespace App\Domain\Personalization\Conditions;

interface ConditionInterface
{
    /**
     * Check if this condition matches the current visitor context.
     */
    public function matches(array $config, Context $context): bool;
}
