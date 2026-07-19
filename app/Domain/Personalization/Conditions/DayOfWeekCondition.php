<?php

namespace App\Domain\Personalization\Conditions;

class DayOfWeekCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $timezone = $config['timezone'] ?? config('app.timezone');
        $now = now()->setTimezone($timezone);
        $dayOfWeek = strtolower($now->format('l'));

        $operator = $config['operator'] ?? 'in';
        $value = $config['value'] ?? [];

        $value = array_map('strtolower', (array) $value);

        return match ($operator) {
            '=' => $dayOfWeek === $value[0] ?? '',
            'in' => in_array($dayOfWeek, $value),
            'not_in' => ! in_array($dayOfWeek, $value),
            default => false,
        };
    }
}
