<?php

namespace App\Domain\Personalization\Conditions;

class GeoCityCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $dbPath = config('personalization.geoip.database_path');
        if (! file_exists($dbPath)) return false;

        try {
            $reader = new \GeoIp2\Database\Reader($dbPath);
            $record = $reader->city($context->request->ip());
            $city = $record->city->name;
        } catch (\Throwable) {
            return false;
        }

        if (! $city) return false;

        $operator = $config['operator'] ?? '=';
        $value = $config['value'] ?? '';

        return match ($operator) {
            '=' => $city === $value,
            'in' => in_array($city, (array) $value),
            default => false,
        };
    }
}
