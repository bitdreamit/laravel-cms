<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class WebauthnCredential extends Model
{
    use CentralConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'credential_id', 'public_key', 'attestation_format',
        'counter', 'name', 'transports', 'aaguid',
    ];

    protected $hidden = ['public_key'];

    protected $casts = [
        'counter' => 'integer',
        'transports' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
