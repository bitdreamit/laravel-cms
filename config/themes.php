<?php

return [
    'enabled' => env('THEMES_ENABLED', true),
    'path' => base_path('themes'),
    'default_theme' => env('THEMES_DEFAULT', 'foundation'),
    'marketplace_enabled' => env('THEMES_MARKETPLACE', false),

    'compilation' => [
        'enabled' => env('THEMES_COMPILE', true),
        'cache_path' => storage_path('framework/views/themes'),
        'auto_recompile' => env('THEMES_AUTO_RECOMPILE', true),
    ],

    'view_cascade' => [
        // Order: child theme → parent theme → grandparent → resources/views
        'max_depth' => 5,
        'fallback_to_resources' => true,
    ],

    'asset_pipeline' => [
        'versioning' => 'content_hash', // content_hash | timestamp | none
        'cdn_url' => env('ASSET_CDN_URL'),
        'cdn_enabled' => env('ASSET_CDN_ENABLED', false),
    ],

    'blade_directives' => [
        '@theme', '@iftheme', '@themeAsset', '@includeTheme', '@themeHasFeature', '@themeComponent',
    ],

    'live_customizer' => [
        'enabled' => env('THEMES_CUSTOMIZER', true),
        'preview_endpoint' => '/admin/api/themes/preview-settings',
        'auto_save' => false,
    ],
];
