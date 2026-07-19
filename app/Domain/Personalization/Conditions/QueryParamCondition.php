<?php

namespace App\Domain\Personalization\Conditions;

class QueryParamCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $param = $config['param'] ?? '';
        $value = $context->request->query($param);

        if ($value === null) return false;

        $operator = $config['operator'] ?? '=';
        $expected = $config['value'] ?? '';

        return match ($operator) {
            '=' => $value == $expected,
            '!=' => $value != $expected,
            'contains' => str_contains((string) $value, (string) $expected),
            'in' => in_array($value, (array) $expected),
            default => false,
        };
    }
}
