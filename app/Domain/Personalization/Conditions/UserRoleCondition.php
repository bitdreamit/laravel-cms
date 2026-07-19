<?php

namespace App\Domain\Personalization\Conditions;

class UserRoleCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $user = auth()->user();
        if (! $user) return false;

        $operator = $config['operator'] ?? '=';
        $value = $config['value'] ?? '';

        return match ($operator) {
            '=' => $user->hasRole($value),
            '!=' => ! $user->hasRole($value),
            'in' => $user->hasAnyRole((array) $value),
            default => false,
        };
    }
}
