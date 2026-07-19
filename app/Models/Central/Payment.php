<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Payment extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'invoice_id', 'tenant_id', 'amount', 'currency', 'gateway',
        'gateway_transaction_id', 'status', 'raw_response', 'processed_at',
    ];

    protected $hidden = ['raw_response'];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'succeeded' || $this->status === 'completed';
    }
}
