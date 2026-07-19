# V4 Multi-Domain Guide

## Overview

V4's Multi-Domain & Subdomain Connectivity Layer (spec section 17) lets a single tenant own multiple domains and subdomains, each with its own:

- Theme override (per-domain theme)
- Locale/Site binding (per-domain default locale)
- Collection routing (subdomain → collection)
- SSL certificate (auto-provisioned via Let's Encrypt)
- DNS ownership verification
- Custom HTTP headers, robots.txt, favicon, OG image
- Status (active / parked / redirect-only)

## Configuring a Domain

### 1. Add a domain

In the admin panel at `/admin/domains`, click "Add Domain":

- **Domain**: `shop.advmedi.test` (or `*.multilingual.test` for wildcards)
- **Tenant**: select from dropdown
- **Options**: verify DNS now, request SSL now, set as primary

The platform generates a DNS verification token and shows instructions:

```
Type: TXT
Name: _cms-verify.shop.advmedi.test
Value: abc123def456...
TTL: 300
```

### 2. Publish the TXT record at your DNS provider

Once published, the platform polls every 5 minutes (up to 50 attempts) to verify.

### 3. SSL is auto-issued

Once DNS is verified:
- For non-wildcard domains: HTTP-01 challenge via Let's Encrypt (no DNS provider config needed)
- For wildcard domains: DNS-01 challenge via Cloudflare/Route53/DigitalOcean API

The cert is stored in `ssl_certificates`, and the domain's `ssl_status` is updated to `active`.

### 4. Configure per-domain overrides

Open the domain detail page at `/admin/domains/{id}`:

- **SSL tab**: cert info, expiry, manual renewal button
- **DNS tab**: verification status, retry button
- **Theme tab**: pick from tenant's installed themes, or "use tenant default"
- **Locale tab**: pick from tenant's sites, or "use tenant default"
- **Routing tab**: set `default_collection_handle` and `route_prefix`
- **Config tab**: JSON editor for custom headers, robots.txt, etc.

## Subdomain-to-Collection Routing

When a domain has `default_collection_handle` set (e.g. `shop.advmedi.test` → `products`):

- `/` renders the `products` collection's index template
- `/{slug}` renders the matching product entry
- `/category/{term}` renders entries tagged with that term

All admin routes (`/admin/*`) and API routes (`/api/*`) still work normally on the same domain.

## Wildcard Subdomains

Wildcard domains follow this pattern:

1. Add domain `*.multilingual.test`
2. The platform extracts the wildcard segment when a request comes in
3. Visit `paris.multilingual.test` → resolves to Multilingual Co. tenant, wildcard segment = "paris"
4. Routes can use `{wildcard}` parameter or `wildcard_segment()` helper

```php
Route::get('/{wildcard}', function ($wildcard) {
    // $wildcard = "paris"
    return view('city-landing', ['city' => $wildcard]);
});
```

## Per-Domain Custom Configuration

The `domain.config` JSON column supports:

```json
{
  "force_https": true,
  "redirect_www": "non-www",
  "custom_headers": {
    "X-Frame-Options": "DENY",
    "Content-Security-Policy": "default-src 'self'"
  },
  "robots_txt_override": "User-agent: *\nDisallow: /private/",
  "favicon_override": "/assets/tenant-2/favicon.ico",
  "og_image_override": "/assets/tenant-2/social-share.png"
}
```

## SSL Automation

### Staging vs Production

For local dev / testing, use Let's Encrypt staging to avoid rate limits:

```
SSL_ENV=staging
```

Switch to production when ready:

```
SSL_ENV=production
```

### DNS Provider Configuration

For wildcard certs (DNS-01 challenge), configure one of:

```env
CLOUDFLARE_API_TOKEN=your-token
# OR
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
# OR
DIGITALOCEAN_API_TOKEN=your-token
```

The tenant's `data.dns_provider_config.provider` field selects which provider to use for that tenant.

### Manual Renewal

The `ssl:renew` Artisan command runs daily via cron and renews certs expiring within 30 days. To manually renew a specific cert:

```bash
php artisan ssl:renew --domain=shop.advmedi.test
```

### Web Server Reload

After cert issuance/renewal, the platform reloads your web server:

```env
SSL_RELOAD_CMD="sudo systemctl reload nginx"
```

Configure sudoers to allow the PHP user to run this command without password prompt:

```
www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
```

## Troubleshooting

### DNS verification stuck at "pending"

- Check the TXT record was published correctly at your DNS provider
- Use `dig TXT _cms-verify.shop.advmedi.test` to verify the record is publicly resolvable
- The platform polls every 5 minutes — wait up to 25 minutes (5 attempts × 5 min)
- If still stuck after 50 attempts, click "Retry" in the admin UI

### SSL issuance fails

- For HTTP-01: verify the domain's A record points at your server
- For DNS-01: verify your DNS provider API credentials are correct
- Check `storage/logs/laravel.log` for ACME error details
- Try staging mode first (`SSL_ENV=staging`) to debug without rate limits

### Per-domain theme override not applying

- Verify `domain.theme_id` is set (not null)
- Verify the theme is installed and active
- Clear cache: `php artisan cache:clear`
- Check that the `ResolveTheme` middleware runs after `InitializeTenancyByDomain`
