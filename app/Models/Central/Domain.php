<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * V4-enhanced Domain model — extends V3 with multi-domain config matrix.
 *
 * V3 columns retained: id, domain, tenant_id, is_primary, ssl_status
 * V4 columns added: is_wildcard, wildcard_parent, ssl_certificate_id, ssl_expires_at,
 *   dns_verification_status, dns_verification_token, dns_verified_at,
 *   theme_id, site_id, default_collection_handle, route_prefix, config,
 *   status, redirect_target, analytics_property_id, last_request_at
 */
class Domain extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'domain', 'tenant_id', 'is_primary', 'ssl_status',
        // V4
        'is_wildcard', 'wildcard_parent', 'ssl_certificate_id', 'ssl_expires_at',
        'dns_verification_status', 'dns_verification_token', 'dns_verified_at',
        'theme_id', 'site_id', 'default_collection_handle', 'route_prefix',
        'config', 'status', 'redirect_target', 'analytics_property_id',
        'last_request_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_wildcard' => 'boolean',
        'ssl_expires_at' => 'datetime',
        'dns_verified_at' => 'datetime',
        'config' => 'array',
        'last_request_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function sslCertificate(): BelongsTo
    {
        return $this->belongsTo(SslCertificate::class, 'ssl_certificate_id');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

    public function dnsVerificationJobs(): HasMany
    {
        return $this->hasMany(DnsVerificationJob::class, 'domain_id');
    }

    /**
     * Check if this domain matches a given hostname (handles wildcards).
     */
    public function matchesHost(string $host): bool
    {
        if (! $this->is_wildcard) {
            return $this->domain === $host;
        }
        // Wildcard match: *.example.com matches foo.example.com but not example.com
        $pattern = '/^' . str_replace('\*', '[^.]+', preg_quote($this->domain, '/')) . '$/';
        return (bool) preg_match($pattern, $host);
    }

    /**
     * Extract the wildcard segment from a host (e.g. "shop" from "shop.example.com" for "*.example.com").
     */
    public function extractWildcardSegment(string $host): ?string
    {
        if (! $this->is_wildcard) return null;
        $suffix = substr($this->domain, 1); // remove leading "*"
        if (! str_ends_with($host, $suffix)) return null;
        return substr($host, 0, strlen($host) - strlen($suffix));
    }

    public function isVerified(): bool
    {
        return $this->dns_verification_status === 'verified';
    }

    public function isSslActive(): bool
    {
        return $this->ssl_status === 'active'
            && $this->sslCertificate
            && ! $this->sslCertificate->isExpired();
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }
}
