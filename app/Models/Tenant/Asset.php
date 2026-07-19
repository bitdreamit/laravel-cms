<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Asset extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'container_id', 'folder', 'filename', 'path',
        'mime_type', 'size', 'width', 'height', 'alt_text', 'title',
        'focal_point', 'meta_data', 'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'focal_point' => 'array',
        'meta_data' => 'array',
    ];

    public function container()
    {
        return $this->belongsTo(AssetContainer::class, 'container_id');
    }

    public function getUrlAttribute(): string
    {
        return '/assets/' . ltrim($this->path, '/');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}
