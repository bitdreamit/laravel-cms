<?php

namespace App\Domain\Personalization\Conditions;

class VisitCountCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $sessions = $context->getVisitorSessions();
        $visitCount = $sessions->count();

        return $this->compare($visitCount, $config['operator'] ?? '>=', $config['value'] ?? 0);
    }

    protected function compare($left, string $operator, $right): bool
    {
        return match ($operator) {
            '=' => $left == $right,
            '!=' => $left != $right,
            '>' => $left > $right,
            '>=' => $left >= $right,
            '<' => $left < $right,
            '<=' => $left <= $right,
            'in' => in_array($left, (array) $right),
            'not_in' => ! in_array($left, (array) $right),
            default => false,
        };
    }
}
