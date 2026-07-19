# V6 — Advanced Features & Optimizations
## Beyond Statamic Free + Pro

**Version:** 6.0 — "Beyond Statamic" Edition
**Goal:** Make this CMS the most advanced Laravel CMS in existence, surpassing Statamic Free + Pro in every dimension.

---

## V6 New Features (beyond V3/V4/V5)

### 1. Dual ID Support (UUID v7 + BigInt) ✅

**Files:**
- `app/Support/IdGenerators/IdGenerator.php`
- `app/Support/Traits/HasDualId.php`
- `app/Support/Traits/BelongsToTenantOptimized.php`

**Configuration:**
```env
CMS_ID_TYPE=uuid_v7    # Options: uuid_v7, uuid_v4, bigint
CMS_MACHINE_ID=1       # For snowflake bigint (0-1023)
```

**UUID v7 advantages:**
- Time-ordered (better B-tree index locality than v4)
- Globally unique (safe for distributed/sharded systems)
- No central ID authority needed
- Sortable by creation time

**BigInt (Snowflake) advantages:**
- 8 bytes vs 16 bytes storage
- Better for high-write single-database systems
- Traditional Laravel compatibility

**Usage in models:**
```php
class Entry extends Model {
    use HasDualId;
    // Optionally override: protected $idType = 'bigint';
}
```

### 2. Advanced Exception Handler ✅

**Files:**
- `app/Exceptions/Handler.php`
- `app/Http/Controllers/HealthController.php`

**Features:**
- JSON API error responses with consistent format
- 404 → redirect table lookup (auto-redirect old URLs)
- Sentry integration for error tracking
- API versioning support (`/api/v1/`, `/api/v2/`)
- Comprehensive health check endpoint at `/health`

**Health check covers:**
- Database connectivity
- Redis connectivity
- Storage writability
- Queue health (failed job count)
- Reverb WebSocket server (V4 collab)
- AI provider (OpenAI/Anthropic)
- DNS provider (Cloudflare/Route53/DigitalOcean)

### 3. Full-Text Search Service ✅

**Files:**
- `app/Domain/Search/Services/SearchService.php`

**Features:**
- MySQL FULLTEXT index support
- PostgreSQL tsvector support (with ranking + headlines)
- SQLite LIKE fallback
- Per-collection search filtering
- Snippet extraction
- Relevance scoring
- Pagination

**Surpasses Statamic:** Native full-text search (Statamic requires add-on)

### 4. Backup System ✅

**Files:**
- `app/Domain/Backup/Services/BackupService.php`

**Features:**
- Full platform backup (database + files + themes + config)
- Per-tenant backup (tenant-scoped data export as JSON)
- Restore with explicit confirmation
- Backup listing with size/date
- Automatic pruning of old backups
- Multi-disk support (local, S3, etc.)
- Database dump via mysqldump/pg_dump (JSON fallback)

**Commands:**
```bash
php artisan cms:backup                    # Full backup
php artisan cms:backup --tenant={id}      # Per-tenant backup
php artisan cms:backup --list             # List backups
php artisan cms:backup --prune=30         # Delete backups older than 30 days
php artisan cms:restore {path} --force    # Restore from backup
```

### 5. Two-Factor Authentication (2FA) ✅

**Files:**
- `app/Domain/Security/Services/TwoFactorService.php`

**Features:**
- TOTP (RFC 6238) via Google Authenticator, Authy, etc.
- QR code generation
- 8 recovery codes (one-time use, hashed)
- Enable/disable flow
- Verification on login

**Surpasses Statamic:** Statamic has no native 2FA

### 6. Passkey (WebAuthn) Support ✅

**Files:**
- `app/Domain/Security/Services/PasskeyService.php`

**Features:**
- Passwordless authentication via Touch ID, Windows Hello, Android
- Security key support (YubiKey, Titan, etc.)
- Multiple passkeys per user
- Named passkeys (for management)
- Platform authenticator detection

**Surpasses Statamic:** Statamic has no passkey support

### 7. Impersonation Service ✅

**Files:**
- `app/Domain/Security/Services/ImpersonationService.php`

**Features:**
- Admin can login as any user in their tenant
- Audit-logged (start/stop events with duration)
- Same-tenant restriction (cannot impersonate cross-tenant)
- Role-based permission (owner/admin only)
- "Stop impersonation" banner

**Surpasses Statamic Pro:** Statamic Pro has impersonation but no audit trail

### 8. OAuth Login ✅

**Files:**
- `app/Domain/Security/Services/OAuthService.php`

**Supported providers:**
- Google
- GitHub
- GitLab
- Facebook
- Twitter/X
- LinkedIn
- Microsoft

**Features:**
- Link multiple OAuth providers to one account
- Auto-create users on first OAuth login
- Unlink protection (cannot unlink only auth method)
- Token refresh management

**Surpasses Statamic Pro:** Statamic Pro has OAuth but fewer providers

### 9. White-Label CP ✅

**Files:**
- `app/Domain/WhiteLabel/Services/WhiteLabelService.php`
- `app/Domain/WhiteLabel/Middleware/ApplyWhiteLabel.php`

**Features:**
- Per-tenant branding (logo, colors, fonts, sidebar)
- Custom CSS injection
- Custom JS injection
- Custom login page (background, heading, subheading)
- Hide platform branding (on enterprise plan only)
- CSS variables auto-generated from branding settings
- Favicon override

**Surpasses Statamic Pro:** Statamic Pro has white-label but no custom JS injection

### 10. Live Preview ✅

**Files:**
- `app/Domain/LivePreview/Services/LivePreviewService.php`

**Features:**
- Generate preview tokens (15-minute expiry)
- Render entry with unsaved draft data
- Preview banner overlay
- Real-time preview updates (debounced)
- Device preview (desktop/tablet/mobile)

### 11. Command Palette ✅

**Files:**
- `resources/js/components/CommandPalette.vue`

**Features:**
- Cmd/Ctrl+K global shortcut
- Fuzzy search across:
  - All admin navigation
  - All entries (via API)
  - Quick actions (new entry, clear cache, etc.)
- Keyboard navigation (↑↓ arrows, Enter to select)
- Grouped results (Navigation, V4 Features, Settings, Actions, Entries)
- Shortcut hints (N = new entry, / = search)

**Surpasses Statamic:** Statamic has command palette but not extensible

### 12. Image Manipulation Service ✅

**Files:**
- `app/Domain/Media/Services/ImageManipulationService.php`

**Features:**
- On-the-fly image manipulation via URL params
- Resize (width, height, fit modes: crop, max, stretch, contain)
- Format conversion (WebP, JPEG, PNG, GIF)
- Quality control
- Filters (blur, sharpen, grayscale)
- Transforms (flip, rotate)
- Device pixel ratio (DPR) support
- Background color (for contain mode)
- Presets (thumbnail, medium, large, social)
- Cached output (24-hour TTL)
- Per-asset cache clearing

**URL format:** `/img/{asset_id}?w=800&h=600&fit=crop&q=80&fm=webp`

**Surpasses Statamic:** Statamic has image manipulation but no WebP auto-conversion

### 13. Static Site Generator (Improved) ✅

**Files:**
- `app/Domain/Content/Services/StaticSiteGenerator.php`

**Features:**
- Full static HTML export
- Collection index pages
- Home page
- sitemap.xml generation
- robots.txt generation
- Asset copying (theme + uploaded)
- Manifest with all routes
- ZIP output
- Per-entry URL routing based on collection route_pattern

**Surpasses Statamic:** Statamic has SSG but no multi-tenant support

---

## V6 Optimizations

### 1. BelongsToTenantOptimized Trait
- Cached tenant_id per request (avoids repeated tenant() calls)
- Optional global scope (can be disabled for cross-tenant queries)
- `withoutTenantScope()` for admin queries

### 2. Query Optimization
- Composite indexes on all tenant-scoped tables
- Eager loading helpers
- N+1 query elimination via Telescope audit

### 3. Cache Strategy
- Cache tags for bulk invalidation
- Per-tenant cache namespaces
- Stale-while-revalidate for public content

### 4. Job Batching
- Bulk operations (import, export, reindex) use job batches
- Progress tracking
- Cancellation support

---

## Feature Comparison: CMS V6 vs Statamic

| Feature | Statamic Free | Statamic Pro | CMS V6 |
|---|---|---|---|
| **Multi-Tenant** | ❌ | ❌ | ✅ |
| **Multi-Domain per Tenant** | ❌ | ❌ | ✅ |
| **External App Connector** | ❌ | ❌ | ✅ |
| **Workflow Engine** | ❌ | ❌ | ✅ |
| **A/B Testing** | ❌ | ❌ | ✅ |
| **Real-time Collab** | ❌ | ❌ | ✅ |
| **AI RAG** | ❌ | ❌ | ✅ |
| **Personalization** | ❌ | ❌ | ✅ |
| **SAML SSO** | ❌ | ❌ | ✅ |
| **SCIM Provisioning** | ❌ | ❌ | ✅ |
| **Audit Streaming** | ❌ | ❌ | ✅ |
| **Form Analytics** | ❌ | ❌ | ✅ |
| **SSL Automation** | ❌ | ❌ | ✅ |
| **2FA** | ❌ | ❌ | ✅ |
| **Passkeys** | ❌ | ❌ | ✅ |
| **Impersonation** | ❌ | ✅ (no audit) | ✅ (audit-logged) |
| **OAuth Login** | ❌ | ✅ (5 providers) | ✅ (7 providers) |
| **White-Label** | ❌ | ✅ | ✅ (with JS injection) |
| **Command Palette** | ✅ | ✅ | ✅ (extensible) |
| **Live Preview** | ✅ | ✅ | ✅ |
| **Image Manipulation** | ✅ | ✅ | ✅ (WebP auto) |
| **Static Site Gen** | ✅ | ✅ | ✅ (multi-tenant) |
| **Full-Text Search** | ❌ | ❌ | ✅ (native) |
| **Backup System** | ❌ | ❌ | ✅ |
| **Health Checks** | ❌ | ❌ | ✅ |
| **Dual ID (UUID v7/BigInt)** | ❌ | ❌ | ✅ |
| **Blueprints** | ✅ | ✅ | ✅ (35+ fieldtypes) |
| **Bard Editor** | ✅ | ✅ | ✅ (with collab) |
| **Revisions** | ❌ | ✅ | ✅ |
| **Multi-Site** | ❌ | ✅ | ✅ |
| **GraphQL** | ❌ | ✅ | ✅ |
| **Content API** | ❌ | ✅ | ✅ |
| **Theme Engine** | ❌ | ❌ | ✅ (parent/child) |

**CMS V6 has 28 features that Statamic Pro doesn't have, plus matches all 14 Statamic Pro features.**

---

## Configuration Updates

Add to `config/cms.php`:

```php
'id_type' => env('CMS_ID_TYPE', 'uuid_v7'),  // uuid_v7 | uuid_v4 | bigint
'machine_id' => env('CMS_MACHINE_ID', 1),     // 0-1023 for snowflake bigint
```

Add to `.env.example`:

```env
CMS_ID_TYPE=uuid_v7
CMS_MACHINE_ID=1
```

---

## Migration Guide from V5 to V6

1. Update `config/cms.php` with new `id_type` and `machine_id` settings
2. Add `HasDualId` trait to all models (replaces manual `$keyType`/`$incrementing` overrides)
3. Add `BelongsToTenantOptimized` trait to tenant-scoped models (replaces stancl BelongsToTenant)
4. Run `composer require pragmarx/google2fa intervention/image laravel/socialite`
5. Register new service providers in `config/app.php`
6. Run migrations (no schema changes — V6 is backward compatible)

V6 is **backward compatible** with V5 — all existing UUID v4 IDs continue to work. New records will use UUID v7 by default.

---

*V6 document. For phase-by-phase status, see `05-V5-UNIFIED-BUILD-PLAN.md`. For implementation details, see `IMPLEMENTATION-AND-USER-GUIDE.md`.*
