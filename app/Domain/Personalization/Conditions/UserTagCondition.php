<?php

namespace App\Domain\Personalization\Conditions;

class UserTagCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $user = auth()->user();
        if (! $user) return false;

        $tags = $user->tags ?? [];
        if (! is_array($tags)) $tags = json_decode($tags, true) ?: [];

        $operator = $config['operator'] ?? 'in';
        $value = $config['value'] ?? '';

        return match ($operator) {
            'in' => in_array($value, $tags),
            'not_in' => ! in_array($value, $tags),
            default => false,
        };
    }
}
