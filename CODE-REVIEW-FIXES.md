# Code Review Fixes — V6.1

**Date:** 2026-07-19
**Reviewer:** Independent static code review
**Status:** All 10 identified issues fixed

---

## Summary

A thorough static code review identified 10 issues ranging from critical security vulnerabilities to minor inconsistencies. All 10 have been fixed in this V6.1 release.

| # | Severity | Issue | Status |
|---|---|---|---|
| 1 | 🔴 Critical | SQL injection in SearchService | ✅ Fixed |
| 2 | 🔴 Critical | Cross-tenant account takeover in AuthBridgeService | ✅ Fixed |
| 3 | 🔴 Critical | Billing automation never scheduled | ✅ Fixed |
| 4 | 🔴 Critical | Missing config/tenancy.php | ✅ Fixed |
| 5 | 🟠 High | DispatchWebhook job tenant bootstrapping | ✅ Fixed |
| 6 | 🟠 High | Hardcoded billing grace period + void invoice bug | ✅ Fixed |
| 7 | 🟠 High | Global middleware stack applied to all routes | ✅ Fixed |
| 8 | 🟡 Medium | Deprecated Claude model string | ✅ Fixed |
| 9 | 🟡 Medium | Duplicate jobs/cache tables in tenant migration | ✅ Fixed |
| 10 | 🟡 Low | RAG_VECTOR_STORE default mismatch | ✅ Fixed |

---

## Fix #1: SQL Injection in SearchService 🔴 → ✅

**Problem:** All three search query builders (`buildMysqlQuery`, `buildPostgresQuery`, `buildLikeQuery`) interpolated the user's search string directly into raw SQL via `DB::select()`. `addslashes()` and `pg_escape_string()` are not adequate escaping for all injection vectors.

**Fix:** Rewrote `SearchService.php` to use Laravel's query builder with parameterized bindings:
- MySQL: `whereRaw('MATCH(...) AGAINST(?)', [$query])`
- PostgreSQL: `whereRaw('to_tsvector(...) @@ plainto_tsquery(?)', [$query])`
- SQLite: Standard `where('title', 'like', "%{$query}%")` (parameterized by Eloquent)

No raw string interpolation anywhere. All user input is bound as a parameter.

---

## Fix #2: Cross-Tenant Account Takeover in AuthBridgeService 🔴 → ✅

**Problem:** `findOrCreateUser()` looked up users by email only and silently attached them to a new tenant without consent or invitation. `verifySsoToken()` verified the JWT signature but never checked `iss`/`aud` claims.

**Fix:** Complete rewrite of `AuthBridgeService.php`:
1. `verifySsoToken()` now enforces `iss === 'host-app'` and `aud === 'cms-platform'` claim checks after signature verification
2. `findOrCreateUser()` now:
   - If user exists AND is already a member of the current tenant → legitimate login
   - If user exists but is NOT a member → requires a pending invitation in `tenant_user_invitations` table (Owner/Admin must invite first)
   - If user doesn't exist → only creates if `auto_create_users=true`
3. Created new `tenant_user_invitations` table (central migration `000008`) with token, email, role, expires_at, accepted_at fields

**New migration:** `database/migrations/central/2024_01_01_000008_create_tenant_user_invitations_table.php`

---

## Fix #3: Billing Automation Never Scheduled 🔴 → ✅

**Problem:** `SuspendOverdueTenant` and `GenerateInvoice` actions existed as code but `routes/console.php` only had Laravel's default `inspire` command — no `Schedule::` entries. The entire billing automation was inert.

**Fix:** Complete rewrite of `routes/console.php` with all scheduled commands:
- `billing:generate-invoices` — daily at 00:00
- `billing:send-reminders` — daily at 08:00 (T-7, T-1, T+1 reminders)
- `billing:suspend-overdue` — daily at 09:00
- `billing:reactivate-paid` — hourly
- `ssl:renew` — daily at 02:00
- `dns:retry-failed` — hourly
- `audit:retry-failed-deliveries` — every 5 minutes
- `audit:verify-chain` — weekly Sundays at 03:00
- `workflow:check-sla-breaches` — daily at 08:00
- `experiments:check-winners` — hourly
- `rag:reindex-stale` — daily at 03:00
- `collab:cleanup-stale-sessions` — every 15 minutes
- `cms:backup` — daily at 04:00
- `cms:backup --prune=30` — weekly Sundays at 05:00

**New Artisan commands created:**
- `app/Console/Commands/GenerateInvoices.php`
- `app/Console/Commands/SendBillingReminders.php`
- `app/Console/Commands/SuspendOverdueTenants.php`
- `app/Console/Commands/ReactivatePaidTenants.php`

**New mail:** `app/Mail/BillingReminderMail.php` + `resources/views/emails/billing-reminder.blade.php`

---

## Fix #4: Missing config/tenancy.php 🔴 → ✅

**Problem:** `stancl/tenancy` requires a `config/tenancy.php` file to know its tenant model, central domains, and queue tenant-awareness settings. It was completely absent.

**Fix:** Created comprehensive `config/tenancy.php` with:
- Tenant model: `App\Models\Central\Tenant`
- Domain model: `App\Models\Central\Domain`
- Central domains (from `APP_CENTRAL_DOMAIN` env)
- Single-database mode explicitly set
- Queue tenant-awareness enabled (`queue.tenant_aware = true`)
- List of central-context jobs excluded from tenant awareness
- Bootstrappers for cache, filesystem, queue, Redis
- Filesystem tenancy config (tenant-prefixed paths, not separate disks)

---

## Fix #5: DispatchWebhook Job Tenant Bootstrapping 🟠 → ✅

**Problem:** `Webhook::find($this->webhookId)` ran in a queued job with no guarantee that tenancy was initialized. The `BelongsToTenant` global scope would filter out all records, returning null.

**Fix:** Updated `app/Jobs/DispatchWebhook.php`:
1. Added `$tenantId` parameter captured at dispatch time
2. In `handle()`, explicitly initializes tenancy via `Tenancy::initialize()` if not already initialized
3. Uses `withoutGlobalScope('tenant')` as extra safety
4. Verifies `webhook->tenant_id === $this->tenantId` before processing
5. Throws exception if tenant not found or webhook doesn't belong to tenant

Also updated `app/Domain/Content/Listeners/DispatchWebhooks.php` to pass `$entry->tenant_id` when dispatching.

---

## Fix #6: Billing Grace Period + Void Invoice Bug 🟠 → ✅

**Problem:** `BillingService::suspendOverdueTenants()` used a hardcoded `now()->subDays(7)` for all tenants regardless of plan. The query `where('status', '!=', 'paid')` also matched `void` invoices, which shouldn't trigger suspension.

**Fix:**
1. Added `grace_period_days` column to `billing_plans` migration (default 7)
2. Added `billing_cycle` column while we're at it
3. Updated `BillingPlan` model with new fillable fields + casts
4. Updated `BillingService::suspendOverdueTenants()`:
   - Uses `whereNotIn('status', ['paid', 'void', 'draft'])` — excludes void AND draft
   - Reads `$tenant->plan?->grace_period_days` per tenant
   - Falls back to `config('billing.invoices.grace_period_days', 7)` default
   - Logs suspension with full context (invoice, overdue_days, grace_period, plan)
   - Fires `TenantSuspended` event
5. Updated `BillingPlanSeeder` with per-plan grace periods:
   - Standard: 7 days
   - Professional: 14 days
   - Enterprise: 30 days

---

## Fix #7: Global Middleware Stack Applied to All Routes 🟠 → ✅

**Problem:** `bootstrap/app.php` applied all 9 tenancy/domain/personalization middleware to every web route including central/platform-owner routes, relying on each middleware to internally short-circuit. The code's own comment admitted this was fragile.

**Fix:** Restructured `bootstrap/app.php` into three middleware groups:
1. **`tenant`** group — all 11 middleware (tenancy + V4 features). Applied ONLY to tenant routes.
2. **`central`** group — empty (no tenancy middleware). For platform-owner console.
3. **`tenant-api`** group — minimal tenancy middleware for API routes.

Route files now reference the appropriate group:
- `routes/tenant-web.php` → uses `tenant` group
- `routes/tenant-admin.php` → uses `tenant` + `auth`
- `routes/saml.php` → uses `tenant` + `saml`
- `routes/api.php` → uses `tenant-api`
- Central routes in `routes/web.php` → no tenancy middleware

This eliminates the "hope it short-circuits" fragility — central routes never run tenancy middleware at all.

---

## Fix #8: Deprecated Claude Model String 🟡 → ✅

**Problem:** `config/ai.php`, `.env.example`, and docs all defaulted to `claude-sonnet-4-20250514`, a deprecated snapshot.

**Fix:**
- `config/ai.php`: Changed default to `claude-sonnet-4-6` (alias that always points to newest Sonnet 4) with explanatory comment linking to `docs.claude.com/en/api/model-names`
- `.env.example`: Updated `ANTHROPIC_MODEL=claude-sonnet-4-6`

---

## Fix #9: Duplicate jobs/failed_jobs/cache_locks Tables 🟡 → ✅

**Problem:** `jobs`, `failed_jobs`, `cache`, and `cache_locks` were created in both central AND tenant migrations, despite the project using Redis for queue and cache (making these database tables unused dead schema).

**Fix:** Removed the duplicate table creation from `database/migrations/tenant/2024_01_01_100002_create_v3_supporting_tables.php`. Added explanatory comment pointing to the central migration (`2024_01_01_000007_create_cache_table.php`) where these tables are created once, globally. Also updated the `down()` method to match.

---

## Fix #10: RAG_VECTOR_STORE Default Mismatch 🟡 → ✅

**Problem:** `config/ai.php` and `config/rag.php` defaulted `RAG_VECTOR_STORE` to `pgvector`, while `.env.example` set it to `json`. Env wins so not a functional bug, but inconsistent.

**Fix:** Aligned both config files to default to `json` (broad compatibility — works with MySQL and SQLite). Added comments explaining when to switch to `pgvector` (PostgreSQL for vector search at scale).

---

## Files Changed in V6.1

### Modified (10 files)
1. `app/Domain/Search/Services/SearchService.php` — parameterized queries
2. `app/Domain/Connector/Services/AuthBridgeService.php` — secure user lookup + JWT claims
3. `routes/console.php` — full schedule registration
4. `app/Jobs/DispatchWebhook.php` — tenant bootstrapping
5. `app/Domain/Content/Listeners/DispatchWebhooks.php` — pass tenant_id
6. `app/Domain/Billing/Services/BillingService.php` — per-plan grace period + void exclusion
7. `database/migrations/central/2024_01_01_000005_create_billing_tables.php` — added grace_period_days + billing_cycle
8. `app/Models/Central/BillingPlan.php` — new fillable + casts
9. `database/seeders/Central/BillingPlanSeeder.php` — per-plan grace periods
10. `bootstrap/app.php` — middleware groups (tenant/central/tenant-api)
11. `config/ai.php` — Claude model + RAG_VECTOR_STORE defaults
12. `config/rag.php` — RAG_VECTOR_STORE default
13. `.env.example` — Claude model + CMS_ID_TYPE + CMS_MACHINE_ID
14. `database/migrations/tenant/2024_01_01_100002_create_v3_supporting_tables.php` — removed duplicate tables

### New (8 files)
1. `config/tenancy.php` — missing stancl/tenancy config
2. `database/migrations/central/2024_01_01_000008_create_tenant_user_invitations_table.php` — invitation system
3. `app/Console/Commands/GenerateInvoices.php`
4. `app/Console/Commands/SendBillingReminders.php`
5. `app/Console/Commands/SuspendOverdueTenants.php`
6. `app/Console/Commands/ReactivatePaidTenants.php`
7. `app/Mail/BillingReminderMail.php`
8. `resources/views/emails/billing-reminder.blade.php`

---

## What Was Already Solid (no changes needed)

- All 44 tenant models correctly use `BelongsToTenant` — zero central models wrongly scoped
- `FieldTypeRegistry` is implemented exactly as specced
- Payment gateway integrations use HTTP client with array payloads (no injection)
- Webhook HMAC signing (`hash_hmac('sha256', ...)`) is cryptographically correct
- Migration directory separation (`central/` vs `tenant/`) is correct
- DDD structure with proper namespace/PSR-4 alignment

---

*This document is the changelog for V6.1. For the full project guide, see `IMPLEMENTATION-AND-USER-GUIDE.md`. For phase status, see `05-V5-UNIFIED-BUILD-PLAN.md`.*
