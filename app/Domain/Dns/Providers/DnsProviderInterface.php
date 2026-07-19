<?php

namespace App\Domain\Dns\Providers;

interface DnsProviderInterface
{
    /**
     * Publish a TXT record.
     */
    public function publishTxtRecord(string $name, string $value, int $ttl = 300): void;

    /**
     * Delete a TXT record.
     */
    public function deleteTxtRecord(string $name, string $value): void;

    /**
     * List TXT records matching a name pattern.
     *
     * @return array<array{name: string, value: string, ttl: int}>
     */
    public function listTxtRecords(string $name): array;

    /**
     * Check if the provider is properly configured.
     */
    public function isConfigured(): bool;
}
