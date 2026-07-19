<?php

namespace App\Domain\Personalization\Conditions;

class DeviceTypeCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $userAgent = $context->request->headers->get('User-Agent', '');
        $device = $this->detectDeviceType($userAgent);

        $operator = $config['operator'] ?? '=';
        $value = $config['value'] ?? '';

        return match ($operator) {
            '=' => $device === $value,
            '!=' => $device !== $value,
            'in' => in_array($device, (array) $value),
            default => false,
        };
    }

    protected function detectDeviceType(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/mobile|iphone|ipod|android.*mobile|windows phone/i', $ua)) {
            return 'mobile';
        }
        return 'desktop';
    }
}
