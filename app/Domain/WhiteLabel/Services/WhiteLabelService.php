<?php

namespace App\Domain\WhiteLabel\Services;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Cache;

class WhiteLabelService
{
    public function getBranding(?Tenant $tenant = null): array
    {
        $tenant ??= tenant();
        if (! $tenant) return $this->getDefaultBranding();

        return Cache::remember("whitelabel:{$tenant->id}", 3600, function () use ($tenant) {
            $branding = data_get($tenant->data, 'branding', []);
            return array_merge($this->getDefaultBranding(), $branding);
        });
    }

    public function updateBranding(Tenant $tenant, array $branding): void
    {
        $data = $tenant->data ?? [];
        $data['branding'] = $branding;
        $tenant->update(['data' => $data]);

        Cache::forget("whitelabel:{$tenant->id}");
    }

    public function getCssVariables(?Tenant $tenant = null): string
    {
        $branding = $this->getBranding($tenant);

        $vars = [
            '--cms-primary-color' => $branding['primary_color'] ?? '#2563eb',
            '--cms-primary-hover' => $branding['primary_hover'] ?? '#1d4ed8',
            '--cms-secondary-color' => $branding['secondary_color'] ?? '#64748b',
            '--cms-sidebar-bg' => $branding['sidebar_bg'] ?? '#1e293b',
            '--cms-sidebar-text' => $branding['sidebar_text'] ?? '#e2e8f0',
            '--cms-logo-url' => $branding['logo_url'] ? "url('{$branding['logo_url']}')" : 'none',
            '--cms-font-family' => $branding['font_family'] ?? "'Inter', system-ui, sans-serif",
        ];

        return ':root {' . collect($vars)->map(fn($v, $k) => "{$k}: {$v};")->implode(' ') . '}';
    }

    public function showPlatformBranding(?Tenant $tenant = null): bool
    {
        $branding = $this->getBranding($tenant);
        return ! ($branding['hide_platform_branding'] ?? false) ||
               ! $this->tenantCanWhiteLabel($tenant);
    }

    public function tenantCanWhiteLabel(?Tenant $tenant = null): bool
    {
        $tenant ??= tenant();
        if (! $tenant) return false;

        $plan = $tenant->plan;
        return $plan?->white_label_allowed ?? false;
    }

    public function getCustomCss(?Tenant $tenant = null): ?string
    {
        $tenant ??= tenant();
        if (! $tenant) return null;

        if (! $this->tenantCanWhiteLabel($tenant)) return null;

        return data_get($tenant->data, 'branding.custom_css');
    }

    public function getCustomJs(?Tenant $tenant = null): ?string
    {
        $tenant ??= tenant();
        if (! $tenant) return null;

        if (! $this->tenantCanWhiteLabel($tenant)) return null;

        return data_get($tenant->data, 'branding.custom_js');
    }

    protected function getDefaultBranding(): array
    {
        return [
            'logo_url' => null,
            'logo_height' => 40,
            'primary_color' => '#2563eb',
            'primary_hover' => '#1d4ed8',
            'secondary_color' => '#64748b',
            'sidebar_bg' => '#1e293b',
            'sidebar_text' => '#e2e8f0',
            'font_family' => "'Inter', system-ui, sans-serif",
            'hide_platform_branding' => false,
            'custom_css' => null,
            'custom_js' => null,
            'favicon_url' => null,
            'login_bg_url' => null,
            'login_heading' => null,
            'login_subheading' => null,
        ];
    }
}
