<?php

namespace App\Console\Commands;

use App\Domain\Audit\Services\ChainHasher;
use App\Models\Central\Tenant;
use Illuminate\Console\Command;

class VerifyAuditChain extends Command
{
    protected $signature = 'audit:verify-chain {--tenant= : Tenant ID (default: all tenants)}';
    protected $description = 'Verify the integrity of the activity log chain.';

    public function handle(ChainHasher $hasher): int
    {
        $tenantId = $this->option('tenant');
        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        $totalBroken = 0;

        foreach ($tenants as $tenant) {
            $this->info("Verifying chain for tenant: {$tenant->name}");
            $broken = $hasher->verifyChain($tenant->id);

            if (empty($broken)) {
                $this->line("  ✓ Chain intact");
            } else {
                $this->error("  ✗ Found " . count($broken) . " broken link(s)");
                foreach (array_slice($broken, 0, 5) as $link) {
                    $this->line("    - Activity {$link['activity_id']}");
                }
                $totalBroken += count($broken);
            }
        }

        return $totalBroken === 0 ? self::SUCCESS : self::FAILURE;
    }
}
