<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Theme extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'slug', 'version', 'description', 'author', 'author_url',
        'parent_id', 'type', 'screenshot_path', 'path', 'is_active',
        'settings_schema', 'supported_features', 'min_cms_version',
        'installed_count', 'is_featured', 'tags',
    ];

    protected $casts = [
        'settings_schema' => 'array',
        'supported_features' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'installed_count' => 'integer',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'current_theme_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Theme::class, 'parent_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(ThemeDependency::class, 'theme_id');
    }

    /**
     * Get the view cascade: child → parent → grandparent.
     */
    public function getViewCascade(): array
    {
        $cascade = [$this];
        $current = $this;

        while ($current->parent_id) {
            $parent = $current->parent;
            if (! $parent) break;
            $cascade[] = $parent;
            $current = $parent;
        }

        return $cascade;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->supported_features ?? []);
    }
}
