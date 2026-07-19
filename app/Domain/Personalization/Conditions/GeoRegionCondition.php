<?php

namespace App\Domain\Personalization\Conditions;

class GeoRegionCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $dbPath = config('personalization.geoip.database_path');
        if (! file_exists($dbPath)) return false;

        try {
            $reader = new \GeoIp2\Database\Reader($dbPath);
            $record = $reader->city($context->request->ip());
            $region = $record->mostSpecificSubdivision->isoCode ?? $record->mostSpecificSubdivision->name;
        } catch (\Throwable) {
            return false;
        }

        if (! $region) return false;

        $operator = $config['operator'] ?? '=';
        $value = $config['value'] ?? '';

        return match ($operator) {
            '=' => $region === $value,
            'in' => in_array($region, (array) $value),
            default => false,
        };
    }
}
