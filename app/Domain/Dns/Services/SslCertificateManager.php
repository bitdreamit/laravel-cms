<?php

namespace App\Domain\Dns\Services;

use App\Domain\Dns\Services\AcmeClient;
use App\Models\Central\AcmeAccount;
use App\Models\Central\Domain;
use App\Models\Central\SslCertificate;
use Illuminate\Support\Facades\Log;

class SslCertificateManager
{
    public function __construct(
        protected AcmeClient $acmeClient,
        protected DnsVerificationService $dnsVerification,
    ) {}

    /**
     * Issue an SSL certificate for a domain.
     * Orchestrates: DNS verification → ACME order → challenge fulfillment → finalization.
     */
    public function issueForDomain(Domain $domain): SslCertificate
    {
        // 1. Ensure DNS verification is complete
        if (! $domain->isVerified()) {
            throw new \App\Domain\Dns\Exceptions\DnsNotVerifiedException(
                "Domain {$domain->domain} is not DNS-verified."
            );
        }

        // 2. Get or create ACME account for the tenant
        $account = $this->getOrCreateAcmeAccount($domain);

        // 3. Order certificate
        $challengeType = $domain->is_wildcard ? 'dns-01' : 'http-01';
        $cert = $this->acmeClient->orderCertificate($domain, $account, $challengeType);

        // 4. Fulfill challenge (in production, this would actually wait for ACME server to verify)
        if ($challengeType === 'http-01') {
            // HTTP challenge - token will be provided by ACME server
            $this->acmeClient->fulfillHttpChallenge($cert, 'mock-token', 'mock-key-auth');
        } else {
            $this->acmeClient->fulfillDnsChallenge($cert, 'mock-key-auth');
        }

        // 5. Finalize (in production, this would fetch the real cert from the ACME server)
        // For now, generate a self-signed cert as placeholder
        [$certPem, $chainPem] = $this->generateSelfSigned($domain);
        $this->acmeClient->finalizeOrder($cert, $certPem, $chainPem);

        // 6. Update domain
        $domain->update([
            'ssl_status' => 'active',
            'ssl_certificate_id' => $cert->id,
            'ssl_expires_at' => $cert->expires_at,
        ]);

        // 7. Fire event
        event(new \App\Domain\Dns\Events\SslCertificateIssued($cert));

        return $cert;
    }

    /**
     * Renew a certificate that's approaching expiry.
     */
    public function renew(SslCertificate $cert): SslCertificate
    {
        if ($cert->renewal_failure_count >= (int) config('ssl.max_renewal_failures', 5)) {
            Log::error('SSL renewal max failures reached', [
                'cert_id' => $cert->id,
                'failures' => $cert->renewal_failure_count,
            ]);
            throw new \App\Domain\Dns\Exceptions\MaxRenewalFailuresException($cert);
        }

        try {
            $cert->update(['last_renewal_attempt' => now()]);

            // In production: actually call ACME to re-issue
            [$certPem, $chainPem] = $this->generateSelfSigned($cert->domains()->first());
            $this->acmeClient->finalizeOrder($cert, $certPem, $chainPem);

            $cert->update([
                'renewal_failure_count' => 0,
                'status' => 'active',
            ]);

            event(new \App\Domain\Dns\Events\SslCertificateRenewed($cert));

            return $cert;
        } catch (\Throwable $e) {
            $cert->increment('renewal_failure_count');
            $cert->update(['status' => 'failed']);

            event(new \App\Domain\Dns\Events\SslCertificateFailed($cert, $e->getMessage()));

            throw $e;
        }
    }

    /**
     * Check if a certificate should be renewed.
     */
    public function shouldRenew(SslCertificate $cert): bool
    {
        return $cert->shouldRenew();
    }

    /**
     * Mark a certificate as failed.
     */
    public function markFailed(SslCertificate $cert, string $reason): void
    {
        $cert->update([
            'status' => 'failed',
            'renewal_failure_count' => $cert->renewal_failure_count + 1,
        ]);

        event(new \App\Domain\Dns\Events\SslCertificateFailed($cert, $reason));
    }

    /**
     * Get or create an ACME account for the tenant.
     */
    protected function getOrCreateAcmeAccount(Domain $domain): AcmeAccount
    {
        $account = AcmeAccount::where('tenant_id', $domain->tenant_id)
            ->where('provider', config('ssl.default_provider'))
            ->first();

        if ($account) {
            return $account;
        }

        $tenant = $domain->tenant;
        $email = data_get($tenant?->data, 'billing_email', "admin@{$domain->domain}");

        return $this->acmeClient->registerAccount($domain->tenant_id, $email);
    }

    /**
     * Generate a self-signed certificate as a placeholder.
     * In production, this would be replaced by the actual ACME-issued cert.
     */
    protected function generateSelfSigned(?Domain $domain): array
    {
        $dn = [
            'countryName' => 'US',
            'stateOrProvinceName' => 'State',
            'localityName' => 'City',
            'organizationName' => 'Self-Signed',
            'organizationalUnitName' => 'CMS Platform',
            'commonName' => $domain?->domain ?? 'localhost',
            'emailAddress' => 'admin@example.com',
        ];

        $privkey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new($dn, $privkey);
        $x509 = openssl_csr_sign($csr, null, $privkey, 90);

        openssl_x509_export($x509, $certPem);
        openssl_pkey_export($privkey, $keyPem);

        return [$certPem, $certPem]; // chain = cert for self-signed
    }
}
