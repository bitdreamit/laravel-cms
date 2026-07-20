<?php

namespace App\Models\Central;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant
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

	/**
	 * Define which attributes map to actual database columns
	 * instead of being nested inside the 'data' JSON field.
	 */
	public static function getCustomColumns(): array
	{
		return [
			'id',
			'name',
			'slug',
			'plan_id',
			'status',
			'trial_ends_at',
			'current_theme_id',
		];
	}

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
}