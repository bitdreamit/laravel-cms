<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class BillingPlan extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'slug', 'description', 'price_monthly', 'price_yearly',
        'currency', 'max_domains', 'max_admin_users', 'max_storage_mb',
        'max_themes', 'theme_marketplace_access', 'white_label_allowed',
        'custom_css_allowed', 'grace_period_days', 'billing_cycle',
        'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'max_domains' => 'integer',
        'max_admin_users' => 'integer',
        'max_storage_mb' => 'integer',
        'max_themes' => 'integer',
        'theme_marketplace_access' => 'boolean',
        'white_label_allowed' => 'boolean',
        'custom_css_allowed' => 'boolean',
        'grace_period_days' => 'integer',
        'sort_order' => 'integer',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }
}
