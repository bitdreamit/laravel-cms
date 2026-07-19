<?php

namespace Platform\CmsConnector\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'cms-connector:install';
    protected $description = 'Install and configure the CMS Connector package.';

    public function handle(): int
    {
        $this->info('CMS Connector Installer');
        $this->info('=======================');

        if (! $this->confirm('This will publish config and run migrations. Continue?', true)) return self::FAILURE;

        $this->call('vendor:publish', ['--tag' => 'cms-connector-config', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'cms-connector-migrations', '--force' => true]);
        $this->call('migrate');

        $baseUrl = $this->ask('CMS Base URL', 'https://cms.example.com');
        $tenantId = $this->ask('Tenant ID');
        $apiToken = $this->secret('API Token (from CMS admin → Connectors → Create)');
        $sharedSecret = $this->secret('Shared Secret (HMAC)');
        $ssoSecret = $this->secret('SSO Bridge Secret (different from shared secret)');

        $this->updateEnv('CMS_BASE_URL', $baseUrl);
        $this->updateEnv('CMS_TENANT_ID', $tenantId);
        $this->updateEnv('CMS_API_TOKEN', $apiToken);
        $this->updateEnv('CMS_SHARED_SECRET', $sharedSecret);
        $this->updateEnv('CMS_AUTH_BRIDGE_SECRET', $ssoSecret);

        $authBridge = $this->confirm('Enable Auth Bridge (SSO)?', false);
        $modelSync = $this->confirm('Enable Model Sync?', false);
        $eventBus = $this->confirm('Enable Event Bus?', false);
        $headless = $this->confirm('Enable Headless API Client?', true);

        $this->updateEnv('CMS_AUTH_BRIDGE_ENABLED', $authBridge ? 'true' : 'false');
        $this->updateEnv('CMS_MODEL_SYNC_ENABLED', $modelSync ? 'true' : 'false');
        $this->updateEnv('CMS_EVENT_BUS_ENABLED', $eventBus ? 'true' : 'false');
        $this->updateEnv('CMS_HEADLESS_ENABLED', $headless ? 'true' : 'false');

        $this->info('Testing connection...');
        try {
            $health = app(\Platform\CmsConnector\ConnectorManager::class)->health();
            $this->info('✓ CMS reachable at ' . $baseUrl);
            $this->info('✓ Connector registered as ID: ' . ($health['connector_id'] ?? 'unknown'));
            $this->info('✓ Installation complete!');
        } catch (\Throwable $e) {
            $this->error('✗ Connection failed: ' . $e->getMessage());
            $this->warn('Check your .env settings and try again.');
        }

        return self::SUCCESS;
    }

    protected function updateEnv(string $key, string $value): void
    {
        $path = base_path('.env');
        if (! file_exists($path)) return;
        $content = file_get_contents($path);
        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }
        file_put_contents($path, $content);
    }
}
