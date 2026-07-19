# Laravel CMS V4 — Multi-Tenant Platform with Multi-Domain, Connector, and Pro Features

A custom-built, Statamic-class Laravel 12 CMS with multi-tenant billing, multi-domain connectivity, external Laravel connector, and enterprise-grade pro features (Workflow Engine, A/B Testing, Collab Editing, AI RAG, Personalization, SAML SSO, SCIM, Audit Streaming).

**📋 For phase-by-phase build status and what's complete vs. remaining, see [`05-V5-UNIFIED-BUILD-PLAN.md`](05-V5-UNIFIED-BUILD-PLAN.md)** — it consolidates all V3 (Phases 0–11) and V4 (Phases 12–19) phases into one document with clear ✅/⚠️/❌ status markers and recommended implementation order.

**This is the full V4 source code** implementing the specification in `04-FIELD-STRUCTURE-SPEC-V4.md`.

## Tech Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12.x |
| PHP | ^8.2 |
| Database | MySQL 8 (default) or Postgres 16 (for pgvector/RAG) |
| Cache/Queue/Session | Redis |
| Multi-tenancy | stancl/tenancy v3 (single-DB mode) |
| Auth | Laravel Sanctum + Fortify + SAML 2.0 + SCIM 2.0 |
| Real-time | Laravel Reverb (WebSocket server) |
| Permissions | spatie/laravel-permission |
| Media | spatie/laravel-medialibrary |
| Activity Log | spatie/laravel-activitylog |

## V4 Features (all feature-flagged, off by default for existing tenants)

### Section 17: Multi-Domain & Subdomain Connectivity Layer
- Per-domain theme override (`domain.theme_id`)
- Per-domain locale binding (`domain.site_id`)
- Wildcard subdomain resolution (`*.example.com`)
- Automated SSL via ACME / Let's Encrypt (`acmephp/core`)
- DNS ownership verification (`spatie/dns`)
- Subdomain-to-collection routing (`domain.default_collection_handle`)
- Per-domain custom headers, robots.txt, favicon, OG image
- Domain status: active / parked / redirect_only

### Section 18: External Laravel Connector (`platform/laravel-cms-connector`)
- SSO Bridge (shared JWT-based single sign-on)
- Bidirectional Model Sync (with conflict resolution)
- Event Bus (HMAC-signed webhooks in both directions)
- Embedded CMS Mode (run CMS admin inside host app)
- Headless API Client (REST + GraphQL)

### Section 19: Professional Features Suite
- **Workflow Engine**: visual DAG with 7 node types (start, approval, condition, action, parallel, wait, end), 7 built-in actions (publish, unpublish, send_email, call_webhook, set_field, add_tag, ask_rag)
- **A/B Testing**: 4 experiment types (entry_variant, template_variant, cta_variant, headline_variant), traffic allocation, statistical significance (two-proportion z-test), auto-promote winner
- **Real-time Collaborative Editing**: Yjs CRDT-based co-editing via Laravel Reverb
- **AI RAG**: per-tenant vector store (pgvector or JSON fallback), citation-grounded answers
- **Personalization & Segments**: 19 condition types, AND/OR/NOT logic, rule priority, 4 target types (field_override, template_override, block_visibility, redirect)
- **SAML 2.0 SSO**: multi-IdP per tenant (Okta, Azure AD, Google Workspace, samltest.id)
- **SCIM 2.0**: standard `/scim/v2/Users` and `/scim/v2/Groups` endpoints
- **Audit Streaming**: 6 destination types (Splunk HEC, Datadog, Elastic, Logtail, HTTP webhook, Syslog) with tamper-evident chain hashing
- **Form Analytics & Lead Scoring**: per-form conversion funnels, weighted lead scoring, sales rep assignment

## Installation

```bash
# 1. Clone and install dependencies
git clone <your-repo> laravel-cms-v4
cd laravel-cms-v4
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with your DB credentials

# 3. Run migrations
php artisan migrate

# 4. Seed test tenants (V3 + V4)
php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder

# 5. Start Reverb WebSocket server (for collab editing)
php artisan reverb:start --debug

# 6. Start the dev server
php artisan serve
```

## Project Structure

```
laravel-cms-v4/
├── app/
│   ├── Domain/                          # DDD domain layer
│   │   ├── Dns/                         # V4: DNS verification + SSL automation
│   │   │   ├── Services/
│   │   │   │   ├── DnsVerificationService.php
│   │   │   │   ├── AcmeClient.php
│   │   │   │   └── SslCertificateManager.php
│   │   │   ├── Providers/               # Cloudflare, Route53, Digitalocean
│   │   │   ├── Jobs/
│   │   │   ├── Events/
│   │   │   └── Exceptions/
│   │   ├── Connector/                   # V4: External Laravel connector
│   │   │   └── Services/
│   │   │       ├── ConnectorManager.php
│   │   │       └── AuthBridgeService.php
│   │   ├── Workflow/                    # V4: Workflow Engine
│   │   │   ├── Services/
│   │   │   │   ├── WorkflowEngine.php
│   │   │   │   ├── ConditionEvaluator.php
│   │   │   │   └── NodeExecutors/       # 7 node type executors
│   │   │   ├── Actions/Builtin/         # 7 built-in actions
│   │   │   ├── Events/
│   │   │   ├── Listeners/
│   │   │   └── Jobs/
│   │   ├── Experiment/                  # V4: A/B Testing
│   │   │   └── Services/ExperimentEngine.php
│   │   ├── Collab/                      # V4: Real-time Collaborative Editing
│   │   ├── Rag/                         # V4: AI RAG
│   │   │   └── Services/
│   │   │       ├── Chunker.php
│   │   │       ├── EmbeddingService.php
│   │   │       ├── VectorSearch.php     # pgvector + JSON fallback
│   │   │       ├── RagService.php
│   │   │       └── CitationFormatter.php
│   │   ├── Personalization/             # V4: Segments & rules
│   │   │   ├── Services/SegmentEvaluator.php
│   │   │   └── Conditions/              # 19 condition types
│   │   ├── Sso/                         # V4: SAML + SCIM
│   │   ├── Audit/                       # V4: Audit streaming
│   │   │   ├── Services/
│   │   │   │   ├── AuditStreamManager.php
│   │   │   │   ├── ChainHasher.php
│   │   │   │   └── Destinations/        # 6 destination types
│   │   │   └── Jobs/DeliverAuditEvent.php
│   │   ├── Content/                     # V3: Entries, Blueprints (stubs)
│   │   └── Theme/                       # V3: Theme Engine (stubs)
│   ├── Http/
│   │   ├── Middleware/                  # V3 + V4 middleware (10+ classes)
│   │   ├── Controllers/
│   │   │   ├── Admin/                   # 12 admin controllers
│   │   │   └── Api/                     # API controllers (connector, webhooks, RAG, etc.)
│   │   ├── Requests/Admin/
│   │   └── Resources/Api/
│   ├── Models/
│   │   ├── Central/                     # Tenant, Domain, SslCertificate, etc.
│   │   └── Tenant/                      # 20+ tenant-scoped models
│   ├── Providers/
│   │   ├── V4ServiceProvider.php        # Wires all V4 services + routes + listeners
│   │   ├── TenancyServiceProvider.php   # Configures stancl/tenancy single-DB mode
│   │   ├── EventServiceProvider.php
│   │   └── CmsServiceProvider.php
│   ├── Support/
│   │   ├── Facades/                     # Workflow, Rag, CmsConnector, Audit, Experiment, Theme, Acme
│   │   ├── Helpers/helpers.php          # tenant_has_feature(), current_domain(), wildcard_segment()
│   │   ├── Traits/
│   │   └── Enums/
│   └── Console/Commands/                # ssl:renew, dns:retry-failed, audit:verify-chain, rag:reindex-stale
├── config/
│   ├── ssl.php                          # ACME + DNS providers
│   ├── ai.php                           # AI providers + RAG config
│   ├── workflow.php                     # Node types + action classes
│   ├── experiments.php
│   ├── collab.php
│   ├── rag.php
│   ├── personalization.php              # 19 condition types
│   ├── sso.php                          # SAML SP config
│   ├── scim.php
│   ├── audit_streams.php                # 6 destination types
│   └── connector.php
├── database/
│   ├── migrations/
│   │   ├── central/                     # 5 V4 central migrations
│   │   └── tenant/                      # 8 V4 tenant migration files (~25 new tables)
│   └── seeders/
├── routes/
│   ├── web.php
│   ├── api.php                          # REST + GraphQL
│   ├── tenant-admin.php                 # 12 resource controllers
│   ├── tenant-web.php                   # Public content + subdomain routing
│   ├── saml.php                         # SAML SP endpoints
│   ├── scim.php                         # SCIM 2.0 endpoints
│   ├── connector.php                    # Connector API
│   └── collab.php                       # Yjs collab WebSocket routes
└── tests/
    ├── Pest.php
    ├── TestCase.php
    ├── Feature/Tenancy/V4TenantIsolationTest.php
    └── Unit/Domain/V4ServicesTest.php
```

## Feature Flag Management

All V4 features are OFF by default. Enable per-tenant via:

```php
// In tinker
$tenant = \App\Models\Central\Tenant::where('slug', 'advmedi')->first();
$data = $tenant->data;
$data['features'] = ['multi_domain', 'workflow_engine', 'ai_rag', 'personalization'];
$tenant->data = $data;
$tenant->save();
```

Or via the admin UI at `/admin/feature-flags` (Owner role only).

## V4 Test Tenants

| Tenant | Purpose |
|---|---|
| AdvMedi (V3) | Multi-domain: advmedi.test, shop.advmedi.test, blog.advmedi.test |
| BitDreamIT (V3) | Single-domain |
| Shopland (V4) | Connector demo (existing Laravel app connecting via composer) |
| EnterpriseCorp (V4) | SAML SSO + SCIM + audit streaming |
| Multilingual Co. (V4) | Wildcard *.multilingual.test + per-domain locale |

## Documentation

The full V4 specification lives in the project root alongside V3 docs:

- `03-FIELD-STRUCTURE-SPEC-V3.md` — V3 schema (foundational)
- `03-AI-BUILD-PROMPTS-V3.md` — V3 build prompts (Phases 0-11)
- `04-FIELD-STRUCTURE-SPEC-V4.md` — V4 schema additions (~30 new tables)
- `04-AI-BUILD-PROMPTS-V4.md` — V4 build prompts (Phases 12-19)
- `04-LARAVEL-INTEGRATION-KIT-V4.md` — Connector package spec
- `04-V3-TO-V4-MIGRATION-GUIDE.md` — Upgrade path

## Running Tests

```bash
php artisan test                      # All tests
php artisan test --filter=V4          # V4 tests only
php artisan test --filter=V4TenantIsolationTest  # Critical isolation tests
```

## Production Deployment

1. Set `SSL_ENV=production` (switches Let's Encrypt from staging to production CA)
2. Configure supervisor for Reverb WebSocket server
3. Configure cron: `* * * * * cd /path && php artisan schedule:run`
4. Configure queue workers: `php artisan queue:work --queue=cms-sync,cms-events,audit-streaming,default`
5. Configure web server (nginx) to proxy `/app` WebSocket connections to Reverb
6. Configure sudoers for SSL webserver reload: `www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx`

## License

MIT
