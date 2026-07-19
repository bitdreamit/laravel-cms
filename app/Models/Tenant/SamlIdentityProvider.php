<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class SamlIdentityProvider extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'name', 'entity_id', 'metadata_xml',
        'sso_url', 'slo_url', 'x509_certificate',
        'attribute_mapping', 'role_mapping', 'is_active',
    ];

    protected $casts = [
        'attribute_mapping' => 'array',
        'role_mapping' => 'array',
        'is_active' => 'boolean',
    ];

    public function getAttributeMap(string $attribute, mixed $default = null): mixed
    {
        return data_get($this->attribute_mapping, $attribute, $default);
    }

    public function getRoleMapping(): array
    {
        return $this->role_mapping ?? [];
    }
}
