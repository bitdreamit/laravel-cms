<?php

namespace App\Domain\Personalization\Conditions;

class ReferrerCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $referrer = $context->request->headers->get('referer', '');
        $operator = $config['operator'] ?? 'contains';
        $value = $config['value'] ?? '';

        return match ($operator) {
            '=' => $referrer === $value,
            '!=' => $referrer !== $value,
            'contains' => str_contains($referrer, $value),
            'starts_with' => str_starts_with($referrer, $value),
            'ends_with' => str_ends_with($referrer, $value),
            default => false,
        };
    }
}
