# Theming Guide

## Overview

The CMS V4 Theme Engine is a complete, production-grade theme system with parent/child inheritance, per-tenant customization, live customizer, and asset pipeline.

## Theme Structure

```
themes/
└── my-theme/
    ├── theme.json           # Theme manifest with settings_schema
    ├── screenshot.png       # 1200x900 preview image
    ├── views/
    │   ├── layouts/
    │   │   ├── app.blade.php
    │   │   └── partials/
    │   │       ├── header.blade.php
    │   │       ├── footer.blade.php
    │   │       └── seo.blade.php
    │   ├── pages/
    │   │   ├── home.blade.php
    │   │   ├── default.blade.php
    │   │   └── blog-single.blade.php
    │   ├── partials/
    │   │   ├── hero.blade.php
    │   │   └── card.blade.php
    │   └── errors/
    │       └── 404.blade.php
    ├── assets/
    │   ├── css/
    │   │   └── theme.css
    │   ├── js/
    │   │   └── theme.js
    │   └── images/
    ├── config/
    │   └── settings_schema.json
    └── dist/                # Compiled assets
```

## theme.json Manifest

```json
{
    "name": "My Theme",
    "slug": "my-theme",
    "version": "1.0.0",
    "description": "A beautiful theme",
    "author": "Your Name",
    "parent": null,
    "type": "custom",
    "supported_features": ["blog", "contact_form"],
    "tags": ["corporate", "minimal"],
    "settings_schema": {
        "branding": {
            "type": "section",
            "title": "Branding",
            "settings": {
                "brand_color": {
                    "type": "color",
                    "label": "Primary Color",
                    "default": "#2563eb"
                }
            }
        }
    }
}
```

## Parent/Child Themes

A child theme inherits all views, assets, and settings from its parent. Override only what you need:

```json
// themes/my-child-theme/theme.json
{
    "name": "My Child Theme",
    "slug": "my-child-theme",
    "parent": "my-theme",
    "version": "1.0.0"
}
```

The view cascade: child → parent → grandparent → resources/views

## Blade Directives

```blade
{{-- Get a theme setting --}}
@theme('branding.brand_color')

{{-- Conditional on theme setting --}}
@iftheme('header.show_top_bar')
    <div class="top-bar">@theme('header.top_bar_text')</div>
@endiftheme

{{-- Theme asset URL (auto-versioned) --}}
@themeAsset('css/theme.css')

{{-- Include from theme cascade --}}
@includeTheme('partials.hero')

{{-- Check theme feature --}}
@themeHasFeature('mega_menu')
    <nav class="mega-menu">...</nav>
@endThemeHasFeature

{{-- Inject compiled CSS variables --}}
@themeCssVars
```

## Creating a Theme

1. Create a folder at `themes/{your-slug}/`
2. Add `theme.json` with your manifest
3. Add views (at minimum `layouts/app.blade.php` and `pages/home.blade.php`)
4. Add `assets/css/theme.css` with CSS using `:root` variables
5. Register in admin panel: `/admin/themes` → your theme appears automatically

## Customizing a Theme

1. Go to `/admin/themes` → click "Customize"
2. Left panel: controls auto-generated from `settings_schema`
3. Right panel: live iframe preview
4. Change settings → preview updates in real-time
5. Click "Publish" to make changes live

Customizations are stored per-tenant in `theme_customizations` table — theme files are never modified.

## Asset Pipeline

- Source assets: `themes/{slug}/assets/`
- Compiled assets: `themes/{slug}/dist/`
- Assets are versioned with content hash: `theme.css?v=abc12345`
- CDN support: set `ASSET_CDN_URL` and `ASSET_CDN_ENABLED=true`
