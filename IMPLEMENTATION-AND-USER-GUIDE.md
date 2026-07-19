# CMS V4 — Implementation Guide & User Manual

**Complete guide for setting up, running, and using the Laravel CMS V4 platform.**

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Installation](#2-installation)
3. [Configuration](#3-configuration)
4. [Running the Project](#4-running-the-project)
5. [Test Tenants & Login Credentials](#5-test-tenants--login-credentials)
6. [Admin Panel Guide](#6-admin-panel-guide)
7. [V4 Feature Usage Guide](#7-v4-feature-usage-guide)
8. [Connector Package Guide](#8-connector-package-guide)
9. [Troubleshooting](#9-troubleshooting)
10. [Production Deployment](#10-production-deployment)

---

## 1. Prerequisites

### Required Software

| Software | Version | Purpose |
|---|---|---|
| PHP | 8.2+ | Backend runtime |
| Composer | 2.x | PHP dependency management |
| Node.js | 18+ | Frontend asset compilation |
| npm | 9+ | JavaScript dependency management |
| MySQL | 8.0+ | Database (or PostgreSQL 16+ for RAG) |
| Redis | 6+ | Cache, queue, sessions |

### PHP Extensions Required

```bash
# Required extensions
php -m | grep -E 'pdo|pdo_mysql|pdo_pgsql|mbstring|xml|ctype|json|bcmath|openssl|curl|fileinfo|tokenizer|gd|zip|redis|pcntl'
```

Install missing extensions (Ubuntu/Debian):
```bash
sudo apt install php8.2-mysql php8.2-pgsql php8.2-redis php8.2-gd php8.2-zip php8.2-curl php8.2-mbstring php8.2-xml php8.2-bcmath
```

### Optional (for V4 features)

- **PostgreSQL 16+ with pgvector** — for AI RAG vector search (MySQL works but is slower for large document sets)
- **MaxMind GeoLite2 database** — for personalization geo-targeting (without it, geo conditions return false)
- **Cloudflare/Route53/DigitalOcean API access** — for wildcard SSL DNS-01 challenge

---

## 2. Installation

### Step 1: Unzip the project

```bash
unzip laravel-cms-v4.zip
cd laravel-cms-v4
```

### Step 2: Install PHP dependencies

```bash
composer install
```

If you encounter memory limit issues:
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

### Step 3: Install JavaScript dependencies

```bash
npm install
```

### Step 4: Create environment file

```bash
cp .env.example .env
php artisan key:generate
```

### Step 5: Configure your `.env` file

Edit `.env` with your local settings (see [Configuration](#3-configuration) below).

### Step 6: Create the database

```sql
-- MySQL
CREATE DATABASE laravel_cms_v4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cms_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL ON laravel_cms_v4.* TO 'cms_user'@'localhost';
FLUSH PRIVILEGES;
```

Or for PostgreSQL (with pgvector for RAG):
```sql
CREATE DATABASE laravel_cms_v4;
CREATE USER cms_user WITH PASSWORD 'your_password';
GRANT ALL ON DATABASE laravel_cms_v4 TO cms_user;
\c laravel_cms_v4
CREATE EXTENSION vector;
```

### Step 7: Run migrations

```bash
php artisan migrate
```

This creates ~77 tables (V3 baseline + V4 features).

### Step 8: Run seeders

```bash
php artisan db:seed
```

This creates:
- 3 billing plans
- 5 test tenants (AdvMedi, BitDreamIT, Shopland, EnterpriseCorp, Multilingual Co.)
- 1 platform super admin user
- 1 admin user per tenant
- Roles & permissions for each tenant
- Sample collections, entries, navigation, forms, globals per tenant
- Foundation theme registered

### Step 9: Create storage symlink

```bash
php artisan storage:link
```

### Step 10: Configure local hosts file

Add test domains to `/etc/hosts` (Linux/Mac) or `C:\Windows\System32\drivers\etc\hosts` (Windows):

```
127.0.0.1   platform.test
127.0.0.1   advmedi.test
127.0.0.1   shop.advmedi.test
127.0.0.1   blog.advmedi.test
127.0.0.1   bitdreamit.test
127.0.0.1   shopland.test
127.0.0.1   enterprise.test
127.0.0.1   multilingual.fr
127.0.0.1   multilingual.de
127.0.0.1   multilingual.bn
127.0.0.1   paris.multilingual.test
127.0.0.1   berlin.multilingual.test
```

---

## 3. Configuration

### Essential `.env` Settings

```env
APP_NAME="Laravel CMS V4"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://platform.test
APP_CENTRAL_DOMAIN=platform.test

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_cms_v4
DB_USERNAME=cms_user
DB_PASSWORD=your_password

# For PostgreSQL + pgvector (RAG)
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session & Cache
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# V4 SSL Automation (optional — for SSL provisioning)
SSL_PROVIDER=letsencrypt
SSL_ENV=staging                    # Use 'staging' for dev, 'production' when live
SSL_RELOAD_CMD="sudo systemctl reload nginx"

# DNS Provider for wildcard certs (pick one)
# CLOUDFLARE_API_TOKEN=
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# DIGITALOCEAN_API_TOKEN=

# AI Providers (required for AI RAG feature)
AI_PROVIDER=openai
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o
OPENAI_EMBEDDING_MODEL=text-embedding-3-small

# Reverb (collaborative editing WebSocket server)
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

# V4 SAML SSO (optional)
SAML_ENABLED=false

# V4 SCIM (optional)
SCIM_ENABLED=false

# V4 AI RAG
RAG_ENABLED=true
RAG_VECTOR_STORE=json               # Use 'pgvector' with PostgreSQL
```

### Generate Reverb Keys

```bash
php artisan reverb:install
```

This generates `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` in `.env`.

---

## 4. Running the Project

### Start all required services

Open 4 terminal windows:

**Terminal 1 — Web server:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Terminal 2 — Queue worker:**
```bash
php artisan queue:work --queue=default,cms-sync,cms-events,audit-streaming --tries=3
```

**Terminal 3 — Reverb WebSocket server (for collab editing):**
```bash
php artisan reverb:start --debug
```

**Terminal 4 — Vite dev server (for frontend hot reload):**
```bash
npm run dev
```

### Quick start (single command)

Create a `start.sh` script:

```bash
#!/bin/bash
echo "Starting CMS V4 services..."

# Kill any existing processes
pkill -f "artisan serve" 2>/dev/null
pkill -f "artisan queue:work" 2>/dev/null
pkill -f "artisan reverb:start" 2>/dev/null

# Start services in background
php artisan serve --host=0.0.0.0 --port=8000 &
php artisan queue:work --queue=default,cms-sync,cms-events,audit-streaming --tries=3 &
php artisan reverb:start --debug &

echo "All services started."
echo "  Web:       http://platform.test:8000"
echo "  Admin:     http://platform.test:8000/admin"
echo "  Telescope: http://platform.test:8000/telescope"
```

```bash
chmod +x start.sh
./start.sh
```

### Verify it's running

1. Visit `http://platform.test:8000` — should see the welcome page listing all test tenants
2. Visit `http://platform.test:8000/up` — should see "OK" health check
3. Visit `http://platform.test:8000/telescope` — Laravel Telescope (dev debugging)

---

## 5. Test Tenants & Login Credentials

### Platform Super Admin

| Field | Value |
|---|---|
| URL | `http://platform.test:8000/admin` |
| Email | `admin@platform.test` |
| Password | `password` |

### Tenant Admins

| Tenant | Domain | Email | Password |
|---|---|---|---|
| AdvMedi | `advmedi.test:8000` | `admin@advmedi.test` | `password` |
| BitDreamIT | `bitdreamit.test:8000` | `admin@bitdreamit.test` | `password` |
| Shopland | `shopland.test:8000` | `admin@shopland.test` | `password` |
| EnterpriseCorp | `enterprise.test:8000` | `admin@enterprisecorp.test` | `password` |
| Multilingual Co. | `multilingual.fr:8000` | `admin@multilingual.test` | `password` |

### V4 Feature Flags per Tenant

| Tenant | Features Enabled |
|---|---|
| AdvMedi | `multi_domain`, `workflow_engine`, `ai_rag`, `personalization` |
| BitDreamIT | `multi_domain` |
| Shopland | `connector`, `multi_domain` |
| EnterpriseCorp | `saml_sso`, `scim_provisioning`, `audit_streaming`, `workflow_engine` |
| Multilingual Co. | `multi_domain`, `ai_rag`, `personalization` |

---

## 6. Admin Panel Guide

### Accessing the Admin Panel

1. Go to `http://{tenant-domain}:8000/admin`
2. Login with the tenant admin credentials above

### Dashboard

Shows:
- Entry stats (total, published, draft)
- Collection count
- User count
- Form count + submissions
- Asset count + storage used
- Domain count
- Recent entries (last 5)
- V4 features enabled for this tenant

### Content Management

#### Entries
- **List**: `/admin/entries` — search, filter by status/collection
- **Create**: `/admin/entries/create`
- **Edit**: `/admin/entries/{id}/edit`
- **Publish**: Click "Publish" button or use the API
- **Schedule**: Set `scheduled_at` date — the `scheduled:make` cron publishes it automatically
- **Revisions**: Every save creates a revision; restore via the revision history
- **Duplicate**: One-click duplicate of any entry

#### Collections
- Collections group entries (like blog posts, products, pages)
- Each collection has a route pattern (e.g. `/{slug}`) and template
- Assign one or more blueprints to a collection

#### Blueprints
- Blueprints define the field structure for entries
- Use the drag-and-drop Blueprint Builder (`/admin/blueprints/{id}/builder`)
- Drag field types from left palette → canvas
- Configure each field in the right panel
- 35+ field types available (text, bard, assets, replicator, etc.)

#### Taxonomies
- Create taxonomies (Categories, Tags)
- Add terms (flat or hierarchical)
- Tag entries with terms via the `terms` field type

#### Globals
- Site-wide settings (site name, tagline, social links, contact info)
- Editable per tenant

#### Navigation
- Create nav menus (Main Menu, Footer)
- Add items (link to entry or custom URL)
- Drag-and-drop nesting

#### Forms
- Create forms with custom fields
- View submissions
- V4: Lead scoring rules, conversion analytics

#### Assets
- Upload images, documents
- Organize in containers and folders
- Image focal point picker
- V4: Dynamic image manipulation

### V4 Feature Management

#### Domains (`/admin/domains`)
- Add/edit domains for the tenant
- Per-domain theme override
- Per-domain locale binding
- Subdomain-to-collection routing
- DNS verification status
- SSL certificate status + manual renewal
- Wildcard domain support (`*.example.com`)

#### Themes (`/admin/themes`)
- List installed themes
- Activate theme for tenant
- Customize (live customizer with real-time preview)
- Theme file editor (Owner only)
- Upload new theme from zip

#### Workflows (`/admin/workflows`)
- Create visual workflow DAGs
- 7 node types: Start, Approval, Condition, Action, Parallel, Wait, End
- 7 built-in actions: Publish Entry, Send Email, Call Webhook, Set Field, Add Tag, Ask RAG
- "My Approvals" queue for pending approvals
- SLA tracking

#### Experiments (`/admin/experiments`)
- A/B test entry variants
- 4 experiment types: entry_variant, template_variant, cta_variant, headline_variant
- Statistical significance calculation (2-proportion z-test)
- Auto-promote winner

#### RAG Playground (`/admin/rag/playground`)
- Ask questions about your published content
- AI retrieves relevant chunks from your vector store
- Returns answers with citations to source entries
- Per-tenant vector store (tenant isolation enforced)

#### Segments (`/admin/segments`)
- Build visitor segments with 19 condition types
- AND/OR/NOT logic
- Dynamic (re-evaluated per request) or static (cached membership)

#### Personalization Rules (`/admin/personalization-rules`)
- 4 target types: entry_field_override, template_override, block_visibility, redirect
- Priority ordering
- Schedule (start/end date)

#### Connectors (`/admin/connectors`)
- Register external Laravel apps to connect via the connector package
- View API token (shown once)
- Configure webhook subscriptions
- View sync activity

#### SAML IdPs (`/admin/saml-idps`)
- Configure SAML 2.0 identity providers (Okta, Azure AD, etc.)
- SP metadata endpoint
- Test login button
- Role mapping from IdP groups

#### SCIM Tokens (`/admin/scim-tokens`)
- Generate SCIM 2.0 bearer tokens for IdP provisioning
- Token shown once on creation

#### Audit Streams (`/admin/audit-streams`)
- Configure audit log streaming to Splunk, Datadog, Elastic, Logtail, HTTP webhook, or Syslog
- Test connection button
- View delivery log + retry failed deliveries
- Chain integrity verification

#### Feature Flags (`/admin/feature-flags`)
- Toggle V4 features on/off per tenant (Owner only)
- All V4 features are OFF by default for new tenants

---

## 7. V4 Feature Usage Guide

### Enabling V4 Features

1. Login as tenant admin (must be Owner role)
2. Go to `/admin/feature-flags`
3. Toggle features on/off
4. Click "Save Feature Flags"

### Multi-Domain Example

**Scenario:** AdvMedi wants `shop.advmedi.test` to show the "products" collection.

1. Login to AdvMedi admin at `advmedi.test:8000/admin`
2. Go to `/admin/domains`
3. Edit `shop.advmedi.test`
4. Set **Routing** → `default_collection_handle` = `products`
5. Save

Now `shop.advmedi.test:8000/` shows the products collection index, and `shop.advmedi.test:8000/{slug}` shows individual products.

### Workflow Example

**Scenario:** Create a 2-step approval workflow for blog posts.

1. Go to `/admin/workflows` → "New Workflow"
2. Name: "Blog Post Approval"
3. Trigger: `entry.submitted_for_review`
4. Trigger Collections: `blog`
5. Build the DAG:
   - Start → Editor Review (approval, assigned to role `editor`)
   - Editor Review → on approve → Admin Review (approval, assigned to role `admin`)
   - Admin Review → on approve → Publish Entry (action)
   - Admin Review → on reject → End (rejected)
6. Save + Activate

When an author creates a blog post and clicks "Submit for Review", the workflow starts. The editor gets an email notification, approves it, then the admin gets notified, approves it, and the entry is auto-published.

### RAG Example

**Scenario:** Ask "What services does AdvMedi offer?"

1. Enable `ai_rag` feature flag for AdvMedi (if not already enabled)
2. Publish some entries with content about services
3. Wait ~30 seconds for RAG indexing (or run `php artisan rag:reindex-stale`)
4. Go to `/admin/rag/playground`
5. Type: "What services does AdvMedi offer?"
6. Click "Ask AI"
7. The system retrieves relevant published entries, sends context to OpenAI, returns an answer with citations

### Personalization Example

**Scenario:** Show a special offer banner to returning visitors from Germany.

1. Enable `personalization` feature flag
2. Go to `/admin/segments` → "New Segment"
3. Name: "Returning German Visitors"
4. Rules (AND):
   - `visit_count` >= 3
   - `geo_country` = "DE"
5. Save
6. Go to `/admin/personalization-rules` → "New Rule"
7. Name: "Show DE Offer Banner"
8. Segment: "Returning German Visitors"
9. Target type: `block_visibility`
10. Target config: `{ "block": "offer_banner", "action": "show" }`
11. Save + Activate

In your theme, wrap the offer banner:
```blade
@personalizeBlock('offer_banner')
    <div class="offer-banner">Special offer for German visitors!</div>
@endPersonalizeBlock
```

### SAML SSO Example

1. Enable `saml_sso` feature flag (EnterpriseCorp tenant)
2. Go to `/admin/saml-idps` → "New IdP"
3. Fill in your IdP details (or use https://samltest.id/ for testing)
4. Get SP metadata from: `http://enterprise.test:8000/saml/metadata/{idp_id}`
5. Upload SP metadata to your IdP
6. Test login at: `http://enterprise.test:8000/saml/login/{idp_id}`

### Audit Streaming Example

1. Enable `audit_streaming` feature flag
2. Go to `/admin/audit-streams` → "New Stream"
3. Name: "Splunk Production"
4. Destination type: `splunk_hec`
5. Config:
   ```json
   {
     "url": "https://your-splunk.splunkcloud.com:8088",
     "token": "your-hec-token",
     "index": "cms_activity"
   }
   ```
6. Click "Test Connection"
7. If successful, save + activate

All activity log entries are now streamed to Splunk.

---

## 8. Connector Package Guide

### Installing the Connector in an External Laravel App

The `laravel-cms-connector/` directory contains the composer package source.

#### Option A: Path-based (for development)

In your external Laravel app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/path/to/laravel-cms-v4/laravel-cms-connector"
        }
    ],
    "require": {
        "platform/laravel-cms-connector": "@dev"
    }
}
```

Then:
```bash
composer require platform/laravel-cms-connector:@dev
```

#### Option B: Publish to Packagist (for production)

1. Push the `laravel-cms-connector/` directory to its own GitHub repo
2. Submit to Packagist
3. In your external app: `composer require platform/laravel-cms-connector`

### Setup in External App

```bash
# Publish config + run migrations
php artisan cms-connector:install

# The install command prompts for:
# - CMS Base URL (e.g. http://cms.example.com)
# - Tenant ID (from CMS admin → Connectors → Create)
# - API Token (from CMS admin → Connectors → Create)
# - Shared Secret (for HMAC webhook signing)
# - SSO Bridge Secret (for JWT signing)

# Enable desired modes in config/cms-connector.php:
# - auth_bridge
# - model_sync
# - event_bus
# - embedded
# - headless
```

### Using Mode 5: Headless API Client

In your external app's controller:

```php
use Platform\CmsConnector\Facades\CmsConnector;

public function blogIndex()
{
    $posts = CmsConnector::collection('blog')
        ->where('status', 'published')
        ->orderBy('published_at', 'desc')
        ->paginate(10);

    return view('blog.index', ['posts' => $posts]);
}
```

### Using Mode 2: Model Sync

In your external app's model:

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
],
```

Now when you create/update/delete a Product in your external app, it automatically syncs to the CMS.

---

## 9. Troubleshooting

### "No application encryption key has been specified"

```bash
php artisan key:generate
```

### "SQLSTATE[HY000] [2002] Connection refused"

- Verify MySQL/PostgreSQL is running: `sudo systemctl status mysql`
- Check `.env` DB credentials
- Test connection: `php artisan db:show`

### "Class 'App\Http\Middleware\X' not found"

```bash
composer dump-autoload
php artisan optimize:clear
```

### Tenancy not working (all domains show same content)

1. Verify the domain exists in `domains` table:
   ```bash
   php artisan tinker
   >>> \App\Models\Central\Domain::where('domain', 'advmedi.test')->first()
   ```
2. Verify `dns_verification_status` = `verified`:
   ```bash
   >>> \App\Models\Central\Domain::where('domain', 'advmedi.test')->update(['dns_verification_status' => 'verified', 'dns_verified_at' => now()])
   ```
3. Verify the domain is NOT in `central_domains` config:
   ```bash
   >>> config('tenancy.central_domains')
   ```
4. Check middleware order in `bootstrap/app.php`

### SSL certificate not issuing

1. Check `ssl_status` in domains table
2. Check `ssl_certificates` table for errors
3. View logs: `tail -f storage/logs/laravel.log | grep -i ssl`
4. For staging mode, ensure `SSL_ENV=staging` in `.env`
5. For DNS-01 challenge, verify your DNS provider API token is correct

### RAG not returning results

1. Verify entries are published (RAG only indexes published entries)
2. Run reindex: `php artisan rag:reindex-stale`
3. Check `rag_documents` table has data:
   ```bash
   php artisan tinker
   >>> \App\Models\Tenant\RagDocument::count()
   ```
4. Verify OpenAI API key is set in `.env`
5. Check rate limits (per-tenant: 200 requests/hour)

### Collab editing not working (WebSocket not connecting)

1. Verify Reverb is running: `php artisan reverb:start --debug`
2. Check `REVERB_*` env vars are set
3. Test WebSocket connection in browser console:
   ```javascript
   let ws = new WebSocket('ws://localhost:8080/app/collab/test');
   ws.onopen = () => console.log('Connected');
   ws.onerror = (e) => console.error('Failed', e);
   ```

### Permission denied on `storage/`

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Queue jobs not processing

1. Verify queue worker is running: `php artisan queue:work`
2. Check failed jobs: `php artisan queue:failed`
3. Retry failed: `php artisan queue:retry all`
4. Check Redis is running: `redis-cli ping`

### "Target class [App\Http\Controllers\Admin\X] does not exist"

The route references a controller that doesn't exist. Check:
```bash
php artisan route:list | grep admin
```

---

## 10. Production Deployment

See `docs/deployment.md` for the full production deployment guide including:

- Nginx configuration
- Supervisor setup (queue workers + Reverb)
- SSL automation
- Cron configuration
- Backup strategy
- Performance optimization
- Monitoring (Telescope, Horizon, Sentry)

### Quick deployment checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Set `SSL_ENV=production` (switch from Let's Encrypt staging to production)
- [ ] Run `php artisan optimize:clear` then `php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache`
- [ ] Configure Supervisor for queue workers
- [ ] Configure Supervisor for Reverb WebSocket server
- [ ] Add cron: `* * * * * cd /path && php artisan schedule:run`
- [ ] Configure nginx (WebSocket proxy for `/app`)
- [ ] Set up sudoers for SSL reload
- [ ] Configure backup strategy
- [ ] Set up monitoring (Sentry, Horizon dashboard)

---

## Quick Reference

### Common Artisan Commands

```bash
# Migrations
php artisan migrate                    # Run all pending migrations
php artisan migrate:rollback           # Rollback last batch
php artisan migrate:refresh --seed     # Reset + reseed

# Cache
php artisan optimize:clear             # Clear all caches
php artisan config:cache               # Cache config
php artisan route:cache                # Cache routes

# Queue
php artisan queue:work                 # Start queue worker
php artisan queue:failed               # List failed jobs
php artisan queue:retry all            # Retry all failed jobs

# V4 SSL
php artisan ssl:renew                  # Renew certs expiring in 30 days
php artisan dns:retry-failed           # Retry pending DNS verification

# V4 RAG
php artisan rag:reindex-stale          # Reindex published entries

# V4 Audit
php artisan audit:verify-chain         # Verify activity log chain integrity
php artisan audit:retry-failed-deliveries  # Retry failed audit deliveries

# V4 Collab
php artisan collab:cleanup-stale-sessions  # Remove stale collab sessions

# V4 Workflow
php artisan workflow:check-sla-breaches   # Check for SLA breaches

# CMS utilities
php artisan cms:install                 # Full installation (migrate + seed)
php artisan cms:create-tenant {name} {slug} --domain={domain}  # Create new tenant
php artisan scheduled:make              # Publish scheduled entries
php artisan site:export --tenant={id}   # Export site as static HTML
php artisan scaffold:collection {name}  # Scaffold a new collection
```

### Default Credentials

| Role | Email | Password |
|---|---|---|
| Platform Super Admin | `admin@platform.test` | `password` |
| Tenant Admin | `admin@{tenant-slug}.test` | `password` |

### Key URLs

| URL | Purpose |
|---|---|
| `http://platform.test:8000` | Welcome page (lists all tenants) |
| `http://platform.test:8000/up` | Health check |
| `http://{tenant}:8000/admin` | Tenant admin panel |
| `http://{tenant}:8000/admin/feature-flags` | Enable/disable V4 features |
| `http://platform.test:8000/telescope` | Laravel Telescope (dev) |
| `http://platform.test:8000/horizon` | Laravel Horizon (queue dashboard) |

---

*This guide covers everything you need to install, run, and use the CMS V4 platform. For architecture details, see `docs/architecture.md`. For deployment, see `docs/deployment.md`. For theming, see `docs/theming-guide.md`. For adding field types, see `docs/adding-fieldtypes.md`.*
