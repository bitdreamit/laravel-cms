<?php

/**
 * CMS V6 Helper Functions
 *
 * These functions are in the global namespace so they can be called
 * from anywhere in the application without namespace prefixes.
 */

if (! function_exists('tenant_has_feature')) {
    /**
     * Check if the current tenant has a specific V4 feature enabled.
     */
    function tenant_has_feature(string $feature): bool
    {
        if (! function_exists('tenancy') || ! tenancy()->initialized) {
            return false;
        }

        $tenant = tenant();
        if (! $tenant) return false;

        $features = data_get($tenant->data, 'features', []);

        // V4 features are off by default
        $v4Features = [
            'multi_domain', 'connector', 'workflow_engine', 'ab_testing',
            'collab_editing', 'ai_rag', 'personalization', 'saml_sso',
            'scim_provisioning', 'audit_streaming', 'form_analytics',
        ];

        if (! in_array($feature, $v4Features)) {
            return true; // V3 features are always on
        }

        return in_array($feature, $features);
    }
}

if (! function_exists('current_domain')) {
    /**
     * Get the current domain model (V4).
     */
    function current_domain(): ?\App\Models\Central\Domain
    {
        return app('current.domain');
    }
}

if (! function_exists('current_theme')) {
    /**
     * Get the current theme model.
     */
    function current_theme(): ?\App\Models\Central\Theme
    {
        return app('current.theme');
    }
}

if (! function_exists('current_site')) {
    /**
     * Get the current site (locale) model (V4).
     */
    function current_site(): ?\App\Models\Tenant\Site
    {
        return app('current.site');
    }
}

if (! function_exists('wildcard_segment')) {
    /**
     * Get the wildcard segment from the request (e.g. "shop" from "shop.example.com").
     */
    function wildcard_segment(): ?string
    {
        return request()->attributes->get('wildcard_segment');
    }
}
