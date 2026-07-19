<?php

namespace Platform\CmsConnector\Console;

use Illuminate\Console\Command;

class StatusCommand extends Command
{
    protected $signature = 'cms-connector:status';
    protected $description = 'Show CMS Connector status.';

    public function handle(): int
    {
        $this->info('CMS Connector Status');
        $this->info('====================');
        $this->line('CMS Base URL: ' . config('cms-connector.cms_base_url'));
        $this->line('Tenant ID: ' . config('cms-connector.tenant_id'));
        $this->line('');
        $this->line('Modes:');
        $this->line('  auth_bridge:  ' . (config('cms-connector.auth_bridge.enabled') ? '✓ enabled' : '✗ disabled'));
        $this->line('  model_sync:   ' . (config('cms-connector.model_sync.enabled') ? '✓ enabled' : '✗ disabled'));
        $this->line('  event_bus:    ' . (config('cms-connector.event_bus.enabled') ? '✓ enabled' : '✗ disabled'));
        $this->line('  embedded:     ' . (config('cms-connector.embedded.enabled') ? '✓ enabled' : '✗ disabled'));
        $this->line('  headless:     ' . (config('cms-connector.headless.enabled') ? '✓ enabled' : '✗ disabled'));
        $this->line('');

        $this->info('Testing connection...');
        try {
            $health = app(\Platform\CmsConnector\ConnectorManager::class)->health();
            $this->info('✓ CMS reachable');
            $this->info('✓ Connector ID: ' . ($health['connector_id'] ?? 'unknown'));
            $this->info('✓ All systems operational.');
        } catch (\Throwable $e) {
            $this->error('✗ CMS unreachable: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
