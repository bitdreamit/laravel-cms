<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Form extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'name', 'handle', 'description',
        'fields', 'email_recipients', 'honeypot_field',
        'success_message', 'error_message', 'redirect_url',
        'store_submissions', 'is_active',
    ];

    protected $casts = [
        'fields' => 'array',
        'email_recipients' => 'array',
        'store_submissions' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }
}
