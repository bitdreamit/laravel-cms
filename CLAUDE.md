# CLAUDE.md — Laravel CMS V4 Project Context

## Project Overview

Custom Laravel 12 CMS + multi-tenant billing platform with V4 enhancements (Multi-Domain, Connector, Pro Features). Statamic-class feature parity plus enterprise additions.

## Stack

- Laravel 12.x, PHP 8.2+
- MySQL 8 (default) or Postgres 16 (for pgvector/RAG)
- Redis (cache, queue, session)
- stancl/tenancy v3 single-DB mode (tenant_id column + BelongsToTenant trait)
- spatie/laravel-permission (team-scoped to tenant_id)
- spatie/laravel-medialibrary, spatie/laravel-activitylog
- Laravel Reverb (WebSocket server for collab editing)
- Laravel Sanctum/Fortify (admin auth)
- scaler-tech/laravel-saml2 (SAML 2.0 SP)
- arietimmerman/laravel-scim-server (SCIM 2.0)
- acmephp/core (ACME SSL automation)
- spatie/dns (DNS verification)
- twig/twig (workflow condition expressions)

## Architecture

- Domain-Driven Design (DDD) — all business logic in `app/Domain/{DomainName}/`
- Each domain has: Actions/, DTOs/, Events/, Listeners/, Services/, Repositories/
- Controllers are thin — delegate to domain Actions and Services
- Models split: `app/Models/Central/` (Tenant, Domain, User, SslCertificate) vs `app/Models/Tenant/` (Entry, Workflow, Experiment, etc.)
- Migrations split: `database/migrations/central/` vs `database/migrations/tenant/`

## V4 Feature Flag Pattern

Every V4 feature is wrapped in `tenant_has_feature('{feature_name}')`. The function:
- Returns false if tenancy not initialized
- Returns false if tenant's `data.features` array doesn't include the feature
- V3 features return true (always available)
- Default for new tenants: all V4 features OFF

V4 middleware all check `tenant_has_feature()` first and short-circuit (return `$next($request)`) if off.

## V4 Features (all in tenants.data.features)

- `multi_domain` — Per-domain theme/locale/collection routing, wildcard subdomains, SSL automation
- `connector` — External Laravel app connector (SSO bridge, model sync, event bus, embedded, headless)
- `workflow_engine` — Visual DAG approval workflows
- `ab_testing` — Per-entry variant testing with statistical significance
- `collab_editing` — Yjs CRDT-based real-time co-editing via Reverb
- `ai_rag` — Per-tenant vector store for AI Q&A grounded in published entries
- `personalization` — Visitor segments + rule engine (19 condition types)
- `saml_sso` — SAML 2.0 SP for enterprise IdPs
- `scim_provisioning` — SCIM 2.0 endpoints for user auto-provisioning
- `audit_streaming` — Activity log streaming to SIEM (Splunk/Datadog/Elastic/Syslog)
- `form_analytics` — Conversion funnels + lead scoring

## Middleware Stack Order (in bootstrap/app.php)

```
InitializeTenancyByDomain         # V3: resolve tenant from exact domain match
ResolveWildcardDomain             # V4: fallback to wildcard if exact match fails
PreventAccessFromCentralDomains   # V3
VerifyDomainActive                # V4: 503 parked / 301 redirect_only
EnforceHttps                      # V4: force HTTPS if domain.config.force_https
TenantActiveGate                  # V3: block suspended tenants
ResolveTheme                      # V3+V4: per-domain theme override
ResolveSite                       # V4: per-domain locale binding
ApplyDomainConfig                 # V4: custom headers, robots.txt override
AssignExperimentVariant           # V4: A/B testing visitor assignment
ApplyPersonalization              # V4: evaluate segments, cache in session
auth guard → role/permission checks
RequireElevatedSession (where applicable)
```

## Common Commands

```bash
# Development
php artisan serve                  # Start dev server
php artisan reverb:start --debug   # Start WebSocket server for collab
php artisan queue:work             # Process queues
php artisan test                   # Run Pest tests
php artisan test --filter=V4       # V4 tests only

# V4 SSL + DNS
php artisan ssl:renew              # Renew certs expiring within 30 days
php artisan dns:retry-failed       # Retry pending DNS verification jobs

# V4 Audit
php artisan audit:verify-chain     # Verify activity log chain integrity

# V4 RAG
php artisan rag:reindex-stale      # Reindex published entries not in RAG
```

## Testing Conventions

- Pest PHP, not PHPUnit
- Feature tests in `tests/Feature/`, unit tests in `tests/Unit/`
- `V4TenantIsolationTest` is MANDATORY for any new tenant-scoped table
- `DomainIsolationTest` for per-domain routing
- Use `RefreshDatabase` trait (already in TestCase)
- Both central and tenant migration paths auto-loaded in TestCase::setUp

## Coding Standards

- PSR-12, strict types
- Form Request classes for all controller validation
- Laravel API Resources for all API output
- Action classes for non-trivial operations (one Action per file, single responsibility)
- Never write raw `DB::` queries against tenant-scoped tables without manual `tenant_id` filtering
- Use `Rule::unique(...)->where('tenant_id', tenant('id'))` for uniqueness validation

## V4 Spec Files (read before editing V4 code)

- `05-V5-UNIFIED-BUILD-PLAN.md` — **PRIMARY** — phase-by-phase status (✅/⚠️/❌) for ALL V3+V4 phases, recommended implementation order, remaining sub-tasks for partial phases
- `04-FIELD-STRUCTURE-SPEC-V4.md` — sections 17 (Multi-Domain), 18 (Connector), 19 (Pro Features)
- `04-AI-BUILD-PROMPTS-V4.md` — Phases 12-19 build prompts
- `04-LARAVEL-INTEGRATION-KIT-V4.md` — Connector package spec
- `04-V3-TO-V4-MIGRATION-GUIDE.md` — upgrade path from V3

V3 spec files (`03-*.md`) remain authoritative for V3 features.

**Always check `05-V5-UNIFIED-BUILD-PLAN.md` first** to see the current status of any phase before starting work.
