<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class FormSubmission extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'form_id', 'visitor_id', 'user_id',
        'data', 'ip_address', 'user_agent', 'submitted_at',
        // V4 columns
        'lead_score', 'lead_score_breakdown', 'attribution',
        'conversion_path', 'is_qualified', 'assigned_to', 'assigned_at',
    ];

    protected $casts = [
        'data' => 'array',
        'lead_score_breakdown' => 'array',
        'attribution' => 'array',
        'conversion_path' => 'array',
        'submitted_at' => 'datetime',
        'assigned_at' => 'datetime',
        'lead_score' => 'integer',
        'is_qualified' => 'boolean',
    ];
}
