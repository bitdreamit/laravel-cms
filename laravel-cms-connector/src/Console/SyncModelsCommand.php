<?php

namespace Platform\CmsConnector\Console;

use Illuminate\Console\Command;

class SyncModelsCommand extends Command
{
    protected $signature = 'cms-connector:sync {model?} {--force}';
    protected $description = 'Sync models to CMS.';

    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $force = $this->option('force');
        $bridge = app(\Platform\CmsConnector\Bridges\ModelSyncBridge::class);
        $models = $modelClass ? [$modelClass] : array_keys(config('cms-connector.model_sync.syncable_models', []));

        foreach ($models as $class) {
            $this->info("Syncing {$class}...");
            $instances = $class::all();
            $count = 0;
            foreach ($instances as $instance) {
                try {
                    $bridge->sync($instance);
                    $count++;
                } catch (\Throwable $e) {
                    $this->error("  Failed: {$instance->getKey()} — {$e->getMessage()}");
                }
            }
            $this->info("  Synced {$count}/{$instances->count()} records.");
        }

        $this->info('Sync complete.');
        return self::SUCCESS;
    }
}
