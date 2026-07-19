<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class RagQuery extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'user_id', 'query_text', 'retrieved_document_ids',
        'answer_text', 'model_used', 'prompt_tokens', 'completion_tokens',
        'latency_ms', 'feedback_rating',
    ];

    protected $casts = [
        'retrieved_document_ids' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'latency_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Central\User::class, 'user_id');
    }
}
