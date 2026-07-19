<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class OauthConnection extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'provider', 'provider_id', 'provider_email',
        'access_token', 'refresh_token', 'expires_at', 'data',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'expires_at' => 'datetime',
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
