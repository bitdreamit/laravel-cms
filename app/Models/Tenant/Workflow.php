<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Workflow extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'name', 'handle', 'description',
        'trigger_event', 'trigger_collections', 'definition',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'trigger_collections' => 'array',
        'definition' => 'array',
        'is_active' => 'boolean',
    ];

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'workflow_id');
    }

    public function appliesToCollection(string $handle): bool
    {
        $collections = $this->trigger_collections ?? [];
        return in_array($handle, $collections) || in_array('*', $collections);
    }
}
