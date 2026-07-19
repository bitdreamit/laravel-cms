<?php

namespace Database\Factories;

use App\Models\Central\SslCertificate;
use App\Models\Central\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SslCertificateFactory extends Factory
{
    protected $model = SslCertificate::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'common_name' => $this->faker->domainName(),
            'san_domains' => [],
            'is_wildcard' => false,
            'provider' => 'letsencrypt',
            'certificate_pem' => '-----BEGIN CERTIFICATE-----\nmock\n-----END CERTIFICATE-----',
            'private_key_pem' => '-----BEGIN PRIVATE KEY-----\nmock\n-----END PRIVATE KEY-----',
            'issued_at' => now(),
            'expires_at' => now()->addDays(90),
            'auto_renew' => true,
            'challenge_type' => 'http-01',
            'status' => 'active',
        ];
    }
}
