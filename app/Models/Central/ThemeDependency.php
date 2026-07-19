<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ThemeDependency extends Model
{
    use CentralConnection;

    protected $table = 'theme_dependencies';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'theme_id', 'dependency_theme_id', 'version_constraint', 'type',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

    public function dependencyTheme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'dependency_theme_id');
    }
}
