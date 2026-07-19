<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class PlatformSetting extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'key', 'value', 'type', 'description', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
        'value' => 'json',
    ];
}
