<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Entry extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'collection_id', 'blueprint_id', 'site_id',
        'title', 'slug', 'status', 'data', 'published_at', 'scheduled_at',
        'author_id', 'template', 'parent_id', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class, 'blueprint_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Entry::class, 'parent_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(EntryRevision::class, 'entry_id');
    }

    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(Term::class, 'entry_terms', 'entry_id', 'term_id')
            ->using(EntryTerm::class)
            ->withPivot(['tenant_id']);
    }

    public function ragDocuments(): HasMany
    {
        return $this->hasMany(RagDocument::class, 'entry_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }
}
