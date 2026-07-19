<?php

namespace App\Domain\Personalization\Conditions;

class GeoCountryCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $country = $this->getCountry($context);

        if (! $country) return false;

        $operator = $config['operator'] ?? '=';
        $value = $config['value'] ?? null;

        return match ($operator) {
            '=' => strtoupper($country) === strtoupper($value),
            '!=' => strtoupper($country) !== strtoupper($value),
            'in' => in_array(strtoupper($country), array_map('strtoupper', (array) $value)),
            'not_in' => ! in_array(strtoupper($country), array_map('strtoupper', (array) $value)),
            default => false,
        };
    }

    protected function getCountry(Context $context): ?string
    {
        // Check profile cache first
        if ($context->profile && isset($context->profile['geo_country'])) {
            return $context->profile['geo_country'];
        }

        // Use GeoIP lookup (MaxMind GeoLite2)
        $dbPath = config('personalization.geoip.database_path');
        if (! file_exists($dbPath)) {
            return null;
        }

        try {
            $reader = new \GeoIp2\Database\Reader($dbPath);
            $record = $reader->country($context->request->ip());
            return $record->country->isoCode;
        } catch (\Throwable) {
            return null;
        }
    }
}
