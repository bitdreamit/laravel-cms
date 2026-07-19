<?php

namespace App\Domain\Theme\Services;

use App\Models\Central\Theme;
use App\Models\Central\Tenant;
use App\Models\Tenant\ThemeCustomization;

class ThemeCustomizer
{
    public function getSettings(?Tenant $tenant = null, ?Theme $theme = null): array
    {
        $tenant ??= tenant();
        $theme ??= app(ThemeResolver::class)->resolve($tenant);

        if (! $tenant || ! $theme) return [];

        $defaults = $this->extractDefaults($theme->settings_schema);
        $customization = ThemeCustomization::where('tenant_id', $tenant->id)
            ->where('theme_id', $theme->id)
            ->first();

        $customValues = $customization?->settings ?? [];
        return $this->mergeSettings($defaults, $customValues);
    }

    public function updateSettings(?Tenant $tenant, Theme $theme, array $values): void
    {
        $validated = $this->validateAgainstSchema($values, $theme->settings_schema);

        ThemeCustomization::updateOrCreate(
            ['tenant_id' => $tenant->id, 'theme_id' => $theme->id],
            ['settings' => $validated],
        );

        \Illuminate\Support\Facades\Cache::forget("theme:{$tenant->id}:settings");

        event(new \App\Domain\Theme\Events\ThemeCustomized($theme, $tenant));
    }

    public function resetToDefaults(?Tenant $tenant, Theme $theme): void
    {
        ThemeCustomization::where('tenant_id', $tenant->id)
            ->where('theme_id', $theme->id)
            ->delete();

        \Illuminate\Support\Facades\Cache::forget("theme:{$tenant->id}:settings");
    }

    public function exportCustomization(?Tenant $tenant, Theme $theme): string
    {
        $customization = ThemeCustomization::where('tenant_id', $tenant->id)
            ->where('theme_id', $theme->id)
            ->first();

        return json_encode([
            'theme_slug' => $theme->slug,
            'settings' => $customization?->settings ?? [],
            'custom_css' => $customization?->custom_css,
            'custom_js' => $customization?->custom_js,
        ], JSON_PRETTY_PRINT);
    }

    public function importCustomization(?Tenant $tenant, Theme $theme, string $json): void
    {
        $data = json_decode($json, true);
        if (! $data || ! isset($data['settings'])) {
            throw new \InvalidArgumentException('Invalid customization JSON.');
        }

        $this->updateSettings($tenant, $theme, $data['settings']);
    }

    protected function extractDefaults(?array $schema): array
    {
        $defaults = [];
        if (! $schema) return $defaults;

        foreach ($schema as $sectionKey => $section) {
            if (($section['type'] ?? '') === 'section' && isset($section['settings'])) {
                foreach ($section['settings'] as $settingKey => $setting) {
                    if (isset($setting['default'])) {
                        $defaults["{$sectionKey}.{$settingKey}"] = $setting['default'];
                    }
                }
            }
        }
        return $defaults;
    }

    protected function mergeSettings(array $defaults, array $custom): array
    {
        $merged = $defaults;
        foreach ($custom as $key => $value) {
            $merged[$key] = $value;
        }
        return $merged;
    }

    protected function validateAgainstSchema(array $values, ?array $schema): array
    {
        $validKeys = [];
        if ($schema) {
            foreach ($schema as $sectionKey => $section) {
                if (($section['type'] ?? '') === 'section' && isset($section['settings'])) {
                    foreach ($section['settings'] as $settingKey => $setting) {
                        $fullKey = "{$sectionKey}.{$settingKey}";
                        $validKeys[$fullKey] = true;
                    }
                }
            }
        }

        $validated = [];
        foreach ($values as $key => $value) {
            if (isset($validKeys[$key]) || empty($validKeys)) {
                $validated[$key] = $value;
            }
        }
        return $validated;
    }
}
