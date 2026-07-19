<?php

namespace App\Domain\Personalization\Conditions;

class BrowserCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $userAgent = $context->request->headers->get('User-Agent', '');
        $browser = $this->detectBrowser($userAgent);

        $operator = $config['operator'] ?? '=';
        $value = $config['value'] ?? '';

        return match ($operator) {
            '=' => $browser === $value,
            'in' => in_array($browser, (array) $value),
            default => false,
        };
    }

    protected function detectBrowser(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'edg/')) return 'edge';
        if (str_contains($ua, 'chrome/') && ! str_contains($ua, 'edg/')) return 'chrome';
        if (str_contains($ua, 'firefox/')) return 'firefox';
        if (str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/')) return 'safari';
        if (str_contains($ua, 'opr/') || str_contains($ua, 'opera')) return 'opera';
        return 'other';
    }
}
