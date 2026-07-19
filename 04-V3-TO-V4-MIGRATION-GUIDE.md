# V3 → V4 Migration Guide

## Upgrading Your Existing V3 Laravel CMS Platform to V4

**Version:** 4.0
**Audience:** Developers who have already built (or partially built) a V3 platform and want to upgrade to V4.
**Estimated time:** 2-4 days for a fully-built V3 platform (all 12 V3 phases complete). Less if V3 is partially built.
**Risk level:** Low — V4 is purely additive. No V3 functionality is removed or broken. All V4 features are feature-flagged OFF by default.

---

## 1. Pre-Upgrade Checklist

Before starting the V4 upgrade, verify your V3 platform is in a known-good state:

- [ ] All V3 phases (0–11) complete and verified
- [ ] All V3 Pest tests pass (100+ tests)
- [ ] Both V3 test tenants (AdvMedi, BitDreamIT) function correctly with their themes
- [ ] `composer dump-autoload` runs without errors
- [ ] `php artisan migrate:status` shows all V3 migrations as "ran"
- [ ] Database backup taken (you'll be running ~25 new migrations)
- [ ] Git working tree is clean (commit or stash any in-progress work)
- [ ] You have the 4 V4 spec files in the project root:
  - `04-FIELD-STRUCTURE-SPEC-V4.md`
  - `04-AI-BUILD-PROMPTS-V4.md`
  - `04-LARAVEL-INTEGRATION-KIT-V4.md`
  - `04-V3-TO-V4-MIGRATION-GUIDE.md` (this file)

---

## 2. Upgrade Strategy Overview

V4 is **purely additive** — every change is either:
1. A new table (no impact on existing tables)
2. A new column on an existing table (nullable, defaults to null/false, no impact on existing rows)
3. A new domain folder under `app/Domain/` (no impact on V3 code)
4. A new middleware added to the stack (only active when feature flag is on)
5. A new config file (no impact on existing config)
6. A new route file (only loaded when feature flag is on)

This means you can:
- **Upgrade gradually** — implement V4 phases one at a time
- **Keep V3 tenants unchanged** — they get exactly V3 behavior until you explicitly enable V4 features per-tenant
- **Roll back easily** — set feature flags back to off, V4 features disappear

---

## 3. Phase-by-Phase Upgrade Order

The recommended upgrade order matches V4's phase numbering. Each phase takes ~0.5 to 1 day for an experienced Laravel developer using the AI build prompts.

| Order | V4 Phase | Duration | Dependencies | Can Skip? |
|---|---|---|---|---|
| 1 | Phase 12: Multi-Domain & Subdomain Layer | 1 day | None (foundational for V4) | No — later phases assume it |
| 2 | Phase 13: External Laravel Connector | 1.5 days | Phase 12 (for tenant context) | Yes, if no external Laravel apps to connect |
| 3 | Phase 14: Workflow Engine | 1 day | Phase 12 | Yes |
| 4 | Phase 15: A/B Testing + Collab Editing | 1.5 days | Phase 12 | Yes (these are independent features) |
| 5 | Phase 16: AI RAG + Personalization | 1.5 days | Phase 12 | Yes |
| 6 | Phase 17: SAML SSO + SCIM + Audit Streaming | 2 days | Phase 12 | Yes |
| 7 | Phase 18: Form Analytics & Lead Scoring | 0.5 day | Phase 12 | Yes |
| 8 | Phase 19: Polish & Final Testing | 1 day | All above | No — mandatory finalization |

**Total estimated time:** ~9 developer-days for full V4 implementation.

---

## 4. Step 1: Add V4 Spec Files to Project Root

Copy these 4 files to your Laravel project root:

```
your-laravel-cms/
├── 03-FIELD-STRUCTURE-SPEC-V3.md    (already there from V3)
├── 03-AI-BUILD-PROMPTS-V3.md        (already there from V3)
├── 04-FIELD-STRUCTURE-SPEC-V4.md    (NEW — copy from /home/z/my-project/download/)
├── 04-AI-BUILD-PROMPTS-V4.md        (NEW — copy from /home/z/my-project/download/)
├── 04-LARAVEL-INTEGRATION-KIT-V4.md (NEW — copy from /home/z/my-project/download/)
├── 04-V3-TO-V4-MIGRATION-GUIDE.md   (NEW — copy from /home/z/my-project/download/)
└── CLAUDE.md                         (update with V4 additions per V4 prompts doc)
```

---

## 5. Step 2: Update `CLAUDE.md`

Append the V4 global context block from `04-AI-BUILD-PROMPTS-V4.md` to your existing `CLAUDE.md`. This ensures your AI coding agent (Claude Code, Cursor, etc.) has V4 context when you start implementing phases.

---

## 6. Step 3: Install V4 Composer Dependencies

V4 adds several new composer dependencies. Install them all upfront so you don't have to context-switch during phase implementation:

```bash
# SSL automation
composer require acmephp/core

# DNS verification
composer require spatie/dns

# Real-time collab
composer require laravel/reverb
composer require phadej/yjs  # or alternative Yjs PHP library

# SAML SSO
composer require scaler-tech/laravel-saml2

# SCIM 2.0
composer require arietimmerman/laravel-scim-server
composer require tmilos/scim-filter-parser

# Workflow engine (Twig expression evaluator)
composer require twig/twig

# Audit streaming (HTTP client already in Laravel — no new dep needed)

# AI RAG (optional, if using pgvector)
# Note: requires Postgres. If on MySQL, no new dep needed (JSON fallback).
# composer require pgvector/laravel  # only if using Postgres

# Dev dependencies
composer require pestphp/pest-plugin-mock --dev
```

After each `composer require`, run `composer dump-autoload` and verify `php artisan` still works.

---

## 7. Step 4: Implement V4 Phases (in Order)

For each V4 phase, follow the workflow:

1. **Open the phase prompt** in `04-AI-BUILD-PROMPTS-V4.md`.
2. **Copy the phase's task block** into your AI coding agent (Claude Code, Cursor, etc.).
3. **The agent will:**
   - Read the relevant V4 spec sections
   - Create migrations, models, services, controllers
   - Implement the UI
   - Write Pest tests
4. **Run the verification checklist** at the end of each phase.
5. **Commit** with message like `feat(v4): phase 12 — multi-domain layer`.
6. **Move to next phase.**

### Critical Phase Notes

#### Phase 12 (Multi-Domain) — Special Attention Required

This phase alters the existing `domains` table (from V3) to add V4 columns. The migration MUST be:

1. **Non-destructive** — all new columns are nullable, with sensible defaults
2. **Idempotent** — re-running doesn't break anything
3. **Backward-compatible** — V3 code paths continue to work because they only read V3 columns

The V4 `alter_domains_table_add_v4_columns` migration should:

```php
public function up(): void
{
    Schema::table('domains', function (Blueprint $table) {
        $table->boolean('is_wildcard')->default(false)->after('is_primary');
        $table->string('wildcard_parent')->nullable()->after('is_wildcard');
        $table->uuid('ssl_certificate_id')->nullable()->after('ssl_status');
        $table->timestamp('ssl_expires_at')->nullable()->after('ssl_certificate_id');
        $table->string('dns_verification_status')->default('unverified')->after('ssl_expires_at');
        $table->string('dns_verification_token', 64)->nullable()->after('dns_verification_status');
        $table->timestamp('dns_verified_at')->nullable()->after('dns_verification_token');
        $table->uuid('theme_id')->nullable()->after('dns_verified_at');
        $table->uuid('site_id')->nullable()->after('theme_id');
        $table->string('default_collection_handle')->nullable()->after('site_id');
        $table->string('route_prefix')->nullable()->after('default_collection_handle');
        $table->json('config')->nullable()->after('route_prefix');
        $table->string('status')->default('active')->after('config');
        $table->string('redirect_target')->nullable()->after('status');
        $table->string('analytics_property_id')->nullable()->after('redirect_target');
        $table->timestamp('last_request_at')->nullable()->after('analytics_property_id');

        $table->foreign('ssl_certificate_id')->references('id')->on('ssl_certificates')->nullOnDelete();
        $table->foreign('theme_id')->references('id')->on('themes')->nullOnDelete();
        // site_id foreign key added after sites table exists (it already does in V3)
        $table->index('is_wildcard');
        $table->index('dns_verification_status');
        $table->index('ssl_expires_at');
    });
}

public function down(): void
{
    Schema::table('domains', function (Blueprint $table) {
        $table->dropForeign(['ssl_certificate_id']);
        $table->dropForeign(['theme_id']);
        $table->dropIndex(['is_wildcard']);
        $table->dropIndex(['dns_verification_status']);
        $table->dropIndex(['ssl_expires_at']);
        $table->dropColumn([
            'is_wildcard', 'wildcard_parent', 'ssl_certificate_id', 'ssl_expires_at',
            'dns_verification_status', 'dns_verification_token', 'dns_verified_at',
            'theme_id', 'site_id', 'default_collection_handle', 'route_prefix',
            'config', 'status', 'redirect_target', 'analytics_property_id',
            'last_request_at',
        ]);
    });
}
```

**Existing V3 domains** will have:
- `is_wildcard = false` (correct — V3 had no wildcard support)
- `ssl_status = 'pending'` (V3 default — V4 will pick this up and try to issue SSL)
- `dns_verification_status = 'unverified'` (V4 default — see Step 5 below for handling)
- All other V4 columns = null (correct — no override set)

**Important:** V3 domains will be marked `dns_verification_status = 'unverified'`. This is fine because V3 didn't enforce DNS verification. V4's `VerifyDomainActive` middleware should treat `dns_verification_status = unverified` as "allowed" for backward compat with V3 domains, OR you can manually mark all V3 domains as `verified` via a one-time script:

```php
// Run once after Phase 12 migration
DB::table('domains')->whereNull('dns_verified_at')->update([
    'dns_verification_status' => 'verified',
    'dns_verified_at' => now(),
]);
```

#### Phase 13 (Connector) — Two Repositories Involved

This phase creates the `platform/laravel-cms-connector` package as a **separate repository**. The package is then `composer require`d by:
1. Your CMS platform (for the CMS-side connector endpoints)
2. Any external Laravel apps that want to connect

Implementation steps:

1. **Create the package repo** (separate from main CMS):
   ```bash
   mkdir laravel-cms-connector && cd laravel-cms-connector
   git init
   # Implement package per 04-LARAVEL-INTEGRATION-KIT-V4.md
   git push origin main
   ```

2. **In your CMS platform repo:**
   ```bash
   composer require platform/laravel-cms-connector:@dev
   # OR if not on Packagist yet:
   composer config repositories.cms-connector path ../laravel-cms-connector
   composer require platform/laravel-cms-connector:@dev
   ```

3. **Implement CMS-side endpoints** per Phase 13 prompt.

#### Phase 15 (Collab Editing) — Reverb Server Setup

Laravel Reverb is a separate process. Set it up:

1. **Install:** `composer require laravel/reverb`
2. **Publish config:** `php artisan reverb:install`
3. **Add to `.env`:**
   ```
   REVERB_HOST=127.0.0.1
   REVERB_PORT=8080
   REVERB_SCHEME=http
   REVERB_APP_ID=your-cms-app
   REVERB_APP_KEY=your-key
   REVERB_APP_SECRET=your-secret
   ```
4. **For local dev:** run `php artisan reverb:start --debug` in a separate terminal.
5. **For production:** use Supervisor to keep Reverb running:
   ```ini
   [program:reverb]
   command=php /path/to/artisan reverb:start
   numprocs=1
   autostart=true
   autorestart=true
   user=www-data
   redirect_stderr=true
   stdout_logfile=/var/log/reverb.log
   ```

#### Phase 17 (SAML/SCIM/Audit) — Multi-Tenant SAML Customization

The `scaler-tech/laravel-saml2` package is designed for single-tenant SP. For multi-tenant, you have two options:

**Option A: Fork the package (recommended)**
- Fork to `your-org/laravel-saml2-multi-tenant`
- Modify the SP entity ID, cert, and IdP config to be loaded from the database (per-tenant) instead of from config file
- Each tenant gets its own SP cert (auto-generated on first SAML setup)

**Option B: Build custom SP using `lightsaml/lightsaml`**
- More work, but more control
- Recommended if you need features the fork doesn't support

For SCIM, `arietimmerman/laravel-scim-server` is similarly single-tenant by default. You'll need to either fork or build custom — Phase 17 prompt covers this.

#### Phase 19 (Polish) — Do Not Skip

Phase 19 includes the critical `V4TenantIsolationTest` and `DomainIsolationTest` suites. These are **mandatory** — skipping them means shipping a platform with potential cross-tenant data leakage vulnerabilities. Treat Phase 19 as a hard requirement, not optional.

---

## 8. Step 5: Feature Flag Configuration

Every V4 feature is wrapped in a feature flag stored in `tenants.data.features.{feature_name}`.

**Default for existing V3 tenants:** all V4 features OFF. The tenant gets exactly V3 behavior until you explicitly turn on V4 features.

**Default for new V4 test tenants:** features ON as needed for their demo purpose:
- Shopland — connector features ON
- EnterpriseCorp — SAML, SCIM, audit streaming ON
- Multilingual Co. — multi-domain, per-domain locale ON

To enable a V4 feature for an existing tenant:

```php
// In tinker
$tenant = \App\Models\Central\Tenant::where('slug', 'advmedi')->first();
$data = $tenant->data;
$data['features'] = array_merge($data['features'] ?? [], [
    'multi_domain' => true,
    'workflow_engine' => true,
    'ab_testing' => true,
    'collab_editing' => true,
    'ai_rag' => true,
    'personalization' => true,
    'saml_sso' => false,        // turn on only when IdP configured
    'scim_provisioning' => false,
    'audit_streaming' => false,
    'form_analytics' => true,
]);
$tenant->data = $data;
$tenant->save();

// Clear tenant cache
cache()->forget("tenant:{$tenant->id}:features");
```

**Or via the V4 Feature Flags admin UI** (built in Phase 19):
- Owner logs in → Settings → Feature Flags → toggle switches → Save

---

## 9. Step 6: V4 Middleware Stack Ordering

V4 adds several new middleware. The correct order (combined with V3's stack) is:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    // V3 stack (unchanged)
    $middleware->append(\App\Http\Middleware\InitializeTenancyByDomain::class);
    $middleware->append(\App\Http\Middleware\PreventAccessFromCentralDomains::class);
    $middleware->append(\App\Http\Middleware\TenantActiveGate::class);
    $middleware->append(\App\Http\Middleware\ResolveTheme::class);

    // V4 additions (only active when corresponding feature flag is on)
    $middleware->append(\App\Http\Middleware\ResolveWildcardDomain::class);    // V4
    $middleware->append(\App\Http\Middleware\VerifyDomainActive::class);       // V4
    $middleware->append(\App\Http\Middleware\EnforceHttps::class);             // V4
    $middleware->append(\App\Http\Middleware\ResolveSite::class);              // V4
    $middleware->append(\App\Http\Middleware\ApplyDomainConfig::class);        // V4
    $middleware->append(\App\Http\Middleware\AssignExperimentVariant::class);  // V4
    $middleware->append(\App\Http\Middleware\ApplyPersonalization::class);     // V4
})
```

**Important:** Each V4 middleware MUST check its feature flag and short-circuit (return `$next($request)`) if the flag is off. Example:

```php
// ResolveWildcardDomain.php
public function handle(Request $request, Closure $next)
{
    if (! tenant_has_feature('multi_domain')) {
        return $next($request);
    }
    // ... actual logic
}
```

---

## 10. Step 7: Update RouteServiceProvider

V4 adds new route files. Register them in `bootstrap/app.php` (Laravel 11) or `app/Providers/RouteServiceProvider.php` (Laravel 10):

```php
// V3 routes (unchanged)
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    // ...
)

// V4: register additional route files via AppServiceProvider::boot()
public function boot(): void
{
    Route::middleware(['web', 'tenant'])
        ->group(base_path('routes/saml.php'));

    Route::middleware(['api', 'scim-auth'])
        ->prefix('scim/v2')
        ->group(base_path('routes/scim.php'));

    Route::middleware(['api', 'connector-auth'])
        ->prefix('api/v1/connector')
        ->group(base_path('routes/connector.php'));

    Route::middleware(['web'])
        ->group(base_path('routes/collab.php'));
}
```

---

## 11. Step 8: Update Scheduled Commands

Add V4 scheduled commands to `app/Console/Kernel.php` (Laravel 10) or `routes/console.php` (Laravel 11):

```php
// Laravel 11 (routes/console.php)
use Illuminate\Support\Facades\Schedule;

Schedule::command('ssl:renew')->dailyAt('02:00');
Schedule->command('dns:retry-failed')->hourly();
Schedule->command('audit:verify-chain --tenant=all')->weekly();
Schedule->command('workflow:check-sla-breaches')->dailyAt('08:00');
Schedule->command('experiments:check-winners')->hourly();
Schedule->command('rag:reindex-stale')->dailyAt('03:00');
Schedule->command('collab:cleanup-stale-sessions')->everyFifteenMinutes();
Schedule->command('audit:retry-failed-deliveries')->everyFiveMinutes();
Schedule->command('connector:cleanup-stale-tokens')->daily();
```

---

## 12. Step 9: Environment Variable Updates

Add to your `.env` and `.env.example`:

```bash
# V4 SSL Automation
SSL_PROVIDER=letsencrypt
SSL_ENV=staging                    # use 'production' when ready
SSL_RELOAD_CMD="sudo systemctl reload nginx"
CLOUDFLARE_API_TOKEN=
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
DIGITALOCEAN_API_TOKEN=

# V4 AI RAG (uses V3's AI config, no new env vars required unless using pgvector)
DB_CONNECTION=pgsql                # if using pgvector — requires Postgres migration
# OR stay on mysql with JSON-based RAG (works for <50k documents)

# V4 Reverb (collab editing)
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

# V4 SAML (per-tenant config in DB, no global env vars needed)

# V4 Audit Streaming (per-tenant config in DB)
# No global env vars — each tenant configures their own SIEM destination
```

---

## 13. Step 10: Migration Rollback Plan

If V4 upgrade causes issues, you can roll back:

### Soft Rollback (Disable V4 Features)

1. Set all V4 feature flags to OFF for all tenants:
   ```php
   \App\Models\Central\Tenant::chunk(100, function ($tenants) {
       foreach ($tenants as $tenant) {
           $data = $tenant->data;
           $data['features'] = [];
           $tenant->data = $data;
           $tenant->save();
       }
   });
   ```

2. V4 middleware will short-circuit on every request (feature flag check).
3. V4 routes will return 404 (feature flag check in route middleware).
4. V4 admin UI pages will not appear in nav (feature flag check in nav builder).
5. Platform effectively runs V3 behavior, even though V4 code is present.

### Hard Rollback (Uninstall V4 Code)

1. `git revert` the V4 commits (or `git checkout v3-last-stable`).
2. Run `php artisan migrate:rollback` to reverse V4 migrations.
3. Run `composer remove` for V4-specific packages:
   ```bash
   composer remove acmephp/core spatie/dns laravel/reverb phadej/yjs \
     scaler-tech/laravel-saml2 arietimmerman/laravel-scim-server tmilos/scim-filter-parser \
     twig/twig
   ```
4. Run `composer dump-autoload`.
5. Run `php artisan config:clear && php artisan cache:clear`.
6. Verify V3 tests still pass.

---

## 14. Step 11: Testing the Upgrade

After each V4 phase:

1. **Run V3 Pest tests** — they should still pass unchanged:
   ```bash
   php artisan test --testsuite=Feature
   ```
   If any V3 test fails, V4 broke V3 behavior — STOP and fix before continuing.

2. **Run V4 Pest tests for the completed phase:**
   ```bash
   php artisan test --filter=V4
   ```

3. **Manual smoke test:**
   - Log in to AdvMedi admin → verify all V3 functionality works
   - Log in to BitDreamIT admin → verify all V3 functionality works
   - Visit `advmedi.test` and `bitdreamit.test` → verify public pages render

After all V4 phases:

4. **Run V4TenantIsolationTest** (Phase 19):
   ```bash
   php artisan test --filter=V4TenantIsolationTest
   ```
   This MUST pass with zero failures.

5. **Run DomainIsolationTest** (Phase 19):
   ```bash
   php artisan test --filter=DomainIsolationTest
   ```
   This MUST pass with zero failures.

6. **Run full test suite:**
   ```bash
   php artisan test
   ```
   Expect 200+ tests passing.

---

## 15. Post-Upgrade: Seeding V4 Test Tenants

After Phase 12 completes, seed the 3 new V4 test tenants:

```bash
php artisan db:seed --class=\\Database\\Seeders\\Central\\V4TenantSeeder
```

The seeder creates:

1. **Shopland** tenant
   - 1 domain: `shopland.test`
   - Theme: `foundation`
   - Plan: V4 plan with `connector_enabled = true`
   - Seeded with `products` collection (10 sample products)

2. **EnterpriseCorp** tenant
   - 1 domain: `enterprise.test`
   - Theme: `foundation`
   - Plan: V4 enterprise plan
   - Seeded with `pages` collection
   - Pre-configured SAML IdP (using `samltest.id`)
   - Pre-configured SCIM token (displayed once in seeder output)
   - Pre-configured audit stream to a mock webhook URL

3. **Multilingual Co.** tenant
   - 3 domains: `multilingual.fr` (site=fr-FR), `multilingual.de` (site=de-DE), `multilingual.bn` (site=bn-BD)
   - 1 wildcard domain: `*.multilingual.test`
   - Theme: `foundation`
   - Sites seeded with localized content for each locale
   - Wildcard routing: `{wildcard}.multilingual.test` shows a city landing page

After seeding, add to your `hosts` file (or local DNS):

```
127.0.0.1   shopland.test
127.0.0.1   enterprise.test
127.0.0.1   multilingual.fr
127.0.0.1   multilingual.de
127.0.0.1   multilingual.bn
127.0.0.1   paris.multilingual.test
127.0.0.1   berlin.multilingual.test
127.0.0.1   dhaka.multilingual.test
```

---

## 16. Post-Upgrade: Documentation Updates

Update your project's documentation:

### `README.md`

Add a new section "V4 Features" with:
- Brief overview of each V4 feature
- How to enable per-tenant (Feature Flags UI)
- Link to `docs/v4/` for detailed guides

### `docs/`

Create the V4 docs directory (per Phase 19 prompt):
```
docs/
├── architecture.md           (V3, updated to mention V4 domains)
├── theming-guide.md          (V3)
├── adding-fieldtypes.md      (V3)
├── adding-ai-prompts.md      (V3)
├── deployment.md             (V3, updated with V4 deploy notes)
├── contributing.md           (V3, updated with V4 conventions)
└── v4/                       (NEW)
    ├── multi-domain-guide.md
    ├── connector-guide.md
    ├── workflow-engine.md
    ├── ab-testing.md
    ├── collab-editing.md
    ├── ai-rag.md
    ├── personalization.md
    ├── saml-sso.md
    ├── scim-provisioning.md
    ├── audit-streaming.md
    └── form-analytics.md
```

### `CLAUDE.md`

Update with V4 conventions:
- New domains and namespaces (per V4 spec section 9.4)
- V4 feature flag pattern (`tenant_has_feature()`)
- V4 middleware stack (with feature flag short-circuits)
- V4 testing conventions (`V4TenantIsolationTest` is mandatory for any new tenant-scoped table)
- V4 phase list (Phases 12-19)

---

## 17. Post-Upgrade: Deployment Updates

### Production Server Requirements

V4 adds these runtime requirements:

1. **Postgres** (optional, for pgvector/RAG)
   - If you stay on MySQL, RAG falls back to JSON-based brute-force search (works for <50k documents)
   - If you migrate to Postgres for RAG, follow Postgres migration guide separately

2. **Supervisor** for Reverb WebSocket server (Phase 15)
   - Reverb runs as a separate process

3. **ACME client** for SSL automation (Phase 12)
   - `acmephp/core` is a PHP library, no separate binary needed
   - Web server reload permissions: the PHP user needs `sudo` access to `systemctl reload nginx` (or equivalent)
   - Configure via sudoers: `www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx`

4. **DNS provider API access** (Phase 12, for wildcard SSL via DNS-01)
   - Cloudflare API token (recommended — easiest)
   - OR AWS Route53 access keys
   - OR DigitalOcean API token

### Deploy Script Updates

Add to your deploy script:

```bash
# After composer install
php artisan migrate --force

# V4: compile theme assets (V3 already did this, but V4 themes may have new assets)
php artisan themes:compile

# V4: warm RAG index for any newly-published entries (catch-up from deploy downtime)
php artisan rag:reindex-stale

# V4: clear all caches (V3 already did this)
php artisan optimize:clear

# V4: cache config + routes
php artisan config:cache
php artisan route:cache

# V4: restart Reverb server (if collab editing is enabled)
sudo supervisorctl restart reverb

# V4: reload web server (in case new SSL certs were issued during deploy)
sudo systemctl reload nginx

# V4: warmup - run scheduled commands that may have been missed
php artisan workflow:check-sla-breaches
php artisan experiments:check-winners
```

### Cron Updates

Add to your production crontab (in addition to V3's `php artisan schedule:run`):

```cron
# V4: separate crons for high-frequency tasks (don't wait for schedule:run)
* * * * * cd /path/to/app && php artisan audit:retry-failed-deliveries
*/15 * * * * cd /path/to/app && php artisan collab:cleanup-stale-sessions
```

---

## 18. Common Migration Issues & Fixes

### Issue 1: V3 domain's `dns_verification_status` is `unverified`, blocking traffic

**Cause:** V4's `VerifyDomainActive` middleware (Phase 12) checks DNS verification status.

**Fix:** Run the one-time update script (Step 4 above) to mark all V3 domains as verified:
```php
DB::table('domains')->whereNull('dns_verified_at')->update([
    'dns_verification_status' => 'verified',
    'dns_verified_at' => now(),
]);
```

### Issue 2: V3 test domains don't resolve after Phase 12

**Cause:** V4's `ResolveWildcardDomain` middleware runs BEFORE `InitializeTenancyByDomain` in some misconfigurations.

**Fix:** Verify middleware order in `bootstrap/app.php`. The correct order is:
1. `InitializeTenancyByDomain` (V3) — runs first, attempts exact match
2. `ResolveWildcardDomain` (V4) — runs second, attempts wildcard match if exact match failed

### Issue 3: Theme override doesn't apply

**Cause:** V3's `ResolveTheme` middleware doesn't know about V4's per-domain theme override.

**Fix:** After Phase 12, the `ThemeResolver::resolve()` method must accept an optional `Domain $domain` parameter. The `ResolveTheme` middleware must pass the current domain (if any) to the resolver. Re-check Phase 12 step 10.

### Issue 4: Reverb WebSocket connection fails in browser

**Cause:** Browser cannot reach Reverb server (firewall, wrong port, wrong scheme).

**Fix:**
- Verify Reverb is running: `php artisan reverb:status`
- Verify `.env` has correct `REVERB_*` values
- For HTTPS sites, Reverb must be proxied through Nginx with TLS:
  ```nginx
  location /app {
      proxy_pass http://127.0.0.1:8080;
      proxy_http_version 1.1;
      proxy_set_header Upgrade $http_upgrade;
      proxy_set_header Connection "upgrade";
      proxy_set_header Host $host;
  }
  ```
- Set `REVERB_SCHEME=https` in `.env` for production.

### Issue 5: SAML login fails with "Entity ID mismatch"

**Cause:** The SAML IdP's metadata and the CMS SP's metadata have different entity IDs.

**Fix:**
- Visit `https://{tenant-domain}/saml/metadata/{idpId}` to download the SP metadata
- Upload this metadata to your IdP (Okta/Azure AD/etc.)
- Verify the IdP's entity ID matches what's stored in `saml_identity_providers.entity_id`

### Issue 6: SCIM user provisioning creates user but role mapping doesn't work

**Cause:** The `role_mapping` JSON in `saml_identity_providers` doesn't match the actual groups in the IdP's SAML response.

**Fix:**
- Enable debug logging for SAML: `LOG_LEVEL=debug` temporarily
- Attempt login
- Check `storage/logs/laravel.log` for the SAML response attributes
- Update `role_mapping` to match the actual attribute names and group values

### Issue 7: Audit streaming deliveries stuck at "pending"

**Cause:** The `DeliverAuditEventJob` queue worker is not running, or the destination is unreachable.

**Fix:**
- Verify queue worker is running: `php artisan queue:work --queue=cms-audit`
- Check the audit stream's `last_delivery_status` in admin UI
- Check `audit_stream_deliveries` table for `processing_error`
- Test destination connectivity via the "Test Connection" button in admin UI

### Issue 8: Connector package install fails with dependency conflicts

**Cause:** Host Laravel app has conflicting package versions.

**Fix:**
- Run `composer depends guzzlehttp/guzzle` to see what's requiring it
- The connector requires `guzzlehttp/guzzle: ^7.5`. If the host has `^6.0`, you need to upgrade Guzzle first.
- For Laravel 5.8/6.x hosts that can't upgrade Guzzle, use the connector's legacy branch (if available) or upgrade the host to Laravel 10+ first.

### Issue 9: V3 Pest tests fail after V4 upgrade

**Cause:** V4 changed a V3 contract (this should NOT happen, but if it does...).

**Fix:**
- Identify which test failed
- Read the V4 spec section relevant to the failure
- V4 is supposed to be purely additive — if a V3 test fails, it's a V4 bug
- Report the bug and roll back that specific V4 phase

### Issue 10: RAG search returns no results

**Cause:** Entries haven't been indexed yet. RAG indexing only happens on entry publish, not retroactively.

**Fix:** Run the bulk reindex command:
```bash
php artisan rag:reindex-all --tenant=advmedi
```

---

## 19. Upgrade Verification Checklist

After completing all V4 phases, verify:

### V3 Compatibility
- [ ] All V3 Pest tests pass (unchanged)
- [ ] AdvMedi admin panel works exactly as before
- [ ] BitDreamIT admin panel works exactly as before
- [ ] AdvMedi public site renders correctly with V3 theme
- [ ] BitDreamIT public site renders correctly with V3 theme
- [ ] V3 billing, permissions, audit log all functional

### V4 Features Functional
- [ ] 3 new V4 test tenants seeded (Shopland, EnterpriseCorp, Multilingual Co.)
- [ ] All 7 V4 test domains resolve correctly:
  - shopland.test, enterprise.test, multilingual.fr, multilingual.de, multilingual.bn, *.multilingual.test
- [ ] Per-domain theme override works (manually test on AdvMedi's shop.advmedi.test)
- [ ] Per-domain locale binding works (multilingual.fr shows French)
- [ ] Wildcard subdomain resolution works (paris.multilingual.test)
- [ ] SSL staging cert issued for at least 1 domain
- [ ] DNS verification flow completes
- [ ] Connector package installs in a fresh Laravel app
- [ ] Shopland bidirectional model sync works
- [ ] Workflow Builder UI works (create a 3-node workflow, test run)
- [ ] A/B experiment creation + visitor assignment + conversion tracking works
- [ ] Collab editing: two browsers edit same Bard field, see each other's cursors
- [ ] RAG: ask a question in admin Playground, get cited answer
- [ ] Personalization: segment + rule + variant content shows correctly
- [ ] SAML login via samltest.id works
- [ ] SCIM: curl POST a user to /scim/v2/Users, verify creation
- [ ] Audit streaming: configure mock webhook, trigger events, verify delivery
- [ ] Form analytics: view dashboard after submitting a form
- [ ] Lead scoring: configure rules, submit form, verify score

### V4 Cross-Tenant Isolation
- [ ] V4TenantIsolationTest passes (zero failures)
- [ ] DomainIsolationTest passes (zero failures)
- [ ] Manual cross-tenant check: AdvMedi admin cannot access BitDreamIT data via any V4 endpoint

### Performance
- [ ] All admin pages load in <500ms (Telescope off)
- [ ] No N+1 queries on V4 admin pages (verified via Telescope)
- [ ] Database indexes per V4 spec are in place
- [ ] Cache hit rate >80% for headless API calls

### Documentation
- [ ] All 11 V4 docs exist in docs/v4/
- [ ] README.md updated with V4 sections
- [ ] CLAUDE.md updated with V4 conventions
- [ ] .env.example updated with V4 env vars

### Deployment
- [ ] Deploy script updated with V4 steps
- [ ] Production cron entries added
- [ ] Supervisor config for Reverb created
- [ ] Web server (nginx) config updated for WebSocket proxying
- [ ] Web server reload permissions configured for SSL automation

### Fresh Clone Test
- [ ] Fresh clone from git
- [ ] Follow only README
- [ ] Local environment running in <45 minutes
- [ ] All V3 and V4 test tenants functional
- [ ] 200+ Pest tests pass

---

## 20. Rollback Summary

If you need to abandon V4 and revert to V3:

1. **Soft rollback (preserve V4 code, disable features):**
   - Set all V4 feature flags to OFF for all tenants
   - V4 code remains but is inert
   - V3 behavior fully restored

2. **Hard rollback (remove V4 code entirely):**
   - `git revert` V4 commits, OR `git checkout v3-last-stable-tag`
   - `php artisan migrate:rollback` (reverses V4 migrations)
   - `composer remove` V4 packages
   - `composer dump-autoload`
   - `php artisan optimize:clear`
   - Run V3 Pest tests to verify

3. **Database-only rollback (keep V4 code, undo DB changes):**
   - Not recommended — V4 code expects V4 schema
   - If absolutely needed, manually drop V4 tables and V4 columns from `domains` and `form_submissions`
   - V4 code will throw schema errors — you must also `git revert` V4 code

---

## 21. FAQ

**Q: Can I skip phases I don't need?**

A: Phases 12 (Multi-Domain) and 19 (Polish) are mandatory — they're foundational and integrative respectively. Phases 13-18 are independent and can be skipped or done in any order. However, skipping a phase means that feature is unavailable to all tenants.

**Q: Will V4 slow down my V3 tenants?**

A: No. Every V4 middleware checks its feature flag first and short-circuits if off. For V3 tenants (all flags off), the overhead is ~1ms per request (the flag check). Real V4 functionality only activates when the flag is on.

**Q: Do I need Postgres for V4?**

A: Only if you want AI RAG at scale (>50k documents per tenant). pgvector enables fast vector search. Without Postgres, the JSON-based fallback works but is slower for large corpora. All other V4 features work on MySQL.

**Q: Can I upgrade to V4 if I haven't finished all V3 phases?**

A: Yes, but you'll need to skip V4 features that depend on incomplete V3 phases. For example, if V3 Phase 5 (Theme Engine) isn't done, V4's per-domain theme override (Phase 12) won't work because there's no theme engine to override. Review each V4 phase's dependencies before starting.

**Q: How do I migrate from MySQL to Postgres for RAG?**

A: This is a separate migration outside the scope of this guide. Use a tool like `pgloader` to migrate the data. Test thoroughly in staging first. Once on Postgres, install `pgvector/laravel` package, run the pgvector-specific RAG migration, and update `config/rag.php` to use the Postgres driver.

**Q: Can I run V4 features on only some tenants?**

A: Yes — that's the entire point of feature flags. Each tenant can have a different set of V4 features enabled. New tenants default to all V4 features OFF for safety.

**Q: What about V5? Will I need another migration?**

A: V5 is not yet planned. When it is, it will follow the same additive pattern: V5 builds on V4, doesn't break V4 functionality, all V5 features are feature-flagged. The same migration guide structure will apply.

---

## 22. Getting Help

If you encounter issues during V4 upgrade:

1. **Re-read the relevant V4 spec section** — `04-FIELD-STRUCTURE-SPEC-V4.md`
2. **Re-read the relevant V4 phase prompt** — `04-AI-BUILD-PROMPTS-V4.md`
3. **Check the troubleshooting section** — Section 18 of this guide
4. **Run the verification checklist** — Section 19 of this guide
5. **Check Pest test failures** — V4 tests are written to fail loudly when something is wrong
6. **Use the AI coding agent** — paste the failing test + relevant V4 spec section into Claude Code for diagnosis

---

*End of V3 → V4 Migration Guide. Companion files: `04-FIELD-STRUCTURE-SPEC-V4.md`, `04-AI-BUILD-PROMPTS-V4.md`, `04-LARAVEL-INTEGRATION-KIT-V4.md`. All V3 files (`03-*.md`, `Laravel-CMS-V3-Specification-Summary.pdf`) should remain in the project root alongside V4 files.*
