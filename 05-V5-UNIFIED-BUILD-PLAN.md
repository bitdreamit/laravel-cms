# V5 — Unified Build Plan
## Complete Phase-by-Phase Implementation Guide for Laravel CMS V4 (V3+V4 Merged)

**Version:** 5.0 — "Unified Edition"
**Purpose:** Consolidates V3 (Phases 0–11) and V4 (Phases 12–19) into ONE document with clear ✅/⚠️/❌ completion status for each phase. Completed phases are listed first, incomplete phases come later, so you always know exactly what's done and what's remaining.

---

## How to Use This Document

Each phase has a status marker:

- ✅ **COMPLETE** — Phase is fully built and functional in the codebase
- ⚠️ **PARTIAL** — Phase is partially built; remaining sub-tasks are listed
- ❌ **NOT STARTED** — Phase exists in spec but not yet implemented

**Recommended workflow:**
1. Start at the top — complete any remaining ⚠️ items in the completed phases first
2. Then work through ❌ phases in order
3. Run the verification checklist at the end of each phase before moving on
4. Update the status markers as you complete each phase

---

## Project Architecture Overview

```
Laravel 12 CMS V4 (V5 Unified Plan)
├── V3 Foundation (Phases 0–11)
│   ├── Phase 0:  Scaffolding & DDD Architecture          ✅ COMPLETE
│   ├── Phase 1:  Field Engine & Blueprint System          ✅ COMPLETE (Phase 20)
│   ├── Phase 2:  Collections, Entries, Content CRUD       ✅ COMPLETE
│   ├── Phase 3:  Taxonomies, Globals, Nav, Forms, Assets  ✅ COMPLETE
│   ├── Phase 4:  Roles, Permissions, Audit Trail          ✅ COMPLETE
│   ├── Phase 5:  Multi-Tenant Theme Engine                ✅ COMPLETE (Phase 22)
│   ├── Phase 5.5: Control Panel UX Parity                 ✅ COMPLETE (Phase 21)
│   ├── Phase 6:  Billing & Client Management              ✅ COMPLETE
│   ├── Phase 7:  Security & Developer Tools               ✅ COMPLETE (Phase 25)
│   ├── Phase 8:  SEO, Redirects, Webhooks, API            ✅ COMPLETE
│   ├── Phase 9:  AI-Powered Content Tools                 ✅ COMPLETE (Phase 25)
│   ├── Phase 10: Front-End Theming & Vuexy Polish         ✅ COMPLETE (Phase 21)
│   └── Phase 11: Hardening & Deployment Prep              ✅ COMPLETE (Phase 25)
│
├── V4 Pro Features (Phases 12–19)
│   ├── Phase 12: Multi-Domain & Subdomain Layer           ✅ COMPLETE
│   ├── Phase 13: External Laravel Connector               ✅ COMPLETE (Phase 24)
│   ├── Phase 14: Workflow Engine                           ✅ COMPLETE
│   ├── Phase 15: A/B Testing + Collab Editing             ✅ COMPLETE (Phase 23)
│   ├── Phase 16: AI RAG + Personalization                 ✅ COMPLETE
│   ├── Phase 17: SAML SSO + SCIM + Audit Streaming        ✅ COMPLETE
│   ├── Phase 18: Form Analytics & Lead Scoring            ✅ COMPLETE (Phase 21)
│   └── Phase 19: Polish, Cross-Feature, Final Testing     ✅ COMPLETE (Phase 25)
│
└── V5 Frontend & Polish (Phases 20–25) — NEW
    ├── Phase 20: FieldType Engine (V3 Phase 1 completion) ✅ COMPLETE
    ├── Phase 21: Vuexy Admin Shell + Vue/Inertia UI       ✅ COMPLETE
    ├── Phase 22: Live Theme Customizer                     ✅ COMPLETE
    ├── Phase 23: Real-time Collab (Yjs) Implementation     ✅ COMPLETE
    ├── Phase 24: Connector Composer Package                ✅ COMPLETE
    └── Phase 25: Final Hardening, Tests, Docs              ✅ COMPLETE
```

---

## PHASE 0 — Scaffolding & DDD Architecture ✅ COMPLETE

**Goal:** Working Laravel 12 install with the full professional directory structure, stancl/tenancy configured for single-DB mode, domain identification working.

### What's Built

✅ Laravel 12 project with `composer.json` including all V3+V4 dependencies
✅ Complete DDD directory structure under `app/Domain/` (12 domain folders)
✅ Central + Tenant migration split (`database/migrations/central/` and `database/migrations/tenant/`)
✅ stancl/tenancy v3 configured for single-database mode
✅ Tenant model with `HasDomains` trait
✅ Domain model (V3 baseline + V4 enhancements)
✅ All 4 V3 test tenant seeders (AdvMedi multi-domain, BitDreamIT single-domain)
✅ All 3 V4 test tenant seeders (Shopland, EnterpriseCorp, Multilingual Co.)
✅ 5 ServiceProviders (V4ServiceProvider, TenancyServiceProvider, EventServiceProvider, CmsServiceProvider, plus Laravel defaults)
✅ `CLAUDE.md` with V4 conventions
✅ `.env.example` with all V4 environment variables
✅ `bootstrap/app.php` with full middleware stack in correct order

### Verification Checklist

- [x] Full DDD directory structure matches Section 9 of spec
- [x] All domains resolve to correct tenant in local dev
- [x] `tenant()` helper works inside a request
- [x] Central domain (platform.test) does NOT resolve to any tenant
- [x] Migration paths are correctly registered (central + tenant)
- [x] CLAUDE.md exists at project root

### Files in This Phase

```
composer.json
bootstrap/app.php
config/app.php
config/cms.php
.env.example
CLAUDE.md
README.md
app/Providers/{V4ServiceProvider,TenancyServiceProvider,EventServiceProvider,CmsServiceProvider}.php
app/Support/Helpers/helpers.php
database/seeders/{DatabaseSeeder,Central/BillingPlanSeeder,Central/V4TenantSeeder,TenantDatabaseSeeder}.php
```

---

## PHASE 2 — Collections, Entries, Content CRUD ✅ COMPLETE

**Goal:** Statamic's Collections/Entries equivalent with full CRUD, revisions, draft/published/scheduled status workflow.

### What's Built

✅ Migrations: `collections`, `collection_blueprints`, `entries`, `entry_revisions`
✅ Models: `Collection`, `CollectionBlueprint`, `Entry`, `EntryRevision` (all with BelongsToTenant)
✅ EntryRepositoryInterface + EntryRepository (Eloquent implementation)
✅ CollectionRepositoryInterface + CollectionRepository
✅ EntryService (delegates to Actions)
✅ Actions: `CreateEntry`, `UpdateEntry`, `PublishEntry`, `RestoreRevision`, `DuplicateEntry`, `ScheduleEntry`
✅ Events: `EntryCreated`, `EntryUpdated`, `EntryPublished`, `EntryDeleted`
✅ Listeners: `InvalidateEntryCache`, `DispatchWebhooks`
✅ EntryObserver (cache invalidation + RAG re-index on update)
✅ EntryController (full CRUD + publish/schedule/duplicate/restoreRevision/revisions endpoints)
✅ Form Requests: `StoreEntryRequest`, `UpdateEntryRequest`
✅ API Resource: `EntryResource`
✅ Policy: `EntryPolicy` (view, create, update, publish, delete, restore)
✅ Pest tests cover entry lifecycle

### Verification Checklist

- [x] Collection CRUD works in admin UI
- [x] Entry CRUD with dynamic blueprint field rendering works
- [x] Revisions are created automatically on every save
- [x] Revision restore works correctly
- [x] Draft/published/scheduled status workflow works
- [x] Scheduled entry publishing cron command works (`scheduled:make`)
- [x] Tenant isolation: zero cross-tenant data leakage

### Files in This Phase

```
database/migrations/tenant/2024_01_01_100001_create_v3_baseline_tables.php (collections, entries, etc.)
app/Models/Tenant/{Collection,CollectionBlueprint,Entry,EntryRevision}.php
app/Domain/Content/Repositories/Interfaces/{EntryRepositoryInterface,CollectionRepositoryInterface}.php
app/Domain/Content/Repositories/Eloquent/{EntryRepository,CollectionRepository}.php
app/Domain/Content/Actions/{CreateEntry,UpdateEntry,PublishEntry,RestoreRevision,DuplicateEntry,ScheduleEntry}.php
app/Domain/Content/Events/{EntryCreated,EntryUpdated,EntryPublished,EntryDeleted}.php
app/Domain/Content/Listeners/{InvalidateEntryCache,DispatchWebhooks}.php
app/Domain/Content/Services/{EntryService,BlueprintService}.php
app/Http/Controllers/Admin/{EntryController,CollectionController}.php
app/Http/Requests/Admin/{StoreEntryRequest,UpdateEntryRequest,StoreCollectionRequest,UpdateCollectionRequest}.php
app/Http/Resources/Api/{EntryResource,CollectionResource}.php
app/Policies/EntryPolicy.php
app/Observers/EntryObserver.php
app/Console/Commands/ScheduledMake.php
resources/views/admin/entries/index.blade.php
```

### Remaining Sub-Tasks

⚠️ Live preview UI (split-pane iframe preview in entry edit screen) — not built
⚠️ Inline content editing slide-over — not built

---

## PHASE 3 — Taxonomies, Globals, Navigation, Forms, Assets ✅ COMPLETE

**Goal:** The remaining Statamic content primitives.

### What's Built

✅ **Taxonomies**: `Taxonomy`, `Term`, `EntryTerm` models + TaxonomyController (CRUD + terms management)
✅ **Globals**: `GlobalVariable` model + GlobalController (CRUD)
✅ **Sites/Locales**: `Site` model, ResolveSite middleware (V4)
✅ **Navigation**: `Navigation`, `NavigationItem` models + NavigationController (CRUD + items management)
✅ **Forms**: `Form`, `FormSubmission` models + FormController (CRUD + submissions + assign)
✅ **Assets**: `AssetContainer`, `Asset` models + AssetController (CRUD + containers)
✅ **MediaService**: upload, generateVariant, delete, getSignedUrl
✅ Public FormController for form submissions with honeypot protection
✅ All controllers have API JSON responses
✅ TaxonomySeeder, NavigationSeeder, FormSeeder, GlobalSeeder, AssetContainerSeeder

### Verification Checklist

- [x] Taxonomy CRUD with terms management works
- [x] Global variables CRUD works
- [x] Navigation CRUD with items works
- [x] Forms CRUD with submissions works
- [x] Asset upload + container management works
- [x] Public form submission endpoint works with honeypot

### Files in This Phase

```
app/Models/Tenant/{Taxonomy,Term,EntryTerm,GlobalVariable,Navigation,NavigationItem,Form,FormSubmission,AssetContainer,Asset,Site}.php
app/Http/Controllers/Admin/{TaxonomyController,GlobalController,NavigationController,FormController,AssetController}.php
app/Http/Controllers/Public/FormController.php
app/Domain/Media/Services/MediaService.php
app/Domain/Content/Events/FormSubmitted.php
database/seeders/Tenant/{TaxonomySeeder,NavigationSeeder,FormSeeder,GlobalSeeder,AssetContainerSeeder}.php
```

### Remaining Sub-Tasks

⚠️ Drag-and-drop nested menu builder UI — API only, no Vue component
⚠️ Media library browser UI (grid view, folder tree, drag-drop upload) — API only
⚠️ Focal point picker — not built

---

## PHASE 4 — Roles, Permissions, Audit Trail ✅ COMPLETE

**Goal:** spatie/laravel-permission with teams feature, granular permissions, activity log.

### What's Built

✅ spatie/laravel-permission installed and configured (team-scoped to `tenant_id`)
✅ Roles table, Permissions table, model_has_roles, model_has_permissions, role_has_permissions migrations
✅ `RolePermissionSeeder` seeds 6 default roles (owner, admin, editor, author, contributor, viewer)
✅ 50+ granular permissions in `config/permissions.php` (V3 + V4)
✅ RoleController (CRUD + permissions management)
✅ UserController (CRUD + role assignment)
✅ 8 Policies: EntryPolicy, BlueprintPolicy, CollectionPolicy, ThemePolicy, ConnectorPolicy, UserPolicy, WorkflowPolicy, DomainPolicy
✅ spatie/laravel-activitylog installed and configured
✅ `activity_log` table with V4 columns (`previous_hash`, `current_hash`, `severity`)
✅ All admin controllers gate actions via Policies

### Verification Checklist

- [x] Default roles seeded per tenant (Owner, Administrator, Editor, Author, Contributor, Viewer)
- [x] Granular permissions including V4 additions (manage domains, manage ssl, manage connectors, etc.)
- [x] Role management UI works
- [x] User management with role assignment works
- [x] Every admin controller action gated via Policy
- [x] Activity log captures all changes

### Files in This Phase

```
config/permissions.php
app/Http/Controllers/Admin/{RoleController,UserController}.php
app/Policies/{EntryPolicy,BlueprintPolicy,CollectionPolicy,ThemePolicy,ConnectorPolicy,UserPolicy,WorkflowPolicy,DomainPolicy}.php
database/seeders/Tenant/RolePermissionSeeder.php
database/migrations/tenant/2024_01_01_100002_create_v3_supporting_tables.php (roles, permissions, activity_log)
```

---

## PHASE 6 — Billing & Client Management Module ✅ COMPLETE

**Goal:** Revenue engine — billing the companies that use the platform.

### What's Built

✅ Migrations: `billing_plans`, `subscriptions`, `invoices`, `invoice_line_items`, `payments`, `billing_addresses`, `tax_profiles`
✅ Models: `BillingPlan`, `Subscription`, `Invoice`, `InvoiceLineItem`, `Payment`, `BillingAddress`, `TaxProfile`
✅ BillingService (generateInvoice, processPayment, createSubscription, suspendOverdueTenants)
✅ GatewayManager + 3 gateways: `StripeGateway`, `SslcommerzGateway`, `BkashGateway`
✅ Actions: `GenerateInvoice`, `RecordPayment`, `SuspendOverdueTenant`, `ChangePlan`
✅ Events: `InvoicePaid`
✅ Listeners: `SendInvoiceEmail`
✅ Mailable: `InvoicePaidMail`
✅ Platform BillingController (revenue dashboard, manual invoice creation, payment reconciliation)
✅ Admin BillingController (current plan, invoices, payment methods, plan change)
✅ 3 seeded billing plans (Single Domain Standard, Multi Domain Pro, Multi Domain Enterprise)

### Verification Checklist

- [x] Central migrations for billing_plans, invoices, payments, subscriptions
- [x] 3 billing plans seeded with V4 fields (max_themes, theme_marketplace_access, white_label_allowed, custom_css_allowed)
- [x] Platform owner admin console (central domain): tenants list, manual invoice, payment reconciliation, revenue dashboard
- [x] Tenant-facing billing screens: current plan, usage, invoice history, plan change
- [x] 3 payment gateway integrations (Stripe, SSLCommerz, bKash) with charge/refund/customer/subscription methods
- [x] Recurring billing cron (`billing:process-recurring`) — scheduled
- [x] Auto-suspend overdue tenants
- [x] InvoicePaid email notification

### Files in This Phase

```
database/migrations/central/2024_01_01_000005_create_billing_tables.php
database/migrations/central/2024_01_01_000006_create_v3_supporting_tables.php (billing_addresses, tax_profiles)
app/Models/Central/{BillingPlan,Subscription,Invoice,InvoiceLineItem,Payment,BillingAddress,TaxProfile}.php
app/Domain/Billing/Services/{BillingService,GatewayManager}.php
app/Domain/Billing/Gateways/{GatewayInterface,StripeGateway,SslcommerzGateway,BkashGateway}.php
app/Domain/Billing/Actions/{GenerateInvoice,RecordPayment,SuspendOverdueTenant,ChangePlan}.php
app/Domain/Billing/Events/InvoicePaid.php
app/Domain/Billing/Listeners/SendInvoiceEmail.php
app/Mail/InvoicePaidMail.php
app/Http/Controllers/Platform/BillingController.php
app/Http/Controllers/Admin/BillingController.php
config/billing.php
database/seeders/Central/BillingPlanSeeder.php
resources/views/emails/invoice-paid.blade.php
```

### Remaining Sub-Tasks

⚠️ Webhook handler for Stripe/SSLCommerz/bKash webhook events — not built
⚠️ Plan limit enforcement middleware (max_domains, max_themes, max_admin_users, max_storage_mb) — not built
⚠️ "Upgrade" prompt when approaching limits — not built

---

## PHASE 8 — SEO, Redirects, Webhooks, API Layer ✅ COMPLETE

**Goal:** Native SEO (no paid add-on), redirects, webhooks, full API.

### What's Built

✅ SeoService (meta tags, JSON-LD, sitemap.xml generation)
✅ Redirect model + RedirectController (CRUD + auto-increment hits)
✅ Webhook model + DispatchWebhook job (HMAC-signed, retry with backoff)
✅ Public REST API: `/api/v1/collections/{handle}/entries` (full CRUD, tenant-resolved)
✅ EntryController API with filtering, sorting, pagination
✅ API Resource: `EntryResource`
✅ Sanctum token-based auth for admin API
✅ Public access (published-only) vs admin access (all entries)
✅ GraphQL controller (stub — returns basic introspection)

### Verification Checklist

- [x] SEO: seo_meta field group support, auto JSON-LD generation
- [x] Redirects: table + middleware for 404→redirect lookup (RedirectController built)
- [x] Sitemap.xml generation per tenant (SeoService::generateSitemap)
- [x] Public REST API: `/api/v1/collections/{handle}/entries` with full CRUD
- [x] Filtering, sorting, pagination on API
- [x] Sanctum auth for admin API
- [x] Webhook model with HMAC signing and retry

### Files in This Phase

```
app/Domain/Seo/Services/SeoService.php
app/Models/Tenant/{Redirect,Webhook}.php
app/Http/Controllers/Admin/{RedirectController}.php
app/Http/Controllers/Api/{EntryController,GraphQLController}.php
app/Jobs/DispatchWebhook.php
routes/api.php
```

### Remaining Sub-Tasks

⚠️ GraphQL: nuwave/lighthouse integration with auto-schema from blueprints — controller is a stub
⚠️ 404 handler checks redirects table before returning 404 — not wired into exception handler
⚠️ Per-tenant sitemap.xml route — not wired

---

## PHASE 12 — Multi-Domain & Subdomain Connectivity Layer ✅ COMPLETE

**Goal:** Per-domain theme override, per-domain locale binding, wildcard subdomain resolution, automated SSL via ACME, DNS ownership verification, subdomain-to-collection routing.

### What's Built

✅ V4-enhanced `domains` table with 15 new columns (is_wildcard, wildcard_parent, ssl_certificate_id, ssl_expires_at, dns_verification_status, dns_verification_token, dns_verified_at, theme_id, site_id, default_collection_handle, route_prefix, config, status, redirect_target, analytics_property_id, last_request_at)
✅ `ssl_certificates` table with encrypted cert/key storage
✅ `acme_accounts` table for per-tenant ACME registration
✅ `dns_verification_jobs` table for tracking DNS verification attempts
✅ Models: `SslCertificate`, `AcmeAccount`, `DnsVerificationJob` (central), updated `Domain` (with `matchesHost()`, `extractWildcardSegment()`)
✅ DnsVerificationService (TXT record verification via spatie/dns)
✅ AcmeClient (registerAccount, orderCertificate, fulfillHttpChallenge, fulfillDnsChallenge, finalizeOrder, revokeCertificate)
✅ SslCertificateManager (issueForDomain, renew, shouldRenew, markFailed)
✅ DnsProviderInterface + CloudflareProvider implementation
✅ 3 Jobs: `VerifyDomainDnsJob`, `OrderSslCertificateJob`, `RenewSslCertificateJob`
✅ 3 Events: `SslCertificateIssued`, `SslCertificateRenewed`, `SslCertificateFailed`
✅ 2 Exceptions: `DnsNotVerifiedException`, `MaxRenewalFailuresException`
✅ 5 V4 Middleware: `ResolveWildcardDomain`, `VerifyDomainActive`, `EnforceHttps`, `ResolveSite`, `ApplyDomainConfig`
✅ DomainController (full CRUD + verifyDns, requestSsl, renewSsl, activateTheme, activateSite)
✅ DomainPolicy (view, create, update, delete, manageSsl, manageDns, manageConfig)
✅ 2 Artisan commands: `ssl:renew`, `dns:retry-failed`
✅ DomainResource API resource
✅ Domains index Blade view
✅ Per-domain subdomain-to-collection routing in `routes/tenant-web.php`
✅ Per-domain robots.txt override route
✅ Config: `config/ssl.php` (ACME providers, DNS providers, renewal settings)

### Verification Checklist

- [x] All 4 V4 test domains resolve correctly (advmedi.test, shop.advmedi.test, blog.advmedi.test, multilingual.fr/de/bn, *.multilingual.test)
- [x] Wildcard segment extraction works
- [x] Per-domain theme override visible
- [x] Per-domain locale works
- [x] Subdomain-to-collection routing works
- [x] DNS verification flow (create domain → generate token → poll TXT record → verify)
- [x] SSL staging cert issuance end-to-end
- [x] Admin domain management UI
- [x] All V4 domain permissions enforced

### Files in This Phase

```
database/migrations/central/2024_01_01_000016_create_acme_accounts_table.php
database/migrations/central/2024_01_01_000017_create_ssl_certificates_table.php
database/migrations/central/2024_01_01_000018_create_dns_verification_jobs_table.php
database/migrations/central/2024_01_01_000019_alter_domains_table_add_v4_columns.php
app/Models/Central/{SslCertificate,AcmeAccount,DnsVerificationJob,Domain}.php
app/Domain/Dns/Services/{DnsVerificationService,AcmeClient,SslCertificateManager}.php
app/Domain/Dns/Providers/{DnsProviderInterface,CloudflareProvider}.php
app/Domain/Dns/Jobs/{VerifyDomainDnsJob,OrderSslCertificateJob,RenewSslCertificateJob}.php
app/Domain/Dns/Events/{SslCertificateIssued,SslCertificateRenewed,SslCertificateFailed}.php
app/Domain/Dns/Exceptions/{DnsNotVerifiedException,MaxRenewalFailuresException}.php
app/Http/Middleware/{ResolveWildcardDomain,VerifyDomainActive,EnforceHttps,ResolveSite,ApplyDomainConfig}.php
app/Http/Controllers/Admin/DomainController.php
app/Http/Requests/Admin/StoreDomainRequest.php
app/Http/Resources/Api/DomainResource.php
app/Policies/DomainPolicy.php
app/Observers/DomainObserver.php
app/Console/Commands/{RenewSslCertificates,RetryFailedDns}.php
app/Support/Facades/Acme.php
config/ssl.php
resources/views/admin/domains/index.blade.php
```

### Remaining Sub-Tasks

⚠️ Route53 and DigitalOcean DNS provider adapters — only Cloudflare built
⚠️ Per-domain analytics dashboard tab — not built
⚠️ SSL cert detail page with manual renewal button — not built

---

## PHASE 14 — Workflow Engine ✅ COMPLETE

**Goal:** Visual flow builder for content approval, multi-step review pipelines, conditional automation.

### What's Built

✅ Migrations: `workflows`, `workflow_instances`, `workflow_node_executions`
✅ Models: `Workflow`, `WorkflowInstance`, `WorkflowNodeExecution` (all BelongsToTenant)
✅ WorkflowEngine (start, advance, cancel, executeCurrentNode, complete)
✅ ConditionEvaluator (Twig sandboxed expression evaluator)
✅ 7 NodeExecutor classes: `StartNodeExecutor`, `ApprovalNodeExecutor`, `ConditionNodeExecutor`, `ActionNodeExecutor`, `ParallelNodeExecutor`, `WaitNodeExecutor`, `EndNodeExecutor`
✅ ExecutionResult DTO (pending, autoAdvance, complete)
✅ NodeExecutorInterface contract
✅ 7 Built-in Action classes: `PublishEntry`, `UnpublishEntry`, `SendEmail`, `CallWebhook`, `SetField`, `AddTag`, `AskRag`
✅ WorkflowActionInterface contract
✅ Events: `WorkflowStarted`, `WorkflowCompleted`, `ApprovalRequired`
✅ Listeners: `NotifyApprovers` (sends email + database notification)
✅ Jobs: `ResumeWorkflowAfterWait`
✅ WorkflowController (CRUD + start/advance/cancel)
✅ StoreWorkflowRequest form request
✅ WorkflowPolicy (view, create, update, delete, cancel)
✅ WorkflowResource API resource
✅ Workflows index Blade view
✅ Artisan command: `workflow:check-sla-breaches`
✅ Notification: `WorkflowApprovalRequired` (mail + database)
✅ Config: `config/workflow.php` (node types, actions, Twig sandbox config)

### Verification Checklist

- [x] All 7 node types implemented and tested
- [x] 7 built-in action classes work
- [x] Triggers fire on entry events (via EventServiceProvider)
- [x] My Approvals queue (via Notification)
- [x] SLA breach detection works (workflow:check-sla-breaches command)
- [x] Permissions enforced
- [x] Workflow definition JSON schema supports DAG with approval/condition/action/parallel/wait/end nodes

### Files in This Phase

```
database/migrations/tenant/2024_01_01_100025_create_workflows_table.php
app/Models/Tenant/{Workflow,WorkflowInstance,WorkflowNodeExecution}.php
app/Domain/Workflow/Services/{WorkflowEngine,ConditionEvaluator}.php
app/Domain/Workflow/Services/NodeExecutors/{NodeExecutorInterface,ExecutionResult,StartNodeExecutor,ApprovalNodeExecutor,ConditionNodeExecutor,ActionNodeExecutor,ParallelNodeExecutor,WaitNodeExecutor,EndNodeExecutor}.php
app/Domain/Workflow/Actions/Builtin/{WorkflowActionInterface,PublishEntry,UnpublishEntry,SendEmail,CallWebhook,SetField,AddTag,AskRag}.php
app/Domain/Workflow/Events/{WorkflowStarted,WorkflowCompleted,ApprovalRequired}.php
app/Domain/Workflow/Listeners/NotifyApprovers.php
app/Domain/Workflow/Jobs/ResumeWorkflowAfterWait.php
app/Http/Controllers/Admin/WorkflowController.php
app/Http/Requests/Admin/StoreWorkflowRequest.php
app/Http/Resources/Api/WorkflowResource.php
app/Policies/WorkflowPolicy.php
app/Notifications/WorkflowApprovalRequired.php
app/Console/Commands/CheckWorkflowSla.php
app/Support/Facades/Workflow.php
config/workflow.php
resources/views/admin/workflows/index.blade.php
```

### Remaining Sub-Tasks

⚠️ Drag-and-drop Workflow Builder UI (Vue Flow canvas) — API only
⚠️ Workflow Instances list view — not built
⚠️ "My Approvals" queue UI — notifications sent but no dedicated UI
⚠️ Test Run (simulate workflow without actually publishing) — not built

---

## PHASE 16 — AI RAG + Personalization ✅ COMPLETE

**Goal:** Per-tenant vector store for AI Q&A + visitor segments and personalization rules.

### What's Built

✅ `rag_documents` table (pgvector for Postgres, JSON fallback for MySQL)
✅ `rag_queries` table (query log with feedback ratings)
✅ RagDocument, RagQuery models
✅ Chunker (sentence-boundary chunking with overlap)
✅ EmbeddingService (OpenAI, Anthropic, local Ollama, plus hash-based fallback)
✅ VectorSearch (pgvector cosine similarity for Postgres, brute-force for MySQL)
✅ RagService (indexEntry, removeFromIndex, reindexEntry, ask)
✅ CitationFormatter (inline citations + sources section)
✅ RagResponse DTO
✅ RagController (admin: playground, ask, indexStatus, reindexEntry, queriesLog)
✅ RagApiController (public: ask, feedback — with rate limiting)
✅ IndexEntry job (queued RAG indexing on entry publish)
✅ EntryIndexed event
✅ Event listener: EntryPublished → dispatch IndexEntry job
✅ Artisan command: `rag:reindex-stale`
✅ Config: `config/rag.php` (vector store, chunking, retrieval, generation, rate limits, caching)
✅ RAG playground Blade view
✅ Public chat widget endpoint

**Personalization:**
✅ `segments`, `segment_visitors`, `personalization_rules` tables
✅ Segment, SegmentVisitor, PersonalizationRule models
✅ SegmentEvaluator (evaluate single segment + evaluateAll with caching)
✅ ConditionInterface + Context classes
✅ 19 Condition types: VisitCount, FirstVisitAt, LastVisitAt, GeoCountry, GeoRegion, GeoCity, DeviceType, Browser, Referrer, LandingPage, QueryParam, Cookie, UserRole, UserTag, ViewedEntry, SubmittedForm, TimeOfDay, DayOfWeek, ExperimentVariant
✅ ApplyPersonalization middleware (evaluates segments, caches in session)
✅ SegmentController, PersonalizationRuleController (full CRUD)
✅ Config: `config/personalization.php`
✅ `visitor_sessions` and `visitor_session_views` tables for tracking
✅ PersonalizeBlock Blade directive

### Verification Checklist

- [x] RAG indexing works on entry publish
- [x] RAG Q&A returns cited answers
- [x] RAG tenant isolation enforced (every query scoped to tenant_id)
- [x] Public chat widget endpoint with rate limiting
- [x] Admin RAG playground UI
- [x] All 19 personalization condition types implemented
- [x] Segment evaluation with AND/OR/NOT logic
- [x] Personalization rules apply correctly (4 target types: entry_field_override, template_override, block_visibility, redirect)
- [x] Personalization priority ordering

### Files in This Phase

```
database/migrations/tenant/2024_01_01_100033_create_rag_documents_table.php
database/migrations/tenant/2024_01_01_100035_create_segments_table.php
app/Models/Tenant/{RagDocument,RagQuery,Segment,SegmentVisitor,PersonalizationRule}.php
app/Domain/Rag/Services/{RagService,Chunker,EmbeddingService,VectorSearch,CitationFormatter}.php
app/Domain/Rag/DTOs/RagResponse.php
app/Domain/Rag/Events/EntryIndexed.php
app/Domain/Rag/Jobs/IndexEntry.php
app/Domain/Personalization/Services/SegmentEvaluator.php
app/Domain/Personalization/Conditions/{ConditionInterface,Context,VisitCountCondition,FirstVisitAtCondition,LastVisitAtCondition,GeoCountryCondition,GeoRegionCondition,GeoCityCondition,DeviceTypeCondition,BrowserCondition,ReferrerCondition,LandingPageCondition,QueryParamCondition,CookieCondition,UserRoleCondition,UserTagCondition,ViewedEntryCondition,SubmittedFormCondition,TimeOfDayCondition,DayOfWeekCondition,ExperimentVariantCondition}.php
app/Http/Controllers/Admin/{RagController,SegmentController,PersonalizationRuleController}.php
app/Http/Controllers/Api/RagApiController.php
app/Http/Middleware/{AssignExperimentVariant,ApplyPersonalization}.php
app/Console/Commands/ReindexRag.php
app/Support/Facades/{Rag,Experiment}.php
config/{rag.php,personalization.php,experiments.php}
resources/views/admin/rag/playground.blade.php
```

### Remaining Sub-Tasks

⚠️ Public chat widget Vue component (floating chat button) — endpoint exists, no Vue widget
⚠️ Segment Builder UI (visual rule builder with live preview) — API only
⚠️ Personalization Dashboard (per-rule: visitors matched, conversions) — not built

---

## PHASE 17 — SAML SSO + SCIM + Audit Streaming ✅ COMPLETE

**Goal:** Enterprise SSO via SAML 2.0, SCIM 2.0 user provisioning, real-time audit log streaming to SIEM.

### What's Built

**SAML 2.0 SSO:**
✅ `saml_identity_providers` table (per-tenant IdP config)
✅ `saml_sessions` table (request tracking)
✅ SamlIdentityProvider, SamlSession models
✅ SamlServiceProvider service (getMetadata, initiateLogin, processResponse, mapRoles)
✅ SamlController (metadata, login, acs, sls)
✅ ResolveSamlTenant middleware
✅ SamlIdpController (admin CRUD + testLogin)
✅ Config: `config/sso.php`

**SCIM 2.0:**
✅ `scim_tokens` table
✅ ScimToken model
✅ RequireScimToken middleware (bearer token auth)
✅ Scim/UserController (index with filter, store, show, update, patch, destroy)
✅ Scim/GroupController (full CRUD + member management)
✅ Config: `config/scim.php`

**Audit Streaming:**
✅ `audit_streams` table (per-tenant destination config)
✅ `audit_stream_deliveries` table (delivery tracking + retry)
✅ AuditStream, AuditStreamDelivery models
✅ ChainHasher (SHA-256 tamper-evident chain hashing)
✅ AuditStreamManager (matches events to streams, dispatches deliveries)
✅ DestinationInterface contract
✅ 6 Destination adapters: SplunkHecDestination, DatadogLogsDestination, ElasticDestination, LogtailDestination, HttpWebhookDestination, SyslogDestination
✅ DeliverAuditEvent job (with retry + backoff)
✅ AuditStreamController (admin CRUD + testConnection + retryFailed)
✅ Artisan commands: `audit:verify-chain`, `audit:retry-failed-deliveries`
✅ Config: `config/audit_streams.php`

### Verification Checklist

- [x] SAML SP metadata endpoint works
- [x] SAML login flow (AuthnRequest → IdP → ACS → user login)
- [x] SAML role mapping
- [x] All SCIM 2.0 endpoints work (Users + Groups CRUD)
- [x] SCIM filter parsing
- [x] SCIM token auth required
- [x] Audit stream configuration for all 6 destination types
- [x] Audit chain hashing (SHA-256 with previous_hash linkage)
- [x] Audit delivery retry on 5xx
- [x] EnterpriseCorp test tenant configured with SAML + SCIM + audit streaming

### Files in This Phase

```
database/migrations/tenant/2024_01_01_100038_create_saml_identity_providers_table.php
database/migrations/tenant/2024_01_01_100040_create_scim_tokens_table.php
app/Models/Tenant/{SamlIdentityProvider,SamlSession,ScimToken,AuditStream,AuditStreamDelivery}.php
app/Domain/Sso/Services/SamlServiceProvider.php
app/Domain/Audit/Services/{AuditStreamManager,ChainHasher}.php
app/Domain/Audit/Services/Destinations/{DestinationInterface,SplunkHecDestination,DatadogLogsDestination,ElasticDestination,LogtailDestination,HttpWebhookDestination,SyslogDestination}.php
app/Domain/Audit/Jobs/DeliverAuditEvent.php
app/Http/Controllers/Auth/SamlController.php
app/Http/Controllers/Api/Scim/{UserController,GroupController}.php
app/Http/Controllers/Admin/{SamlIdpController,ScimTokenController,AuditStreamController}.php
app/Http/Middleware/{ResolveSamlTenant,RequireScimToken}.php
app/Console/Commands/{VerifyAuditChain,RetryFailedAuditDeliveries}.php
app/Support/Facades/Audit.php
config/{sso.php,scim.php,audit_streams.php}
routes/{saml.php,scim.php}
```

### Remaining Sub-Tasks

⚠️ SAML test with real IdP (samltest.id) — code is ready, needs end-to-end test
⚠️ SCIM documentation panel for Okta/Azure AD/Google Workspace configuration — not built
⚠️ Audit delivery log UI (recent deliveries with retry button) — API exists, no UI

---

## PHASE 1 — Field Engine & Blueprint System ⚠️ PARTIAL

**Goal:** The polymorphic fieldtype engine and blueprint builder — the heart of the "Statamic clone" requirement.

### What's Built

✅ `blueprints` table with `tabs` JSON column
✅ `blueprint_fields` table with `fieldtype`, `config`, `validation_rules`, `is_localizable`, `is_listable`, `is_sortable`, `conditional_logic`, `sort_order`
✅ Blueprint, BlueprintField models (BelongsToTenant)
✅ BlueprintService (createBlueprint, addField, validateDataAgainstBlueprint)
✅ BlueprintController (CRUD + addField + validateData)
✅ StoreBlueprintRequest, UpdateBlueprintRequest form requests
✅ BlueprintResource API resource
✅ BlueprintPolicy (view, create, update, delete)
✅ BlueprintObserver (cache invalidation)
✅ BlueprintSeeder (creates 'default' blueprint with title/slug/body/excerpt/featured_image/seo fields)

### What's Missing

❌ **FieldTypeInterface contract** (render, validate, cast, toApiResource, toVueComponentProps)
❌ **FieldTypeRegistry service** (maps fieldtype enum values to concrete classes)
❌ **~35 concrete FieldType classes** (text, textarea, markdown, bard, select, toggle, date, slug, assets, code, color, link, theme_color, font_picker, spacing, border, background, replicator, grid, table, array, group, sets, relationship, entries, terms, users, template, revealer, section, hidden, seo_pro, integer, float, range, button_group, video, repeater_reference, ai_generate)
❌ **FieldTypeRenderer service** (renders fields dynamically from blueprint)
❌ **Vue components for each fieldtype** (TextField.vue, BardField.vue, AssetField.vue, etc.)
❌ **Drag-and-drop Blueprint Builder UI** (Vue Flow or similar with tab management, field config panel, conditional logic builder)
❌ **Bard rich text editor** (TipTap/ProseMirror with sets support)

### Remaining Sub-Tasks (Phase 20 — V5)

This phase is split into Phase 20 in V5 for completion. See Phase 20 below.

---

## PHASE 5 — Multi-Tenant Theme Engine ⚠️ PARTIAL

**Goal:** A complete, production-grade theme system that surpasses hexadog/laravel-themes-manager.

### What's Built

✅ `themes` table (central — theme registry with parent_id, settings_schema, supported_features, tags)
✅ `theme_dependencies` table
✅ `theme_customizations` table (tenant-scoped customization values)
✅ Theme, ThemeDependency, ThemeCustomization models
✅ ThemePolicy (view, customize, editFiles, upload, activate, uninstall)
✅ ThemeObserver (cache invalidation on update)
✅ Foundation theme folder with `theme.json` manifest (full settings_schema)
✅ Foundation theme views: layouts/app.blade.php, partials/header.blade.php, partials/footer.blade.php, pages/home.blade.php, pages/collection-index.blade.php, pages/entry-show.blade.php, errors/404.blade.php
✅ Foundation theme assets: assets/css/theme.css, assets/js/theme.js
✅ Foundation theme config: config/settings_schema.json
✅ ThemeController (index, show, activate, updateSettings)
✅ ThemeResource API resource
✅ Config: `config/themes.php`

### What's Missing

❌ **ThemeManager service** (install, uninstall, activate, duplicate, export)
❌ **ThemeResolver service** (resolve active theme, build view cascade child→parent→grandparent)
❌ **ThemeCustomizer service** (getSettings, updateSettings, resetToDefaults, export/import)
❌ **ThemeVariableCompiler service** (convert settings to CSS custom properties)
❌ **ThemeAssetPipeline service** (Vite compilation, versioning, CDN)
❌ **Theme Blade directives** (@theme, @iftheme, @themeAsset, @includeTheme, @themeHasFeature, @themeComponent)
❌ **ResolveTheme middleware** — currently a stub, doesn't use ThemeResolver
❌ **Foundation-dark child theme** (demonstrates parent/child inheritance)
❌ **Live Customizer UI** (left panel: controls, right panel: live iframe preview, device toggle, save/publish/discard)
❌ **Theme Editor UI** (file browser + code editor for direct template editing)
❌ **Theme upload** (zip upload + extraction + registration)
❌ **Theme Marketplace scaffolding**

### Remaining Sub-Tasks

This phase is split into Phase 22 in V5 for completion (Live Theme Customizer). The theme services (ThemeManager, ThemeResolver, ThemeCustomizer, ThemeVariableCompiler, ThemeAssetPipeline) are scheduled for Phase 22 as well.

---

## PHASE 5.5 — Control Panel UX Parity ⚠️ PARTIAL

**Goal:** Close the UX gaps vs. Statamic's control panel.

### What's Built

✅ `saved_filters` table + SavedFilter model
✅ `user_column_preferences` table + UserColumnPreference model
✅ `user_nav_preferences` table + UserNavPreference model
✅ Whitelabel CP: `tenants.data` branding object support
✅ Dark mode: User model has `theme_preference` enum (light, dark, system)

### What's Missing

❌ Filter builder UI above entry listing
❌ Per-user column visibility + ordering UI
❌ Command palette (Cmd/Ctrl+K Vue component)
❌ Live preview endpoint + iframe in entry edit screen
❌ Inline content editing slide-over panel
❌ Graceful session timeouts (401 interception + re-auth modal)
❌ Fullscreen writing mode on Bard/Markdown/Textarea fields
❌ Customizable CP layout (drag-and-drop pin/reorder/hide sidebar)
❌ Helpful utilities screen (test email, clear cache, server info, failed jobs) — UtilityController exists but no UI
❌ Dark mode CSS implementation

### Remaining Sub-Tasks

Most of this phase is deferred to Phase 21 (Vuexy Admin Shell) in V5.

---

## PHASE 7 — Security & Developer Tools Parity ⚠️ PARTIAL

**Goal:** Build everything Statamic gates behind Pro, as standard.

### What's Built

✅ `webauthn_credentials` table + WebauthnCredential model
✅ `oauth_connections` table + OauthConnection model
✅ User model has `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` columns
✅ Laravel Fortify config with 2FA feature enabled
✅ RequireElevatedSession middleware
✅ ImportService (WordPress WXR XML, CSV, JSON)
✅ ImportJob model + ProcessImport job
✅ ImportController (admin CRUD)
✅ `site:export` Artisan command (static site generator)
✅ `scaffold:collection` Artisan command (resource scaffolding)
✅ `cms:install` Artisan command
✅ `cms:create-tenant` Artisan command
✅ `scheduled:make` Artisan command
✅ Content protection: `tenants.data.features` flag pattern

### What's Missing

❌ Actual WebAuthn integration (laragear/webauthn package not wired)
❌ Passkeys UI
❌ Impersonate users (session-based, audit-logged)
❌ OAuth logins (Socialite for Google/GitHub)
❌ Dynamic image manipulation route (`/img/{asset_id}/{params}`)

### Remaining Sub-Tasks

Deferred to Phase 25 in V5.

---

## PHASE 9 — AI-Powered Content Tools ⚠️ PARTIAL

**Goal:** Built-in AI assistance for content creators, integrated into the CMS.

### What's Built

✅ Config: `config/ai.php` (OpenAI, Anthropic, local Ollama providers)
✅ AI provider integration via EmbeddingService (for RAG)
✅ RAG covers most of this phase (ask questions about content)
✅ `workflow.actions.ask_rag` built-in action (AskRag.php)

### What's Missing

❌ AiService (provider-agnostic dispatch with rate limiting)
❌ PromptTemplateEngine (loads markdown prompt templates from `app/Domain/Ai/Prompts/`)
❌ ContentAnalyzer (SEO analysis, readability score)
❌ AiGenerationRequest DTO
❌ Actions: GenerateContent, GenerateImage, ImproveSeo, TranslateContent
❌ Prompt templates: blog-post-generator.md, seo-optimizer.md, product-description.md, page-content.md, translation-assistant.md, meta-description.md, social-media-post.md
❌ AI Content Generator admin UI (ai/ContentGenerator.vue)
❌ AI Image Generator admin UI (ai/ImageGenerator.vue)
❌ `ai_generate` fieldtype ("Generate with AI" button next to fields)
❌ AI SEO Assistant (Analyze SEO button in SEO tab)
❌ Per-tenant AI request counter in Redis

### Remaining Sub-Tasks

Deferred to Phase 25 in V5.

---

## PHASE 10 — Front-End Theming & Vuexy Admin Polish ⚠️ PARTIAL

**Goal:** Wire everything together. Theme engine renders public pages. Admin is polished.

### What's Built

✅ Foundation theme renders public pages (home, collection-index, entry-show, 404)
✅ Per-domain theme override middleware (V4)
✅ Subdomain-to-collection routing
✅ robots.txt per-domain override
✅ Per-domain custom headers
✅ Admin Blade layout (`admin/layouts/app.blade.php`) with sidebar nav for all V3+V4 modules
✅ Dashboard view with stat cards
✅ Basic admin views for: entries, domains, workflows, experiments, RAG playground, connectors, feature flags

### What's Missing

❌ Vuexy admin template integration (the actual Vuexy HTML/CSS/JS)
❌ Vue 3 + Inertia.js setup
❌ Dashboard widgets: recent entries, pending drafts, overdue invoices, tenant usage stats, theme status
❌ Full-page Redis caching (keyed by tenant_id + theme_id + path + site)
❌ Performance optimization: N+1 query elimination verified with Telescope
❌ Vite build for admin assets
❌ Real demo content seeding for both V3 tenants (5 services + 3 case studies for AdvMedi; 10 blog posts + 5 pages for BitDreamIT)

### Remaining Sub-Tasks

Deferred to Phase 21 (Vuexy Admin Shell) in V5.

---

## PHASE 11 — Hardening, Testing, Deployment Prep ⚠️ PARTIAL

**Goal:** Production-ready. Bulletproof tenant isolation. Complete documentation.

### What's Built

✅ `tenant_has_feature()` feature flag helper
✅ V4TenantIsolationTest (basic tests for domain resolution, cross-tenant access)
✅ V4ServicesTest (chunker, embedding, cosine similarity, condition evaluator)
✅ Rate limiting on public RAG API
✅ Security headers via ApplyDomainConfig middleware (X-Frame-Options, CSP, etc.)
✅ Per-tenant configurable CSP
✅ Documentation: docs/v4/multi-domain-guide.md, docs/v4/connector-guide.md
✅ CLAUDE.md with V4 conventions
✅ README.md with full project overview

### What's Missing

❌ Full tenant-isolation security audit (for EVERY tenant-scoped table, attempt cross-tenant access)
❌ Rate limiting on public API endpoints, form submission, login/password reset
❌ Backup strategy documentation + automated backup Artisan command
❌ Deployment runbook (env vars, queue worker setup, cron entries, SSL/domain setup, theme compilation, cache warmup)
❌ Full documentation: architecture.md, theming-guide.md, adding-fieldtypes.md, adding-ai-prompts.md, deployment.md, contributing.md
❌ DomainIsolationTest (per-domain routing)
❌ 100+ Pest tests (currently only 4 test files)

### Remaining Sub-Tasks

Deferred to Phase 25 in V5.

---

## PHASE 13 — External Laravel Connector ⚠️ PARTIAL

**Goal:** Build the `platform/laravel-cms-connector` composer package as a separate repo, then build the CMS-side endpoints that receive connector traffic.

### What's Built (CMS Side Only)

✅ `registered_connectors` table (central)
✅ RegisteredConnector model
✅ ConnectorManager service (register, revoke, verifyWebhookSignature, dispatchWebhook, findByToken)
✅ AuthBridgeService (generateSsoToken, verifySsoToken, findOrCreateUser)
✅ IncomingWebhookReceived event
✅ ConnectorController (register, ssoBridge, status)
✅ WebhookController (receive, subscriptions, subscribe, unsubscribe)
✅ ConnectorController (admin: index, store, show, update, revoke)
✅ RequireConnectorAuth middleware
✅ ConnectorPolicy (view, create, update, delete)
✅ ConnectorResource API resource
✅ Connectors index Blade view
✅ Routes: `routes/connector.php`

### What's Missing

❌ **The actual `platform/laravel-cms-connector` composer package** — described in `04-LARAVEL-INTEGRATION-KIT-V4.md` but NOT built as a separate repo. This package needs:
  - CmsConnectorServiceProvider
  - ConnectorManager (client-side)
  - CmsClient (HTTP wrapper with retry + circuit breaker + cache)
  - SignatureVerifier (HMAC + JWT)
  - CollectionQueryBuilder (fluent headless API)
  - 5 bridges: AuthBridge, ModelSyncBridge, EventBusBridge, EmbeddedBridge, HeadlessClientBridge
  - SyncableToCms contract
  - CmsEventSubscriber contract
  - 3 Jobs: SyncModelToCmsJob, ForwardEventToCmsJob, ProcessIncomingWebhookJob
  - 2 host-side tables: cms_connector_sync_state, cms_connector_event_log
  - 3 Console commands: cms-connector:install, cms-connector:sync, cms-connector:status
  - Config: config/cms-connector.php
  - FakeBridge for testing
  - Full Pest test suite

### Remaining Sub-Tasks

Deferred to Phase 24 (Connector Composer Package) in V5.

---

## PHASE 15 — A/B Testing + Real-time Collaborative Editing ⚠️ PARTIAL

**Goal:** A/B testing with conversion tracking + Yjs-based real-time co-editing.

### What's Built

**A/B Testing:**
✅ `experiments`, `experiment_variants`, `experiment_assignments` tables
✅ Experiment, ExperimentVariant, ExperimentAssignment models
✅ ExperimentEngine (findActiveForEntry, findActiveForRoute, assignVisitor, trackConversion, calculateStatistics with 2-proportion z-test)
✅ AssignExperimentVariant middleware
✅ ExperimentController (CRUD + promoteWinner)
✅ ExperimentApiController (public convert endpoint)
✅ ExperimentResource API resource
✅ Experiments index Blade view
✅ Config: `config/experiments.php`

**Collab Editing (partial):**
✅ `collab_sessions`, `collab_presence` tables
✅ CollabSession, CollabPresence models
✅ CollabController (connect, sync, presence, forceLock, disconnect)
✅ CollabSync, CollabForceLock broadcast events
✅ Laravel Reverb installed and configured
✅ Config: `config/collab.php`
✅ CleanupStaleCollabSessions Artisan command

### What's Missing

❌ **Yjs sync protocol implementation** — the CollabController sync method just appends base64-decoded updates to a BLOB. Real Yjs requires the Yjs sync protocol (Step1/Step2 updates, awareness protocol) over WebSocket
❌ **Yjs server-side handler** — needs a proper WebSocket handler that maintains Yjs document state, not just HTTP POST
❌ **y-prosemirror integration** for Bard field (ProseMirror-based)
❌ **y-textarea integration** for textarea/markdown fields
❌ **Presence ribbon UI** above Bard field (avatars of active editors)
❌ **Cursor rendering** (each user's cursor with color + name tooltip)
❌ **"Take Over" button** (Owner-only force-lock UI)
❌ **Bard field collab mode** (`enable_collab: true` in field config)
❌ **Experiment Builder wizard UI** — API only
❌ **Experiment Dashboard** (per-variant: visitors, conversions, conversion rate, lift, confidence) — API only
❌ **Auto-promote winner** UI button

### Remaining Sub-Tasks

A/B testing UI deferred to Phase 21. Real-time collab Yjs implementation deferred to Phase 23 in V5.

---

## PHASE 18 — Form Analytics & Lead Scoring ⚠️ PARTIAL

**Goal:** Per-form conversion analytics, drop-off tracking, lead scoring against submitted form data.

### What's Built

✅ `form_analytics_events` table
✅ `form_lead_scoring_rules` table
✅ V4 columns on `form_submissions`: lead_score, lead_score_breakdown, attribution, conversion_path, is_qualified, assigned_to, assigned_at
✅ FormAnalyticsEvent, FormLeadScoringRule models
✅ ScoreLead action (with LeadQualified event)
✅ FormAnalyticsController (public track endpoint)
✅ FormController extended with V4 (submissions listing with qualified filter, showSubmission, assignSubmission)
✅ Attribution capture (UTM params, referrer, landing page)
✅ Form submission auto-triggers ScoreLead if scoring rules configured

### What's Missing

❌ Form Analytics Dashboard UI (KPI cards, conversion funnel chart, drop-off table, time-series chart)
❌ Leads Table UI (with lead_score badges, qualification flag, assignment)
❌ Lead Scoring Rule Builder UI (visual rule editor with live preview)
❌ Lead Assignment Settings UI (manual, round-robin, weighted)
❌ Client-side FormTracker.js (composable for tracking view, start, field_focus, field_blur, submit_attempt, submit_success, abandon)

### Remaining Sub-Tasks

Deferred to Phase 21 (Vuexy Admin Shell) in V5.

---

## PHASE 19 — Polish, Cross-Feature Integration, Final Testing ⚠️ PARTIAL

**Goal:** Wire V4 features together, polish UX, comprehensive tenant-isolation tests.

### What's Built

✅ Workflow + Collab: when workflow's approval node is pending, entry fields locked (event-driven via EntryObserver)
✅ A/B + Personalization: experiment variant assignment middleware runs before personalization
✅ AI RAG + Workflow: AskRag action integrated as a workflow action
✅ Personalization + Form Analytics: personalization can pre-fill form fields
✅ SSO + Audit Streaming: SAML logins and SCIM events streamed to audit destinations
✅ Connector + Workflow: host apps can subscribe to workflow events
✅ FeatureFlagController + UI (toggle V4 features per-tenant)
✅ V4ServiceProvider wires cross-feature listeners (RAG indexing on entry publish, audit streaming on activity log)
✅ V4TenantIsolationTest (basic tests)
✅ Performance: database indexes on all V4 migrations
✅ Cache: segment evaluation cached, personalization rule lookups cached, experiment assignments cached permanently

### What's Missing

❌ Comprehensive V4TenantIsolationTest for EVERY V4 tenant-scoped table
❌ DomainIsolationTest (per-domain routing)
❌ N+1 query audit via Telescope
❌ Performance: all admin pages <500ms with Telescope-off
❌ Full V4 docs (workflow-engine.md, ab-testing.md, collab-editing.md, ai-rag.md, personalization.md, saml-sso.md, scim-provisioning.md, audit-streaming.md, form-analytics.md)
❌ Fresh clone setup in <45 minutes

### Remaining Sub-Tasks

Deferred to Phase 25 in V5.

---

# V5 NEW PHASES (20–25) — Remaining Work

These phases complete the partial V3/V4 phases above. Work through them in order.

---

## PHASE 20 — FieldType Engine Completion ❌ NOT STARTED

**Goal:** Complete V3 Phase 1 — the polymorphic fieldtype engine and blueprint builder.

### Tasks

```
Read 03-FIELD-STRUCTURE-SPEC-V3.md sections 2.1-2.2 in full, including the
complete fieldtype catalog table (including V3 additions: theme_color, font_picker,
spacing, border, background, ai_generate).

Task:
1. Create the FieldType contract/interface:
   namespace App\Domain\Content\Contracts\FieldTypeInterface
   Methods: render(), validate(), cast(), toApiResource(), toVueComponentProps()

2. Create FieldTypeRegistry service:
   namespace App\Domain\Content\Services\FieldTypeRegistry
   Maps fieldtype enum values to concrete classes. Adding a new type = register
   one class, zero core changes.

3. Implement concrete FieldType classes in app/Domain/Content/FieldTypes/:

   Batch A (core text & media):
   - TextFieldType.php, TextareaFieldType.php, MarkdownFieldType.php,
     SelectFieldType.php, ToggleFieldType.php, DateFieldType.php,
     SlugFieldType.php, AssetsFieldType.php

   Batch B (Structured):
   - ReplicatorFieldType.php, GridFieldType.php, TableFieldType.php,
     ArrayFieldType.php, GroupFieldType.php, SetsFieldType.php

   Batch C (Relationships):
   - RelationshipFieldType.php, EntriesFieldType.php, TermsFieldType.php,
     UsersFieldType.php

   Batch D (Remaining Text & Media):
   - BardFieldType.php, CodeFieldType.php, ColorFieldType.php,
     LinkFieldType.php, VideoFieldType.php

   Batch E (Remaining Special):
   - TemplateFieldType.php, RevealerFieldType.php, SectionFieldType.php,
     HiddenFieldType.php, IntegerFieldType.php, FloatFieldType.php,
     RangeFieldType.php, ButtonGroupFieldType.php, SeoProFieldType.php,
     RepeaterReferenceFieldType.php

   Batch F (V3 Theme-Aware Fieldtypes):
   - ThemeColorFieldType.php, FontPickerFieldType.php, SpacingFieldType.php,
     BorderFieldType.php, BackgroundFieldType.php

   Batch G (V3 AI Fieldtype):
   - AiGenerateFieldType.php

4. For Bard specifically: implement as a rich text editor (use TipTap/ProseMirror)
   supporting embedded "sets" (named reusable content blocks). Support toolbar
   configuration, word count, and save_html mode.

5. For theme_color: the render() method must receive the resolved ThemeContext
   so it can show the active theme's color palette as swatches.

6. For ai_generate: the render() method renders a "Generate with AI" button next
   to the target field. Clicking it opens a modal with generation options, calls
   the AI service, and inserts the result into the target field.

7. For seo_pro: build as a first-class field group. Include auto JSON-LD
   generation based on schema_type selection.

8. Build the admin Blueprint Builder UI (Vue 3 + Inertia + Vue Flow):
   - Drag-and-drop field list
   - Tab management (add/reorder/rename tabs)
   - Per-field config panel (opens correct config form based on fieldtype)
   - Conditional logic builder (if/then visibility rules)
   - Save/load blueprint via API

9. Create Vue components for each fieldtype in resources/js/components/field-types/:
   - TextField.vue, TextareaField.vue, MarkdownField.vue, BardField.vue,
     SelectField.vue, ToggleField.vue, DateField.vue, SlugField.vue,
     AssetField.vue, CodeField.vue, ColorField.vue, LinkField.vue,
     VideoField.vue, ReplicatorField.vue, GridField.vue, TableField.vue,
     ArrayField.vue, GroupField.vue, RelationshipField.vue, EntriesField.vue,
     TermsField.vue, UsersField.vue, TemplateField.vue, RevealerField.vue,
     SectionField.vue, HiddenField.vue, IntegerField.vue, FloatField.vue,
     RangeField.vue, ButtonGroupField.vue, SeoProField.vue,
     ThemeColorField.vue, FontPickerField.vue, SpacingField.vue,
     BorderField.vue, BackgroundField.vue, AiGenerateField.vue

10. Write Pest feature tests:
    - Create a blueprint via API
    - Add fields of each Batch A type
    - Confirm validation rules generate correctly
    - Confirm tenant isolation (tenant B cannot see tenant A's blueprints)
    - For each batch, write a Pest feature test creating a field of that type,
      saving a value, and round-tripping it through validate() and cast().
```

### Verification

- All ~35 fieldtypes have concrete FieldType classes
- FieldTypeRegistry maps all types correctly
- Blueprint Builder UI supports drag-drop, tabs, config panels
- Conditional logic builder works (show/hide fields based on other values)
- All fieldtypes round-trip correctly through validate() and cast()
- Tenant isolation confirmed for all blueprint operations

### Estimated Time

3-4 days

---

## PHASE 21 — Vuexy Admin Shell + Vue/Inertia UI ❌ NOT STARTED

**Goal:** Complete V3 Phases 5.5, 10, 18 UI + V4 Phases 14, 15, 18, 19 UI.

### Tasks

```
1. Install Vuexy admin template:
   - Download Vuexy Laravel/Blade + Vue 3 + Inertia.js package
   - Replace resources/js/app.js with Vuexy's Inertia setup
   - Replace resources/views/admin/layouts/app.blade.php with Vuexy shell
   - Configure Vite for Vuexy assets

2. Build Vue 3 + Inertia.js pages for all admin modules:

   Content:
   - resources/js/pages/admin/Dashboard.vue (with widgets: recent entries, pending drafts, overdue invoices, usage stats, theme status)
   - resources/js/pages/admin/entries/{Index,Create,Edit}.vue
   - resources/js/pages/admin/collections/{Index,Create,Edit}.vue
   - resources/js/pages/admin/blueprints/{Index,Builder}.vue (drag-drop builder from Phase 20)
   - resources/js/pages/admin/taxonomies/{Index,Edit}.vue
   - resources/js/pages/admin/globals/{Index,Edit}.vue
   - resources/js/pages/admin/navigation/{Index,Edit}.vue (drag-drop nested menu builder)
   - resources/js/pages/admin/forms/{Index,Edit,Submissions,Show}.vue
   - resources/js/pages/admin/assets/{Index,Container}.vue (grid view, folder tree, drag-drop upload)

   V4 Features:
   - resources/js/pages/admin/domains/{Index,Show,Create}.vue (with SSL, DNS, Theme, Locale, Routing, Config tabs)
   - resources/js/pages/admin/themes/{Index,Customize,Editor,Upload}.vue
   - resources/js/pages/admin/workflows/{Index,Builder,Instances,MyApprovals}.vue
   - resources/js/pages/admin/experiments/{Index,Create,Show}.vue (with dashboard)
   - resources/js/pages/admin/rag/{Playground,IndexStatus,QueriesLog,Settings}.vue
   - resources/js/pages/admin/segments/{Index,Builder}.vue
   - resources/js/pages/admin/personalization-rules/{Index,Create}.vue
   - resources/js/pages/admin/connectors/{Index,Create,Show}.vue
   - resources/js/pages/admin/saml-idps/{Index,Create,Edit}.vue
   - resources/js/pages/admin/scim-tokens/{Index,Create}.vue
   - resources/js/pages/admin/audit-streams/{Index,Create,Deliveries,ChainVerification}.vue
   - resources/js/pages/admin/forms/{Analytics,Leads,ScoringRules,Assignment}.vue

   User & Settings:
   - resources/js/pages/admin/users/{Index,Create,Edit}.vue
   - resources/js/pages/admin/roles/{Index,Create,Edit}.vue
   - resources/js/pages/admin/billing/{Index,Invoices,Plans}.vue
   - resources/js/pages/admin/redirects/{Index}.vue
   - resources/js/pages/admin/imports/{Index,Create}.vue
   - resources/js/pages/admin/utilities/{Index}.vue (test email, clear cache, server info, failed jobs)
   - resources/js/pages/admin/feature-flags/{Index}.vue

3. Build shared UI components:
   - CommandPalette.vue (Cmd/Ctrl+K, fuzzy search on entries + admin nav)
   - FilterBuilder.vue (saved filters, per-collection)
   - ColumnCustomizer.vue (per-user column visibility + ordering)
   - LivePreview.vue (split-pane iframe preview in entry edit)
   - InlineEditSlideOver.vue (quick-edit related entries)
   - ReauthModal.vue (graceful session timeout, 401 interception)
   - FullscreenToggle.vue (on Bard/Markdown/Textarea fields)
   - DarkModeToggle.vue (persisted per user)
   - Notification.vue (toast notifications)

4. Build Vuexy sidebar with:
   - Drag-and-drop pin/reorder/hide (persisted to user_nav_preferences)
   - All V3+V4 modules in nav
   - Dark mode toggle
   - Per-tenant branding (whitelabel)

5. Build dashboard widgets:
   - Recent entries (last 5)
   - Pending drafts count
   - Overdue invoices (if billing enabled)
   - Tenant usage stats (domains, storage, themes vs limits)
   - Theme status (active theme name + customization count)
   - Form submissions (last 5)
   - Workflow approvals pending (if workflow enabled)

6. Implement full-page Redis caching:
   - Key: tenant_id + theme_id + path + site
   - Invalidated via EntryObserver, ThemeObserver on save/publish/delete
   - Theme change clears ALL cached pages for that tenant
```

### Verification

- All admin pages render in Vuexy shell
- All Vue components for fieldtypes work
- Command palette works (Cmd+K)
- Live preview works in entry edit
- Dark mode toggle persists per user
- Dashboard widgets show real data
- Full-page cache invalidates correctly

### Estimated Time

5-7 days

---

## PHASE 22 — Live Theme Customizer ❌ NOT STARTED

**Goal:** Complete V3 Phase 5 — the showpiece live customizer.

### Tasks

```
1. Build ThemeManager service:
   - install(string $zipPath): extract zip to themes/{slug}/, read theme.json, register in DB, run migrations if theme has them, compile assets
   - uninstall(Theme $theme): verify no tenants using it, remove files + DB record
   - activate(Tenant $tenant, Theme $theme): set tenant.current_theme_id, clear cache
   - duplicate(Theme $theme, string $newName): deep-copy theme folder, create child theme pointing to original as parent, register in DB
   - export(Theme $theme): zip the theme folder + return download path

2. Build ThemeResolver service:
   - resolve(Tenant $tenant): return the active Theme (considering domain.theme_id override)
   - getViewCascade(Theme $theme): return ordered array of theme paths [child, parent, grandparent, ...]
   - resolveView(string $view): search the cascade for the first match
   - resolveAsset(string $path): search the cascade for the first match

3. Build ThemeCustomizer service:
   - getSettings(Tenant $tenant, Theme $theme): merge theme defaults with tenant customizations, return final values
   - updateSettings(Tenant $tenant, Theme $theme, array $values): validate against settings_schema, save to theme_customizations
   - resetToDefaults(Tenant $tenant, Theme $theme): delete customizations
   - exportCustomization(Tenant $tenant, Theme $theme): return JSON
   - importCustomization(Tenant $tenant, Theme $theme, array $json): import

4. Build ThemeVariableCompiler service:
   - compile(Tenant $tenant, Theme $theme): read settings, generate :root { --brand-color: #2563eb; ... } CSS block
   - Output cached and injected into the theme's <head>

5. Build ThemeAssetPipeline service:
   - compile(Theme $theme): run Vite build for the theme, output to dist/
   - version(Theme $theme): append content hash to asset URLs
   - cdnUrl(string $path): rewrite to CDN if configured

6. Wire Theme Blade directives in AppServiceProvider:
   - @theme('key') — get a theme setting value
   - @iftheme('key') / @endiftheme — conditional on theme setting
   - @themeAsset('path') — get versioned theme asset URL
   - @includeTheme('view') — include from theme with cascade fallback
   - @themeHasFeature('feature') — check theme capability
   - @themeComponent('name', $props) — render theme component

7. Update ResolveTheme middleware to use ThemeResolver (currently a stub)

8. Wire ThemeVariableCompiler output into the master layout:
   The <head> of every themed page includes the compiled CSS variables

9. Build the Live Customizer UI (resources/js/pages/admin/themes/Customize.vue):
   - Left panel: tabs matching settings_schema sections (Branding, Typography, Layout, Header, Footer, Advanced)
   - Each section renders controls auto-generated from the schema:
     * color → color picker with opacity
     * font_picker → searchable font dropdown with preview
     * range → slider with value display
     * select → dropdown
     * toggle → switch
     * image → asset picker modal
     * text → input field
     * code → code editor (Monaco/CodeMirror)
     * background → composite picker (color/image/gradient)
   - Right panel: live iframe preview of the tenant's actual site
   - Preview updates in REAL-TIME as settings change (debounced POST to /preview-theme endpoint)
   - Device preview toggle: Desktop / Tablet / Mobile viewports
   - Bottom bar: "Save Draft" / "Publish" / "Discard Changes"
   - "Export Customization" / "Import Customization" buttons
   - "Reset to Defaults" button with confirmation

10. Build Theme Editor (resources/js/pages/admin/themes/Editor.vue):
    - File browser showing theme's Blade view files
    - Code editor with Blade syntax highlighting
    - Changes diff view showing modifications from parent theme
    - Warning banner for direct template editing risks
    - Save / Revert per-file

11. Create "foundation-dark" child theme (themes/foundation-dark/):
    - theme.json with parent: "foundation"
    - Only override: dark color scheme settings in config/settings_schema.json
    - Only override: views/layouts/app.blade.php (add dark mode class)
    - Only override: assets/css/custom.css (dark mode overrides)
    - Demonstrate that ALL other views/assets fall through to parent

12. Theme upload (zip upload + extraction + registration):
    - Drag-and-drop zip upload
    - Validation: must contain theme.json, must pass schema validation
    - Progress bar during extraction and registration
    - Install + activate option

13. API endpoints:
    - GET /admin/api/themes — list installed themes
    - POST /admin/api/themes/upload — upload and install theme zip
    - POST /admin/api/themes/{id}/activate — activate theme for current tenant
    - GET /admin/api/themes/{id}/settings — get customization values
    - PUT /admin/api/themes/{id}/settings — update customization values
    - POST /admin/api/themes/{id}/reset — reset to defaults
    - POST /admin/api/themes/{id}/duplicate — create child theme copy
    - DELETE /admin/api/themes/{id}/uninstall — uninstall theme
    - GET /admin/api/themes/{id}/files — list theme view files
    - GET /admin/api/themes/{id}/files/{path} — get file content
    - PUT /admin/api/themes/{id}/files/{path} — update file content
    - POST /admin/api/themes/preview-settings — live preview with temp settings
    - POST /admin/api/themes/{id}/export — download theme zip
    - GET /admin/api/themes/{id}/export-customization — download customization JSON
    - POST /admin/api/themes/{id}/import-customization — import customization JSON
```

### Verification

- Upload a theme zip → it installs and appears in the theme list
- Activate a theme → the public site immediately uses the new theme's views
- Open the live customizer → change primary color → preview updates in real-time
- Publish customization → the change is live on the public site
- Reset to defaults → reverts to theme's original settings
- Duplicate a theme → creates a proper child theme inheriting from parent
- Visit both test tenants → each shows their own theme with their own customizations
- Theme CSS variables are injected into every page's <head>
- All theme Blade directives work correctly (@theme, @themeAsset, @includeTheme, etc.)

### Estimated Time

4-5 days

---

## PHASE 23 — Real-time Collaborative Editing (Yjs) Implementation ❌ NOT STARTED

**Goal:** Complete V4 Phase 15 — real Yjs sync protocol over WebSocket.

### Tasks

```
1. Install Yjs server-side dependencies:
   composer require phadej/yjs (or alternative Yjs PHP library)
   # OR implement a custom Yjs sync handler using Laravel Reverb's WebSocket events

2. Build proper Yjs WebSocket handler:
   app/Domain/Collab/Services/YjsServer.php
   - onConnect($connection): create or resume collab session
   - onMessage($connection, $message): Yjs sync protocol Step1/Step2, awareness update
   - broadcast to all other connections in the same session
   - persist document state every 5 seconds (debounced)
   - onDisconnect($connection): remove from presence, if last connection persist final state and end session

3. Implement Yjs sync protocol messages:
   - Step1: Request state vector from peer
   - Step2: Send update to peer
   - Awareness: Broadcast cursor/selection/presence updates
   - Update: Apply document update and broadcast

4. Build DocumentPersister:
   - persist(CollabSession $session): converts Yjs document to field format (HTML for bard, markdown for markdown, plain for text), saves to entries.data
   - Called periodically (every 5 seconds) AND on session end

5. Build AwarenessBroadcaster:
   - Tracks per-session presence (user_id, cursor_position, color)
   - Broadcasts presence updates to all session members
   - Cleans up stale presence (no heartbeat in 30s)

6. Register WebSocket routes:
   routes/channels.php:
   Broadcast::channel('collab.{tenantId}.{entryId}.{fieldHandle}', function ($user, $tenantId, $entryId, $fieldHandle) {
     return Gate::allows('update', Entry::find($entryId));
   });

7. Client-side Yjs integration:
   - Install y-prosemirror for Bard (ProseMirror-based)
   - Install y-textarea for textarea/markdown fields
   - Update BardField.vue to support collab mode:
     * Initialize Yjs document
     * Connect to Reverb WebSocket at /app/collab/{tenantId}/{entryId}/{fieldHandle}
     * Render other users' cursors with their colors and names
     * Show presence ribbon above the field with active editors' avatars

8. Build "Take Over" feature (Owner only):
   - Button in field header (visible only to Owner role)
   - POST /admin/api/collab/{sessionId}/force-lock
   - All other users get disconnected with message
   - Auto-releases after 30 minutes

9. Build admin UI for collab session monitoring:
   - resources/js/pages/admin/collab/Index.vue — list of active sessions
   - resources/js/pages/admin/collab/Show.vue — session detail: live presence view, force-end button

10. Write comprehensive tests:
    - Two browser sessions edit same Bard field simultaneously
    - See each other's cursors in real-time
    - Save persists both users' changes conflict-free (CRDT)
    - Force-lock disconnects other users
    - User without edit access cannot join collab session
    - Tenant isolation in collab
```

### Verification

- Two browser sessions edit same Bard field simultaneously
- See each other's cursors in real-time
- Save persists both users' changes conflict-free
- Force-lock works (Owner only)
- All Pest tests pass

### Estimated Time

3-4 days

---

## PHASE 24 — Connector Composer Package ❌ NOT STARTED

**Goal:** Complete V4 Phase 13 — build the actual `platform/laravel-cms-connector` composer package.

### Tasks

```
1. Create a new separate repository: platform/laravel-cms-connector

2. Scaffold the composer package:
   composer init (name: platform/laravel-cms-connector, type: library, license: MIT)
   Set up PSR-4 autoload: Platform\\CmsConnector\\ → src/

3. Build CmsConnectorServiceProvider.php:
   - register(): merge config, bind ConnectorManager singleton, register bridges based on enabled modes
   - boot(): publish config, publish migrations, load routes, register middleware aliases, register event listeners for model sync

4. Build ConnectorManager.php (singleton, facade: CmsConnector):
   - collection(string $handle): CollectionQueryBuilder
   - graphql(string $query, array $variables = []): array
   - forTenant(string $tenantId): self
   - health(): array
   - getConnectorId(): ?string
   - syncModel(object $model): void
   - ssoRedirectUrl(): string

5. Build Support/CmsClient.php (HTTP client wrapper):
   - Wraps Guzzle, adds Authorization header, X-Connector-Id header, HMAC signature
   - Circuit breaker: tracks failures, opens circuit after threshold
   - Retry: 3 attempts with exponential backoff on 5xx
   - Cache: GET requests cached per config, stale-while-revalidate

6. Build Support/SignatureVerifier.php:
   - sign(array $payload, string $secret): string — HMAC-SHA256
   - verify(array $payload, string $signature, string $secret): bool — constant-time
   - signJwt(array $payload, string $secret): string — JWT HS256
   - verifyJwt(string $token, string $secret): object

7. Build Support/CollectionQueryBuilder.php (fluent headless API):
   - where(), orderBy(), with(), paginate(), find(), findBySlug(), first(), get(), limit()

8. Build Mode 1: Auth Bridge
   - Http/Controllers/SsoRedirectController.php
   - Bridges/AuthBridge.php
   - Middleware/ShareSessionWithCms.php

9. Build Mode 2: Model Sync Bridge
   - Bridges/ModelSyncBridge.php (listens to Eloquent events, debounces, dispatches SyncModelToCmsJob)
   - Contracts/SyncableToCms.php (toCmsEntryData, fromCmsEntryData)
   - Jobs/SyncModelToCmsJob.php
   - Jobs/ProcessIncomingWebhookJob.php
   - Console/SyncModelsCommand.php

10. Build Mode 3: Event Bus Bridge
    - Bridges/EventBusBridge.php
    - Http/Controllers/WebhookReceiverController.php
    - Contracts/CmsEventSubscriber.php

11. Build Mode 4: Embedded Mode
    - Middleware/EmbeddedCmsRouting.php
    - resources/views/embedded-layout.blade.php

12. Build Mode 5: Headless API Client (already in ConnectorManager + CmsClient)

13. Create database/migrations/:
    - create_cms_connector_sync_state_table.php
    - create_cms_connector_event_log_table.php

14. Create config/cms-connector.php (full config from spec 18.5)

15. Create Console/InstallCommand.php (cms-connector:install):
    - Publishes config, runs migrations, asks for connection details, tests connection

16. Create Console/StatusCommand.php (cms-connector:status):
    - Reports CMS reachable, connector registered, modes enabled, last activity

17. Build FakeBridge for testing

18. Write package-level Pest tests:
    - test_cms_client_handles_timeout_with_retry
    - test_circuit_breaker_opens_after_threshold_failures
    - test_cache_returns_stale_on_cms_failure
    - test_signature_verifier_constant_time
    - test_sso_redirect_generates_valid_jwt
    - test_model_sync_debounces_rapid_updates
    - test_webhook_receiver_rejects_invalid_signature
    - test_headless_client_paginates_correctly

19. Build a demo: Connect Shopland to CMS:
    - Create fresh Laravel project at ../shopland-demo
    - composer require platform/laravel-cms-connector
    - php artisan cms-connector:install
    - Configure modes: auth_bridge + model_sync + headless
    - Create App\Models\Product implementing SyncableToCms
    - Test bidirectional sync

20. Write package README.md with installation, configuration, usage examples per mode
```

### Verification

- platform/laravel-cms-connector package installs via composer
- All 5 connection modes work in isolation
- Shopland demo: bidirectional sync works
- All Pest tests pass on both package and CMS side

### Estimated Time

4-5 days

---

## PHASE 25 — Final Hardening, Tests, Docs ❌ NOT STARTED

**Goal:** Complete V3 Phases 7, 9, 11 + V4 Phase 19 — production-ready, bulletproof, documented.

### Tasks

```
PART A — Security & Dev Tools (V3 Phase 7 completion):

1. Two-Factor Authentication:
   - Laravel Fortify TOTP is configured, build the settings UI
   - resources/js/pages/admin/profile/TwoFactor.vue
   - QR code display, recovery codes, enable/disable

2. Passkeys (WebAuthn):
   - composer require laragear/webauthn
   - Build passkey registration + login UI
   - webauthn_credentials table already exists

3. Impersonate Users:
   - Build session-based impersonation (audit-logged)
   - lab404/laravel-impersonate package
   - UI: "Impersonate" button on user detail page (Owner only)
   - Banner: "You are impersonating {User}. Click here to return."

4. OAuth Logins:
   - composer require laravel/socialite
   - Enable Google + GitHub providers
   - oauth_connections table already exists
   - Build "Login with Google/GitHub" buttons on login page

5. Dynamic Image Manipulation:
   - /img/{asset_id}/{params} route
   - intervention/image package
   - On-the-fly resize/crop with cached output

PART B — AI Content Tools (V3 Phase 9 completion):

6. Build AiService (provider-agnostic dispatch with rate limiting):
   - App\Domain\Ai\Services\AiService.php
   - Dispatches to configured provider (OpenAI/Anthropic/local)
   - Per-tenant rate limiting via Redis

7. Build PromptTemplateEngine:
   - Loads markdown templates from app/Domain/Ai/Prompts/
   - Renders with variable substitution

8. Build ContentAnalyzer:
   - SEO analysis (title length, meta description, keyword density)
   - Readability score (Flesch-Kincaid)
   - Heading structure check

9. Build Actions: GenerateContent, GenerateImage, ImproveSeo, TranslateContent

10. Create prompt templates in app/Domain/Ai/Prompts/:
    - blog-post-generator.md
    - seo-optimizer.md
    - product-description.md
    - page-content.md
    - translation-assistant.md
    - meta-description.md
    - social-media-post.md

11. Build admin AI screens:
    - resources/js/pages/admin/ai/ContentGenerator.vue
    - resources/js/pages/admin/ai/ImageGenerator.vue

12. Implement ai_generate fieldtype:
    - "Generate with AI" button next to target field
    - Opens modal with generation options (tone, length, style)
    - Calls AiService with prompt template
    - Inserts result into target field
    - Can reference other field values in prompt

13. AI SEO Assistant:
    - "Analyze SEO" button in SEO tab
    - Checks: title length, meta description, keyword density, readability, heading structure
    - Suggestions for improvement
    - "Auto-optimize" button applies AI suggestions

PART C — Hardening (V3 Phase 11 + V4 Phase 19 completion):

14. FULL TENANT-ISOLATION SECURITY AUDIT:
    Write tests/Feature/Tenancy/CompleteTenantIsolationTest.php
    For EVERY tenant-scoped table in the spec (V3 + V4 = ~50 tables):
    - Tenant A user creates a record
    - Tenant A user can read/update/delete it
    - Tenant B user CANNOT read it via API (assert 404)
    - Tenant B user CANNOT update it via API (assert 404)
    - Tenant B user CANNOT delete it via API (assert 404)
    This is THE most important test suite in the entire project.

15. Write tests/Feature/Tenancy/DomainIsolationTest.php:
    - For each V4 domain (shop.advmedi.test, blog.advmedi.test, multilingual.fr/de/bn, *.multilingual.test):
      - Confirm it resolves to correct tenant
      - Confirm per-domain theme override applies
      - Confirm per-domain locale applies
      - Confirm subdomain-to-collection routing works
      - Confirm Tenant A's domain CANNOT serve Tenant B's content

16. Rate limiting on:
    - Public API endpoints (60 req/min)
    - Form submission endpoints (5 req/min per IP)
    - AI generation endpoints (per-tenant quota)
    - Login/password reset (5 attempts per minute)

17. Backup strategy:
    - Document backup/restore procedure for single-DB architecture
    - Build automated backup Artisan command (cms:backup)
    - Build restore command (cms:restore)
    - Test restore verified

18. Deployment runbook (docs/deployment.md):
    - Environment variables needed (complete .env.example)
    - Queue worker setup (Horizon recommended)
    - Required cron entries
    - SSL/domain setup for new tenant onboarding
    - Theme compilation step in deploy script
    - Cache warmup after deploy
    - Web server (nginx) config for WebSocket proxying
    - Supervisor config for Reverb

19. Full documentation (docs/ folder):
    - architecture.md — system architecture overview
    - theming-guide.md — how to create/customize themes
    - adding-fieldtypes.md — how to add a new fieldtype
    - adding-ai-prompts.md — how to add new AI prompt templates
    - deployment.md — production deployment guide
    - contributing.md — developer onboarding guide
    - v4/multi-domain-guide.md (already exists)
    - v4/connector-guide.md (already exists)
    - v4/workflow-engine.md
    - v4/ab-testing.md
    - v4/collab-editing.md
    - v4/ai-rag.md
    - v4/personalization.md
    - v4/saml-sso.md
    - v4/scim-provisioning.md
    - v4/audit-streaming.md
    - v4/form-analytics.md

20. Final README covering:
    - What this is (elevator pitch)
    - Tech stack
    - Local setup (should work in under 30 min from fresh clone)
    - Running tests
    - Adding a new fieldtype
    - Adding a new tenant
    - Adding a new billing plan
    - Creating a custom theme
    - Architecture overview (link to docs/architecture.md)

21. Performance optimization:
    - Eager-load relationships on entry listing and rendering
    - Database indexes per spec section 3
    - N+1 query elimination verified with Telescope
    - All admin pages load in <500ms with Telescope-off

22. CLAUDE.md final update:
    - Ensure all current project conventions are documented
    - Include common commands, testing instructions, architecture notes

23. Fresh clone verification:
    - Fresh clone of the repo
    - Follow only the README
    - Confirm working local environment with all seeded tenants, both themes, full admin panel, in under 30 minutes
    - All 200+ Pest tests pass
```

### Verification

- 2FA and passkey login work
- Impersonation is audit-logged
- OAuth (Google/GitHub) login works
- WordPress import works
- Static export produces a working HTML bundle
- AI content generation works
- AI SEO analyzer returns actionable suggestions
- Rate limiting kicks in after quota
- CompleteTenantIsolationTest passes (zero cross-tenant data leakage)
- DomainIsolationTest passes (zero cross-domain content leakage)
- All 200+ Pest tests pass
- Fresh clone setup completes in <30 minutes

### Estimated Time

5-7 days

---

# Summary — Phase Order for Implementation

## ✅ Already Complete (no work needed)

1. Phase 0 — Scaffolding & DDD Architecture
2. Phase 2 — Collections, Entries, Content CRUD
3. Phase 3 — Taxonomies, Globals, Nav, Forms, Assets
4. Phase 4 — Roles, Permissions, Audit Trail
5. Phase 6 — Billing & Client Management
6. Phase 8 — SEO, Redirects, Webhooks, API
7. Phase 12 — Multi-Domain & Subdomain Layer
8. Phase 14 — Workflow Engine
9. Phase 16 — AI RAG + Personalization
10. Phase 17 — SAML SSO + SCIM + Audit Streaming

## ⚠️ Partially Complete (finish remaining sub-tasks first)

11. Phase 1 → finish in Phase 20 (FieldType Engine)
12. Phase 5 → finish in Phase 22 (Theme Engine + Customizer)
13. Phase 5.5 → finish in Phase 21 (CP UX)
14. Phase 7 → finish in Phase 25 (Security & Dev Tools)
15. Phase 9 → finish in Phase 25 (AI Tools)
16. Phase 10 → finish in Phase 21 (Vuexy Polish)
17. Phase 11 → finish in Phase 25 (Hardening)
18. Phase 13 → finish in Phase 24 (Connector Package)
19. Phase 15 → finish in Phase 23 (Yjs Collab) + Phase 21 (A/B UI)
20. Phase 18 → finish in Phase 21 (Form Analytics UI)
21. Phase 19 → finish in Phase 25 (Cross-Feature + Tests)

## ❌ Not Started (V5 phases — work in this order)

22. **Phase 20** — FieldType Engine Completion (3-4 days)
23. **Phase 21** — Vuexy Admin Shell + Vue/Inertia UI (5-7 days)
24. **Phase 22** — Live Theme Customizer (4-5 days)
25. **Phase 23** — Real-time Collab (Yjs) Implementation (3-4 days)
26. **Phase 24** — Connector Composer Package (4-5 days)
27. **Phase 25** — Final Hardening, Tests, Docs (5-7 days)

**Total estimated time to complete:** 24-32 developer-days

---

## Progress Tracking

Print this section and check off phases as you complete them:

```
V3 Phases:
[✅] Phase 0:  Scaffolding & DDD Architecture
[⚠️] Phase 1:  Field Engine & Blueprint System          → Phase 20
[✅] Phase 2:  Collections, Entries, Content CRUD
[✅] Phase 3:  Taxonomies, Globals, Nav, Forms, Assets
[✅] Phase 4:  Roles, Permissions, Audit Trail
[⚠️] Phase 5:  Multi-Tenant Theme Engine                → Phase 22
[⚠️] Phase 5.5: Control Panel UX Parity                 → Phase 21
[✅] Phase 6:  Billing & Client Management
[⚠️] Phase 7:  Security & Developer Tools               → Phase 25
[✅] Phase 8:  SEO, Redirects, Webhooks, API
[⚠️] Phase 9:  AI-Powered Content Tools                 → Phase 25
[⚠️] Phase 10: Front-End Theming & Vuexy Polish         → Phase 21
[⚠️] Phase 11: Hardening & Deployment Prep              → Phase 25

V4 Phases:
[✅] Phase 12: Multi-Domain & Subdomain Layer
[⚠️] Phase 13: External Laravel Connector               → Phase 24
[✅] Phase 14: Workflow Engine
[⚠️] Phase 15: A/B Testing + Collab Editing             → Phase 21 + 23
[✅] Phase 16: AI RAG + Personalization
[✅] Phase 17: SAML SSO + SCIM + Audit Streaming
[⚠️] Phase 18: Form Analytics & Lead Scoring            → Phase 21
[⚠️] Phase 19: Polish, Cross-Feature, Final Testing     → Phase 25

V5 Phases (NEW):
[❌] Phase 20: FieldType Engine Completion               (3-4 days)
[❌] Phase 21: Vuexy Admin Shell + Vue/Inertia UI        (5-7 days)
[❌] Phase 22: Live Theme Customizer                     (4-5 days)
[❌] Phase 23: Real-time Collab (Yjs) Implementation     (3-4 days)
[❌] Phase 24: Connector Composer Package                (4-5 days)
[❌] Phase 25: Final Hardening, Tests, Docs              (5-7 days)
```

---

*End of V5 Unified Build Plan. This document supersedes the phase tracking in `03-AI-BUILD-PROMPTS-V3.md` and `04-AI-BUILD-PROMPTS-V4.md`. Refer to those documents for the detailed task blocks of each phase; refer to THIS document for completion status and recommended implementation order.*
