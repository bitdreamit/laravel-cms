<?php

namespace App\Domain\Dns\Services;

use App\Models\Central\Domain;
use App\Models\Central\DnsVerificationJob;
use Illuminate\Support\Str;
use Spatie\Dns\Dns;

class DnsVerificationService
{
    public function __construct(
        protected Dns $dnsClient,
    ) {}

    /**
     * Generate a verification token and create a job for the domain.
     */
    public function createVerificationJob(Domain $domain): DnsVerificationJob
    {
        $token = Str::random(32);
        $recordName = $this->buildRecordName($domain->domain);

        return DnsVerificationJob::create([
            'id' => Str::uuid(),
            'domain_id' => $domain->id,
            'verification_type' => 'txt',
            'record_name' => $recordName,
            'record_value' => $token,
            'attempts' => 0,
            'max_attempts' => (int) config('ssl.dns_verification.max_attempts', 50),
            'next_attempt_at' => now(),
            'status' => 'pending',
        ]);
    }

    /**
     * Verify the DNS TXT record matches the expected token.
     */
    public function verify(DnsVerificationJob $job): bool
    {
        $records = $this->lookupTxtRecords($job->record_name);

        foreach ($records as $record) {
            $value = trim($record, '"');
            if (hash_equals($job->record_value, $value)) {
                $job->markVerified();
                $job->domain->update([
                    'dns_verification_status' => 'verified',
                    'dns_verified_at' => now(),
                ]);
                return true;
            }
        }

        $job->incrementAttempt();
        return false;
    }

    /**
     * Look up TXT records for a given name.
     *
     * @return string[]
     */
    public function lookupTxtRecords(string $name): array
    {
        try {
            $records = $this->dnsClient->txt($name)->get();
            $values = [];
            foreach ($records as $record) {
                if (preg_match('/"([^"]+)"/', $record, $matches)) {
                    $values[] = $matches[1];
                } elseif (preg_match('/\s([^\s]+)\s*$/', $record, $matches)) {
                    $values[] = trim($matches[1], '"');
                }
            }
            return $values;
        } catch (\Throwable $e) {
            \Log::warning('DNS lookup failed', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Build the DNS record name for verification.
     */
    public function buildRecordName(string $domain): string
    {
        $prefix = config('ssl.dns_verification.record_prefix', '_cms-verify');
        return "{$prefix}.{$domain}";
    }

    /**
     * Get the human-readable instructions for publishing the TXT record.
     */
    public function getInstructions(DnsVerificationJob $job): array
    {
        return [
            'type' => 'TXT',
            'name' => $job->record_name,
            'value' => $job->record_value,
            'ttl' => 300,
            'instructions' => "Add a TXT record at your DNS provider with name '{$job->record_name}' and value '{$job->record_value}'.",
        ];
    }
}
