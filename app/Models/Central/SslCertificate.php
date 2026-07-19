<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class SslCertificate extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'common_name', 'san_domains', 'is_wildcard', 'provider',
        'certificate_pem', 'private_key_pem', 'chain_pem',
        'issued_at', 'expires_at', 'auto_renew', 'last_renewal_attempt',
        'renewal_failure_count', 'acme_account_id', 'challenge_type', 'status',
    ];

    protected $hidden = ['private_key_pem', 'certificate_pem', 'chain_pem'];

    protected $casts = [
        'san_domains' => 'array',
        'is_wildcard' => 'boolean',
        'auto_renew' => 'boolean',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_renewal_attempt' => 'datetime',
        'renewal_failure_count' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function acmeAccount(): BelongsTo
    {
        return $this->belongsTo(AcmeAccount::class, 'acme_account_id');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'ssl_certificate_id');
    }

    public function shouldRenew(): bool
    {
        if (! $this->expires_at || ! $this->auto_renew) {
            return false;
        }
        return $this->expires_at->diffInDays(now()) <= (int) config('ssl.renewal_window_days', 30);
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expires_at) return false;
        return $this->expires_at->diffInDays(now()) <= $days;
    }
}
