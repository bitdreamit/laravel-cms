<?php

namespace App\Domain\Dns\Services;

use App\Models\Central\AcmeAccount;
use App\Models\Central\Domain;
use App\Models\Central\SslCertificate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * ACME client — talks to Let's Encrypt (or any ACME-compatible CA) to obtain SSL certs.
 *
 * This is a simplified implementation that demonstrates the V4 architecture.
 * For production, you'd want to use acmephp/core's full implementation.
 */
class AcmeClient
{
    public function __construct(
        protected string $directoryUrl,
        protected ?string $apiKey = null,
    ) {}

    /**
     * Register a new ACME account for a tenant.
     */
    public function registerAccount(string $tenantId, string $email): AcmeAccount
    {
        // Generate RSA key pair for the account
        $keyPair = $this->generateKeyPair();

        return AcmeAccount::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'provider' => config('ssl.default_provider', 'letsencrypt'),
            'email' => $email,
            'account_key_pem' => $keyPair['private'],
            'account_url' => null, // will be set after ACME registration
            'status' => 'active',
        ]);
    }

    /**
     * Order a certificate for a domain.
     */
    public function orderCertificate(
        Domain $domain,
        AcmeAccount $account,
        string $challengeType = 'http-01',
    ): SslCertificate {
        $isWildcard = $domain->is_wildcard;
        $commonName = $domain->domain;
        $sanDomains = $isWildcard ? [$domain->wildcard_parent, $domain->domain] : [$domain->domain];

        // For wildcard domains, only dns-01 challenge is supported
        if ($isWildcard) {
            $challengeType = 'dns-01';
        }

        return SslCertificate::create([
            'id' => Str::uuid(),
            'tenant_id' => $domain->tenant_id,
            'common_name' => $commonName,
            'san_domains' => $sanDomains,
            'is_wildcard' => $isWildcard,
            'provider' => config('ssl.default_provider'),
            'certificate_pem' => '', // will be filled after finalization
            'private_key_pem' => $this->generateKeyPair()['private'],
            'chain_pem' => null,
            'issued_at' => null,
            'expires_at' => null,
            'auto_renew' => true,
            'acme_account_id' => $account->id,
            'challenge_type' => $challengeType,
            'status' => 'pending',
        ]);
    }

    /**
     * Fulfill HTTP-01 challenge (per-domain certs only).
     * The platform serves /.well-known/acme-challenge/{token} on the target domain.
     */
    public function fulfillHttpChallenge(SslCertificate $cert, string $token, string $keyAuthorization): void
    {
        // Store the challenge response — the platform serves it via a route handler
        cache()->put(
            "acme:http-challenge:{$token}",
            $keyAuthorization,
            now()->addMinutes(30)
        );
    }

    /**
     * Fulfill DNS-01 challenge (required for wildcard certs).
     * Publishes a TXT record at _acme-challenge.{domain} via the configured DNS provider.
     */
    public function fulfillDnsChallenge(SslCertificate $cert, string $keyAuthorization): void
    {
        $dnsProvider = $this->getDnsProvider($cert->tenant_id);
        $recordName = '_acme-challenge.' . $cert->common_name;
        $recordValue = hash('sha256', $keyAuthorization, true);
        $recordValue = base64_encode($recordValue);

        $dnsProvider->publishTxtRecord($recordName, $recordValue);
    }

    /**
     * Finalize the order — fetch the issued certificate from the ACME server.
     */
    public function finalizeOrder(SslCertificate $cert, string $certificatePem, string $chainPem): void
    {
        $cert->update([
            'certificate_pem' => $certificatePem,
            'chain_pem' => $chainPem,
            'issued_at' => now(),
            'expires_at' => now()->addDays(90), // Let's Encrypt certs are 90 days
            'status' => 'active',
        ]);
    }

    /**
     * Revoke a certificate.
     */
    public function revokeCertificate(SslCertificate $cert): void
    {
        $cert->update(['status' => 'revoked']);
    }

    /**
     * Generate an RSA key pair.
     */
    protected function generateKeyPair(int $bits = 2048): array
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);
        openssl_pkey_export($resource, $privatePem);
        $publicKey = openssl_pkey_get_details($resource)['key'];

        return [
            'private' => $privatePem,
            'public' => $publicKey,
        ];
    }

    /**
     * Get the DNS provider for a tenant.
     */
    protected function getDnsProvider(string $tenantId): DnsProviderInterface
    {
        $tenant = \App\Models\Central\Tenant::find($tenantId);
        $providerName = data_get($tenant?->data, 'dns_provider_config.provider', 'cloudflare');

        return match ($providerName) {
            'cloudflare' => app(\App\Domain\Dns\Providers\CloudflareProvider::class),
            'route53' => app(\App\Domain\Dns\Providers\Route53Provider::class),
            'digitalocean' => app(\App\Domain\Dns\Providers\DigitaloceanProvider::class),
            default => app(\App\Domain\Dns\Providers\CloudflareProvider::class),
        };
    }
}
