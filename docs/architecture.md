# Architecture Overview

## System Architecture

Laravel CMS V4 is a multi-tenant content management platform built on Laravel 12, using Domain-Driven Design (DDD) with modular domain packages.

## Core Architecture Principles

1. **Domain-Driven Design (DDD)** — Business logic organized by domain (Content, Billing, Theme, AI, etc.), not by technical layer
2. **Single-Database Multi-Tenancy** — All tenants share one database, isolated by `tenant_id` column + `BelongsToTenant` trait
3. **Action Classes over Fat Controllers** — Every non-trivial operation is an Action class (single responsibility)
4. **Repository Pattern** — Database access through interfaces, swappable for testing
5. **Event-Driven** — Domain events for cross-module communication
6. **Feature Flags** — Every V4 feature is wrapped in `tenant_has_feature()` for per-tenant control

## Request Flow

```
HTTP Request
  → InitializeTenancyByDomain (resolve tenant from domain)
  → ResolveWildcardDomain (V4: wildcard *.example.com support)
  → PreventAccessFromCentralDomains
  → VerifyDomainActive (V4: parked/redirect_only)
  → EnforceHttps (V4)
  → TenantActiveGate (block suspended tenants)
  → ResolveTheme (per-domain theme override)
  → ResolveSite (V4: per-domain locale)
  → ApplyDomainConfig (V4: custom headers, robots.txt)
  → AssignExperimentVariant (V4: A/B testing)
  → ApplyPersonalization (V4: segments + rules)
  → Auth Guard
  → Role/Permission Check
  → Controller
    → Action / Service
      → Repository / Model
      → Event → Listener
  → Response (cached if full-page cache enabled)
```

## Database Architecture

- **Central tables** (in `database/migrations/central/`): tenants, domains, users, themes, billing — shared across all tenants
- **Tenant-scoped tables** (in `database/migrations/tenant/`): entries, collections, blueprints, workflows, etc. — all have `tenant_id` column with `BelongsToTenant` trait
- **Total: ~77 tables** (44 from V2 + 3 from V3 + 30 from V4)

## Multi-Tenancy

Using `stancl/tenancy` v3 in single-database mode:
- Every tenant-scoped model uses `BelongsToTenant` trait
- Queries are automatically scoped to `tenant_id`
- Central models (Tenant, Domain, User) are NOT scoped
- Domain identification: `InitializeTenancyByDomain` middleware resolves tenant from HTTP host

## V4 Multi-Domain Layer

Each domain under a tenant can have:
- Its own theme override (`domain.theme_id`)
- Its own locale binding (`domain.site_id`)
- Its own collection routing (`domain.default_collection_handle`)
- Wildcard subdomain support (`*.example.com`)
- Automated SSL via Let's Encrypt ACME
- DNS ownership verification

## Theme Engine

- Themes live at `themes/{slug}/` (self-contained, distributable)
- Parent/child inheritance with view cascade (child → parent → grandparent → resources/views)
- Per-tenant customization stored in `theme_customizations` table (non-destructive)
- CSS variables compiled from settings via `ThemeVariableCompiler`
- Live customizer with real-time iframe preview

## AI Architecture

- Provider-agnostic: OpenAI, Anthropic, local Ollama
- AI RAG: per-tenant vector store (pgvector for Postgres, JSON fallback for MySQL)
- Per-tenant rate limiting
- Prompt templates in `app/Domain/Ai/Prompts/`
- `ai_generate` fieldtype integrates AI into blueprint fields

## Security

- SAML 2.0 SSO (per-tenant IdP configuration)
- SCIM 2.0 user provisioning (Okta, Azure AD, Google Workspace)
- Audit log streaming to SIEM (Splunk, Datadog, Elastic, Syslog)
- Tamper-evident activity log chain (SHA-256 hash chain)
- Sanctum token-based API auth
- spatie/laravel-permission with team-scoped roles

## Connector Architecture

The `platform/laravel-cms-connector` package lets external Laravel apps connect via:
1. SSO Bridge (shared JWT)
2. Model Sync (bidirectional, with conflict resolution)
3. Event Bus (HMAC-signed webhooks)
4. Embedded Mode (CMS admin inside host app)
5. Headless API Client (REST + GraphQL)

## Testing Strategy

- Pest PHP for all tests
- `CompleteTenantIsolationTest` — mandatory for every tenant-scoped table
- `DomainIsolationTest` — per-domain routing verification
- Factory-based test data generation
- Feature tests for every controller action
