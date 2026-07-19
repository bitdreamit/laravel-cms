<?php

namespace App\Domain\Personalization\Conditions;

class LandingPageCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $sessions = $context->getVisitorSessions();
        if ($sessions->isEmpty()) return false;

        $landingPage = $sessions->first()->landing_page ?? '';
        $operator = $config['operator'] ?? '=';
        $value = $config['value'] ?? '';

        return match ($operator) {
            '=' => $landingPage === $value,
            'contains' => str_contains($landingPage, $value),
            'starts_with' => str_starts_with($landingPage, $value),
            default => false,
        };
    }
}
