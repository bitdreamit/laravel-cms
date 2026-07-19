<?php

namespace App\Domain\Personalization\Conditions;

class TimeOfDayCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $timezone = $config['timezone'] ?? config('app.timezone');
        $now = now()->setTimezone($timezone);
        $hour = (int) $now->format('H');

        $operator = $config['operator'] ?? 'between';
        $value = $config['value'] ?? [0, 24];

        return match ($operator) {
            'between' => $hour >= $value[0] && $hour < $value[1],
            '=' => $hour === (int) $value,
            '>' => $hour > (int) $value,
            '<' => $hour < (int) $value,
            default => false,
        };
    }
}
