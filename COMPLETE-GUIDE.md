# CMS V6 — Complete Developer & Operations Manual
## The Single Source of Truth for Features, Routes, Setup, and Usage

---

## Table of Contents

1. [Feature List & Details](#1-feature-list--details)
2. [Complete Route Map](#2-complete-route-map)
3. [Full Setup Process](#3-full-setup-process)
4. [Usage Guide](#4-usage-guide)
5. [Documentation Index](#5-documentation-index)

---

## 1. Feature List & Details

This CMS surpasses Statamic Free + Pro. Below is the complete list of features, grouped by module.

### Core Content Management (V3)
*   **Collections & Entries**: Group content (e.g., Blog, Products). Entries support draft/published/scheduled statuses, revisions, and tree structures.
*   **Blueprints**: Drag-and-drop builder for content schemas. Defines which fields appear on an entry.
*   **Field Engine**: 41+ field types (Text, Bard rich text, Assets, Replicator, Grid, Relationship, SEO, etc.).
*   **Taxonomies & Terms**: Categorize and tag entries (flat or hierarchical).
*   **Globals**: Site-wide settings (site name, contact info, social links).
*   **Navigation**: Drag-and-drop menu builder for headers and footers.
*   **Forms & Submissions**: Build custom forms with field types. View submissions in admin.
*   **Assets & Media**: Upload images/files, organize in containers, image focal points.
*   **Image Manipulation**: On-the-fly resize/crop/format conversion via URL params (`/img/{id}?w=800&fm=webp`).

### Platform & Billing (V3)
*   **Multi-Tenant Billing**: Subscription plans, automatic invoice generation, payment gateway integration (Stripe, SSLCommerz, bKash).
*   **Auto-Suspension**: Tenants with overdue invoices are automatically suspended after a configurable grace period.
*   **Revenue Dashboard**: Platform owner console for MRR, upcoming renewals, and tenant management.

### Security & User Management (V3/V6)
*   **Roles & Permissions**: Granular, team-scoped permissions (Owner, Admin, Editor, Author, Contributor, Viewer).
*   **Two-Factor Auth (2FA)**: TOTP-based 2FA with recovery codes.
*   **Passkeys (WebAuthn)**: Passwordless login via Touch ID, Windows Hello, or security keys.
*   **OAuth Login**: Social login via Google, GitHub, GitLab, Facebook, Twitter, LinkedIn, Microsoft.
*   **Impersonation**: Admins can login as other users (fully audit-logged).
*   **Audit Log**: Track all user and system actions with tamper-evident SHA-256 chain hashing.

### Multi-Domain & Connectivity (V4)
*   **Multi-Domain per Tenant**: A single tenant can own multiple domains (e.g., `shop.example.com`, `blog.example.com`).
*   **Per-Domain Theme/Locale**: Each domain can override the tenant's default theme and locale.
*   **Wildcard Subdomains**: `*.example.com` resolves dynamically (e.g., `city.example.com`).
*   **SSL Automation**: Automatic Let's Encrypt certificate provisioning and renewal via ACME.
*   **DNS Verification**: TXT record verification for domain ownership before activation.
*   **External Laravel Connector**: A composer package (`platform/laravel-cms-connector`) allowing external Laravel apps to connect via SSO, Model Sync, Event Bus, or Headless API.

### Professional Features (V4/V6)
*   **Workflow Engine**: Visual DAG builder for content approvals (Start → Editor Review → Admin Review → Publish). 7 node types, 7 built-in actions.
*   **A/B Testing**: Entry variant testing with traffic allocation, statistical significance (z-test), and auto-promote winner.
*   **Real-time Collab Editing**: Yjs/CRDT-based co-editing of Bard/text fields via Laravel Reverb.
*   **AI RAG (Retrieval-Augmented Generation)**: Per-tenant vector store of published entries. Ask questions, get AI answers grounded in your content with citations.
*   **Personalization & Segments**: 19 condition types (geo, device, referrer, visit count, etc.) to show personalized content.
*   **SAML 2.0 SSO**: Enterprise single sign-on (Okta, Azure AD, Google Workspace).
*   **SCIM 2.0 Provisioning**: Automated user provisioning from IdPs.
*   **Audit Streaming**: Stream activity logs to Splunk, Datadog, Elastic, Logtail, or Syslog in real-time.
*   **Form Analytics & Lead Scoring**: Per-form conversion funnels, drop-off tracking, weighted lead scoring.

### Developer Tools (V6)
*   **Dual ID Support**: Configurable UUID v7 (default, time-ordered, globally unique) or BigInt (Snowflake, compact).
*   **Static Site Generator**: Export a tenant's site as a static HTML bundle.
*   **Backup System**: Full platform or per-tenant backups (database + files + themes).
*   **GraphQL API**: Query content via GraphQL (in addition to REST).
*   **Live Preview**: Real-time preview of unsaved entry drafts.
*   **Command Palette**: Cmd/Ctrl+K global search for admin navigation and entries.
*   **White-Label CP**: Per-tenant branding (logo, colors, custom CSS/JS injection).
*   **Search**: Native full-text search (MySQL FULLTEXT, PostgreSQL tsvector).

---

## 2. Complete Route Map

*Note: Tenant routes require tenancy to be initialized via domain.*

### Public Routes (`routes/tenant-web.php`)
| Method | URI | Action | Description |
|---|---|---|---|
| GET | `/` | `EntryController@home` | Home page or default collection index |
| GET | `/{slug}` | `EntryController@collectionShow` | Show entry (subdomain mode) |
| GET | `/{collectionHandle}/{slug}` | `EntryController@collectionShow` | Show entry (standard mode) |
| GET | `/category/{term}` | `EntryController@collectionTerm` | Show entries by term |
| POST | `/forms/{formHandle}/submit` | `FormController@submit` | Submit a form |
| GET | `/robots.txt` | Closure | Robots.txt (per-domain override) |

### Admin Routes (`routes/tenant-admin.php`)
*All prefixed with `/admin`, requires `auth` middleware.*

**Content:**
| Method | URI | Action |
|---|---|---|
| GET | `/entries` | `EntryController@index` |
| POST | `/entries` | `EntryController@store` |
| GET/POST/PUT/DELETE | `/entries/{id}` | `EntryController@show/update/destroy` |
| POST | `/entries/{id}/publish` | `EntryController@publish` |
| GET | `/collections` | `CollectionController@index` |
| GET | `/blueprints` | `BlueprintController@index` |
| GET | `/taxonomies` | `TaxonomyController@index` |
| GET | `/globals` | `GlobalController@index` |
| GET | `/navigations` | `NavigationController@index` |
| GET | `/forms` | `FormController@index` |
| GET | `/assets` | `AssetController@index` |

**V4 Features:**
| Method | URI | Action |
|---|---|---|
| GET | `/domains` | `DomainController@index` |
| GET | `/themes` | `ThemeController@index` |
| GET | `/workflows` | `WorkflowController@index` |
| GET | `/experiments` | `ExperimentController@index` |
| GET | `/rag/playground` | `RagController@playground` |
| GET | `/segments` | `SegmentController@index` |
| GET | `/personalization-rules` | `PersonalizationRuleController@index` |
| GET | `/connectors` | `ConnectorController@index` |
| GET | `/saml-idps` | `SamlIdpController@index` |
| GET | `/scim-tokens` | `ScimTokenController@index` |
| GET | `/audit-streams` | `AuditStreamController@index` |

**Settings:**
| Method | URI | Action |
|---|---|---|
| GET | `/users` | `UserController@index` |
| GET | `/roles` | `RoleController@index` |
| GET | `/billing` | `BillingController@index` |
| GET | `/redirects` | `RedirectController@index` |
| GET | `/imports` | `ImportController@index` |
| GET | `/utilities` | `UtilityController@index` |
| GET | `/feature-flags` | `FeatureFlagController@index` |

### API Routes (`routes/api.php`)
*All prefixed with `/api/v1`, requires Sanctum auth for write operations.*

| Method | URI | Action |
|---|---|---|
| GET | `/collections/{handle}/entries` | `EntryController@index` (published only) |
| POST | `/collections/{handle}/entries` | `EntryController@store` (auth required) |
| GET | `/collections/{handle}/entries/{slug}` | `EntryController@show` |
| PUT | `/collections/{handle}/entries/{slug}` | `EntryController@update` (auth) |
| DELETE | `/collections/{handle}/entries/{slug}` | `EntryController@destroy` (auth) |
| POST | `/graphql` | `GraphQLController@handle` |
| POST | `/rag/ask` | `RagApiController@ask` |
| POST | `/experiments/{id}/convert` | `ExperimentApiController@convert` |
| POST | `/forms/{formId}/analytics-event` | `FormAnalyticsController@track` |

### Connector Routes (`routes/connector.php`)
*All prefixed with `/api/v1/connector`.*

| Method | URI | Action |
|---|---|---|
| POST | `/register` | Register a new connector |
| POST | `/sso/bridge` | SSO JWT verification |
| GET | `/status` | Health check |
| POST | `/webhooks/incoming` | Receive event from host app |

### SAML Routes (`routes/saml.php`)
| Method | URI | Action |
|---|---|---|
| GET | `/saml/metadata/{idpId}` | SP Metadata XML |
| GET | `/saml/login/{idpId}` | Initiate login |
| POST | `/saml/acs` | Assertion Consumer Service |
| GET/POST | `/saml/sls` | Single Logout Service |

### SCIM Routes (`routes/scim.php`)
*All prefixed with `/scim/v2`, requires `scim-auth` (bearer token).*

| Method | URI | Action |
|---|---|---|
| GET/POST | `/Users` | `UserController@index/store` |
| GET/PUT/PATCH/DELETE | `/Users/{id}` | `UserController@show/update/patch/destroy` |
| GET/POST | `/Groups` | `GroupController@index/store` |
| GET/PUT/PATCH/DELETE | `/Groups/{id}` | `GroupController@show/update/patch/destroy` |

### Collab Routes (`routes/collab.php`)
| Method | URI | Action |
|---|---|---|
| GET | `/collab/{sessionId}/connect` | `CollabController@connect` |
| POST | `/collab/{sessionId}/sync` | `CollabController@sync` |
| POST | `/collab/{sessionId}/presence` | `CollabController@presence` |
| POST | `/collab/{sessionId}/force-lock` | `CollabController@forceLock` |

### Platform Owner Routes (Central)
*These are registered in `routes/web.php` or dedicated platform route files, accessible only on the central domain.*

| Method | URI | Action |
|---|---|---|
| GET | `/platform/dashboard` | `Platform\DashboardController@index` |
| GET | `/platform/tenants` | `Platform\TenantController@index` |
| GET | `/platform/billing` | `Platform\BillingController@index` |
| GET | `/platform/plans` | `Platform\PlanController@index` |

---

## 3. Full Setup Process

### Prerequisites
1. PHP 8.2+ with extensions: `pdo_mysql, mbstring, xml, gd, zip, redis, bcmath`
2. MySQL 8.0+ (or PostgreSQL 16+ with pgvector for RAG)
3. Redis 6+
4. Composer 2.x
5. Node.js 18+ & npm

### Installation Steps

1.  **Unzip & Install Dependencies**
    ```bash
    unzip laravel-cms-v4.zip
    cd laravel-cms-v4
    composer install
    npm install
    ```

2.  **Configure Environment**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    Edit `.env`:
    ```env
    APP_URL=http://localhost
    APP_CENTRAL_DOMAIN=localhost

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=laravel_cms_v4
    DB_USERNAME=root
    DB_PASSWORD=your_password

    SESSION_DRIVER=database  # Use database if Redis isn't set up locally
    QUEUE_CONNECTION=database
    CACHE_STORE=database

    CMS_ID_TYPE=uuid_v7
    ```

3.  **Database Setup**
    Create the database in MySQL, then run:
    ```bash
    php artisan migrate:fresh --seed
    php artisan storage:link
    ```

4.  **Local Hosts File**
    Add to `/etc/hosts` (or `C:\Windows\System32\drivers\etc\hosts`):
    ```
    127.0.0.1   platform.test
    127.0.0.1   advmedi.test
    127.0.0.1   shopland.test
    ```

5.  **Start the Server**
    ```bash
    php artisan serve
    ```
    Visit `http://localhost:8000`

### Production Setup
1.  Set `APP_ENV=production`, `APP_DEBUG=false`.
2.  Configure Redis for `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE`.
3.  Configure Supervisor for `php artisan queue:work` and `php artisan reverb:start`.
4.  Add Cron: `* * * * * cd /path && php artisan schedule:run`.
5.  Configure Nginx with WebSocket proxy for `/app` (Reverb).

---

## 4. Usage Guide

### Logging In
*   **Platform Admin**: `http://localhost:8000/admin` → `admin@platform.test` / `password`
*   **Tenant Admin**: `http://advmedi.test:8000/admin` → `admin@advmedi.test` / `password`

### Enabling V4 Features
1. Login as a Tenant Admin (must have `owner` role).
2. Go to `/admin/feature-flags`.
3. Toggle features (Workflow, RAG, Multi-Domain, etc.) and Save.

### Creating Content
1. Go to **Collections** → Create a collection (e.g., "Blog").
2. Go to **Blueprints** → Create a blueprint with fields (Title, Bard body).
3. Assign the blueprint to the collection.
4. Go to **Entries** → Create entry → Select collection → Fill fields → Save/Publish.

### Using AI RAG
1. Enable `ai_rag` in Feature Flags.
2. Set `OPENAI_API_KEY` in `.env`.
3. Publish some entries.
4. Run `php artisan rag:reindex-stale`.
5. Go to `/admin/rag/playground` and ask questions.

### Using Workflows
1. Enable `workflow_engine`.
2. Go to `/admin/workflows` → Create.
3. Set trigger (e.g., `entry.submitted_for_review`).
4. Build the DAG: Start → Approval (role: editor) → Action (publish_entry) → End.
5. When an author submits an entry for review, the editor gets an email.

### Managing Domains (V4)
1. Enable `multi_domain`.
2. Go to `/admin/domains` → Add domain (e.g., `shop.advmedi.test`).
3. Edit the domain to set a theme override or default collection routing.
4. Visit the domain to see the customized site.

---

## 5. Documentation Index

All documentation is in the project root and `docs/` directory.

| File | Purpose |
|---|---|
| `SETUP-GUIDE.md` | Detailed installation and environment setup |
| `IMPLEMENTATION-AND-USER-GUIDE.md` | Feature usage guide (Workflows, RAG, etc.) |
| `CODE-REVIEW-FIXES.md` | Security and correctness fixes applied in V6.1 |
| `05-V5-UNIFIED-BUILD-PLAN.md` | Phase-by-phase build status (all 26 phases) |
| `06-V6-ADVANCED-FEATURES.md` | V6 feature comparison vs Statamic |
| `docs/architecture.md` | System architecture overview |
| `docs/deployment.md` | Production deployment guide (Nginx, Supervisor) |
| `docs/theming-guide.md` | How to create and customize themes |
| `docs/adding-fieldtypes.md` | How to add custom field types |
| `docs/v4/multi-domain-guide.md` | Multi-domain and SSL setup |
| `docs/v4/connector-guide.md` | External Laravel app connector setup |
| `CLAUDE.md` | AI coding agent context and conventions |

### Artisan Command Reference
```bash
# Core
php artisan migrate:fresh --seed     # Reset DB and seed
php artisan optimize:clear           # Clear all caches
php artisan tinker                   # REPL

# Billing
php artisan billing:generate-invoices
php artisan billing:send-reminders
php artisan billing:suspend-overdue
php artisan billing:reactivate-paid

# V4 Operations
php artisan ssl:renew                # Renew SSL certs
php artisan dns:retry-failed         # Retry DNS verification
php artisan rag:reindex-stale        # Reindex RAG vector store
php artisan audit:verify-chain       # Verify audit log integrity
php artisan workflow:check-sla-breaches
php artisan collab:cleanup-stale-sessions

# V6 Backup
php artisan cms:backup               # Create full backup
php artisan cms:backup --tenant={id} # Per-tenant backup
php artisan cms:restore {path}       # Restore from backup
```
