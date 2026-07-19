<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Contracts\TenantWithDatabase;

class Tenant extends Model implements TenantWithDatabase
{
    use CentralConnection, HasDomains;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'slug', 'plan_id', 'status', 'trial_ends_at',
        'data', 'current_theme_id',
    ];

    protected $casts = [
        'data' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class, 'plan_id');
    }

    public function currentTheme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'current_theme_id');
    }

    public function themes(): HasMany
    {
        return $this->hasMany(Theme::class, 'tenant_id');
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'tenant_id');
    }

    public function getDatabaseName(): string
    {
        return config('database.connections.mysql.database');
    }

    /**
     * Check if tenant has a specific feature flag enabled.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, data_get($this->data, 'features', []));
    }

    /**
     * Get a config value from tenant data.
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Run a callback within this tenant's context.
     */
    public function run(callable $callback): mixed
    {
        tenancy()->runForMultiple($this, $callback);
    }
}
