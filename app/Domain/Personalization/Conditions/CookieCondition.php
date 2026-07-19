<?php

namespace App\Domain\Personalization\Conditions;

class CookieCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $name = $config['name'] ?? '';
        $value = $context->request->cookie($name);

        if ($value === null) return false;

        $operator = $config['operator'] ?? '=';
        $expected = $config['value'] ?? '';

        return match ($operator) {
            '=' => $value == $expected,
            '!=' => $value != $expected,
            'contains' => str_contains((string) $value, (string) $expected),
            default => false,
        };
    }
}
