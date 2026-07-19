<?php

namespace App\Domain\Audit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChainHasher
{
    /**
     * Compute the hash for a new activity log entry.
     */
    public function hash(string $id, ?string $previousHash, array $payload): string
    {
        return hash('sha256', $id . ($previousHash ?? '') . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Verify the integrity of the activity log chain for a tenant.
     *
     * @return array<int, array{activity_id: string, expected_hash: string, actual_hash: string}>
     */
    public function verifyChain(string $tenantId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = DB::table('activity_log')
            ->where('properties->tenant_id', $tenantId)
            ->orderBy('created_at')
            ->orderBy('id');

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        $activities = $query->get(['id', 'previous_hash', 'current_hash', 'properties']);
        $brokenLinks = [];
        $previousHash = null;

        foreach ($activities as $activity) {
            $expectedHash = $this->hash(
                $activity->id,
                $previousHash,
                json_decode($activity->properties, true) ?? [],
            );

            if ($activity->current_hash !== $expectedHash) {
                $brokenLinks[] = [
                    'activity_id' => $activity->id,
                    'expected_hash' => $expectedHash,
                    'actual_hash' => $activity->current_hash,
                ];
            }

            $previousHash = $activity->current_hash;
        }

        return $brokenLinks;
    }

    /**
     * Set the hash on a new activity log entry (called before insert).
     */
    public function setHashOnActivity(array &$activityData): void
    {
        $previousHash = DB::table('activity_log')
            ->where('properties->tenant_id', $activityData['properties']['tenant_id'] ?? null)
            ->orderByDesc('id')
            ->value('current_hash');

        $activityData['previous_hash'] = $previousHash;
        $activityData['current_hash'] = $this->hash(
            $activityData['id'] ?? Str::uuid()->toString(),
            $previousHash,
            $activityData['properties'] ?? [],
        );
    }
}
