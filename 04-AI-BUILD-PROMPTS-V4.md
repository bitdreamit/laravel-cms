# AI Build Prompt Document — V4
## World-Class Task-by-Task Prompts for Building the V4 Platform Expansion

Version 4.0 — "Multi-Connectivity" Edition
Adds: Multi-Domain & Subdomain Layer, External Laravel Connector, Professional Features Suite (Workflow Engine, A/B Testing, Collab Editing, AI RAG, Personalization, SAML SSO, SCIM, Audit Streaming, Form Analytics).

**Relationship to V3:** This file adds Phases 12–19. V3 Phases 0–11 remain as-is. Work through V3 phases first (foundational CMS, billing, themes, AI tools). Then start V4 phases. Each V4 phase assumes all V3 phases are complete and verified.

**How to use this file:** Same workflow as V3 — one phase at a time, run the verification checklist before moving on, reference `03-FIELD-STRUCTURE-SPEC-V3.md` AND `04-FIELD-STRUCTURE-SPEC-V4.md` so the agent has both spec files in context.

---

**Global context to ADD to your `CLAUDE.md` (append to the V3 global context):**

```
V4 additions: multi-domain per-domain theme/locale/collection routing, automated SSL via
ACME (acmephp/core), DNS ownership verification (spatie/dns), external Laravel connector
package (platform/laravel-cms-connector) supporting 5 modes: SSO bridge, model sync,
event bus, embedded mode, headless API. Pro features: Workflow Engine (custom DAG),
A/B Testing (custom), Real-time Collab (Laravel Reverb + Yjs), AI RAG (pgvector or
Meilisearch hybrid), Personalization (custom rules engine), SAML 2.0 SSO
(aacotroneo/laravel-saml2), SCIM 2.0 (limedeck/laravel-scim-server), Audit Streaming
(Splunk/Datadog/Elastic/Syslog), Form Analytics & Lead Scoring. ~30 new tables, total
~77 tables. All V4 features are feature-flagged in tenants.data.features.{feature}.
New test tenants: Shopland (existing Laravel app connecting via connector),
EnterpriseCorp (SAML SSO + SCIM + audit streaming), Multilingual Co. (per-domain
locale + wildcard subdomains).
```

---

## PHASE 12 — Multi-Domain & Subdomain Connectivity Layer

**Goal:** Per-domain theme override, per-domain locale binding, wildcard subdomain resolution, automated SSL via ACME, DNS ownership verification, subdomain-to-collection routing.

```
Read 04-FIELD-STRUCTURE-SPEC-V4.md section 17 in its ENTIRETY — sections 17.1 through
17.16. This is the foundational V4 phase; later V4 phases depend on it.

Task:

PART A — Domain Table Migration & Wildcard Resolution:

1. Create central migration: alter_domains_table_add_v4_columns.php
   - Add columns per spec 17.2: is_wildcard, wildcard_parent, ssl_certificate_id,
     ssl_expires_at, dns_verification_status, dns_verification_token, dns_verified_at,
     theme_id, site_id, default_collection_handle, route_prefix, config (json),
     status (enum), redirect_target, analytics_property_id, last_request_at.
   - All new columns nullable for backward compat with V3 rows.
   - Add indexes: (tenant_id), (is_wildcard), (dns_verification_status),
     (ssl_expires_at).

2. Create central migrations for:
   - ssl_certificates table (spec 17.3)
   - acme_accounts table (spec 17.4)
   - dns_verification_jobs table (spec 17.5)

3. Create models in app/Models/Central/:
   - SslCertificate.php (encrypted casts for certificate_pem, private_key_pem)
   - AcmeAccount.php
   - DnsVerificationJob.php
   - Update Domain.php model with V4 relationships:
     belongsTo(SslCertificate::class), belongsTo(Theme::class, 'theme_id'),
     belongsTo(Site::class, 'site_id'), hasMany(DnsVerificationJob::class)

4. Build app/Domain/Dns/Services/DnsVerificationService.php:
   - generateToken(Domain $domain): string — random 32-char hex
   - createVerificationJob(Domain $domain): DnsVerificationJob
   - verify(DnsVerificationJob $job): bool — uses spatie/dns to look up TXT record,
     compare to expected token, return true/false
   - markVerified(Domain $domain): void — sets dns_verification_status, dns_verified_at

5. Build app/Domain/Dns/Services/AcmeClient.php (wraps acmephp/core):
   - registerAccount(Tenant $tenant, string $email): AcmeAccount
   - orderCertificate(Domain $domain, string $challengeType): SslCertificate
   - fulfillHttpChallenge(SslCertificate $cert): void
   - fulfillDnsChallenge(SslCertificate $cert): void — uses DnsProviderFactory
   - finalizeOrder(SslCertificate $cert): void — fetch issued cert, store PEM
   - revokeCertificate(SslCertificate $cert): void

6. Build app/Domain/Dns/Services/SslCertificateManager.php:
   - issueForDomain(Domain $domain): SslCertificate — orchestrates verification →
     ACME order → fulfillment → finalization → domain update
   - renew(SslCertificate $cert): SslCertificate
   - shouldRenew(SslCertificate $cert): bool — true if expires within 30 days
   - markFailed(SslCertificate $cert, string $reason): void

7. Build app/Domain/Dns/Providers/ — DNS provider adapters for DNS-01 challenge:
   - CloudflareProvider.php — publishTxtRecord(), deleteTxtRecord()
   - Route53Provider.php
   - DigitaloceanProvider.php
   - DnsProviderFactory.php — returns correct provider based on
     tenants.data.dns_provider_config.provider

8. Build middleware stack per spec 17.7:
   - app/Http/Middleware/ResolveWildcardDomain.php — runs after
     InitializeTenancyByDomain, extracts wildcard segment if exact match fails
   - app/Http/Middleware/VerifyDomainActive.php — checks domain.status, returns
     503 for parked, 301 for redirect_only
   - app/Http/Middleware/EnforceHttps.php — 301 to https if config.force_https
   - app/Http/Middleware/ResolveSite.php — sets request's default site from
     domain.site_id
   - app/Http/Middleware/ApplyDomainConfig.php — applies custom headers, robots.txt,
     favicon override, og_image override

9. Register middleware in bootstrap/app.php in the correct order per spec 17.7.

PART B — Theme & Site Resolution with Domain Override:

10. Update app/Domain/Theme/Services/ThemeResolver.php (V3):
    - resolve() now accepts optional Domain $domain parameter
    - If domain.theme_id is set, load that theme; else fall back to
      tenant.current_theme_id (V3 behavior); else foundation default
    - View cascade (child → parent → grandparent) unchanged

11. Update app/Domain/Content/Services/SiteService.php (V3 or new in V4):
    - resolveSiteForRequest(): returns Site based on domain.site_id OR
      tenant default site
    - Locale switcher still works for manual override

12. Implement subdomain-to-collection routing per spec 17.9:
    - In routes/tenant-web.php, add a fallback route that checks if
      domain.default_collection_handle is set
    - If set, route "/" to the collection's index template
    - Route "/{slug}" to the collection's show template (lookup entry by slug
      within that collection)
    - Route "/category/{term}" to the collection's term template
    - Admin routes (/admin/*) and API routes (/api/*) are unaffected

PART C — SSL & DNS Jobs:

13. Create queued jobs in app/Domain/Dns/Jobs/ (or app/Jobs/Dns/):
    - VerifyDomainDnsJob.php — calls DnsVerificationService::verify(), retry
      every 5 minutes for up to 50 attempts
    - OrderSslCertificateJob.php — orchestrates ACME order
    - RenewSslCertificateJob.php — renews expiring certs
    - ReloadWebserverJob.php — runs config('ssl.webserver_reload_cmd')

14. Create Artisan commands:
    - dns:verify {domain} — manually trigger DNS verification
    - ssl:issue {domain} — manually trigger SSL issuance
    - ssl:renew — daily cron, finds all certs expiring within 30 days, queues
      RenewSslCertificateJob for each
    - ssl:status — table of all certs with expiry countdown

15. Register daily cron in app/Console/Kernel.php:
    - ssl:renew daily at 02:00
    - dns:retry-failed hourly

PART D — Admin Domain Management UI:

16. Build admin domain management screens:
    - resources/js/pages/admin/domains/Index.vue — table with all V4 columns,
      filterable by status, SSL status, DNS status
    - resources/js/pages/admin/domains/Create.vue — modal: domain name, tenant
      (if platform user), options checkboxes (verify DNS, request SSL, set primary)
    - resources/js/pages/admin/domains/Show.vue — tabbed detail page per spec 17.15:
      Overview, SSL, DNS, Theme, Locale, Routing, Config, Analytics

17. Build API endpoints:
    - GET /admin/api/domains — list (with V4 columns)
    - POST /admin/api/domains — create
    - GET /admin/api/domains/{id} — detail
    - PUT /admin/api/domains/{id} — update
    - DELETE /admin/api/domains/{id} — remove (cancels SSL, frees domain)
    - POST /admin/api/domains/{id}/verify-dns — trigger verification
    - POST /admin/api/domains/{id}/request-ssl — trigger SSL issuance
    - POST /admin/api/domains/{id}/renew-ssl — manual renewal
    - POST /admin/api/domains/{id}/activate-theme — set domain.theme_id
    - POST /admin/api/domains/{id}/activate-site — set domain.site_id

18. Build DomainPolicy with V4 permissions (spec 17.16):
    - viewAny: any tenant user
    - create/update/delete: Owner, Administrator with 'manage domains' permission
    - manageSsl: requires 'manage ssl certificates' permission
    - manageDns: requires 'manage dns verification' permission
    - viewAnalytics: requires 'view domain analytics' permission
    - manageConfig: requires 'manage domain config' permission

PART E — Test Tenants:

19. Update TenantSeeder to add V4 test tenants:
    - AdvMedi (V3) — add 2 new domains: shop.advmedi.test (theme=ecommerce,
      default_collection=products), blog.advmedi.test (theme=magazine,
      default_collection=blog)
    - BitDreamIT (V3) — unchanged
    - Shopland (NEW) — single domain: shopland.test, will be used for
      connector demo in Phase 13
    - EnterpriseCorp (NEW) — single domain: enterprise.test, will be used
      for SSO + SCIM + audit streaming demo in Phases 16-17
    - Multilingual Co. (NEW) — 3 domains: multilingual.fr (site=fr-FR),
      multilingual.de (site=de-DE), multilingual.bn (site=bn-BD), plus
      wildcard *.multilingual.test for city landing pages

20. Seed DNS verification tokens for all test domains (in dev, auto-verify
    so SSL staging certs can be issued).

PART F — Pest Tests:

21. Write Pest feature tests:
    - test_exact_domain_resolution — request to shop.advmedi.test resolves
      to AdvMedi tenant
    - test_wildcard_domain_resolution — request to cityX.multilingual.test
      resolves to Multilingual Co. tenant, wildcard_segment = "cityX"
    - test_per_domain_theme_override — shop.advmedi.test uses ecommerce
      theme, advmedi.test uses foundation theme
    - test_per_domain_locale_binding — multilingual.fr defaults to French
      site, multilingual.de defaults to German site
    - test_subdomain_to_collection_routing — GET / on shop.advmedi.test
      renders products collection index, GET /{slug} renders product entry
    - test_dns_verification_flow — full lifecycle: create domain → publish
      TXT record (mock) → verify → SSL issuance (mock ACME)
    - test_ssl_renewal_cron — cert with expires_at in 20 days triggers
      RenewSslCertificateJob
    - test_parked_domain_returns_503
    - test_redirect_only_domain_returns_301
    - test_force_https_redirects
    - test_custom_headers_applied — X-Frame-Options etc. in response
    - test_robots_txt_override — /robots.txt on domain returns overridden
      content
    - test_tenant_isolation — Tenant B's domains invisible to Tenant A's
      admin API

Verification:
- All 4 test domains resolve correctly (advmedi.test, shop.advmedi.test,
  blog.advmedi.test, multilingual.fr/de/bn, *.multilingual.test)
- Wildcard segment extraction works
- Per-domain theme override visible (shop.advmedi.test shows ecommerce
  theme, advmedi.test shows foundation theme)
- Per-domain locale works (multilingual.fr content is French)
- Subdomain-to-collection routing works (GET / on shop.advmedi.test lists
  products)
- SSL staging cert issued for at least one domain (use Let's Encrypt
  staging for tests)
- DNS verification flow completes end-to-end (mock TXT record lookup)
- All Pest tests pass
```

**Checklist before moving on:**
- [ ] domains table extended with V4 columns, all V3 rows intact
- [ ] ssl_certificates, acme_accounts, dns_verification_jobs tables exist
- [ ] Wildcard domain resolution middleware works
- [ ] Per-domain theme override works
- [ ] Per-domain locale binding works
- [ ] Subdomain-to-collection routing works
- [ ] SSL staging cert issuance end-to-end (DNS-01 challenge)
- [ ] DNS verification flow completes
- [ ] Admin domain management UI shows all V4 tabs
- [ ] All V4 domain permissions enforced
- [ ] 3 new test tenants seeded (Shopland, EnterpriseCorp, Multilingual Co.)
- [ ] All Pest tests pass

---

## PHASE 13 — External Laravel Connector Package

**Goal:** Build the `platform/laravel-cms-connector` composer package as a separate repo, then build the CMS-side endpoints that receive connector traffic.

```
Read 04-FIELD-STRUCTURE-SPEC-V4.md section 18 in its ENTIRETY — sections 18.1 through
18.8. Also read 04-LARAVEL-INTEGRATION-KIT-V4.md for the package's full design.

Task:

PART A — Connector Package (separate repo: platform/laravel-cms-connector):

1. Scaffold a fresh composer package:
   - composer init (name: platform/laravel-cms-connector, type: library,
     license: MIT, require php: ^8.1, illuminate/contracts: ^10.0|^11.0)
   - Set up PSR-4 autoload: Platform\\CmsConnector\\ → src/

2. Create CmsConnectorServiceProvider.php:
   - register(): merge config, bind ConnectorManager singleton, register
     bridges based on enabled modes
   - boot(): publish config, publish migrations, load routes, register
     middleware aliases, register event listeners for model sync

3. Build ConnectorManager.php (singleton, facade: CmsConnector):
   - collection(string $handle): CollectionQueryBuilder
   - graphql(string $query, array $variables = []): array
   - forTenant(string $tenantId): self (returns a cloned instance scoped
     to a different tenant — for multi-tenant host apps)
   - health(): array — returns CMS health check response
   - getConnectorId(): ?string — the registered connector ID from CMS

4. Build Support/CmsClient.php (HTTP client wrapper):
   - Wraps Guzzle, adds Authorization header (Bearer token), X-Connector-Id
     header, HMAC signature on webhook-sending routes
   - Circuit breaker: tracks failures, opens circuit after threshold,
     half-opens after reset_seconds
   - Retry: 3 attempts with exponential backoff on 5xx
   - Cache: GET requests cached per config, stale-while-revalidate

5. Build Support/SignatureVerifier.php:
   - sign(array $payload, string $secret): string — HMAC-SHA256
   - verify(array $payload, string $signature, string $secret): bool
   - constant-time comparison to prevent timing attacks

6. Build Mode 1: Auth Bridge
   - Http/Controllers/SsoRedirectController.php — generates JWT, redirects
     to CMS SSO URL
   - Bridges/AuthBridge.php — maps host User → CMS user data, signs JWT,
     handles redirect URL generation
   - Middleware/ShareSessionWithCms.php — adds SSO link to view data

7. Build Mode 2: Model Sync Bridge
   - Bridges/ModelSyncBridge.php — listens to Eloquent events on
     configured models, debounces, dispatches SyncModelToCmsJob
   - Contracts/SyncableToCms.php — interface host models implement:
     toCmsEntryData(): array, fromCmsEntryData(array $data): static
   - Jobs/SyncModelToCmsJob.php — calls CmsClient PUT
     /api/v1/collections/{handle}/entries/{slug}
   - Jobs/ProcessIncomingWebhookJob.php — receives CMS event, finds
     matching model by entry slug, calls fromCmsEntryData()
   - Console/SyncModelsCommand.php — artisan command to bulk-resync all
     configured models: php artisan cms-connector:sync {model?}

8. Build Mode 3: Event Bus Bridge
   - Bridges/EventBusBridge.php — listens to configured host events,
     forwards to CMS POST /api/v1/webhooks/incoming (HMAC-signed)
   - Http/Controllers/WebhookReceiverController.php — receives CMS
     webhooks, verifies HMAC, dispatches to configured listeners
   - Contracts/CmsEventSubscriber.php — interface host classes implement:
     handle(string $eventType, array $payload): void

9. Build Mode 4: Embedded Mode
   - Middleware/EmbeddedCmsRouting.php — intercepts /{prefix}/* requests,
     rewrites URL, forwards to CMS internally
   - Configurable route prefix (default: cms)
   - resources/views/embedded-layout.blade.php — extends host's main layout
   - Note: this mode requires the CMS to be configured with
     tenancy_identification_mode = 'path' for this tenant — document this
     in the README

10. Build Mode 5: Headless API Client
    - Already covered in step 3 (ConnectorManager) and step 4 (CmsClient)
    - Add CollectionQueryBuilder.php with fluent interface:
      ->where(), ->orderBy(), ->paginate(), ->find(), ->findBySlug(),
      ->first(), ->get(), ->related()
    - Add GraphQL support: $connector->graphql($query, $variables)

11. Create database/migrations/:
    - create_cms_connector_sync_state_table.php — spec 18.4
    - create_cms_connector_event_log_table.php — spec 18.4

12. Create config/cms-connector.php — full config from spec 18.5

13. Create Console/InstallCommand.php (cms-connector:install):
    - Publishes config
    - Runs migrations
    - Asks for CMS_BASE_URL, CMS_TENANT_ID, CMS_API_TOKEN, CMS_SHARED_SECRET
    - Updates .env with provided values
    - Tests connection, reports success/failure

14. Create Console/StatusCommand.php (cms-connector:status):
    - Reports: CMS reachable? connector registered? modes enabled?
    - For each enabled mode: last activity, error count
    - Color-coded output (green/red/yellow)

15. Write package-level Pest tests:
    - test_cms_client_handles_timeout_with_retry
    - test_circuit_breaker_opens_after_threshold_failures
    - test_cache_returns_stale_on_cms_failure
    - test_signature_verifier_constant_time
    - test_sso_redirect_generates_valid_jwt
    - test_model_sync_debounces_rapid_updates
    - test_webhook_receiver_rejects_invalid_signature
    - test_headless_client_paginates_correctly

16. Write package README.md with:
    - Installation
    - Configuration for each mode
    - Usage examples (one per mode)
    - Troubleshooting
    - Upgrade guide

PART B — CMS-Side Connector Endpoints:

17. Create central migration: create_registered_connectors_table.php
    (spec 18.6)

18. Create app/Models/Central/RegisteredConnector.php with:
    - belongsTo(Tenant::class)
    - belongsTo(PersonalAccessToken::class, 'api_token_id')
    - encrypted cast for webhook_secret

19. Build app/Domain/Connector/Actions/:
    - RegisterConnector.php — generates Sanctum token, webhook_secret,
      stores RegisteredConnector, returns credentials to caller
    - HandleIncomingWebhook.php — verifies HMAC signature, looks up
      event_type, dispatches to listeners
    - DispatchOutgoingWebhook.php — sends webhook to connector's
      webhook_url, HMAC-signed, with retry
    - RevokeConnector.php — deletes token, marks connector inactive

20. Build app/Domain/Connector/Services/:
    - ConnectorManager.php — registry of all active connectors per tenant,
      cache for performance
    - WebhookSigner.php — sign/verify HMAC for outgoing/incoming webhooks
    - ConnectorAuthService.php — token-based auth for connector API calls

21. Build app/Domain/Connector/Listeners/
    ForwardDomainEventsToConnectors.php — listens to EntryCreated,
    EntryUpdated, EntryPublished, EntryDeleted, FormSubmitted; for each
    active connector subscribed to that event, dispatches
    DispatchOutgoingWebhook.

22. Build routes/connector.php with V4 endpoints (spec 18.7):
    - POST /api/v1/connector/register — register new connector
    - POST /api/v1/connector/sso/bridge — verify SSO JWT, log in user,
      return session cookie
    - GET /api/v1/connector/status — health check (auth: connector token)
    - POST /api/v1/webhooks/incoming — receive events from host app
    - GET/POST/DELETE /api/v1/webhooks/subscriptions[/{id}] — manage
      connector's event subscriptions
    - All V3 /api/v1/collections/* and /api/v1/entries/* endpoints now
      accept X-Connector-Id header for audit attribution

23. Register routes in bootstrap/app.php, with auth middleware
    (auth:connector-token — custom guard).

24. Build ConnectorAuth guard (config/auth.php):
    - Driver: token (Sanctum)
    - Provider: users (central users table)
    - Storage: hash
    - Looks up token via Bearer header, validates against
      personal_access_tokens, sets connector context

25. Build app/Http/Middleware/RequireConnectorAuth.php — applies to
    /api/v1/connector/* and /api/v1/webhooks/incoming routes.

26. Build admin UI for connector management:
    - resources/js/pages/admin/connectors/Index.vue — list registered
      connectors, name, base_url, last_seen_at, is_active, actions
    - resources/js/pages/admin/connectors/Create.vue — modal: name,
      base_url, subscribed_events (multi-select), syncable_collections
      (multi-select)
    - resources/js/pages/admin/connectors/Show.vue — detail: API token
      (shown once on create, hidden after), webhook_secret (shown once),
      recent API calls log, recent webhooks sent/received, revoke button
    - resources/js/pages/admin/connectors/Edit.vue — change name,
      subscribed_events, syncable_collections, is_active

27. Build ConnectorPolicy:
    - viewAny: Owner, Administrator
    - create: Owner, Administrator with 'manage connectors' permission
    - update: Owner, Administrator with 'manage connectors' permission
    - delete: Owner only
    - viewLogs: requires 'view connector logs' permission

PART C — Demo: Connect Shopland to CMS:

28. Set up a fresh Laravel project at ../shopland-demo (sibling repo):
    - composer create-project laravel/laravel shopland-demo
    - cd shopland-demo && composer require platform/laravel-cms-connector
    - php artisan cms-connector:install
      - Enter CMS URL: http://cms-platform.test
      - Enter tenant ID: shopland
      - Enter API token: (generate from CMS admin connector create screen)
      - Enter shared secret: (auto-generated, copy from CMS)
    - Configure modes: auth_bridge + model_sync + headless

29. In shopland-demo, create App\Models\Product implementing SyncableToCms:
    - 3 fields: name, price, description
    - toCmsEntryData() maps to collection 'products'
    - fromCmsEntryData() reverses the mapping

30. In CMS admin panel, create 'products' collection for Shopland tenant
    with blueprint matching the product fields.

31. In Shopland, create a Product via tinker:
    - Product::create(['name' => 'Test Product', 'price' => 99, ...])
    - Within 5 seconds (debounce), entry appears in CMS products collection
    - Edit entry in CMS → within 5 seconds, Product model in Shopland updates
    - Test create/update/delete in both directions

32. Write Pest feature tests on the CMS side:
    - test_connector_registration_creates_token_and_secret
    - test_sso_bridge_jwt_verifies_and_logs_in_user
    - test_incoming_webhook_with_valid_signature_processes
    - test_incoming_webhook_with_invalid_signature_returns_401
    - test_outgoing_webhook_delivery_with_retry
    - test_x_connector_id_header_attributed_in_audit_log
    - test_revoked_connector_token_no_longer_works
    - test_connector_cannot_access_other_tenants_data

Verification:
- platform/laravel-cms-connector package installs via composer
- All 5 modes (auth_bridge, model_sync, event_bus, embedded, headless) work
  in isolation
- Shopland demo: create product in Shopland → entry appears in CMS;
  edit entry in CMS → product updates in Shopland
- CMS admin connector UI works end-to-end
- Connector tokens correctly attributed in audit log
- Revoked connector can no longer make API calls
- All Pest tests pass on both package and CMS side
```

**Checklist before moving on:**
- [ ] platform/laravel-cms-connector package exists as separate composer package
- [ ] All 5 connection modes implemented and tested
- [ ] CMS-side connector endpoints work
- [ ] registered_connectors table exists
- [ ] ConnectorAuth guard works
- [ ] Admin connector management UI works
- [ ] Shopland demo bidirectional sync works
- [ ] All Pest tests pass

---

## PHASE 14 — Workflow Engine

**Goal:** Visual workflow builder for content approval, multi-step review, conditional automation.

```
Read 04-FIELD-STRUCTURE-SPEC-V4.md section 19.1 in its ENTIRETY — sections 19.1.1 through
19.1.5.

Task:

PART A — Schema & Models:

1. Create tenant migrations:
   - create_workflows_table.php (spec 19.1.1)
   - create_workflow_instances_table.php
   - create_workflow_node_executions_table.php

2. Create app/Models/Tenant/:
   - Workflow.php — belongsTo(Tenant), hasMany(WorkflowInstance),
     casts definition to json, trigger_collections to json
   - WorkflowInstance.php — belongsTo(Workflow), belongsTo(Entry),
     hasMany(WorkflowNodeExecution), casts context to json
   - WorkflowNodeExecution.php — belongsTo(WorkflowInstance),
     belongsTo(User, 'executed_by'), casts output to json

PART B — Domain Layer:

3. Build app/Domain/Workflow/Services/WorkflowEngine.php:
   - start(Workflow $workflow, Entry $entry, array $initialContext = []):
     WorkflowInstance — creates instance, executes start node
   - advance(WorkflowInstance $instance, string $action, ?User $user,
     ?string $comment = null): WorkflowInstance — processes the action on
     the current node, advances to next node per definition
   - cancel(WorkflowInstance $instance, User $user, string $reason): void
   - getNodeDefinition(WorkflowInstance $instance, string $nodeId): object
   - getNextNode(WorkflowInstance $instance, string $currentNodeId,
     string $action): ?string

4. Build app/Domain/Workflow/Services/WorkflowValidator.php:
   - validateDefinition(array $definition): array — returns list of errors
     - All node IDs unique
     - All `next` / `on_approve` / `on_reject` / `on_true` / `on_false`
       references point to existing nodes
     - Exactly one `start` node
     - All paths eventually reach an `end` node (no infinite loops in DAG)
     - All `approval` nodes have assignee_type and assignee_value
     - All `condition` nodes have valid Twig expression
     - All `action` nodes have valid action class
   - validateTrigger(Workflow $workflow): array — checks if trigger event
     is valid, trigger_collections exist

5. Build app/Domain/Workflow/Services/NodeExecutor/ (one class per node type):
   - ApprovalNode.php — execute(WorkflowInstance $instance, $nodeDef):
     WorkflowNodeExecution (status=pending, awaits user action)
   - ConditionNode.php — evaluate Twig expression against entry context,
     returns WorkflowNodeExecution with output {branch: "true"|"false"}
   - ActionNode.php — instantiate action class, call execute(), returns
     WorkflowNodeExecution with output
   - ParallelNode.php — spawns child instances for each branch
   - WaitNode.php — schedules a queued job to resume at wait_until time
   - StartNode.php / EndNode.php — minimal, transition to next

6. Build built-in action classes in app/Domain/Workflow/Actions/Builtin/:
   - PublishEntry.php — calls V3 PublishEntry action
   - UnpublishEntry.php
   - SendEmail.php — sends templated email
   - CallWebhook.php — POST to URL with HMAC signing
   - SetField.php — sets a field value on the entry
   - AddTag.php — adds a taxonomy term
   All implement WorkflowActionInterface: execute(Entry $entry, array $config):
   array

7. Build app/Domain/Workflow/Actions/:
   - StartWorkflow.php — wraps WorkflowEngine::start()
   - AdvanceWorkflow.php — wraps WorkflowEngine::advance()
   - CancelWorkflow.php — wraps WorkflowEngine::cancel()

8. Build events + listeners:
   - Events: WorkflowStarted, WorkflowNodeCompleted, WorkflowCompleted,
     ApprovalRequired, WorkflowCancelled
   - Listeners/NotifyApprovers.php — on ApprovalRequired, sends
     notification (email + in-app) to assigned approvers
   - Listeners/HandleSlaBreaches.php — daily cron, finds pending approval
     nodes past SLA, notifies admin

9. Build app/Domain/Workflow/Services/ConditionEvaluator.php:
   - Twig-based expression evaluator (use twig/twig)
   - Available variables: entry (with data, taxonomy_terms, etc.), tenant,
     user (current), now
   - Sandboxed: no access to PHP functions, only property access and
     basic operators

PART C — Triggers & Auto-Start:

10. Register event listeners in EventServiceProvider:
    - When EntryCreated fires, find all active workflows with
      trigger_event = 'entry.created' whose trigger_collections includes
      the entry's collection handle; for each, dispatch StartWorkflow
    - Same for 'entry.updated', 'entry.submitted_for_review',
      'entry.published'

11. Add a "Submit for Review" button to entry edit screen that fires
    entry.submitted_for_review event (V3 had this concept implicitly;
    make it explicit in V4).

PART D — Admin UI:

12. Build Workflow Builder (resources/js/pages/admin/workflows/Builder.vue):
    - Use Vue Flow (or @vue-flow/core) for the DAG canvas
    - Node palette on left: Start, Approval, Condition, Action, Parallel,
      Wait, End
    - Properties panel on right: changes based on selected node type
    - JSON preview at bottom (read-only, can be copy-pasted for sharing)
    - Save/Save As Draft/Test Run buttons
    - Test Run: lets admin pick a test entry, simulates the workflow
      without actually publishing/notify, shows execution trace

13. Build Workflow Instances list:
    - resources/js/pages/admin/workflows/Instances.vue
    - Filterable by workflow, status, current node
    - Drill into instance detail: timeline view of node executions,
      with user avatars, timestamps, comments
    - "Cancel" button (with confirmation) for running instances

14. Build My Approvals queue:
    - resources/js/pages/admin/workflows/MyApprovals.vue
    - Cards: entry title, workflow name, current node label, assigned at,
      SLA countdown
    - Approve / Reject / Request Changes buttons
    - Comment field (required on reject, optional on approve)

PART E — Permissions & Policy:

15. Build WorkflowPolicy:
    - viewAny: all tenant users
    - create/update/delete: 'manage workflows' permission
    - approve: 'approve in workflows' permission OR user is assigned to
      current node
    - cancel: 'cancel workflow instances' permission (Owner/Admin)
    - viewInstances: 'view workflow instances' permission

16. Add V4 permissions to RolePermissionSeeder:
    - manage workflows, approve in workflows, view workflow instances,
      cancel workflow instances

PART F — Pest Tests:

17. Pest feature tests:
    - test_workflow_starts_on_entry_created_trigger
    - test_approval_node_waits_for_action
    - test_condition_node_evaluates_twig_expression
    - test_action_node_publishes_entry
    - test_sla_breach_triggers_notification
    - test_cancel_workflow_marks_instance_cancelled
    - test_only_assigned_approver_can_approve
    - test_workflow_validation_rejects_invalid_definition
    - test_workflow_validation_rejects_orphaned_next_references
    - test_tenant_isolation_in_workflows
    - test_my_approvals_queue_shows_only_my_pending

Verification:
- Admin can build a 5-node workflow via drag-drop UI
- Create entry in trigger collection → workflow auto-starts
- Approver receives email + sees approval in their queue
- Approve → workflow advances, eventually publishes entry
- Reject → workflow ends with outcome 'rejected', entry stays draft
- Cancel button works
- SLA breach notification fires
- All Pest tests pass
```

**Checklist before moving on:**
- [ ] Workflow Builder UI works (drag-drop, save, test run)
- [ ] All 7 node types implemented
- [ ] 6 built-in action classes work
- [ ] Triggers fire on entry events
- [ ] My Approvals queue works
- [ ] SLA breach detection works
- [ ] Permissions enforced
- [ ] All Pest tests pass

---

## PHASE 15 — A/B Testing + Real-time Collaborative Editing

**Goal:** A/B testing with conversion tracking + Yjs-based real-time co-editing.

```
Read 04-FIELD-STRUCTURE-SPEC-V4.md sections 19.2 and 19.3 in their ENTIRETY.

Task:

PART A — A/B Testing Schema:

1. Create tenant migrations:
   - create_experiments_table.php (spec 19.2.1)
   - create_experiment_variants_table.php
   - create_experiment_assignments_table.php
   - Note: experiment_assignments needs unique index on
     (experiment_id, visitor_id)

2. Create app/Models/Tenant/:
   - Experiment.php with casts for traffic_allocation, goal_config,
     trigger_collections to native types
   - ExperimentVariant.php
   - ExperimentAssignment.php

PART B — A/B Testing Engine:

3. Build app/Domain/Experiment/Services/ExperimentEngine.php:
   - findActiveForEntry(Entry $entry): ?Experiment — finds experiments
     whose entry_id matches OR collection_handle matches and status=running
   - findActiveForRoute(string $route): ?Experiment — for template/CTA
     experiments
   - assignVisitor(Experiment $experiment, string $visitorId, ?int $userId):
     ExperimentVariant — checks for existing assignment first, else
     weighted random selection
   - shouldShowVariant(Experiment $experiment, string $visitorId,
     ?int $userId): ?ExperimentVariant — null if visitor not in
     experiment (traffic_allocation check)

4. Build app/Domain/Experiment/Services/VariantSelector.php:
   - selectWeightedRandom(Collection $variants): ExperimentVariant —
     weighted random per variant.weight (control + variants should sum
     to 100, but normalize if not)
   - isInExperiment(int $trafficAllocation): bool — random 0-99,
     return true if < trafficAllocation

5. Build app/Domain/Experiment/Services/StatisticalSignificance.php:
   - calculate(Experiment $experiment): object — returns per-variant
     stats: visitors, conversions, conversion_rate, lift_vs_control,
     confidence (using two-proportion z-test)
   - isSignificant(Experiment $experiment): bool — true if any variant
     has confidence >= experiment.confidence_threshold AND sample size
     >= experiment.min_sample_size
   - determineWinner(Experiment $experiment): ?ExperimentVariant —
     returns the variant with highest conversion rate that is also
     statistically significant

6. Build app/Http/Middleware/AssignExperimentVariant.php:
   - Runs after auth, before controller
   - Sets visitor_id cookie if not present (10-year expiry)
   - For the current entry/route, finds active experiment
   - Assigns visitor to variant, stores in request attributes
   - Controller reads via request()->experimentVariant()

7. Build app/Domain/Experiment/Actions/:
   - CreateExperiment.php
   - AssignVisitorToVariant.php
   - TrackConversion.php — sets converted_at, conversion_value on
     experiment_assignments row
   - PromoteWinningVariant.php — for entry_variant: copies variant entry
     data to control entry, archives experiment. For template_variant:
     updates collection's default template, archives experiment.

8. Build tracking endpoint:
   - POST /api/v1/experiments/{id}/convert — body: {variant_id, value?}
   - Sets converted_at = now, conversion_value = value
   - Idempotent: if already converted, no-op

9. Build admin UI:
   - resources/js/pages/admin/experiments/Index.vue — list with status
     badges, traffic allocation, conversion rate preview
   - resources/js/pages/admin/experiments/Create.vue — wizard:
     1. Pick type (entry_variant, template_variant, cta_variant,
        headline_variant)
     2. Pick entry/collection/CTA
     3. Define variants (pick alternate entries OR define field
        overrides inline)
     4. Set goal (conversion form, bounce rate, time on page, custom
        event)
     5. Set traffic allocation + variant weights + schedule
     6. Review + Launch
   - resources/js/pages/admin/experiments/Show.vue — dashboard:
     per-variant visitors, conversions, conversion rate, lift, confidence
     "Promote Winner" button (only appears when winner determined)

PART C — Real-time Collaborative Editing (Yjs):

10. Install Laravel Reverb:
    - composer require laravel/reverb
    - php artisan reverb:install
    - Configure .env: REVERB_HOST, REVERB_PORT, REVERB_SCHEME
    - Start server: php artisan reverb:start --debug

11. Install Yjs server-side:
    - composer require phadej/yjs — OR — implement a custom Yjs sync
      handler using Laravel Reverb's WebSocket events
    - Alternative: use y-websocket server-side compatible handler

12. Create tenant migrations:
    - create_collab_sessions_table.php (spec 19.3.1) — yjs_document_state
      is a longblob column
    - create_collab_presence_table.php

13. Create app/Models/Tenant/:
    - CollabSession.php
    - CollabPresence.php

14. Build app/Domain/Collab/Services/YjsServer.php:
    - onConnect($connection): create or resume collab session
    - onMessage($connection, $message): Yjs sync protocol Step1/Step2,
      awareness update
    - broadcast to all other connections in the same session
    - persist document state every 5 seconds (debounced)
    - onDisconnect($connection): remove from presence, if last connection
      persist final state and end session

15. Build app/Domain/Collab/Services/DocumentPersister.php:
    - persist(CollabSession $session): converts Yjs document to field
      format (HTML for bard, markdown for markdown, plain for text),
      saves to entries.data
    - This is called periodically AND on session end

16. Build app/Domain/Collab/Services/AwarenessBroadcaster.php:
    - Tracks per-session presence (user_id, cursor_position, color)
    - Broadcasts presence updates to all session members
    - Cleans up stale presence (no heartbeat in 30s)

17. Register WebSocket routes:
    - routes/channels.php:
      Broadcast::channel('collab.{tenantId}.{entryId}.{fieldHandle}',
        function ($user, $tenantId, $entryId, $fieldHandle) {
          // Verify user has edit access to this entry
          return Gate::allows('update', Entry::find($entryId));
        });

18. Build client-side Yjs integration:
    - resources/js/components/field-types/BardField.vue (V3) — extend
      with optional collab mode
    - When field config has enable_collab: true:
      - Initialize Yjs document
      - Connect to Reverb WebSocket at /app/collab/{tenantId}/{entryId}/
        {fieldHandle}
      - Render other users' cursors with their colors and names
      - Show presence ribbon above the field with active editors' avatars
    - Use y-prosemirror for Bard (ProseMirror-based)
    - Use y-textarea for textarea/markdown fields

19. Build "Take Over" feature (Owner only):
    - Button in field header (visible only to Owner role)
    - POST /admin/api/collab/{sessionId}/force-lock
    - All other users get disconnected with message "Session force-locked
      by {Owner Name}. You can no longer edit this field."
    - Auto-releases after 30 minutes

20. Build admin UI for collab session monitoring:
    - resources/js/pages/admin/collab/Index.vue — list of active sessions:
      entry title, field handle, active editors (avatars), started at
    - resources/js/pages/admin/collab/Show.vue — session detail: live
      presence view, force-end button

PART D — Pest Tests:

21. Pest tests:
    - test_visitor_assignment_respects_traffic_allocation (run 1000
      assignments, assert distribution matches weights ±5%)
    - test_visitor_assignment_is_sticky (same visitor_id gets same variant)
    - test_conversion_tracking_idempotent
    - test_statistical_significance_calculates_correctly (known data set)
    - test_promote_winner_copies_variant_to_control
    - test_yjs_document_persists_after_disconnect
    - test_collab_presence_cleans_up_after_30s_no_heartbeat
    - test_force_lock_disconnects_other_users
    - test_user_without_edit_access_cannot_join_collab_session
    - test_tenant_isolation_in_experiments_and_collab

Verification:
- Create an A/B experiment with 2 variants (control + variant), 100%
  traffic allocation, 50/50 split
- Visit entry 100 times with different visitor cookies, assert split is
  ~50/50
- Submit conversion form, assert conversion is tracked
- Run statistical significance calculation, assert correct output
- Promote winner, assert variant entry replaces control
- Two browser sessions edit same Bard field simultaneously
- See each other's cursors in real-time
- Save persists both users' changes conflict-free
- All Pest tests pass
```

**Checklist before moving on:**
- [ ] A/B experiment creation wizard works
- [ ] Visitor assignment respects traffic allocation and weights
- [ ] Conversion tracking works (form submission, custom event)
- [ ] Statistical significance calculation correct
- [ ] Promote winner works for all 4 experiment types
- [ ] Yjs WebSocket server runs
- [ ] Real-time cursor presence works
- [ ] Bard field supports collab mode
- [ ] Force-lock works (Owner only)
- [ ] All Pest tests pass

---

## PHASE 16 — AI RAG + Personalization

**Goal:** Per-tenant vector store for AI Q&A + visitor segments and personalization rules.

```
Read 04-FIELD-STRUCTURE-SPEC-V4.md sections 19.4 and 19.5 in their ENTIRETY.

Task:

PART A — AI RAG Schema:

1. Create tenant migrations:
   - create_rag_documents_table.php (spec 19.4.1)
     - For Postgres: use pgvector migration ($table->vector('embedding', 1536))
     - For MySQL: $table->json('embedding') with note that brute-force
       search will be used (works for <50k documents)
     - Add HNSW index on embedding for Postgres
   - create_rag_queries_table.php

2. Create app/Models/Tenant/:
   - RagDocument.php — belongsTo(Entry)
   - RagQuery.php — belongsTo(User) nullable

PART B — RAG Indexing Pipeline:

3. Build app/Domain/Rag/Services/Chunker.php:
   - chunk(Entry $entry, string $fieldHandle, int $chunkSize = 500,
     int $overlap = 50): array — splits text into chunks with overlap
   - Uses sentence boundaries for cleaner chunks
   - Returns array of Chunk DTOs: { text, chunk_index, metadata: {heading, page_url, language} }

4. Build app/Domain/Rag/Services/EmbeddingService.php:
   - embed(string $text): array — calls AI provider's embedding endpoint,
     returns float[1536] (or whatever the model's dimension is)
   - embedBatch(array $texts): array — batch embedding for efficiency
   - Configurable model: OpenAI text-embedding-3-small (default),
     text-embedding-3-large, or Anthropic equivalent
   - Caches embeddings in Redis for 30 days to avoid re-embedding
     identical text

5. Build app/Domain/Rag/Services/VectorSearch.php:
   - search(int $tenantId, array $queryEmbedding, int $k = 5): array
     - Postgres: SELECT * FROM rag_documents WHERE tenant_id = ? ORDER BY
       embedding <-> ? LIMIT ?
     - MySQL: SELECT * FROM rag_documents WHERE tenant_id = ? — then
       compute cosine similarity in PHP, sort, take top K
   - Returns array of { document, similarity_score }

6. Build app/Domain/Rag/Services/RagService.php:
   - indexEntry(Entry $entry): void — for each text field with
     include_in_rag: true, chunk + embed + store
   - removeFromIndex(Entry $entry): void — delete all rag_documents for
     this entry
   - reindexEntry(Entry $entry): void — remove + index
   - ask(int $tenantId, string $question, ?int $userId = null): RagResponse
     1. Embed question
     2. Vector search top-K chunks
     3. Build prompt: system prompt + chunks + question
     4. Call AI provider (configurable: gpt-4o, claude-sonnet-4-20250514, etc.)
     5. Post-process answer to add citation links
     6. Log to rag_queries
     7. Return RagResponse DTO (answer, citations, retrieved_chunks)

7. Build app/Domain/Rag/Services/CitationFormatter.php:
   - format(string $answer, array $retrievedChunks): string — looks for
     phrases in the answer that match chunks, inserts markdown links
     `[According to {entry.title}]({entry.url})` at the first mention
   - Appends a "Sources" section at the end with all referenced entries

8. Build app/Domain/Rag/Actions/:
   - IndexEntry.php — wraps RagService::indexEntry, dispatches as queued job
   - RemoveEntryFromIndex.php
   - RegenerateEmbeddings.php — bulk reindex for a tenant (used when
     embedding model changes)
   - Ask.php — wraps RagService::ask

9. Wire into entry lifecycle events:
   - EntryPublished → dispatch IndexEntry job
   - EntryUpdated → dispatch reindexEntry job (only if entry is published)
   - EntryUnpublished → dispatch RemoveEntryFromIndex job
   - EntryDeleted → dispatch RemoveEntryFromIndex job

PART C — RAG API & UI:

10. Build public API endpoint:
    - POST /api/v1/rag/ask — body: { question: string }
    - Auth: optional (anonymous allowed if tenant config allows; else
      requires auth)
    - Rate limited per IP: 30 req/min, per tenant total: configurable
    - Returns: { answer, citations: [{title, url}], query_id }

11. Build public chat widget:
    - resources/js/components/RagChatWidget.vue — droppable component
    - Floating chat button bottom-right
    - Slide-up chat panel with conversation history (last 5 queries)
    - Uses theme's --brand-color for styling
    - Sends POST /api/v1/rag/ask, streams response (if AI provider supports)
    - Thumbs up/down feedback after each answer
    - Widget enable/disable per-tenant via tenants.data.features.rag_public_widget

12. Build admin UI:
    - resources/js/pages/admin/rag/Playground.vue — chat interface for
      admins to test RAG Q&A
    - resources/js/pages/admin/rag/IndexStatus.vue — per-entry: indexed
      status, last indexed at, chunk count, reindex button
    - resources/js/pages/admin/rag/QueriesLog.vue — table of all queries
      with feedback ratings, filterable by date/user/rating
    - resources/js/pages/admin/rag/Settings.vue — model selection,
      embedding model, chunk size, top-K, system prompt customization,
      rate limits

PART D — Personalization Schema:

13. Create tenant migrations:
    - create_segments_table.php (spec 19.5.1)
    - create_segment_visitors_table.php
    - create_personalization_rules_table.php

14. Create app/Models/Tenant/:
    - Segment.php — casts rules to json
    - SegmentVisitor.php
    - PersonalizationRule.php — casts target_config to json

PART E — Personalization Engine:

15. Build app/Domain/Personalization/Services/SegmentEvaluator.php:
    - evaluate(Segment $segment, Request $request): bool — evaluates the
      segment's rules against the current visitor
    - evaluateAll(int $tenantId, Request $request): Collection — finds
      all matching segments for the visitor, caches in session
    - Logic combinator: AND / OR / NOT supported

16. Build condition implementations in app/Domain/Personalization/Conditions/:
    - VisitCountCondition.php — reads from visitor_sessions table (new
      V4 table OR reuse existing analytics)
    - GeoCountryCondition.php — uses GeoIP lookup (MaxMind GeoLite2 free)
    - ReferrerCondition.php — reads Referer header
    - QueryParamCondition.php — reads query string
    - CookieCondition.php — reads cookies
    - UserRoleCondition.php — reads from auth user
    - UserTagCondition.php — reads from users.tags (V4 column)
    - ViewedEntryCondition.php — reads from visitor_session_views table
    - SubmittedFormCondition.php — reads from form_submissions joined by
      visitor_id or user_id
    - TimeOfDayCondition.php — current hour
    - DayOfWeekCondition.php
    - ExperimentVariantCondition.php — reads from experiment_assignments
    - Each implements ConditionInterface: matches(array $config, Context
      $context): bool

17. Build app/Domain/Personalization/Services/VisitorProfiler.php:
    - getProfile(Request $request): VisitorProfile — aggregates all known
      info about the current visitor: visit_count, first_visit_at,
      last_visit_at, geo, referrer, device_type, browser, viewed_entries,
      submitted_forms, segment memberships
    - Caches profile in session for 1 hour, then refreshes
    - Updates visitor_sessions table on every request (last_active_at)

18. Build app/Domain/Personalization/Services/RuleApplier.php:
    - apply(Entry $entry, Request $request): Entry — finds all active
      personalization_rules matching the visitor's segments, ordered by
      priority, applies each:
      - entry_field_override: merges overrides into entry.data
      - template_override: changes entry's resolved_template
      - block_visibility: marks named blocks as visible/hidden (via
        session state, Blade reads)
      - redirect: returns RedirectResponse (abort current request flow)

19. Build app/Http/Middleware/ApplyPersonalization.php:
    - Runs after theme resolution, before controller
    - Evaluates segments for current visitor, caches result
    - Loads active personalization_rules, applies to request attributes
    - Controllers and Blade views read via request()->personalization() or
      @personalizeBlock Blade directive

20. Register @personalizeBlock Blade directive:
    - resources/views/components/personalize-block.blade.php
    - Usage: @personalizeBlock('hero_banner') ... @endPersonalizeBlock
    - Reads from session: if hidden by any rule, renders empty string

PART F — Personalization Admin UI:

21. Build segment builder:
    - resources/js/pages/admin/segments/Builder.vue
    - Visual rule builder: add conditions, pick type, operator, value
    - Logic combinator: AND/OR/NOT
    - Live preview: shows estimated segment size (queries visitor_sessions
      with the rule)
    - Save as static (cache memberships) or dynamic (re-evaluate per
      request)

22. Build personalization rules UI:
    - resources/js/pages/admin/personalization/Index.vue — list with
      drag-drop priority reordering
    - resources/js/pages/admin/personalization/Create.vue — wizard:
      1. Pick segment
      2. Pick target type (field override, template override, block
         visibility, redirect)
      3. Configure target
      4. Set schedule (start/end)
      5. Review + Activate

23. Build dashboard:
    - resources/js/pages/admin/personalization/Dashboard.vue — per-rule:
      visitors matched, conversions attributed, lift vs. baseline

PART G — Pest Tests:

24. Pest tests:
    - test_rag_indexes_published_entry_chunks
    - test_rag_removes_index_on_unpublish
    - test_rag_search_returns_relevant_chunks (known fixture data)
    - test_rag_ask_returns_citations
    - test_rag_tenant_isolation (Tenant B's RAG cannot see Tenant A's docs)
    - test_segment_evaluates_visit_count_condition
    - test_segment_evaluates_geo_condition (mocked GeoIP)
    - test_personalization_field_override_changes_entry_data
    - test_personalization_template_override_renders_different_view
    - test_personalization_block_visibility_hides_block
    - test_personalization_redirects
    - test_personalization_priority_ordering
    - test_visitor_profile_caches_for_1_hour

Verification:
- Publish 5 entries on AdvMedi tenant, wait for RAG indexing
- Ask "what services does AdvMedi offer?" via Playground → answer cites
  the relevant entry
- Ask same question via API as Tenant B → no results (tenant isolation)
- Create segment "Returning visitors from Germany" (visit_count >= 3,
  geo_country = DE)
- Create personalization rule: show special offer banner to this segment
- Visit site 3 times with German IP, assert banner appears
- Visit site from US IP, assert banner does not appear
- All Pest tests pass
```

**Checklist before moving on:**
- [ ] RAG indexing works on entry publish
- [ ] RAG Q&A returns cited answers
- [ ] RAG tenant isolation enforced
- [ ] Public chat widget embeddable on themed pages
- [ ] Segment builder works with all 12+ condition types
- [ ] Personalization rules apply correctly (all 4 target types)
- [ ] Personalization priority ordering works
- [ ] All Pest tests pass

---

## PHASE 17 — SAML SSO + SCIM + Audit Streaming

**Goal:** Enterprise SSO via SAML 2.0, SCIM 2.0 user provisioning, real-time audit log streaming to SIEM.

```
Read 04-FIELD-STRUCTURE-SPEC-V4.md sections 19.6, 19.7, 19.8 in their ENTIRETY.

Task:

PART A — SAML 2.0 SSO:

1. Install dependencies:
   - composer require aacotroneo/laravel-saml2 (extended for multi-tenant)
   - Note: this package needs minor customization for multi-tenant SP —
     each tenant has its own SP entity ID and cert. Either fork the package
     OR build a custom SAML SP using lightsaml/lightsaml.

2. Create tenant migrations:
   - create_saml_identity_providers_table.php (spec 19.6.1)
   - create_saml_sessions_table.php

3. Create app/Models/Tenant/:
   - SamlIdentityProvider.php — casts attribute_mapping, role_mapping to json
   - SamlSession.php

4. Build app/Domain/Sso/Services/SamlServiceProvider.php:
   - getMetadata(SamlIdentityProvider $idp): string — generates SP metadata
     XML for this IdP
   - initiateLogin(SamlIdentityProvider $idp, ?string $relayState = null):
     RedirectResponse — generates AuthnRequest, redirects to IdP
   - processResponse(SamlIdentityProvider $idp, string $samlResponse):
     User — validates response, extracts attributes, finds or creates user
   - initiateLogout(SamlIdentityProvider $idp): RedirectResponse
   - processLogout(SamlIdentityProvider $idp, string $samlResponse): void

5. Build app/Domain/Sso/Services/AttributeMapper.php:
   - mapUserAttributes(User $user, SamlIdentityProvider $idp, array
     $samlAttributes): User — applies attribute_mapping config
   - mapRoles(User $user, SamlIdentityProvider $idp, array $samlAttributes):
     User — applies role_mapping config, syncs roles (assigns mapped,
     unassignes unmapped if 'sync_roles_strict' is true)

6. Build routes/saml.php:
   - GET /saml/metadata/{idpId} — returns SP metadata XML
   - GET /saml/login/{idpId} — initiates login
   - POST /saml/acs — Assertion Consumer Service, processes response
   - GET /saml/sls — Single Logout Service
   - All routes scoped under tenant middleware (so /saml/* on
     enterprise.test hits EnterpriseCorp's IdPs)

7. Build admin UI:
   - resources/js/pages/admin/sso/IdpIndex.vue — list of configured IdPs
   - resources/js/pages/admin/sso/IdpCreate.vue — form:
     - Name
     - IdP metadata: paste XML OR upload file OR enter URL (auto-fetch)
     - After metadata parsed: show entity_id, sso_url, slo_url,
       x509_certificate (read-only, from metadata)
     - Attribute mapping: form fields for email, name, groups attribute
       names
     - Role mapping: pick CMS roles, map to IdP group names
     - auto_create_users: toggle
     - Test Login button (opens new tab, walks through SAML flow)
   - resources/js/pages/admin/sso/IdpShow.vue — detail: last logins,
     recent SAML responses received, test login button, deactivate

PART B — SCIM 2.0:

8. Install dependencies:
   - composer require limedeck/laravel-scim-server (extended for
     multi-tenant)

9. Create tenant migration:
   - create_scim_tokens_table.php (spec 19.7.1)

10. Create app/Models/Tenant/ScimToken.php — token_hash cast, last_used_at

11. Build app/Domain/Sso/Services/ScimServer.php:
    - Implements SCIM 2.0 endpoints per RFC 7643/7644
    - Maps CMS User → SCIM User resource
    - Maps CMS Role → SCIM Group resource
    - Supports SCIM filtering: ?filter=userName eq "..." (use
      tmilos/scim-filter-parser)
    - Supports pagination: startIndex, count
    - All operations scoped to current tenant

12. Build routes/scim.php:
    - All routes under /scim/v2/*
    - Auth middleware: ScimTokenAuth (custom guard, Bearer token)
    - GET /scim/v2/Users — list (with filter, pagination)
    - POST /scim/v2/Users — create
    - GET /scim/v2/Users/{id} — get
    - PUT /scim/v2/Users/{id} — replace
    - PATCH /scim/v2/Users/{id} — patch (add/replace/remove operations)
    - DELETE /scim/v2/Users/{id} — deactivate (sets user.active = false,
      does not delete)
    - GET /scim/v2/Groups — list
    - POST /scim/v2/Groups — create (creates CMS role)
    - GET /scim/v2/Groups/{id} / PUT / PATCH / DELETE

13. Build admin UI:
    - resources/js/pages/admin/sso/ScimTokens.vue — list tokens, name,
      last_used_at, expires_at, create new token modal (shows token ONCE
      on creation), revoke button
    - Documentation panel: shows the SCIM base URL
      (https://{tenant-domain}/scim/v2) and instructions for Okta, Azure
      AD, Google Workspace configuration

PART C — Audit Streaming:

14. Install dependencies:
    - composer require spatie/laravel-activitylog (V3 already)
    - No new dependency for streaming — uses Guzzle HTTP

15. Create tenant migrations:
    - create_audit_streams_table.php (spec 19.8.1)
    - create_audit_stream_deliveries_table.php

16. Create app/Models/Tenant/:
    - AuditStream.php — casts destination_config (encrypted), event_filter
      to json
    - AuditStreamDelivery.php — casts payload to json

17. Add V4 columns to activity_log table:
    - previous_hash (string, nullable)
    - current_hash (string)
    - severity (enum: info, warning, critical) — default 'info'

18. Build app/Domain/Audit/Services/ChainHasher.php:
    - hash(Activity $activity): string — SHA-256 of (id + previous_hash +
      payload_json)
    - verifyChain(int $tenantId, Carbon $from, Carbon $to): array —
      iterates activities in order, recomputes hashes, returns list of
      broken links

19. Build app/Domain/Audit/Services/AuditStreamManager.php:
    - registerListener(): registers a listener on ActivityLogged event
    - onActivityLogged(Activity $activity): finds all active audit_streams
      for the tenant whose event_filter matches, creates
      AuditStreamDelivery rows, dispatches DeliverAuditEvent jobs
    - matchesFilter(Activity $activity, array $filter): bool

20. Build app/Domain/Audit/Services/Destinations/ — one class per
    destination type:
    - SplunkHecDestination.php — POST to HEC URL with JSON payload,
      Authorization: Splunk {token}
    - DatadogLogsDestination.php — POST to Datadog intake with
      ddsource, ddtags
    - ElasticDestination.php — POST to Elastic _bulk endpoint with NDJSON
    - LogtailDestination.php — POST to Logtail HTTP input
    - HttpWebhookDestination.php — POST JSON with HMAC signature header
    - SyslogDestination.php — RFC 5424 UDP/TCP packet
    - Each implements DestinationInterface: send(AuditStream $stream,
      array $payload): DeliveryResult

21. Build app/Domain/Audit/Jobs/DeliverAuditEvent.php:
    - Looks up stream, gets destination adapter, calls send()
    - On 2xx: marks delivered, sets response_status, response_body
    - On 4xx: marks failed (permanent error)
    - On 5xx or network error: increments attempts, schedules retry
      with exponential backoff (3 attempts)
    - After 5 failed attempts: marks failed, notifies tenant admin

22. Build Artisan command:
    - audit:verify-chain {tenant} — runs ChainHasher::verifyChain,
      prints any broken links
    - audit:replay {stream} {from} {to} — re-delivers all events in
      window to a stream (for backfill after misconfiguration)

23. Build admin UI:
    - resources/js/pages/admin/audit/Streams.vue — list streams, status,
      last delivery, create new stream modal (pick type, fill config,
      test connection)
    - resources/js/pages/admin/audit/Deliveries.vue — recent deliveries
      table: stream, activity description, status, response status, retry
      button for failed
    - resources/js/pages/admin/audit/ChainVerification.vue — run chain
      verification, shows any broken links with details

PART D — EnterpriseCorp Demo:

24. Configure EnterpriseCorp tenant:
    - Set tenants.data.features: sso, scim, audit_streaming = true
    - Create SamlIdentityProvider record (use a test IdP like
      https://samltest.id/)
    - Create ScimToken
    - Create AuditStream → Splunk HEC (use a free Splunk trial or mock
      endpoint)

25. Test SSO login flow:
    - Visit enterprise.test/saml/login/{idpId}
    - Redirected to test IdP, authenticate
    - Redirected back, user is logged in to CMS admin
    - Test SLO (logout)

26. Test SCIM provisioning:
    - Use Postman or curl to POST a User to /scim/v2/Users with Bearer
      token
    - Verify user is created in CMS
    - PATCH the user (change email)
    - Verify user is updated
    - DELETE the user
    - Verify user is deactivated

27. Test audit streaming:
    - Perform some audited actions (login, edit entry, delete user)
    - Verify Activity rows have previous_hash / current_hash populated
      correctly
    - Verify AuditStreamDelivery rows created
    - Verify webhook received at mock endpoint (or Splunk if available)
    - Run audit:verify-chain, assert no broken links

PART E — Pest Tests:

28. Pest tests:
    - test_saml_metadata_returns_valid_xml
    - test_saml_login_creates_user_if_auto_create (mock IdP response)
    - test_saml_role_mapping_syncs_roles
    - test_saml_tenant_isolation (Tenant B's IdP not accessible on
      Tenant A's domain)
    - test_scim_create_user_endpoint
    - test_scim_filter_query_parsing
    - test_scim_patch_add_remove_replace_operations
    - test_scim_delete_deactivates_not_deletes
    - test_scim_token_auth_required
    - test_audit_stream_matches_event_filter
    - test_audit_stream_delivery_with_retry
    - test_audit_chain_hashing_links_correctly
    - test_audit_chain_breaks_on_tampering
    - test_audit_replay_redelivers_events

Verification:
- EnterpriseCorp admin can log in via SAML test IdP
- SCIM endpoints work with Postman/curl, mapped to Okta/Azure AD docs
- Audit events stream to mock Splunk endpoint
- Chain verification reports no broken links
- Tamper with one activity_log row, chain verification reports the break
- All Pest tests pass
```

**Checklist before moving on:**
- [ ] SAML SP metadata endpoint works
- [ ] SAML login flow end-to-end with test IdP
- [ ] SAML role mapping works
- [ ] All SCIM 2.0 endpoints work (Users + Groups CRUD)
- [ ] SCIM token auth required
- [ ] Audit stream configuration works for all 6 destination types
- [ ] Audit chain hashing works, tampering detected
- [ ] Audit delivery retry works on 5xx
- [ ] EnterpriseCorp demo fully working
- [ ] All Pest tests pass

---

## PHASE 18 — Form Analytics & Lead Scoring

**Goal:** Per-form conversion funnels, lead scoring against submissions, sales rep assignment.

```
Read 04-FIELD-STRUCTURE-SPEC-V4.md section 19.9 in its ENTIRETY.

Task:

PART A — Schema:

1. Create tenant migrations:
   - alter_form_submissions_table_add_v4_columns.php — adds lead_score,
     lead_score_breakdown, attribution, conversion_path, is_qualified,
     assigned_to, assigned_at (spec 19.9.1)
   - create_form_analytics_events_table.php
   - create_form_lead_scoring_rules_table.php

2. Update app/Models/Tenant/FormSubmission.php with V4 casts:
   - lead_score_breakdown → json
   - attribution → json
   - conversion_path → json
   - Add relationships: belongsTo(User, 'assigned_to')

3. Create app/Models/Tenant/:
   - FormAnalyticsEvent.php
   - FormLeadScoringRule.php — casts rules to json

PART B — Analytics Event Tracking:

4. Build tracking endpoint:
   - POST /api/v1/forms/{formId}/analytics-event — body: { event_type,
     field_handle?, event_data?, page_url }
   - Auth: none required (anonymous visitors track)
   - Rate limited: 60 req/min per IP
   - Sets visitor_id cookie if not present
   - Stores row in form_analytics_events

5. Build client-side tracking:
   - resources/js/components/FormTracker.js — composable, dropped into
     any form rendering component
   - Tracks: view (form visible in viewport), start (first field focus),
     field_focus, field_blur, field_change, submit_attempt,
     submit_success, submit_error, abandon (page unload with unsaved
     data)
   - Uses navigator.sendBeacon for abandon tracking to avoid blocking
     page unload

6. Build attribution capture:
   - On first visit, capture utm_source/medium/campaign/term/content from
     query params, store in cookie for 30 days
   - On form submission, copy cookie data to form_submissions.attribution
   - Also captures referrer, landing_page, conversion_path (last 5 page
     views before submission)

PART C — Lead Scoring:

7. Build app/Domain/Form/Actions/ScoreLead.php:
   - execute(FormSubmission $submission, FormLeadScoringRule $rules):
     void
   - For each rule in rules.rules:
     - Evaluate the condition (field, operator, value, points)
     - Sum points for matching conditions
   - Store lead_score, lead_score_breakdown
   - If score >= threshold_for_qualified, set is_qualified = true,
     trigger LeadQualified event

8. Build LeadQualified listener:
   - Sends notification to configured email/sales rep
   - If round-robin assignment is configured, picks next sales rep,
     sets assigned_to, assigned_at

9. Wire ScoreLead into form submission flow:
   - V3's form submission endpoint is extended: after storing the
     submission, look up FormLeadScoringRule for this form; if exists,
     dispatch ScoreLead job (queued, async)

PART D — Admin UI:

10. Build Form Analytics Dashboard:
    - resources/js/pages/admin/forms/{formId}/Analytics.vue
    - KPI cards: Views, Starts, Submissions, Conversion Rate, Average
      Lead Score, Qualified Leads count
    - Conversion funnel chart: View → Start → (each field) → Submit
    - Drop-off table: per-field abandon rate
    - Time-series chart of submissions over last 30 days
    - Top referrers, top landing pages

11. Build Leads Table:
    - resources/js/pages/admin/forms/{formId}/Leads.vue
    - Table: submitted_at, name, email, lead_score (with color-coded
      badge), is_qualified (star icon), assigned_to (avatar)
    - Filter: by date range, qualified only, by assigned rep
    - Sort: by lead_score descending, by submitted_at
    - Click row → lead detail page: all submission data, attribution,
      conversion path, lead score breakdown, assign/reassign button

12. Build Lead Scoring Rule Builder:
    - resources/js/pages/admin/forms/{formId}/ScoringRules.vue
    - Visual rule editor: add conditions (pick field, operator, value,
      points)
    - Live preview: shows which existing submissions would qualify
      under current rules
    - Set threshold_for_qualified (slider)
    - Save / Activate

13. Build Lead Assignment Settings:
    - resources/js/pages/admin/forms/{formId}/Assignment.vue
    - Mode: manual, round-robin, weighted
    - Round-robin: list of sales reps (tenant users with 'view leads'
      permission)
    - Weighted: per-rep weight
    - Auto-assign on: form submission, score threshold met, manual

PART E — Permissions:

14. Build FormAnalyticsPolicy:
    - viewAnalytics: requires 'view form analytics' permission
    - manageScoringRules: requires 'manage lead scoring' permission
    - viewLeads: requires 'view leads' permission
    - assignLeads: requires 'assign leads' permission

15. Add V4 permissions to RolePermissionSeeder:
    - view form analytics, manage lead scoring, view leads, assign leads

PART F — Pest Tests:

16. Pest tests:
    - test_analytics_event_tracking_records_event
    - test_attribution_captures_utm_params
    - test_conversion_path_records_last_5_page_views
    - test_lead_scoring_sums_points_correctly
    - test_qualified_flag_set_when_score_above_threshold
    - test_lead_qualified_notification_sent
    - test_round_robin_assignment_cycles_through_reps
    - test_lead_score_preview_matches_actual_scoring
    - test_tenant_isolation_in_form_analytics

Verification:
- Create a form with 4 fields, add lead scoring rules
- Visit form via tracked link with utm_source=test
- Submit form 5 times with different data
- View Form Analytics Dashboard, assert correct metrics
- View Leads table, assert scores and qualification flags correct
- Test round-robin assignment
- All Pest tests pass
```

**Checklist before moving on:**
- [ ] Form analytics events tracked end-to-end
- [ ] Attribution (UTM, referrer, conversion path) captured
- [ ] Lead scoring rules engine works
- [ ] Qualified flag triggers notifications
- [ ] Round-robin assignment works
- [ ] Admin UI: dashboard, leads table, scoring rules, assignment settings
- [ ] All Pest tests pass

---

## PHASE 19 — Polish, Cross-Feature Integration, Final Testing

**Goal:** Wire V4 features together, polish UX, comprehensive tenant-isolation tests.

```
Read 04-FIELD-STRUCTURE-SPEC-V4.md sections 9.4, 14 in their ENTIRETY (V4 additions to
file structure + summary table).

Task:

PART A — Cross-Feature Integration:

1. Workflow Engine + Collab Editing:
   - When a workflow's approval node is pending, lock the entry's fields
     from collab editing (show "Entry in review, edits locked" banner)
   - On workflow complete (published or rejected), unlock

2. A/B Testing + Personalization:
   - A/B experiment variants can target specific segments
   - Variant assignment is conditional on segment membership first

3. AI RAG + Workflow Engine:
   - New workflow action: "Ask AI RAG" — action node that takes a prompt,
     queries the tenant's RAG, stores the answer in entry context for
     subsequent nodes to use

4. Personalization + Form Analytics:
   - Personalization rule target: "modify form" — can pre-fill form
     fields based on segment context
   - Form analytics dashboard: filter conversions by segment

5. SSO + Audit Streaming:
   - SAML logins and SCIM provisioning events are streamed to configured
     audit destinations

6. Connector + Workflow Engine:
   - Host app can subscribe to 'workflow.completed' event via event bus
   - Host app can trigger workflows via API endpoint:
     POST /api/v1/workflows/{id}/start

PART B — Feature Flag Management UI:

7. Build admin feature flag management screen:
   - resources/js/pages/admin/settings/FeatureFlags.vue
   - Lists all V4 features with toggle switches
   - Per-tenant (visible only to Owner)
   - Shows feature description + dependencies (e.g. "Audit Streaming
     requires spatie/laravel-activitylog")
   - Toggling a feature on/off: writes to tenants.data.features.{name},
     clears relevant caches

8. Update AppServiceProvider to read feature flags and:
   - Conditionally register middleware (e.g. ApplyPersonalization only
     if feature enabled)
   - Conditionally register event listeners
   - Conditionally register routes (e.g. /scim/* only if SCIM enabled)

PART C — Performance Optimization:

9. Index verification per spec section 3:
   - Verify all V4 migrations have appropriate indexes
   - Add composite indexes for common query patterns:
     - (tenant_id, status) on workflows, experiments
     - (tenant_id, experiment_id, visitor_id) on experiment_assignments
     - (tenant_id, entry_id) on rag_documents
     - (tenant_id, visitor_id) on segment_visitors
     - (form_id, occurred_at) on form_analytics_events

10. N+1 query audit:
    - Run Laravel Telescope in dev, exercise every V4 admin page
    - Identify and fix any N+1 queries
    - Verify eager loading on: workflow instances with node executions,
      experiment variants with assignments, rag documents with entries,
      form submissions with assignment user

11. Cache optimization:
    - Cache segment evaluation per visitor (in session, 1 hour TTL)
    - Cache personalization rule lookups per tenant (in Redis, 5 min TTL,
      invalidated on rule save)
    - Cache experiment variant assignments permanently (in cookie + DB)
    - Cache RagService::ask for 24 hours on identical questions (only
      for anonymous users, not for logged-in)

PART D — Final Tenant-Isolation Test Suite:

12. Write the most important test suite in the entire project:
    tests/Feature/Tenancy/V4TenantIsolationTest.php

    For EVERY V4 tenant-scoped table, attempt:
    - Tenant A user creates a record (workflow, experiment, collab
      session, rag_document, segment, personalization_rule,
      saml_idp, scim_token, audit_stream, form_analytics_event,
      form_lead_scoring_rule, workflow_instance, experiment_assignment)
    - Tenant A user can read/update/delete it
    - Tenant B user CANNOT read it via API (assert 404 or empty result)
    - Tenant B user CANNOT update it via API (assert 404 or 403)
    - Tenant B user CANNOT delete it via API (assert 404 or 403)
    - Raw DB query through Tenant B's context returns 0 rows

    This is THE security test suite. Every V4 feature MUST pass this.

13. Write tests/Feature/Tenancy/DomainIsolationTest.php:
    - For each V4 domain (shop.advmedi.test, blog.advmedi.test,
      multilingual.fr, multilingual.de, multilingual.bn,
      *.multilingual.test):
      - Confirm it resolves to the correct tenant
      - Confirm per-domain theme override applies
      - Confirm per-domain locale applies
      - Confirm subdomain-to-collection routing works
      - Confirm Tenant A's domain CANNOT serve Tenant B's content
        (no domain spoofing)

PART E — Documentation:

14. Update docs/ with V4 guides:
    - docs/v4/multi-domain-guide.md — how to configure multi-domain,
      wildcards, SSL automation, DNS verification
    - docs/v4/connector-guide.md — how to install the connector package
      in an existing Laravel app, configure each mode
    - docs/v4/workflow-engine.md — how to build, test, deploy workflows
    - docs/v4/ab-testing.md — how to set up experiments, read stats,
      promote winners
    - docs/v4/collab-editing.md — how collab editing works, how to
      enable per-field
    - docs/v4/ai-rag.md — how RAG indexing works, how to customize
      system prompt, how to embed the chat widget
    - docs/v4/personalization.md — how to build segments and rules
    - docs/v4/saml-sso.md — how to configure SAML with Okta/Azure AD/
      Google Workspace
    - docs/v4/scim-provisioning.md — how to configure SCIM with major IdPs
    - docs/v4/audit-streaming.md — how to configure Splunk/Datadog/
      Elastic destinations
    - docs/v4/form-analytics.md — how to set up lead scoring, read
      analytics dashboard

15. Update main README.md with V4 sections:
    - V4 feature overview
    - How to enable V4 features per-tenant
    - How to install the connector package
    - How to configure multi-domain
    - Link to all V4 docs

16. Update CLAUDE.md with V4 conventions:
    - All V4 domains and their namespaces
    - V4 feature flag pattern
    - V4 testing conventions (V4TenantIsolationTest is mandatory)
    - V4 middleware order

PART F — Final Verification:

17. Fresh clone of the repo, follow only the README, confirm:
    - V3 phases (0-11) still work end-to-end (AdvMedi, BitDreamIT both
      functional with their themes and content)
    - All V4 phases (12-19) work end-to-end (3 new tenants functional)
    - All V4 features can be toggled on/off per tenant via Feature Flags UI
    - V4TenantIsolationTest passes (zero cross-tenant data leakage)
    - DomainIsolationTest passes (zero cross-domain content leakage)
    - 200+ Pest tests pass

Verification:
- All V4 features work in isolation
- All V4 cross-feature integrations work
- V4TenantIsolationTest: zero failures
- DomainIsolationTest: zero failures
- Feature flags correctly enable/disable functionality
- Performance: all admin pages load in <500ms with Telescope-off
- Fresh clone setup completes in <45 minutes (V3 baseline + V4 additions)
- All Pest tests pass
```

**Checklist (FINAL):**
- [ ] All V4 cross-feature integrations work
- [ ] Feature Flags management UI works
- [ ] All V4 migrations have appropriate indexes
- [ ] Zero N+1 queries on V4 admin pages
- [ ] V4TenantIsolationTest passes (zero cross-tenant leakage)
- [ ] DomainIsolationTest passes (zero cross-domain leakage)
- [ ] All 11 V4 docs written
- [ ] README updated with V4 sections
- [ ] CLAUDE.md updated with V4 conventions
- [ ] Fresh clone setup completes in <45 minutes
- [ ] All 200+ Pest tests pass

---

## Notes on Using These V4 Prompts with an AI Coding Agent

- **Complete V3 first.** V4 phases assume V3 phases 0-11 are complete and verified.
- **Phase 12 (Multi-Domain) is foundational.** Build it first; later V4 phases reference domain resolution.
- **Phase 13 (Connector) is independent.** Can be built in parallel with Phases 14-18 by a separate developer; the connector package lives in a separate repo.
- **Phases 14-18 are independent of each other** (except where noted in Phase 19 integration). They can be built in parallel.
- **Phase 19 (Polish) is the final integration phase.** Do not skip it — the cross-feature integrations and tenant-isolation tests are critical.
- **Budget extra time for Phase 17 (SAML/SCIM/Audit).** SAML SP setup with multi-tenant is tricky; allocate 2-3x the time of other phases.
- **Test IdPs for SAML:** use https://samltest.id/ (free, public test IdP).
- **For SCIM testing:** use Postman with the SCIM 2.0 collection; or use Microsoft's SCIM reference client.
- **For RAG:** pgvector requires Postgres. If you're on MySQL, the JSON-based fallback works for <50k documents but is significantly slower for larger corpora. Consider migrating to Postgres for tenants using RAG at scale.
- **For collab editing:** Laravel Reverb is a separate process. In production, run it via Supervisor. For local dev, `php artisan reverb:start --debug` works.
- **For audit streaming:** test with a free Splunk Cloud trial or use the HTTP webhook destination with a mockbin URL.
- **V4 feature flags:** every V4 feature is wrapped in a feature flag. Roll out per-tenant gradually. Default for new tenants is all OFF.
- **Full V4 phase list (8 phases):**
  12 Multi-Domain & Subdomain Layer → 13 External Laravel Connector → 14 Workflow Engine → 15 A/B Testing + Collab Editing → 16 AI RAG + Personalization → 17 SAML SSO + SCIM + Audit Streaming → 18 Form Analytics & Lead Scoring → 19 Polish & Final Testing

---

*End of V4 AI Build Prompts. Companion files: `04-FIELD-STRUCTURE-SPEC-V4.md`, `04-LARAVEL-INTEGRATION-KIT-V4.md`, `04-V3-TO-V4-MIGRATION-GUIDE.md`. Keep all V3 and V4 files in the project root for the AI coding agent to reference.*
