<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Subscription extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'plan_id', 'status', 'started_at',
        'current_period_end', 'cancelled_at', 'gateway', 'gateway_subscription_id',
        'quantity', 'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'quantity' => 'integer',
        'metadata' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function plan()
    {
        return $this->belongsTo(BillingPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->cancelled_at !== null;
    }
}
