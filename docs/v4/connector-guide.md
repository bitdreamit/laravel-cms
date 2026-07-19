# V4 Connector Guide

## Overview

The `platform/laravel-cms-connector` package lets any existing Laravel app (5.8 through 12.x) connect to this CMS platform with zero refactoring. Five connection modes are supported, each independently toggleable.

## Installation in Host App

```bash
composer require platform/laravel-cms-connector
php artisan cms-connector:install
```

The install command prompts for:

- `CMS_BASE_URL` — e.g. `https://cms.shopland.test`
- `CMS_TENANT_ID` — the tenant ID on the CMS platform
- `CMS_API_TOKEN` — generated from CMS admin → Connectors → Create
- `CMS_SHARED_SECRET` — for HMAC webhook signing

## Enabling Modes

Edit `config/cms-connector.php` (published by the install command):

```php
'auth_bridge' => ['enabled' => true],
'model_sync' => ['enabled' => true],
'event_bus' => ['enabled' => true],
'embedded' => ['enabled' => false],
'headless' => ['enabled' => true],
```

## Mode 1: SSO Bridge

Lets users signed into the host app auto-sign-in to the CMS without re-entering credentials.

In host app's nav:

```blade
@if(auth()->check())
    <a href="{{ cms_connector_sso_url() }}">Open CMS Admin</a>
@endif
```

The link redirects to the CMS, which verifies the JWT, finds/creates the user, and logs them in.

## Mode 2: Model Sync

Syncs host Laravel models into CMS entries bidirectionally.

In host model:

```php
namespace App\Models;

use Platform\CmsConnector\Contracts\SyncableToCms;

class Product extends Model implements SyncableToCms
{
    public function toCmsEntryData(): array
    {
        return [
            'collection_handle' => 'products',
            'slug' => $this->slug,
            'status' => $this->is_active ? 'published' : 'draft',
            'data' => [
                'title' => $this->name,
                'price' => $this->price,
                'description' => $this->description,
            ],
        ];
    }

    public static function fromCmsEntryData(array $data): static
    {
        return static::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['data']['title'],
                'price' => $data['data']['price'],
                'description' => $data['data']['description'],
                'is_active' => $data['status'] === 'published',
            ]
        );
    }
}
```

In `config/cms-connector.php`:

```php
'model_sync' => [
    'enabled' => true,
    'direction' => 'bidirectional',
    'syncable_models' => [
        \App\Models\Product::class => [
            'collection_handle' => 'products',
            'watch_events' => ['created', 'updated', 'deleted'],
            'debounce_seconds' => 5,
        ],
    ],
    'conflict_resolution' => 'newest_wins',
],
```

## Mode 3: Event Bus

Forward host events to CMS, receive CMS events in host.

In host config:

```php
'event_bus' => [
    'enabled' => true,
    'subscriptions' => [
        'entry.published' => \App\Listeners\CmsEntryPublishedListener::class,
        'form.submitted' => \App\Listeners\CmsFormSubmittedListener::class,
    ],
    'publish' => [
        \App\Events\OrderPlaced::class => 'order.placed',
        \App\Events\UserRegistered::class => 'user.registered',
    ],
],
```

## Mode 4: Embedded Mode

Run the CMS admin inside the host app at `/cms/*`:

```php
// host's routes/web.php
Route::group([
    'prefix' => 'cms',
    'middleware' => ['auth', 'cms-connector.embedded'],
], function () {
    // CMS routes auto-mounted here
});
```

## Mode 5: Headless API Client

Pull CMS content via REST or GraphQL:

```php
// In host's controller
$posts = CmsConnector::collection('blog')
    ->where('status', 'published')
    ->orderBy('published_at', 'desc')
    ->paginate(10);

return view('blog.index', ['posts' => $posts]);
```

## Registering a Connector

1. In CMS admin at `/admin/connectors`, click "Create Connector"
2. Fill in name (e.g. "Shopland E-commerce"), base URL, webhook URL
3. Save — the API token and webhook secret are shown ONCE
4. Copy these into your host app's `.env`:
   ```
   CMS_API_TOKEN=1|abc123...
   CMS_SHARED_SECRET=xyz789...
   ```

## Testing the Connection

In host app:

```bash
php artisan cms-connector:status
```

Expected output:

```
CMS Reachable:       ✓ (response: 87ms)
Authenticated:       ✓ (token valid)

Modes:
  auth_bridge:       ✓ enabled
  model_sync:        ✓ enabled
  headless:          ✓ enabled
  ...
```

## Troubleshooting

- **CmsUnreachableException**: check `CMS_BASE_URL` and verify CMS is up
- **Circuit open**: 5+ failures in 60s; wait or restart queue workers
- **Webhook 401**: HMAC signature mismatch — verify `CMS_EVENT_BUS_SECRET` matches CMS
- **SSO 401**: JWT secret mismatch — verify `CMS_AUTH_BRIDGE_SECRET` matches CMS
