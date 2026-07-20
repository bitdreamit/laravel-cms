<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class User extends Authenticatable
{
	use CentralConnection, HasApiTokens, HasFactory, HasRoles, Notifiable;

	protected $keyType = 'string';
	public $incrementing = false;

	protected $fillable = [
		'id', 'name', 'email', 'password', 'avatar',
		'is_platform_super_admin', 'two_factor_secret', 'two_factor_recovery_codes',
		'two_factor_confirmed_at', 'last_login_at', 'locale', 'theme_preference',
		'is_active', 'tags',
	];

	protected $hidden = [
		'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
	];

	protected $casts = [
		'email_verified_at' => 'datetime',
		'last_login_at' => 'datetime',
		'two_factor_confirmed_at' => 'datetime',
		'is_platform_super_admin' => 'boolean',
		'two_factor_recovery_codes' => 'array',
		'tags' => 'array',
	];

	public function tenants(): BelongsToMany
	{
		return $this->belongsToMany(Tenant::class, 'tenant_users', 'user_id', 'tenant_id')
			->withPivot(['role', 'invited_by', 'joined_at'])
			->withTimestamps();
	}

	public function tenantUsers(): HasMany
	{
		return $this->hasMany(TenantUser::class, 'user_id');
	}
}