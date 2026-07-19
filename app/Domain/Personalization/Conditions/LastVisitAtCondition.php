<?php

namespace App\Domain\Personalization\Conditions;

class LastVisitAtCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $sessions = $context->getVisitorSessions();
        if ($sessions->isEmpty()) return false;

        $lastVisit = $sessions->max('updated_at');
        if (! $lastVisit) return false;

        $leftDate = \Carbon\Carbon::parse($lastVisit);
        $rightDate = \Carbon\Carbon::parse($config['value'] ?? 'now');
        $operator = $config['operator'] ?? '>=';

        return match ($operator) {
            '=' => $leftDate->isSameDay($rightDate),
            '>' => $leftDate->gt($rightDate),
            '>=' => $leftDate->gte($rightDate),
            '<' => $leftDate->lt($rightDate),
            '<=' => $leftDate->lte($rightDate),
            default => false,
        };
    }
}
