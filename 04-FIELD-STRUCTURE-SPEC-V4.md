# Field & Data Structure Specification — V4
## Custom Laravel CMS + Multi-Tenant Billing Platform
### Multi-Domain & Subdomain Connectivity · Laravel Connector · Professional Features Edition

**For:** AdvMedi (multi-domain tenant) & BitDreamIT (single-domain tenant) + 3 new V4 tenants (Shopland, EnterpriseCorp, Multilingual Co.)
**Prepared for:** Md. Siraj-Ud-Doulla
**Version:** 4.0 — "World-Class Multi-Connectivity" Edition
**Relationship to V3:** DELTA — V4 builds on V3. Keep `03-FIELD-STRUCTURE-SPEC-V3.md` and `03-AI-BUILD-PROMPTS-V3.md` in the repo root alongside this file. Every V4 section is additive; nothing in V3 is removed.

---

**What changed in V4:**

- **Section 17 (NEW): Multi-Domain & Subdomain Connectivity Layer** — per-domain theme override, per-domain locale binding, wildcard subdomain resolution, automated SSL via ACME, DNS ownership verification, subdomain-to-collection routing. Surpasses V3's `HasDomains` with full per-domain configuration matrix.
- **Section 18 (NEW): External Laravel Connector** — a composer-installable package (`platform/laravel-cms-connector`) that lets ANY existing Laravel project (5.8 → 11.x) plug into this platform: SSO bridge, model sync, event bus, embedded mode, headless API client. Zero refactor required on the host project.
- **Section 19 (NEW): Professional Features Suite** — Workflow Engine, A/B Testing, Real-time Collaborative Editing, AI RAG (tenant-grounded AI), Personalization Engine, SCIM/SAML SSO, Audit Log Streaming to SIEM, Form Analytics & Lead Scoring.
- **Section 14 (updated):** Summary table reflecting all new V4 tables (~30 new tables, total ~77 tables).
- **Section 9 (extended):** New domain folders added to the DDD structure: `app/Domain/Workflow/`, `app/Domain/Experiment/`, `app/Domain/Collab/`, `app/Domain/Rag/`, `app/Domain/Personalization/`, `app/Domain/Sso/`, `app/Domain/Audit/`, `app/Domain/Connector/`, `app/Domain/Dns/`.

---

## 0. Stack Decisions (locked, V4 additions in bold)

| Layer | Choice |
|---|---|
| Framework | Laravel 11.x |
| Architecture | Domain-Driven Design (DDD) with Modular Packages |
| Multi-tenancy | `stancl/tenancy` v3, single-database mode + **V4 multi-domain config matrix** |
| Domain identification | `stancl/tenancy` domain identification + **V4 wildcard + DNS verification middleware** |
| Admin UI | Vuexy (Laravel/Blade + Vue 3 + Inertia.js) |
| Frontend Public | Theme Engine (Blade-based) + optional Inertia/Vue headless mode |
| Database | MySQL 8 |
| Cache/Queue/Session | Redis |
| Search | Laravel Scout + Meilisearch |
| Media | Laravel filesystem (S3-compatible) + `spatie/laravel-medialibrary` |
| Auth | Laravel Sanctum/Fortify (admin), tenant-scoped guards + **V4 SAML 2.0 + SCIM 2.0** |
| API | REST (Laravel API Resources) + GraphQL (Lighthouse) |
| Billing | Custom module + Stripe/SSLCommerz/bKash gateway adapters |
| Permissions | `spatie/laravel-permission`, tenant-scoped roles |
| Theme Engine | Custom (V3 Section 15) + **V4 per-domain theme override** |
| Event Bus | Laravel Events + Redis Horizon + **V4 cross-project event bus** |
| Monitoring | Laravel Telescope (dev), Sentry (production) + **V4 SIEM audit streaming** |
| **V4: SSL Automation** | **acmephp/core** for Let's Encrypt ACME client, wildcard cert support |
| **V4: DNS Verification** | **spatie/dns** for TXT/CNAME record lookups |
| **V4: Vector Store** | **pgvector** (Postgres) or **Meilisearch hybrid** for AI RAG embeddings |
| **V4: Realtime Collab** | **Laravel Reverb** (WebSocket server) + **Yjs** for CRDT-based co-editing |
| **V4: SAML** | **scaler-tech/laravel-saml2** for SAML 2.0 SP |
| **V4: SCIM** | **arietimmerman/laravel-scim-server** for SCIM 2.0 user provisioning |
| **V4: Workflow Engine** | **Custom** (visual flow builder, DAG executor) — no external dep |
| **V4: A/B Testing** | **Custom** (variant assignment + traffic splitter) — no external dep |

---

## 0.1 V4 Feature Additions to the Statamic-Parity Checklist

| Feature Area | V4 Feature | V4 Section | Statamic Equivalent |
|---|---|---|---|
| **Multi-Domain** | Per-domain theme override | 17.4 | None (Statamic is single-tenant) |
| | Per-domain locale binding | 17.5 | Multi-Site (Pro) — but per-domain not supported |
| | Wildcard subdomain resolution | 17.6 | None |
| | Automated SSL via ACME | 17.7 | None |
| | DNS ownership verification | 17.8 | None |
| | Subdomain-to-collection routing | 17.9 | None |
| **Connector** | Composer package for external Laravel apps | 18.1 | None |
| | SSO bridge (shared Sanctum token) | 18.4 | None |
| | Bidirectional model sync | 18.5 | None |
| | Cross-project event bus | 18.6 | None |
| | Embedded CMS mode (no separate domain) | 18.7 | None |
| | Headless API client SDK | 18.8 | Content API (Pro) — but no SDK |
| **Pro Features** | Visual Workflow Engine | 19.1 | None |
| | A/B Testing with conversion tracking | 19.2 | None (add-on only) |
| | Real-time Collaborative Editing (Yjs) | 19.3 | None (Statamic is single-editor) |
| | AI RAG (tenant-grounded AI Q&A) | 19.4 | None |
| | Personalization & Segments | 19.5 | None (add-on only) |
| | SAML 2.0 SSO | 19.6 | None (Pro add-on charges extra) |
| | SCIM 2.0 user provisioning | 19.7 | None |
| | Audit Log Streaming to SIEM | 19.8 | None |
| | Form Analytics & Lead Scoring | 19.9 | None |

**Bottom line:** V4 takes the platform from "Statamic-class" to "enterprise-class" — features that Statamic charges extra for or doesn't offer at any price, all built standard.

---

## 17. Multi-Domain & Subdomain Connectivity Layer (NEW in V4)

V3's `HasDomains` trait lets a tenant own multiple domains. V4 turns that into a full per-domain configuration matrix: each domain/subdomain under one tenant can have its own theme, locale, collection routing, SSL state, and access rules — all managed from one tenant admin panel.

### 17.1 Design Philosophy

1. **Domain is a first-class configurable resource** — not just a routing key. Each domain row carries its own configuration layer that overrides tenant defaults.
2. **Wildcard support** — `*.advmedi.test` resolves dynamically; the wildcard segment becomes a route parameter (e.g. `shop`, `blog`, `city-name`).
3. **SSL is automated** — no manual cert provisioning. Platform talks ACME to Let's Encrypt, including wildcard certs via DNS-01 challenge.
4. **DNS ownership is verified before activation** — prevents cybersquatting inside the platform (tenant can't claim a domain they don't control).
5. **Subdomains can route to specific collections** — `shop.advmedi.test` automatically uses the `products` collection's index template; `blog.advmedi.test` uses `posts` collection.
6. **Per-domain analytics** — traffic, top content, conversion data split per domain, not just per tenant.

### 17.2 Table: `domains` (V4 enhanced — replaces V3 version)

The V3 `domains` table is extended with a `config` JSON column and several new structured columns. Existing V3 columns are retained; new columns are nullable for backward compatibility.

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| domain | string, unique | full domain incl. subdomain (e.g. `shop.advmedi.test`, `*.advmedi.test` for wildcard) |
| tenant_id | FK → `tenants.id` | |
| is_primary | boolean | primary domain for the tenant (used in emails, sitemap root, etc.) |
| is_wildcard | boolean, default false | V4: true if `domain` contains `*` |
| wildcard_parent | string, nullable | V4: for wildcard domains, the parent (e.g. `advmedi.test` for `*.advmedi.test`) |
| ssl_status | enum: `pending`, `active`, `failed`, `renewing`, `disabled` | V4: `renewing` and `disabled` added |
| ssl_certificate_id | FK → `ssl_certificates.id`, nullable | V4: link to cert record |
| ssl_expires_at | timestamp, nullable | V4: cert expiry, used by renewal cron |
| dns_verification_status | enum: `unverified`, `pending`, `verified`, `failed` | V4: DNS ownership state |
| dns_verification_token | string, nullable | V4: TXT record value the tenant must publish |
| dns_verified_at | timestamp, nullable | V4 |
| theme_id | FK → `themes.id`, nullable | V4: per-domain theme override (null = use tenant default) |
| site_id | FK → `sites.id`, nullable | V4: per-domain locale/site binding (null = use tenant default) |
| default_collection_handle | string, nullable | V4: subdomain-to-collection routing (e.g. `products` for `shop.*`) |
| route_prefix | string, nullable | V4: optional prefix applied to all routes on this domain (e.g. `eu/` for `eu.advmedi.test`) |
| config | json | V4: catch-all for per-domain config — `redirect_www`, `force_https`, `custom_headers`, `robots_txt_override`, `favicon_override`, `og_image_override` |
| status | enum: `active`, `parked`, `redirect_only` | V4: `parked` = 503, `redirect_only` = always 301 to `redirect_target` |
| redirect_target | string, nullable | V4: URL or domain to redirect to when `status = redirect_only` |
| analytics_property_id | string, nullable | V4: per-domain GA4 property ID |
| last_request_at | timestamp, nullable | V4: updated on every request, used for stale-domain reports |
| created_at / updated_at | timestamp | |

**Indexes:** `(tenant_id)`, `(domain)` unique, `(is_wildcard)`, `(dns_verification_status)`, `(ssl_expires_at)` for renewal cron.

### 17.3 Table: `ssl_certificates` (NEW, central)

One row per issued SSL certificate. Wildcard certs cover many domains; per-domain certs cover one. Certs are reusable across domains that share the same CN or SAN list.

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK → `tenants.id` | tenant that owns the cert |
| common_name | string | e.g. `*.advmedi.test` or `shop.advmedi.test` |
| san_domains | json | array of subject alternative names |
| is_wildcard | boolean | |
| provider | enum: `letsencrypt`, `zerossl`, `custom` | |
| certificate_pem | text (encrypted at rest) | the signed cert chain |
| private_key_pem | text (encrypted at rest) | the private key, encrypted with app key |
| chain_pem | text | intermediate certs |
| issued_at | timestamp | |
| expires_at | timestamp | |
| auto_renew | boolean, default true | |
| last_renewal_attempt | timestamp, nullable | |
| renewal_failure_count | integer, default 0 | blocks further attempts after 5 failures |
| acme_account_id | FK → `acme_accounts.id`, nullable | |
| challenge_type | enum: `http-01`, `dns-01` | `dns-01` required for wildcard |
| status | enum: `active`, `expired`, `revoked`, `renewing`, `failed` | |
| created_at / updated_at | timestamp | |

### 17.4 Table: `acme_accounts` (NEW, central)

ACME accounts are per-tenant (each tenant registers its own Let's Encrypt account so cert issuance is attributed correctly).

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK → `tenants.id` | |
| provider | enum: `letsencrypt`, `zerossl` | |
| email | string | registration contact |
| account_key_pem | text (encrypted) | account private key |
| account_url | string | ACME server's URL for this account |
| status | enum: `active`, `deactivated` | |
| created_at / updated_at | timestamp | |

### 17.5 Table: `dns_verification_jobs` (NEW, central)

Tracks DNS ownership verification attempts. A domain must be DNS-verified before SSL can be issued and before the domain goes live.

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| domain_id | FK → `domains.id` | |
| verification_type | enum: `txt`, `cname` | |
| record_name | string | e.g. `_cms-verify.shop.advmedi.test` |
| record_value | string | the token the tenant publishes |
| attempts | integer, default 0 | how many DNS lookups performed |
| max_attempts | integer, default 10 | give up after this |
| next_attempt_at | timestamp | when to retry the lookup |
| verified_at | timestamp, nullable | |
| failed_at | timestamp, nullable | |
| status | enum: `pending`, `verified`, `failed` | |
| created_at / updated_at | timestamp | |

### 17.6 Per-Domain Configuration Matrix

For a tenant with N domains, the resolution order at request time is:

1. **Domain matching** — exact match on `domains.domain` (e.g. `shop.advmedi.test`).
2. **Wildcard fallback** — if no exact match, look up wildcard domains for the tenant and match the pattern (e.g. `*.advmedi.test`).
3. **Tenant context** — load the tenant record, apply tenant defaults.
4. **Domain overrides** — apply `domain.config`, `domain.theme_id`, `domain.site_id`, `domain.default_collection_handle`, `domain.route_prefix`.
5. **Theme resolution** — if `domain.theme_id` is set, use it; otherwise use `tenant.current_theme_id` (V3 behavior).
6. **Locale resolution** — if `domain.site_id` is set, the request's default site/locale is that site; otherwise tenant default site.
7. **Collection routing** — if `domain.default_collection_handle` is set, the root URL (`/`) of that domain renders the collection's index template instead of the home page.

### 17.7 Middleware Stack (V4 updated)

```
InitializeTenancyByDomain (V3)
  → ResolveWildcardDomain (V4 NEW)
  → PreventAccessFromCentralDomains (V3)
  → VerifyDomainActive (V4 NEW — checks domain.status)
  → EnforceHttps (V4 NEW — if domain.config.force_https, 301 to https)
  → TenantActiveGate (V3)
  → ResolveTheme (V3, but now reads domain.theme_id first)
  → ResolveSite (V4 NEW — reads domain.site_id)
  → auth guard → role/permission checks
  → RequireElevatedSession (where applicable)
```

### 17.8 Wildcard Subdomain Resolution

Wildcard domains follow this resolution flow:

1. Request arrives at `cityX.advmedi.test`.
2. `InitializeTenancyByDomain` (V3) — no exact match found.
3. `ResolveWildcardDomain` (V4) — extracts `cityX` as the wildcard segment, queries `domains WHERE is_wildcard = true AND wildcard_parent = 'advmedi.test'`. Finds the wildcard row.
4. The wildcard segment (`cityX`) is stored in `request()->attributes->set('wildcard_segment', 'cityX')`.
5. Tenant context is loaded from the wildcard domain's `tenant_id`.
6. Routes can read the wildcard segment via `request()->wildcardSegment()` or via route parameter `{wildcard}`.

**Use case:** Multilingual Co. tenant uses `*.multilingual.test` where the wildcard is the city name. Each city renders a localized landing page using the same theme but different content (entries filtered by taxonomy term `city = cityX`).

### 17.9 Subdomain-to-Collection Routing

When a domain has `default_collection_handle` set (e.g. `shop.advmedi.test` → `products`):

- `/` renders the `products` collection's `index` template (lists all product entries).
- `/{slug}` renders the `products` collection's `show` template for the matching entry.
- `/category/{term}` renders the `products` collection's `term` template.
- All admin routes (`/admin/*`) still work normally on the same domain.
- All API routes (`/api/v1/*`) still work normally.

The collection's `route_pattern` field (V3) is used as the URL pattern within the domain. Example: if `products.route_pattern = '/{slug}'`, then `shop.advmedi.test/my-product` resolves to the entry with slug `my-product` in the `products` collection.

### 17.10 SSL Automation Pipeline

The SSL pipeline runs entirely in the background via the queue:

1. **Trigger:** Domain created with `ssl_status = pending`, or existing cert within 30 days of expiry.
2. **DNS verification:** If `dns_verification_status != verified`, dispatch `VerifyDomainDnsJob`. Tenant must have published the TXT record `dns_verification_token` at `record_name`.
3. **ACME order:** Once DNS-verified, dispatch `OrderSslCertificateJob`. Uses `acmephp/core` to talk to Let's Encrypt.
4. **Challenge fulfillment:**
   - `http-01` (per-domain certs only): platform serves the challenge token at `/.well-known/acme-challenge/{token}` on the target domain. Requires the domain's DNS to already point at the platform.
   - `dns-01` (wildcard certs): platform publishes a TXT record at `_acme-challenge.{domain}` via the tenant's DNS provider API (Cloudflare, Route53, DigitalOcean supported; tenant configures API credentials in `tenants.data.dns_provider_config`).
5. **Cert storage:** Once issued, the cert PEM + encrypted private key are stored in `ssl_certificates`. `domains.ssl_certificate_id` is set, `domains.ssl_status = active`, `domains.ssl_expires_at` is set.
6. **Web server reload:** Platform emits a `SslCertificateActivated` event. A listener calls the configured web server reload command (e.g. `sudo systemctl reload nginx` or `caddy reload`).
7. **Auto-renewal:** Daily cron `ssl:renew` checks all certs expiring within 30 days, dispatches `OrderSslCertificateJob` again. After 5 consecutive renewal failures, the cert is marked `failed` and the tenant is notified.

**Config (`config/ssl.php`):**

```php
return [
    'default_provider' => env('SSL_PROVIDER', 'letsencrypt'),
    'providers' => [
        'letsencrypt' => [
            'directory_url' => 'https://acme-v02.api.letsencrypt.org/directory',
            'environment' => env('SSL_ENV', 'production'), // 'staging' for test certs
        ],
        'zerossl' => [
            'directory_url' => 'https://acme.zerossl.com/v2/DV90',
            'api_key' => env('ZEROSSL_API_KEY'),
        ],
    ],
    'dns_providers' => [
        'cloudflare' => ['api_token' => env('CLOUDFLARE_API_TOKEN')],
        'route53' => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY')],
        'digitalocean' => ['token' => env('DIGITALOCEAN_API_TOKEN')],
    ],
    'webserver_reload_cmd' => env('SSL_RELOAD_CMD', 'sudo systemctl reload nginx'),
    'renewal_window_days' => 30,
    'max_renewal_failures' => 5,
];
```

### 17.11 DNS Ownership Verification Flow

Required before a domain can serve traffic or obtain SSL:

1. **Tenant adds domain** in admin panel → row in `domains` with `dns_verification_status = unverified`.
2. **Platform generates token** (random 32-char hex), stores as `dns_verification_token`, creates `dns_verification_jobs` row with `record_name = _cms-verify.{domain}`.
3. **Admin UI shows instructions:** "Publish a TXT record: `_cms-verify.shop.advmedi.test` → `abc123def456...`"
4. **Tenant publishes the record** at their DNS provider.
5. **Platform polls** via `spatie/dns` — every 5 minutes for up to 50 attempts (configurable).
6. **On match:** `dns_verification_status = verified`, `dns_verified_at = now()`. SSL pipeline auto-triggers.
7. **On failure (50 attempts no match):** `dns_verification_status = failed`, admin UI shows the queried record vs. expected value side-by-side so the tenant can debug.

**Wildcard domains** require DNS verification of the parent domain (`advmedi.test`) only, not every subdomain.

### 17.12 Per-Domain Theme Override Resolution

When a request comes in:

1. `ResolveTheme` middleware (V3) is extended in V4.
2. First check: `domain.theme_id` — if set, load that theme.
3. Else: `tenant.current_theme_id` (V3 behavior).
4. Else: `foundation` default theme.

The `ThemeResolver` service's `resolve()` method now accepts an optional `Domain $domain` parameter. If provided, domain theme overrides tenant theme. The view cascade (child → parent → grandparent) works identically.

**Use case:** AdvMedi tenant uses `foundation` theme on `advmedi.test` (corporate site) but uses `ecommerce` theme on `shop.advmedi.test` (online store) — both themes installed on the same tenant, each domain picks its own.

### 17.13 Per-Domain Locale (Site) Binding

V3's `sites` table already supports multi-site/multi-locale entries per tenant. V4 adds the ability to bind a domain to a specific site:

- `domain.site_id = 5` → all requests on this domain default to site 5's locale.
- The locale switcher in the admin panel still allows manual override per entry.
- Public visitors see the locale's content automatically; no `?site=fr` query param needed.
- Sitemap.xml is generated per-domain-per-site.

**Use case:** Multilingual Co. tenant has domains `multilingual.fr` (bound to site `fr-FR`), `multilingual.de` (bound to site `de-DE`), `multilingual.bn` (bound to site `bn-BD`). Each domain shows its locale's content natively.

### 17.14 Per-Domain Custom Headers & Robots.txt

The `domain.config` JSON column supports:

```json
{
  "force_https": true,
  "redirect_www": "non-www",
  "custom_headers": {
    "X-Frame-Options": "DENY",
    "X-Content-Type-Options": "nosniff",
    "Content-Security-Policy": "default-src 'self'; ..."
  },
  "robots_txt_override": "User-agent: *\nDisallow: /private/",
  "favicon_override": "/assets/tenant-2/favicon.ico",
  "og_image_override": "/assets/tenant-2/social-share.png"
}
```

A `ApplyDomainConfig` middleware reads these and applies them to the response.

### 17.15 Admin UI: Domain Management Screen (V4 enhanced)

Replaces V3's simple domain list with a full management interface:

- **Domain list table** — domain, tenant, status, SSL status (with expiry countdown), DNS status, theme, locale, primary flag.
- **Add domain modal** — input domain, pick tenant, choose options (verify DNS now, request SSL now, set as primary).
- **Domain detail page** — tabs:
  - **Overview** — status, last request, traffic graph.
  - **SSL** — cert details, expiry, renewal log, manual renewal button, replace cert upload (for custom certs).
  - **DNS** — verification status, token, instructions, retry button.
  - **Theme** — pick from tenant's installed themes, or "use tenant default".
  - **Locale** — pick from tenant's sites, or "use tenant default".
  - **Routing** — set `default_collection_handle`, `route_prefix`.
  - **Config** — JSON editor for `config` object with schema validation.
  - **Analytics** — per-domain traffic, top pages, referrers.
- **Bulk actions** — force SSL renewal on multiple domains, mark parked, redirect-only.

### 17.16 V4 Domain-Related Permissions (added to V3 Section 6)

- `manage domains` — add/edit/remove domains (V3, retained)
- `manage ssl certificates` — request/renew/replace SSL certs
- `manage dns verification` — trigger DNS verification retries
- `view domain analytics` — view per-domain traffic stats
- `manage domain config` — edit `domain.config` JSON, custom headers, robots.txt

---

## 18. External Laravel Connector (NEW in V4)

A first-party composer package — `platform/laravel-cms-connector` — that lets any existing Laravel project (5.8 through 11.x) connect to this CMS platform with zero refactoring. Five connection modes, all opt-in.

### 18.1 Design Philosophy

1. **Zero host refactor** — install via composer, register `CmsConnectorServiceProvider`, publish config. No code changes to the host's models, controllers, or routes.
2. **Pick only what you need** — five connection modes (SSO, Sync, Event Bus, Embedded, Headless) are independently toggleable.
3. **Respects existing auth** — if the host already has users, the connector bridges them; it does NOT replace the host's auth system.
4. **Tenant-aware** — one host app connects to exactly one tenant on the CMS platform. Multi-tenant host apps can use the connector's `forTenant($id)` method to switch context per request.
5. **Failure-isolated** — if the CMS is down, the host app continues serving its own routes (depending on mode). The connector uses circuit breaker + cache fallback.
6. **Auditable** — every connector action is logged locally and optionally forwarded to the CMS audit log.

### 18.2 Package Structure

```
platform/laravel-cms-connector/
├── composer.json
├── src/
│   ├── CmsConnectorServiceProvider.php
│   ├── ConnectorManager.php              # facade-accessible singleton
│   ├── Console/
│   │   ├── InstallCommand.php            # php artisan cms-connector:install
│   │   ├── SyncModelsCommand.php         # php artisan cms-connector:sync {model}
│   │   └── StatusCommand.php             # php artisan cms-connector:status
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── ShareSessionWithCms.php   # SSO bridge middleware
│   │   │   └── EmbeddedCmsRouting.php    # embedded mode middleware
│   │   └── Controllers/
│   │       ├── SsoRedirectController.php
│   │       ├── SsoCallbackController.php
│   │       └── WebhookReceiverController.php  # receives CMS event bus callbacks
│   ├── Bridges/
│   │   ├── AuthBridge.php                # maps host User → CMS user/session
│   │   ├── ModelSyncBridge.php           # syncs host models → CMS entries
│   │   ├── EventBusBridge.php            # forwards host events → CMS, receives CMS → host
│   │   └── HeadlessClientBridge.php      # REST/GraphQL client for pulling CMS content
│   ├── Config/
│   │   └── config.php                    # published as config/cms-connector.php
│   ├── Contracts/
│   │   ├── SyncableToCms.php             # host models implement this to be syncable
│   │   ├── CmsEventSubscriber.php        # host classes implement to receive CMS events
│   │   └── AuthBridgeInterface.php
│   ├── Exceptions/
│   │   ├── ConnectorNotConfiguredException.php
│   │   ├── CmsUnreachableException.php
│   │   └── SyncConflictException.php
│   └── Support/
│       ├── CmsClient.php                 # HTTP client wrapper (Guzzle + retry + circuit breaker)
│       ├── SignatureVerifier.php         # HMAC signature verify for webhooks
│       └── CacheFallback.php
├── config/
│   └── cms-connector.php                 # default config (copied to host on install)
├── resources/
│   └── views/
│       └── embedded-layout.blade.php     # layout wrapper for embedded mode
├── database/
│   └── migrations/
│       ├── create_cms_connector_sync_state_table.php
│       └── create_cms_connector_event_log_table.php
└── README.md
```

### 18.3 Connection Modes

#### Mode 1: SSO Bridge (`auth_bridge`)

Lets users signed into the host Laravel app auto-sign-in to the CMS admin panel without re-entering credentials.

**Flow:**

1. User is authenticated on host app (any guard — session, Sanctum, custom).
2. User clicks "Open CMS Admin" link in host app's nav.
3. Link goes to `SsoRedirectController` → generates a one-time token (JWT signed with shared secret, expires in 60s), redirects to `https://cms.{tenant}.com/sso/bridge?token=...`.
4. CMS verifies token, looks up the user by email (or creates a CMS user if `auto_create_users = true`), logs them in, redirects to admin dashboard.
5. From then on, the session is a normal CMS session — independent of the host app.
6. Sign-out from the CMS does NOT sign out from the host, and vice versa (configurable).

**Config:**

```php
// config/cms-connector.php
'auth_bridge' => [
    'enabled' => env('CMS_AUTH_BRIDGE_ENABLED', true),
    'shared_secret' => env('CMS_AUTH_BRIDGE_SECRET'), // 32+ char random, shared with CMS
    'cms_sso_url' => env('CMS_SSO_URL', 'https://cms.example.com/sso/bridge'),
    'token_ttl_seconds' => 60,
    'auto_create_users' => true,
    'default_role' => 'editor',
    'user_field_map' => [
        'email' => 'email',
        'name' => 'name',
        'avatar' => 'avatar_url',
    ],
    'sign_out_together' => false,
],
```

**Host-side requirement:** none. The bridge reads from the host's default auth guard. If the host uses a non-standard user model, configure `cms-connector.auth_bridge.user_model`.

#### Mode 2: Bidirectional Model Sync (`model_sync`)

Syncs host Laravel models into CMS entries (or vice versa) using a declarative attribute map.

**Host model implementation:**

```php
// In host app
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
                'image' => $this->primary_image_url,
                'category' => $this->category->name,
            ],
            'taxonomy_terms' => [
                'categories' => [$this->category->slug],
            ],
        ];
    }

    public static function fromCmsEntryData(array $data): static
    {
        // Reverse: when an entry is edited in the CMS, sync back to host model
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

    public function cmsLastSyncAt(): ?Carbon
    {
        return $this->cms_sync_state?->last_synced_at;
    }
}
```

**Config:**

```php
'model_sync' => [
    'enabled' => env('CMS_MODEL_SYNC_ENABLED', true),
    'direction' => 'bidirectional', // 'host_to_cms', 'cms_to_host', 'bidirectional'
    'syncable_models' => [
        \App\Models\Product::class => [
            'collection_handle' => 'products',
            'watch_events' => ['created', 'updated', 'deleted'],
            'debounce_seconds' => 5, // batch rapid updates
        ],
        \App\Models\BlogPost::class => [
            'collection_handle' => 'blog',
            'watch_events' => ['created', 'updated', 'deleted'],
        ],
    ],
    'conflict_resolution' => 'cms_wins', // 'host_wins', 'cms_wins', 'newest_wins', 'manual'
    'queue' => 'cms-sync',
    'retry_attempts' => 3,
],
```

**How it works:**

1. Host model fires Eloquent `created`/`updated`/`deleted` event.
2. Connector's `ModelSyncBridge` listener picks it up, debounces, dispatches `SyncModelToCmsJob`.
3. Job calls `toCmsEntryData()` on the model, builds a `PUT /api/v1/collections/{handle}/entries/{slug}` request, sends to CMS via `CmsClient`.
4. CMS validates against the collection's blueprint, saves the entry, fires `EntryUpdated` event.
5. CMS-side `EventBusBridge` (if enabled) forwards the event back to the host via signed webhook.
6. Host's `WebhookReceiverController` receives the webhook, verifies HMAC signature, calls `fromCmsEntryData()` to update the host model.
7. **Conflict resolution:** if both sides updated the same record since last sync, the configured strategy applies. `newest_wins` compares `updated_at` timestamps. `manual` writes a conflict row to `cms_connector_sync_state` for human review.

**Reverse sync (CMS → host):**

The CMS exposes a webhook subscription endpoint. The host configures webhooks in the CMS admin panel for `entry.updated`, `entry.published`, `entry.deleted` events targeting the host's `/cms-connector/webhook` URL. When an editor saves an entry in the CMS, the webhook fires, the host's `WebhookReceiverController` resolves the matching model class via the `syncable_models` config, calls `fromCmsEntryData()`.

#### Mode 3: Cross-Project Event Bus (`event_bus`)

Lets the host app and the CMS publish/subscribe to each other's domain events without model sync.

**Host publishes events:**

```php
// In host app
event(new \App\Events\OrderPlaced($order));

// Connector auto-forwards to CMS:
// POST /api/v1/webhooks/incoming  (signed with HMAC)
// payload: { event: 'order.placed', data: {...}, source: 'host:shopland', timestamp: ... }
```

**Host subscribes to CMS events:**

```php
// config/cms-connector.php
'event_bus' => [
    'enabled' => true,
    'subscriptions' => [
        'entry.published' => \App\Listeners\CmsEntryPublishedListener::class,
        'entry.updated' => \App\Listeners\CmsEntryUpdatedListener::class,
        'form.submitted' => \App\Listeners\CmsFormSubmittedListener::class,
        'user.invited' => \App\Listeners\CmsUserInvitedListener::class,
    ],
    'publish' => [
        // host events to forward to CMS
        \App\Events\OrderPlaced::class => 'order.placed',
        \App\Events\UserRegistered::class => 'user.registered',
    ],
    'signature_secret' => env('CMS_EVENT_BUS_SECRET'),
    'retry_queue' => 'cms-events',
],
```

**Webhook delivery guarantees:** at-least-once, with retry on 5xx (3 attempts, exponential backoff). The host's `WebhookReceiverController` deduplicates via `event_id` field stored in `cms_connector_event_log`.

#### Mode 4: Embedded CMS Mode (`embedded`)

Runs the CMS as a sub-route inside the host Laravel app — no separate domain required. The host's auth session is shared.

**Setup:**

```php
// host's routes/web.php
Route::group([
    'prefix' => 'cms',
    'middleware' => ['auth', 'cms-connector.embedded'],
], function () {
    // All CMS routes are mounted here automatically by EmbeddedCmsRouting middleware
});
```

**How it works:**

1. The host's `/cms/*` routes are intercepted by `EmbeddedCmsRouting` middleware.
2. The middleware rewrites the request URL (stripping `/cms` prefix), forwards it internally to the CMS's tenant-admin route file.
3. The CMS is configured with `tenancy_identification_mode = 'path'` (instead of `'domain'`) for embedded mode. The tenant is identified by `cms-connector.tenant_id` config value.
4. The host's auth session is bridged into the CMS auth context via `ShareSessionWithCms` middleware — no separate login required.
5. CMS Blade views render inside the host's layout via `embedded-layout.blade.php`, which extends the host's main layout (configurable).

**Use case:** Shopland (e-commerce host app) wants the CMS admin panel at `shopland.test/admin/cms` without building a separate subdomain. Embedded mode delivers that.

**Limitations:**

- Public-facing content (entries, blog posts) is NOT served from the host app — only the admin panel. Use Mode 5 (Headless) for public content.
- Host's auth driver must be session-based. Sanctum token auth is not supported for embedded mode.
- Theme engine's per-domain theme override does NOT apply (there is no domain). The tenant's default theme is used.

#### Mode 5: Headless API Client (`headless`)

Lets the host app pull CMS content via REST or GraphQL API and render it inside the host's own Blade/Vue/Inertia views. No shared DB, no shared session.

**Setup:**

```php
// In host's controller
use Platform\CmsConnector\Facades\CmsConnector;

public function blogIndex()
{
    $posts = CmsConnector::collection('blog')
        ->where('status', 'published')
        ->orderBy('published_at', 'desc')
        ->paginate(10);

    return view('blog.index', ['posts' => $posts]);
}

public function blogShow($slug)
{
    $post = CmsConnector::collection('blog')->findBySlug($slug);
    abort_unless($post, 404);

    // Optional: pull related entries via relationship
    $related = $post->related('related_posts')->take(3);

    return view('blog.show', ['post' => $post, 'related' => $related]);
}
```

**Cache layer:** the connector caches API responses keyed by URL + params. TTL is configurable per collection. Stale-while-revalidate is the default — if the CMS is unreachable, cached responses are served.

**GraphQL support:**

```php
$response = CmsConnector::graphql(<<<'GQL'
    query {
        entries(collection: "blog", status: PUBLISHED) {
            edges {
                node {
                    slug
                    title
                    excerpt
                    publishedAt
                }
            }
        }
    }
GQL);
```

### 18.4 Connector Database Tables (in host app's DB)

#### `cms_connector_sync_state` (host-side, tracks model sync)

| Field | Type | Notes |
|---|---|---|
| id | PK | |
| syncable_type | string | host model class (e.g. `App\Models\Product`) |
| syncable_id | bigint | host model ID |
| cms_entry_id | uuid, nullable | the CMS entry's UUID |
| cms_entry_slug | string, nullable | the CMS entry's slug |
| last_synced_at | timestamp | |
| last_sync_direction | enum: `host_to_cms`, `cms_to_host` | |
| last_sync_status | enum: `success`, `failed`, `conflict` | |
| conflict_data | json, nullable | for manual conflict resolution |
| created_at / updated_at | timestamp | |

**Unique:** `(syncable_type, syncable_id)`.

#### `cms_connector_event_log` (host-side, dedup table for incoming webhooks)

| Field | Type | Notes |
|---|---|---|
| id | PK | |
| event_id | string, unique | CMS-provided event UUID |
| event_type | string | e.g. `entry.published` |
| payload | json | full webhook payload |
| received_at | timestamp | |
| processed_at | timestamp, nullable | null = still processing |
| processing_error | text, nullable | if processing failed |

### 18.5 Connector Configuration (`config/cms-connector.php`)

Full published config:

```php
return [
    'cms_base_url' => env('CMS_BASE_URL', 'https://cms.example.com'),
    'tenant_id' => env('CMS_TENANT_ID'), // the tenant this host connects to
    'api_token' => env('CMS_API_TOKEN'), // Sanctum token for API auth
    'shared_secret' => env('CMS_SHARED_SECRET'), // for HMAC webhook signing + SSO bridge

    'timeout_seconds' => 30,
    'retry_attempts' => 3,
    'circuit_breaker' => [
        'enabled' => true,
        'failure_threshold' => 5,
        'reset_seconds' => 60,
    ],

    'cache' => [
        'enabled' => true,
        'ttl_seconds' => 300,
        'stale_while_revalidate' => true,
        'store' => env('CMS_CACHE_STORE', 'redis'),
    ],

    'auth_bridge' => [/* see Mode 1 */],
    'model_sync' => [/* see Mode 2 */],
    'event_bus' => [/* see Mode 3 */],
    'embedded' => [
        'enabled' => env('CMS_EMBEDDED_ENABLED', false),
        'route_prefix' => 'cms',
        'layout' => 'layouts.app', // host layout to extend
    ],
    'headless' => [
        'enabled' => env('CMS_HEADLESS_ENABLED', true),
        'default_cache_ttl' => 300,
        'collections' => [
            'blog' => ['cache_ttl' => 600],
            'products' => ['cache_ttl' => 60],
        ],
    ],

    'logging' => [
        'channel' => 'stack',
        'level' => 'info',
    ],

    'queue' => 'cms-connector',
];
```

### 18.6 CMS-Side Tables for Connector Support

The CMS platform itself needs one new central table to track registered connectors (for webhook management and audit purposes):

#### `registered_connectors` (NEW, central)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK → `tenants.id` | which tenant this connector belongs to |
| name | string | human label, e.g. "Shopland E-commerce App" |
| connector_type | enum: `laravel`, `wordpress`, `shopify`, `custom` | V4: only `laravel` is supported; rest reserved |
| base_url | string | the host app's URL |
| api_token_id | FK → `personal_access_tokens.id` | the Sanctum token used by this connector |
| webhook_secret | string (encrypted) | shared secret for HMAC signing |
| webhook_url | string, nullable | where the host receives CMS webhook callbacks |
| subscribed_events | json | array of event types the host subscribes to |
| syncable_collections | json | array of collection handles the host syncs with |
| last_seen_at | timestamp, nullable | updated on each API call from this connector |
| is_active | boolean, default true | |
| created_at / updated_at | timestamp | |

### 18.7 CMS-Side API Endpoints (V4 additions)

V3's `/api/v1/*` endpoints are extended with connector-specific routes:

- `POST /api/v1/connector/register` — register a new connector (returns `api_token`, `webhook_secret`)
- `POST /api/v1/connector/sso/bridge` — verify SSO token, return session cookie
- `GET /api/v1/connector/status` — connector health check
- `POST /api/v1/webhooks/incoming` — receive events FROM host app
- `POST /api/v1/webhooks/subscriptions` — host subscribes to CMS events
- `DELETE /api/v1/webhooks/subscriptions/{id}` — unsubscribe
- `GET /api/v1/collections/{handle}/entries` (V3, extended) — supports `X-Connector-Id` header for audit attribution
- `PUT /api/v1/collections/{handle}/entries/{slug}` (V3, extended) — supports `X-Connector-Id` header

All connector API calls are logged to the CMS activity log with `connector_id` attribution.

### 18.8 Connector Permissions (added to V3 Section 6)

- `manage connectors` — register, edit, revoke connectors
- `view connector logs` — view connector API call history
- `manage connector webhooks` — configure webhook subscriptions

---

## 19. Professional Features Suite (NEW in V4)

Eight enterprise-grade features built standard. Each is independently toggleable per tenant via `tenants.data.features` JSON.

### 19.1 Workflow Engine

Visual flow builder for content approval, multi-step review pipelines, conditional automation.

#### 19.1.1 Tables

**`workflows`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| name | string | e.g. "Standard Article Approval" |
| handle | string | machine name |
| description | text, nullable | |
| trigger_event | enum: `entry.created`, `entry.updated`, `entry.submitted_for_review`, `entry.published`, `manual` | what kicks off the workflow |
| trigger_collections | json | array of collection handles this workflow applies to |
| definition | json | the DAG definition (nodes + edges) — see 19.1.2 |
| is_active | boolean | |
| created_by | FK → users.id | |
| created_at / updated_at | timestamp | |

**`workflow_instances`** (tenant-scoped) — one per running workflow execution

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| workflow_id | FK → workflows.id | |
| entry_id | FK → entries.id | the entry being approved |
| current_node_id | string | which node is currently active |
| status | enum: `running`, `completed`, `cancelled`, `failed` | |
| context | json | accumulated context (approver comments, etc.) |
| started_at | timestamp | |
| completed_at | timestamp, nullable | |
| created_at / updated_at | timestamp | |

**`workflow_node_executions`** (tenant-scoped) — one per node that has executed

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| workflow_instance_id | FK → workflow_instances.id | |
| node_id | string | matches `definition.nodes[].id` |
| node_type | enum: `start`, `approval`, `condition`, `action`, `parallel`, `wait`, `end` | |
| executed_by | FK → users.id, nullable | null = automatic |
| started_at | timestamp | |
| completed_at | timestamp, nullable | |
| status | enum: `pending`, `approved`, `rejected`, `skipped`, `error` | |
| comment | text, nullable | approver comment |
| output | json, nullable | node execution output (for conditions: which branch was taken) |
| created_at / updated_at | timestamp | |

#### 19.1.2 Workflow Definition JSON Schema

```json
{
  "nodes": [
    {
      "id": "start",
      "type": "start",
      "next": "review_editor"
    },
    {
      "id": "review_editor",
      "type": "approval",
      "label": "Editor Review",
      "assignee_type": "role",
      "assignee_value": "editor",
      "actions": ["approve", "reject", "request_changes"],
      "on_approve": "review_legal",
      "on_reject": "end_rejected",
      "on_request_changes": "start",
      "sla_hours": 24
    },
    {
      "id": "review_legal",
      "type": "approval",
      "label": "Legal Review",
      "assignee_type": "user",
      "assignee_value": "legal@advmedi.test",
      "actions": ["approve", "reject"],
      "on_approve": "check_high_value",
      "on_reject": "end_rejected",
      "sla_hours": 48
    },
    {
      "id": "check_high_value",
      "type": "condition",
      "label": "High Value Content?",
      "condition": "entry.data.estimated_value > 10000",
      "on_true": "review_ceo",
      "on_false": "publish"
    },
    {
      "id": "review_ceo",
      "type": "approval",
      "assignee_type": "user",
      "assignee_value": "ceo@advmedi.test",
      "on_approve": "publish",
      "on_reject": "end_rejected"
    },
    {
      "id": "publish",
      "type": "action",
      "action": "publish_entry",
      "next": "end_approved"
    },
    {
      "id": "end_approved",
      "type": "end",
      "outcome": "approved"
    },
    {
      "id": "end_rejected",
      "type": "end",
      "outcome": "rejected"
    }
  ]
}
```

#### 19.1.3 Node Types Reference

| Node Type | Behavior |
|---|---|
| `start` | Workflow entry point. No configuration. |
| `approval` | Assigns to user(s) or role. Waits for approve/reject/request_changes action. Optional SLA. |
| `condition` | Evaluates a Twig expression against `entry.*`. Branches to `on_true` / `on_false`. |
| `action` | Executes a server-side action class. Built-in: `publish_entry`, `unpublish_entry`, `send_email`, `call_webhook`, `set_field`, `add_tag`. Custom actions implement `WorkflowActionInterface`. |
| `parallel` | Spawns multiple branches that all must complete before merging. |
| `wait` | Pauses for a duration or until a specific datetime. Useful for scheduled publishing. |
| `end` | Terminal node. Records the workflow outcome. |

#### 19.1.4 Admin UI

- **Workflow Builder** — drag-and-drop canvas (Vue Flow or similar), node palette on left, properties panel on right, JSON preview at bottom.
- **Workflow Instances** — list of running/completed workflows, filter by status, drill into instance detail showing node execution timeline.
- **My Approvals** — current user's pending approvals queue, with one-click approve/reject/request_changes.

#### 19.1.5 Permissions

- `manage workflows` — create/edit/delete workflow definitions
- `approve in workflows` — approve/reject in any workflow where role matches
- `view workflow instances` — view running instances (all tenant users)
- `cancel workflow instances` — cancel running instances (Owner/Admin only)

---

### 19.2 A/B Testing

Per-entry variant testing with traffic split, conversion tracking, auto-promote winner.

#### 19.2.1 Tables

**`experiments`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| name | string | |
| handle | string | |
| description | text, nullable | |
| experiment_type | enum: `entry_variant`, `template_variant`, `cta_variant`, `headline_variant` | |
| entry_id | FK → entries.id, nullable | for `entry_variant` type, the control entry |
| collection_handle | string, nullable | for collection-wide experiments |
| status | enum: `draft`, `running`, `paused`, `completed`, `archived` | |
| traffic_allocation | integer | 0-100, % of visitors included in experiment |
| winning_variant_id | uuid, nullable | set when experiment completes |
| start_at | timestamp | |
| end_at | timestamp, nullable | null = run indefinitely |
| goal_type | enum: `conversion`, `bounce`, `time_on_page`, `scroll_depth`, `custom_event` | |
| goal_config | json | e.g. `{"conversion_form_handle": "newsletter_signup"}` |
| min_sample_size | integer | minimum visitors per variant before stats are calculated |
| confidence_threshold | decimal, default 0.95 | statistical significance threshold |
| created_by | FK → users.id | |
| created_at / updated_at | timestamp | |

**`experiment_variants`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| experiment_id | FK → experiments.id | |
| name | string | e.g. "Control", "Variant A", "Variant B" |
| handle | string | |
| is_control | boolean | |
| weight | integer | 0-100, traffic split weight (control + variants = 100) |
| entry_id | FK → entries.id, nullable | for `entry_variant` — the alternate entry to show |
| template_override | string, nullable | for `template_variant` — alternate theme view path |
| field_overrides | json, nullable | for `headline_variant`, `cta_variant` — `{"title": "New Headline"}` |
| created_at / updated_at | timestamp | |

**`experiment_assignments`** (tenant-scoped) — visitor → variant assignment

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| experiment_id | FK → experiments.id | |
| variant_id | FK → experiment_variants.id | |
| visitor_id | string | anonymous visitor UUID (cookie) |
| user_id | FK → users.id, nullable | if visitor is a logged-in user |
| assigned_at | timestamp | |
| converted_at | timestamp, nullable | when the goal was met |
| conversion_value | decimal, nullable | for revenue-tracking goals |

**Index:** `(experiment_id, visitor_id)` unique — one visitor per experiment.

#### 19.2.2 Assignment Flow

1. Visitor arrives at a URL that's part of an experiment.
2. `AssignExperimentVariant` middleware checks if visitor has existing assignment (via `visitor_id` cookie). If yes, use it.
3. If no, randomly assign to a variant weighted by `weight` field, set cookie, store in `experiment_assignments`.
4. Render the assigned variant (alternate entry, alternate template, or field overrides applied to the control entry).
5. Track goal completion via `trackExperimentConversion` JavaScript helper — fires `POST /api/v1/experiments/{id}/convert`.

#### 19.2.3 Admin UI

- **Experiment Builder** — pick type, set goal, configure variants (select alternate entries from same collection OR define field overrides inline), set traffic allocation and variant weights, set schedule.
- **Experiment Dashboard** — per-variant: visitors, conversions, conversion rate, lift vs. control, statistical confidence. Real-time updating.
- **Auto-promote** — when experiment reaches `min_sample_size` AND `confidence_threshold` for a winning variant, "Promote Winner" button appears. Clicking it: replaces the control entry with the winning variant's content, archives the experiment.

---

### 19.3 Real-time Collaborative Editing (Yjs)

Multi-user simultaneous editing of the same entry, with presence indicators and conflict-free merges via CRDT (Yjs).

#### 19.3.1 Tables

**`collab_sessions`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| entry_id | FK → entries.id | |
| field_handle | string | which field is being collaboratively edited (must be a `bard`, `markdown`, `textarea`, or `text` field) |
| yjs_document_state | longblob | the binary Yjs document state (updated on every edit) |
| last_active_at | timestamp | used for cleanup of stale sessions |
| created_at / updated_at | timestamp | |

**`collab_presence`** (tenant-scoped, ephemeral — also cached in Redis)

| Field | Type | Notes |
|---|---|---|
| id | PK | |
| tenant_id | FK, BelongsToTenant | |
| collab_session_id | FK → collab_sessions.id | |
| user_id | FK → users.id | |
| cursor_position | json | `{ "anchor": 42, "head": 50 }` |
| selection_color | string | assigned color for this user's cursor |
| last_heartbeat_at | timestamp | cleaned up after 30s of no heartbeat |
| created_at / updated_at | timestamp | |

#### 19.3.2 Architecture

1. **WebSocket server:** Laravel Reverb (Laravel 11's first-party WS server).
2. **Yjs sync protocol:** the standard Yjs sync protocol runs over WebSockets. The server holds the canonical Yjs document, clients sync to it.
3. **Persistence:** every 5 seconds (configurable), the server persists the current Yjs document state to `collab_sessions.yjs_document_state`. On entry save, the Yjs document is converted to the field's final format (HTML for `bard`, markdown for `markdown`, plain text for `text`/`textarea`) and saved to `entries.data`.
4. **Awareness:** Yjs awareness protocol handles presence (cursors, selections, "user is typing" indicators).
5. **Conflict resolution:** Yjs CRDTs are mathematically conflict-free. No manual merge needed.

#### 19.3.3 Admin UI

- Field-level: when a `bard` / `markdown` / `textarea` / `text` field has `enable_collab: true` in its blueprint config, the editor loads the Yjs client.
- Presence ribbon above the field: avatars of all currently-active editors, color-coded.
- Cursors in the text area: each user's cursor is shown with their name as a tooltip.
- "Take over" button: forcefully locks the field to one user (Owner only) — use sparingly.

#### 19.3.4 Permissions

- `collaborate on entries` — all editors with edit rights to the entry can join a collab session.
- `force lock entry fields` — Owner only, for the "Take over" button.

---

### 19.4 AI RAG (Retrieval-Augmented Generation)

Per-tenant vector store of published entries. AI Q&A grounded in the tenant's own content, with source citations.

#### 19.4.1 Tables

**`rag_documents`** (tenant-scoped) — one row per chunked text segment

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| entry_id | FK → entries.id | |
| field_handle | string | which field was chunked |
| chunk_index | integer | 0-based chunk within the field |
| chunk_text | text | the actual text chunk (typically 500-1000 tokens) |
| embedding | vector(1536) | V4: pgvector column type; falls back to JSON in MySQL |
| metadata | json | `{"heading": "...", "page_url": "...", "language": "en"}` |
| created_at / updated_at | timestamp | |

**Index:** HNSW index on `embedding` for nearest-neighbor search (pgvector).

**`rag_queries`** (tenant-scoped) — log of all RAG queries (for analytics + audit)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| user_id | FK → users.id, nullable | null = anonymous public visitor |
| query_text | text | the user's question |
| retrieved_document_ids | json | array of `rag_documents.id` used in the answer |
| answer_text | text | the AI's response |
| model_used | string | e.g. `gpt-4o`, `claude-sonnet-4-20250514` |
| prompt_tokens | integer | |
| completion_tokens | integer | |
| latency_ms | integer | |
| feedback_rating | enum: `positive`, `negative`, null | user thumbs up/down |
| created_at | timestamp | |

#### 19.4.2 Architecture

1. **Indexing pipeline:** when an entry is published, dispatch `IndexEntryForRagJob`. Chunks the entry's text fields (title, body, custom fields) into ~500-token segments with overlap. Generates embeddings via the configured AI provider. Stores in `rag_documents`.
2. **Query pipeline:**
   - User submits question via `/api/v1/rag/ask` endpoint OR the admin RAG chat UI.
   - `RagService::ask($tenantId, $question)`:
     1. Generate embedding for the question.
     2. Vector search `rag_documents` for top-K (default 5) similar chunks.
     3. Build a RAG prompt: `Context: {chunks}. Question: {question}. Answer:`.
     4. Call AI provider.
     5. Post-process the answer to add citation links: `According to [Article Title](/blog/article-slug), ...`.
     6. Log to `rag_queries`.
3. **Re-indexing:** when an entry is updated or unpublished, its `rag_documents` rows are deleted and (if still published) re-created.
4. **Tenant isolation:** every query is scoped to `tenant_id`. Cross-tenant RAG leakage is impossible (the SQL query has `WHERE tenant_id = ?`).

#### 19.4.3 Public chat widget

A droppable Vue component `<RagChatWidget />` that tenants can embed on any themed page. Styles inherit from the active theme's `--brand-color` variables. Conversations are stored in `rag_queries` for analytics.

#### 19.4.4 Admin UI

- **Rag Chat Playground** — admin can ask questions and see the retrieved chunks + answer.
- **Rag Index Status** — per-entry: indexed/not indexed, last indexed at, chunk count.
- **Rag Queries Log** — table of all queries with feedback ratings, used to fine-tune prompts.
- **Settings** — choose AI model, embedding model, chunk size, top-K, system prompt customization.

#### 19.4.5 Permissions

- `use rag tools` — access the admin RAG playground
- `manage rag settings` — Owner/Admin only
- `view rag queries log` — Owner/Admin only

---

### 19.5 Personalization & Segments

Show different content variants based on visitor segment.

#### 19.5.1 Tables

**`segments`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| name | string | e.g. "Returning Visitors from Europe" |
| handle | string | |
| description | text, nullable | |
| rules | json | array of condition objects (see 19.5.2) |
| is_dynamic | boolean, default true | dynamic = re-evaluated each request; static = membership cached |
| estimated_size | integer, nullable | cached count of matching visitors |
| created_at / updated_at | timestamp | |

**`segment_visitors`** (tenant-scoped, pivot) — for static segments

| Field | Type | Notes |
|---|---|---|
| id | PK | |
| tenant_id | FK, BelongsToTenant | |
| segment_id | FK → segments.id | |
| visitor_id | string | anonymous visitor UUID |
| user_id | FK → users.id, nullable | |
| matched_at | timestamp | |
| expires_at | timestamp, nullable | null = permanent membership |

**`personalization_rules`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| name | string | e.g. "Show Discount Banner to Returning Visitors" |
| handle | string | |
| segment_id | FK → segments.id | which segment this rule applies to |
| target_type | enum: `entry_field_override`, `template_override`, `block_visibility`, `redirect` | |
| target_config | json | what to do when the rule matches |
| priority | integer | lower = higher priority |
| is_active | boolean | |
| start_at / end_at | timestamp, nullable | schedule |
| created_at / updated_at | timestamp | |

#### 19.5.2 Segment Rule JSON

```json
{
  "logic": "and",
  "conditions": [
    {
      "type": "visit_count",
      "operator": ">=",
      "value": 3
    },
    {
      "type": "geo_country",
      "operator": "in",
      "value": ["DE", "FR", "IT"]
    },
    {
      "type": "referrer",
      "operator": "contains",
      "value": "google"
    },
    {
      "type": "query_param",
      "param": "utm_campaign",
      "operator": "=",
      "value": "summer_sale"
    },
    {
      "type": "user_role",
      "operator": "=",
      "value": "subscriber"
    }
  ]
}
```

**Available condition types:** `visit_count`, `first_visit_at`, `last_visit_at`, `geo_country`, `geo_region`, `geo_city`, `device_type`, `browser`, `referrer`, `landing_page`, `query_param`, `cookie`, `user_role`, `user_tag`, `viewed_entry`, `submitted_form`, `time_of_day`, `day_of_week`, `experiment_variant`.

#### 19.5.3 Rule Application Flow

1. `ApplyPersonalization` middleware (after theme resolution, before controller).
2. Evaluate all segments for the current visitor. Cache result in session.
3. Load all active `personalization_rules` matching the visitor's segments, ordered by `priority`.
4. Apply rules:
   - `entry_field_override` — overrides specific field values on the entry being rendered.
   - `template_override` — renders a different theme view.
   - `block_visibility` — hides/shows named content blocks (Blade `@personalizeBlock('banner')`).
   - `redirect` — redirects to a different URL.

#### 19.5.4 Admin UI

- **Segment Builder** — visual rule builder, live preview of estimated segment size.
- **Personalization Rules** — list rules, drag-drop priority, schedule.
- **Personalization Dashboard** — per-rule: visitors matched, conversions attributed.

---

### 19.6 SAML 2.0 SSO

Enterprise single sign-on via SAML 2.0 service provider. Supports Okta, Azure AD, Google Workspace, OneLogin, Auth0, any SAML 2.0 IdP.

#### 19.6.1 Tables

**`saml_identity_providers`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| name | string | e.g. "Okta Production" |
| entity_id | string | IdP's entity ID |
| metadata_xml | longtext | IdP's metadata XML |
| sso_url | string | login URL |
| slo_url | string, nullable | single logout URL |
| x509_certificate | text | IdP's signing cert |
| attribute_mapping | json | `{"email": "email", "name": "displayName", "groups": "groups"}` |
| role_mapping | json | `{"groups": ["Admins"], "role": "administrator"}` — maps IdP groups to CMS roles |
| is_active | boolean | |
| created_at / updated_at | timestamp | |

**`saml_sessions`** (tenant-scoped, ephemeral)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| user_id | FK → users.id, nullable | null until SAML response is processed |
| request_id | string | SAML AuthnRequest ID |
| relay_state | string, nullable | |
| created_at | timestamp | |
| expires_at | timestamp | 5 minute TTL |

#### 19.6.2 Architecture

Uses `scaler-tech/laravel-saml2` (extended for multi-tenant). Each tenant can configure multiple IdPs.

**Login flow:**

1. User visits `https://{tenant-domain}/saml/login/{idp_id}`.
2. CMS generates AuthnRequest, redirects to IdP's `sso_url`.
3. User authenticates at IdP.
4. IdP POSTs SAML response to `https://{tenant-domain}/saml/acs`.
5. CMS validates response, extracts attributes, looks up user by `email` (creates if `auto_create_users` is on).
6. Maps IdP groups to CMS roles per `role_mapping`.
7. Logs user in, redirects to relay_state or admin dashboard.

**SLO (Single Logout):** supported via `slo_url`. Logout from CMS triggers IdP logout, which triggers logout across all SPs.

#### 19.6.3 Admin UI

- **IdP Configuration** — paste metadata XML OR upload XML file OR enter IdP URL (auto-fetch metadata).
- **Attribute Mapping** — map IdP attributes to CMS user fields.
- **Role Mapping** — map IdP groups/roles to CMS roles.
- **Test Login** — button that opens a new tab with the SAML login flow, reports success/failure.

#### 19.6.4 Permissions

- `manage sso` — Owner only, configure SAML IdPs.

---

### 19.7 SCIM 2.0 User Provisioning

Standard SCIM 2.0 endpoints so enterprise IdPs can auto-provision/deprovision users.

#### 19.7.1 Tables

**`scim_tokens`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| name | string | e.g. "Okta Provisioning Token" |
| token_hash | string | hash of the bearer token (never store plaintext) |
| last_used_at | timestamp, nullable | |
| expires_at | timestamp, nullable | null = no expiry |
| created_at / updated_at | timestamp | |

#### 19.7.2 Endpoints (V4 additions)

Standard SCIM 2.0 endpoints at `/scim/v2/*`, authenticated via bearer token:

- `GET /scim/v2/Users` — list users (supports SCIM filtering: `?filter=userName eq "john@example.com"`)
- `POST /scim/v2/Users` — create user
- `GET /scim/v2/Users/{id}` — get user
- `PUT /scim/v2/Users/{id}` — replace user
- `PATCH /scim/v2/Users/{id}` — patch user (e.g. add/remove from groups)
- `DELETE /scim/v2/Users/{id}` — deactivate user
- `GET /scim/v2/Groups` — list groups (maps to CMS roles)
- `POST /scim/v2/Groups` — create group
- `GET /scim/v2/Groups/{id}` / `PUT` / `PATCH` / `DELETE`

#### 19.7.3 Implementation

Uses `arietimmerman/laravel-scim-server` extended for multi-tenant. The CMS user model is mapped to SCIM `User` resource; CMS roles are mapped to SCIM `Group` resources.

**IdP integration examples:**

- **Okta:** Assign "CMS Application" to a user in Okta → Okta calls `POST /scim/v2/Users` → user is created in CMS with mapped role.
- **Azure AD:** Same flow via Enterprise Application provisioning.
- **Google Workspace:** Same via Google's SCIM client.

#### 19.7.4 Permissions

- `manage scim` — Owner only, generate/revoke SCIM tokens.

---

### 19.8 Audit Log Streaming to SIEM

Stream the platform's activity log to external SIEM systems (Splunk, Datadog, Elastic, Logtail) in real-time.

#### 19.8.1 Tables

**`audit_streams`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| name | string | e.g. "Splunk Production" |
| destination_type | enum: `splunk_hec`, `datadog_logs`, `elastic`, `logtail`, `http_webhook`, `syslog` | |
| destination_config | json | `{"url": "...", "token": "..."}` (encrypted) |
| event_filter | json | `{"types": ["entry.*", "user.*"], "severity": ["warning", "critical"]}` |
| is_active | boolean | |
| last_delivery_at | timestamp, nullable | |
| last_delivery_status | enum: `success`, `failed`, null | |
| last_delivery_error | text, nullable | |
| created_at / updated_at | timestamp | |

**`audit_stream_deliveries`** (tenant-scoped) — for delivery tracking + retry

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| audit_stream_id | FK → audit_streams.id | |
| activity_log_id | FK → activity_log.id | |
| payload | json | what was sent |
| response_status | integer, nullable | HTTP status code |
| response_body | text, nullable | |
| attempts | integer, default 0 | |
| status | enum: `pending`, `delivered`, `failed` | |
| next_attempt_at | timestamp, nullable | |
| created_at / updated_at | timestamp | |

#### 19.8.2 Architecture

1. Every audited action in the platform (V3's `spatie/laravel-activitylog`) fires an `ActivityLogged` event.
2. `StreamToAuditDestinations` listener picks up the event, finds all active `audit_streams` for the tenant whose `event_filter` matches.
3. For each match, creates an `audit_stream_deliveries` row, dispatches `DeliverAuditEventJob`.
4. Job sends the event to the destination via the configured protocol (HTTP POST for Splunk HEC / Datadog / Elastic / Logtail / webhook; RFC 5424 UDP/TCP for syslog).
5. On 2xx response, marks as delivered. On 4xx (permanent error), marks as failed. On 5xx / network error, retries up to 5 times with exponential backoff.
6. **Tamper-evident chain:** every activity log entry includes `previous_hash` and `current_hash` fields (SHA-256 of `id + previous_hash + payload`). Tampering with any entry breaks the chain, detectable via an audit command.

#### 19.8.3 Destination-Specific Formats

- **Splunk HEC:** JSON with `event` field, `source`, `sourcetype=cms_platform`, `index` configurable.
- **Datadog Logs:** JSON with `ddsource='cms'`, `service='laravel-cms'`, `ddtags` from event categories.
- **Elastic:** NDJSON with `_index` configurable, mapping follows Elastic Common Schema (ECS).
- **Logtail:** JSON via their HTTP input.
- **HTTP Webhook:** POST JSON with HMAC signature header `X-Cms-Signature`.
- **Syslog:** RFC 5424 with `facility=local0`, `severity` mapped from event severity.

#### 19.8.4 Admin UI

- **Stream Configuration** — pick destination type, fill in connection details, set event filter, test connection button.
- **Delivery Log** — recent deliveries with status, response body, retry button for failed.
- **Chain Verification** — runs the chain integrity check, reports any broken links.

---

### 19.9 Form Analytics & Lead Scoring

Per-form conversion analytics, drop-off tracking, lead scoring against submitted form data.

#### 19.9.1 Tables (V4 extensions to V3's `forms` and `form_submissions`)

**V4 columns added to `form_submissions`:**

| Field | Type | Notes |
|---|---|---|
| lead_score | integer, nullable | 0-100, computed on submission |
| lead_score_breakdown | json, nullable | `{"email_domain": 20, "company_size": 30, ...}` |
| attribution | json, nullable | `{"utm": {...}, "referrer": "...", "landing_page": "..."}` |
| conversion_path | json, nullable | array of page views before submission |
| is_qualified | boolean, nullable | true if lead_score >= threshold |
| assigned_to | FK → users.id, nullable | sales rep assignment |
| assigned_at | timestamp, nullable | |

**`form_analytics_events`** (tenant-scoped) — granular per-form interaction tracking

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| form_id | FK → forms.id | |
| visitor_id | string | anonymous visitor UUID |
| event_type | enum: `view`, `start`, `field_focus`, `field_blur`, `field_change`, `submit_attempt`, `submit_success`, `submit_error`, `abandon` | |
| field_handle | string, nullable | for field-level events |
| event_data | json, nullable | |
| page_url | string | |
| occurred_at | timestamp | |
| created_at | timestamp | |

**Index:** `(form_id, occurred_at)`, `(visitor_id, form_id)`.

**`form_lead_scoring_rules`** (tenant-scoped)

| Field | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| tenant_id | FK, BelongsToTenant | |
| form_id | FK → forms.id | |
| name | string | |
| rules | json | array of conditions with point values |
| threshold_for_qualified | integer | default 50 |
| created_at / updated_at | timestamp | |

#### 19.9.2 Lead Scoring Rule JSON

```json
{
  "rules": [
    {
      "field": "company_size",
      "operator": ">=",
      "value": 100,
      "points": 30
    },
    {
      "field": "email_domain",
      "operator": "not_in",
      "value": ["gmail.com", "yahoo.com", "outlook.com"],
      "points": 20
    },
    {
      "field": "budget",
      "operator": ">=",
      "value": 10000,
      "points": 25
    },
    {
      "field": "job_title",
      "operator": "in",
      "value": ["CEO", "CTO", "VP", "Director"],
      "points": 25
    }
  ]
}
```

On form submission, `ScoreLead` action runs each rule, sums points, stores `lead_score` + `lead_score_breakdown`. If `lead_score >= threshold_for_qualified`, sets `is_qualified = true` and triggers notifications.

#### 19.9.3 Admin UI

- **Form Analytics Dashboard** — per-form: views, starts (focus on first field), submissions, conversion rate, drop-off points (which field has highest abandon rate).
- **Conversion Funnel** — view → start → field-by-field progression → submit. Visual funnel chart.
- **Leads Table** — all submissions with lead score, qualification status, assignment. Filter by qualified/unqualified, sort by score.
- **Lead Scoring Rule Builder** — visual rule editor with live preview of which existing submissions would qualify.

---

## 14. Summary Table — Full Schema List (V4 updated)

**Central (non-tenant-scoped), V3 retained + V4 additions:**
users, tenants, domains (V4 enhanced), tenant_users, billing_plans, invoices, invoice_line_items, payments, subscriptions, billing_addresses, tax_profiles, platform_settings, oauth_connections, webauthn_credentials, themes, theme_dependencies, **ssl_certificates (V4)**, **acme_accounts (V4)**, **dns_verification_jobs (V4)**, **registered_connectors (V4)**.

**Tenant-scoped (V3 retained + V4 additions):**
sites, blueprints, blueprint_fields, collections, collection_blueprints, entries, entry_revisions, taxonomies, terms, entry_terms, globals, navigations, navigation_items, forms, form_submissions (V4 enhanced), asset_containers, assets, redirects, webhooks, roles, permissions, activity_log, saved_filters, user_column_preferences, user_nav_preferences, import_jobs, theme_customizations, **workflows (V4)**, **workflow_instances (V4)**, **workflow_node_executions (V4)**, **experiments (V4)**, **experiment_variants (V4)**, **experiment_assignments (V4)**, **collab_sessions (V4)**, **collab_presence (V4)**, **rag_documents (V4)**, **rag_queries (V4)**, **segments (V4)**, **segment_visitors (V4)**, **personalization_rules (V4)**, **saml_identity_providers (V4)**, **saml_sessions (V4)**, **scim_tokens (V4)**, **audit_streams (V4)**, **audit_stream_deliveries (V4)**, **form_analytics_events (V4)**, **form_lead_scoring_rules (V4)**.

**Total: ~77 tables** (44 from V2 + 3 from V3 + 30 new in V4).

---

## 9.4 Extension to V3 Section 9 (Directory Structure — V4 additions)

V4 adds the following domain folders under `app/Domain/`:

```
app/Domain/
├── Dns/                              # V4: DNS verification + SSL automation
│   ├── Actions/
│   │   ├── VerifyDomainDns.php
│   │   ├── OrderSslCertificate.php
│   │   ├── RenewSslCertificate.php
│   │   └── ReloadWebserver.php
│   ├── Services/
│   │   ├── DnsVerificationService.php
│   │   ├── AcmeClient.php            # wraps acmephp/core
│   │   ├── SslCertificateManager.php
│   │   └── DnsProviderFactory.php    # cloudflare, route53, digitalocean
│   ├── Providers/                    # DNS provider adapters
│   │   ├── CloudflareProvider.php
│   │   ├── Route53Provider.php
│   │   └── DigitaloceanProvider.php
│   ├── Events/
│   │   ├── DnsVerified.php
│   │   ├── SslCertificateIssued.php
│   │   ├── SslCertificateRenewed.php
│   │   └── SslCertificateFailed.php
│   └── Listeners/
│       └── TriggerSslOnDnsVerification.php
│
├── Connector/                        # V4: External Laravel connector
│   ├── Actions/
│   │   ├── RegisterConnector.php
│   │   ├── HandleIncomingWebhook.php
│   │   ├── DispatchOutgoingWebhook.php
│   │   └── RevokeConnector.php
│   ├── Services/
│   │   ├── ConnectorManager.php
│   │   ├── WebhookSigner.php
│   │   └── ConnectorAuthService.php
│   ├── Events/
│   │   ├── ConnectorRegistered.php
│   │   ├── IncomingWebhookReceived.php
│   │   └── ConnectorRevoked.php
│   └── Listeners/
│       └── ForwardDomainEventsToConnectors.php
│
├── Workflow/                         # V4: Workflow Engine
│   ├── Actions/
│   │   ├── StartWorkflow.php
│   │   ├── AdvanceWorkflow.php
│   │   ├── CancelWorkflow.php
│   │   └── ExecuteWorkflowNode.php
│   ├── Services/
│   │   ├── WorkflowEngine.php
│   │   ├── WorkflowValidator.php
│   │   └── NodeExecutor/
│   │       ├── ApprovalNode.php
│   │       ├── ConditionNode.php
│   │       ├── ActionNode.php
│   │       ├── ParallelNode.php
│   │       └── WaitNode.php
│   ├── Events/
│   │   ├── WorkflowStarted.php
│   │   ├── WorkflowNodeCompleted.php
│   │   ├── WorkflowCompleted.php
│   │   └── ApprovalRequired.php
│   └── Listeners/
│       ├── NotifyApprovers.php
│       └── HandleSlaBreaches.php
│
├── Experiment/                       # V4: A/B Testing
│   ├── Actions/
│   │   ├── CreateExperiment.php
│   │   ├── AssignVisitorToVariant.php
│   │   ├── TrackConversion.php
│   │   └── PromoteWinningVariant.php
│   ├── Services/
│   │   ├── ExperimentEngine.php
│   │   ├── StatisticalSignificance.php
│   │   └── VariantSelector.php
│   └── Events/
│       ├── VisitorAssigned.php
│       ├── ConversionTracked.php
│       └── WinnerPromoted.php
│
├── Collab/                           # V4: Real-time Collaborative Editing
│   ├── Actions/
│   │   ├── StartCollabSession.php
│   │   ├── PersistYjsDocument.php
│   │   └── EndCollabSession.php
│   ├── Services/
│   │   ├── YjsServer.php             # WebSocket handler
│   │   ├── AwarenessBroadcaster.php
│   │   └── DocumentPersister.php
│   └── Events/
│       ├── UserJoinedSession.php
│       ├── UserLeftSession.php
│       └── DocumentPersisted.php
│
├── Rag/                              # V4: AI RAG
│   ├── Actions/
│   │   ├── IndexEntry.php
│   │   ├── RemoveEntryFromIndex.php
│   │   ├── Ask.php
│   │   └── RegenerateEmbeddings.php
│   ├── Services/
│   │   ├── RagService.php
│   │   ├── Chunker.php
│   │   ├── EmbeddingService.php
│   │   ├── VectorSearch.php
│   │   └── CitationFormatter.php
│   ├── DTOs/
│   │   ├── RagQuery.php
│   │   └── RagResponse.php
│   └── Events/
│       ├── EntryIndexed.php
│       └── QueryAnswered.php
│
├── Personalization/                  # V4: Personalization & Segments
│   ├── Actions/
│   │   ├── EvaluateSegments.php
│   │   ├── ApplyPersonalizationRules.php
│   │   └── AddVisitorToSegment.php
│   ├── Services/
│   │   ├── SegmentEvaluator.php
│   │   ├── RuleApplier.php
│   │   └── VisitorProfiler.php
│   └── Conditions/                   # condition type implementations
│       ├── VisitCountCondition.php
│       ├── GeoCountryCondition.php
│       ├── ReferrerCondition.php
│       ├── QueryParamCondition.php
│       ├── UserRoleCondition.php
│       └── ...
│
├── Sso/                              # V4: SAML 2.0 + SCIM 2.0
│   ├── Actions/
│   │   ├── ProcessSamlResponse.php
│   │   ├── MapIdpGroupsToRoles.php
│   │   ├── ProvisionScimUser.php
│   │   └── DeprovisionScimUser.php
│   ├── Services/
│   │   ├── SamlServiceProvider.php
│   │   ├── ScimServer.php
│   │   └── AttributeMapper.php
│   └── Events/
│       ├── SamlLoginSucceeded.php
│       ├── ScimUserProvisioned.php
│       └── ScimUserDeprovisioned.php
│
└── Audit/                            # V4: Audit Streaming
    ├── Actions/
    │   ├── StreamActivityToDestinations.php
    │   ├── DeliverAuditEvent.php
    │   └── VerifyChainIntegrity.php
    ├── Services/
    │   ├── AuditStreamManager.php
    │   ├── ChainHasher.php
    │   └── Destinations/
    │       ├── SplunkHecDestination.php
    │       ├── DatadogLogsDestination.php
    │       ├── ElasticDestination.php
    │       ├── LogtailDestination.php
    │       ├── HttpWebhookDestination.php
    │       └── SyslogDestination.php
    └── Events/
        └── AuditStreamFailed.php
```

**V4 additions to `app/Http/Middleware/`:**

```
app/Http/Middleware/
├── ResolveWildcardDomain.php          # V4
├── VerifyDomainActive.php             # V4
├── EnforceHttps.php                   # V4
├── ResolveSite.php                    # V4
├── ApplyDomainConfig.php              # V4
├── AssignExperimentVariant.php        # V4
├── ApplyPersonalization.php           # V4
└── RequireConnectorAuth.php           # V4 (for connector-restricted endpoints)
```

**V4 additions to `app/Models/Central/`:** `SslCertificate.php`, `AcmeAccount.php`, `DnsVerificationJob.php`, `RegisteredConnector.php`.

**V4 additions to `app/Models/Tenant/`:** `Workflow.php`, `WorkflowInstance.php`, `WorkflowNodeExecution.php`, `Experiment.php`, `ExperimentVariant.php`, `ExperimentAssignment.php`, `CollabSession.php`, `CollabPresence.php`, `RagDocument.php`, `RagQuery.php`, `Segment.php`, `SegmentVisitor.php`, `PersonalizationRule.php`, `SamlIdentityProvider.php`, `SamlSession.php`, `ScimToken.php`, `AuditStream.php`, `AuditStreamDelivery.php`, `FormAnalyticsEvent.php`, `FormLeadScoringRule.php`.

**V4 additions to `config/`:** `ssl.php`, `connector.php`, `workflow.php`, `experiments.php`, `collab.php`, `rag.php`, `personalization.php`, `sso.php`, `scim.php`, `audit_streams.php`.

**V4 additions to `routes/`:** `scim.php` (SCIM 2.0 endpoints), `saml.php` (SAML ACS/SLO endpoints), `connector.php` (connector-facing API endpoints), `collab.php` (WebSocket routes for Yjs).

**V4 additions to `database/migrations/central/`:**
- `2024_01_01_000016_create_ssl_certificates_table.php`
- `2024_01_01_000017_create_acme_accounts_table.php`
- `2024_01_01_000018_create_dns_verification_jobs_table.php`
- `2024_01_01_000019_alter_domains_table_add_v4_columns.php`
- `2024_01_01_000020_create_registered_connectors_table.php`

**V4 additions to `database/migrations/tenant/`:**
- `2024_01_01_100025_create_workflows_table.php`
- `2024_01_01_100026_create_workflow_instances_table.php`
- `2024_01_01_100027_create_workflow_node_executions_table.php`
- `2024_01_01_100028_create_experiments_table.php`
- `2024_01_01_100029_create_experiment_variants_table.php`
- `2024_01_01_100030_create_experiment_assignments_table.php`
- `2024_01_01_100031_create_collab_sessions_table.php`
- `2024_01_01_100032_create_collab_presence_table.php`
- `2024_01_01_100033_create_rag_documents_table.php`
- `2024_01_01_100034_create_rag_queries_table.php`
- `2024_01_01_100035_create_segments_table.php`
- `2024_01_01_100036_create_segment_visitors_table.php`
- `2024_01_01_100037_create_personalization_rules_table.php`
- `2024_01_01_100038_create_saml_identity_providers_table.php`
- `2024_01_01_100039_create_saml_sessions_table.php`
- `2024_01_01_100040_create_scim_tokens_table.php`
- `2024_01_01_100041_create_audit_streams_table.php`
- `2024_01_01_100042_create_audit_stream_deliveries_table.php`
- `2024_01_01_100043_create_form_analytics_events_table.php`
- `2024_01_01_100044_create_form_lead_scoring_rules_table.php`
- `2024_01_01_100045_alter_form_submissions_table_add_v4_columns.php`

**V4 additions to `themes/`:** No structural change. Themes continue to follow V3's `themes/{slug}/` pattern. V4's per-domain theme override simply allows different domains to point at different existing themes.

---

## Notes on V4 Implementation Strategy

- **Phasing:** V4 features are split across Phases 12-19 in `04-AI-BUILD-PROMPTS-V4.md`. Each phase is self-contained; phases can be built in parallel by separate developers.
- **Feature flags:** Every V4 feature is wrapped in a feature flag in `tenants.data.features.{feature_name}`. New tenants default to all V4 features OFF; turn on per-tenant as needed. This keeps the platform stable for existing tenants during rollout.
- **Backward compatibility:** V3 code paths continue to work unchanged. The V4 domain middleware stack adds new middleware AFTER V3's; nothing is removed. A tenant with no V4 features enabled gets exactly V3 behavior.
- **Performance considerations:**
  - pgvector for RAG (Postgres required for tenants using RAG; MySQL tenants get a JSON-based fallback with brute-force search — works for <50k documents).
  - Reverb WebSocket server for collab editing — separate process, scaled independently.
  - Audit streaming is async via queue — no impact on request latency.
  - SSL automation is async via queue — no impact on request latency.
- **Cost considerations:**
  - Let's Encrypt is free, no per-cert cost.
  - AI RAG uses tenant-configured AI provider; tenant pays for their own usage.
  - Reverb can run on the same server for small deployments; large deployments need a dedicated WS server.

---

*End of V4 Field Structure Specification. Companion files: `04-AI-BUILD-PROMPTS-V4.md`, `04-LARAVEL-INTEGRATION-KIT-V4.md`, `04-V3-TO-V4-MIGRATION-GUIDE.md`. Keep all V3 and V4 files in the project root for the AI coding agent to reference.*
