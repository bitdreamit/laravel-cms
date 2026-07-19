<?php

namespace App\Domain\Theme\Services;

use App\Models\Central\Theme;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Cache;

class ThemeVariableCompiler
{
    public function __construct(protected ThemeCustomizer $customizer) {}

    public function compile(?Tenant $tenant = null, ?Theme $theme = null): string
    {
        $tenant ??= tenant();
        $theme ??= app(ThemeResolver::class)->resolve($tenant);

        if (! $theme) return '';

        $cacheKey = "theme:{$tenant?->id}:css-vars";
        return Cache::remember($cacheKey, 3600, function () use ($tenant, $theme) {
            $settings = $this->customizer->getSettings($tenant, $theme);
            return $this->generateCss($settings);
        });
    }

    protected function generateCss(array $settings): string
    {
        $vars = [];
        foreach ($settings as $key => $value) {
            $cssVar = $this->toCssVariableName($key);
            $cssValue = $this->toCssValue($value);
            if ($cssValue !== null) {
                $vars[] = "  {$cssVar}: {$cssValue};";
            }
        }

        if (empty($vars)) return '';

        return ":root {\n" . implode("\n", $vars) . "\n}";
    }

    protected function toCssVariableName(string $key): string
    {
        return '--' . str_replace(['.', '_'], '-', $key);
    }

    protected function toCssValue(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;

        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_array($value)) {
            if (isset($value['value'])) return (string) $value['value'];
            if (isset($value['color'])) return (string) $value['color'];
            return null;
        }
        return (string) $value;
    }

    public function clearCache(?Tenant $tenant = null): void
    {
        $tenant ??= tenant();
        if ($tenant) {
            Cache::forget("theme:{$tenant->id}:css-vars");
            Cache::forget("theme:{$tenant->id}:settings");
        }
    }
}
