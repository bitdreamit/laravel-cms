<?php

namespace App\Domain\Personalization\Conditions;

class FirstVisitAtCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $sessions = $context->getVisitorSessions();
        if ($sessions->isEmpty()) return false;

        $firstVisit = $sessions->min('created_at');
        if (! $firstVisit) return false;

        return $this->compareDate($firstVisit, $config['operator'] ?? '>=', $config['value'] ?? 'now');
    }

    protected function compareDate($left, string $operator, $right): bool
    {
        $leftDate = \Carbon\Carbon::parse($left);
        $rightDate = \Carbon\Carbon::parse($right);

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
