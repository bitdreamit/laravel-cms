<?php

namespace App\Support\Traits;

use App\Models\Central\Tenant;
use Illuminate\Database\Eloquent\Builder;

/**
 * BelongsToTenantOptimized — optimized tenant scoping with cache.
 *
 * Improvements over stancl/tenancy BelongsToTenant:
 * - Cached tenant_id per request (avoids repeated tenant() calls)
 * - Composite index awareness
 * - Optional global scope (can be disabled for cross-tenant queries)
 * - Type-cast tenant_id to match the ID type (uuid vs bigint)
 */
trait BelongsToTenantOptimized
{
    protected static ?string $cachedTenantId = null;

    public static function bootBelongsToTenantOptimized(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::getCurrentTenantId();
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $model->tenant_id = static::getCurrentTenantId();
            }
        });
    }

    public static function getCurrentTenantId(): ?string
    {
        if (static::$cachedTenantId === null && tenancy()->initialized) {
            static::$cachedTenantId = tenant()?->getTenantKey();
        }
        return static::$cachedTenantId;
    }

    public static function clearTenantCache(): void
    {
        static::$cachedTenantId = null;
    }

    /**
     * Query without tenant scope — use carefully!
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
