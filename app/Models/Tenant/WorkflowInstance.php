<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WorkflowInstance extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'workflow_id', 'entry_id', 'current_node_id',
        'status', 'context', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public function nodeExecutions(): HasMany
    {
        return $this->hasMany(WorkflowNodeExecution::class, 'workflow_instance_id');
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'cancelled', 'failed']);
    }
}
