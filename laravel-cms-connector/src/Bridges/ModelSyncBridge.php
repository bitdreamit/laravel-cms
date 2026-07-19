<?php

namespace Platform\CmsConnector\Bridges;

use Illuminate\Support\Facades\Cache;
use Platform\CmsConnector\ConnectorManager;
use Platform\CmsConnector\Contracts\SyncableToCms;
use Platform\CmsConnector\Jobs\SyncModelToCmsJob;
use Platform\CmsConnector\Models\CmsConnectorSyncState;

class ModelSyncBridge
{
    public function __construct(protected ConnectorManager $manager) {}

    public function onModelEvent($model, string $event): void
    {
        if (! $model instanceof SyncableToCms) return;
        $debounceKey = "cms-sync-debounce:" . get_class($model) . ":" . $model->getKey();
        Cache::put($debounceKey, true, now()->addSeconds(config('cms-connector.model_sync.syncable_models.' . get_class($model) . '.debounce_seconds', 5)));
        SyncModelToCmsJob::dispatch(get_class($model), $model->getKey(), $event)->delay(now()->addSeconds(config('cms-connector.model_sync.syncable_models.' . get_class($model) . '.debounce_seconds', 5)))->onQueue(config('cms-connector.model_sync.queue'));
    }

    public function sync($model): void
    {
        if (! $model instanceof SyncableToCms) throw new \InvalidArgumentException('Model must implement SyncableToCms');
        $data = $model->toCmsEntryData();
        try {
            $response = $this->manager->collection($data['collection_handle']) && false ? [] : [];
            $response = app(\Platform\CmsConnector\Support\CmsClient::class)->put("/api/v1/collections/{$data['collection_handle']}/entries/{$data['slug']}", $data);
            CmsConnectorSyncState::updateOrCreate(['syncable_type' => get_class($model), 'syncable_id' => $model->getKey()], ['cms_entry_id' => $response['data']['id'] ?? null, 'cms_entry_slug' => $data['slug'], 'last_synced_at' => now(), 'last_sync_direction' => 'host_to_cms', 'last_sync_status' => 'success']);
        } catch (\Throwable $e) {
            CmsConnectorSyncState::updateOrCreate(['syncable_type' => get_class($model), 'syncable_id' => $model->getKey()], ['last_synced_at' => now(), 'last_sync_direction' => 'host_to_cms', 'last_sync_status' => 'failed']);
            throw $e;
        }
    }

    public function syncFromCms(array $entryData, string $modelClass): void
    {
        if (! is_subclass_of($modelClass, SyncableToCms::class)) return;
        $existing = CmsConnectorSyncState::where('cms_entry_slug', $entryData['slug'])->where('syncable_type', $modelClass)->first();
        if ($existing && $existing->last_synced_at && $existing->last_synced_at->gt(now()->subSeconds(10))) return;
        $model = $modelClass::fromCmsEntryData($entryData);
        CmsConnectorSyncState::updateOrCreate(['syncable_type' => $modelClass, 'syncable_id' => $model->getKey()], ['cms_entry_id' => $entryData['id'], 'cms_entry_slug' => $entryData['slug'], 'last_synced_at' => now(), 'last_sync_direction' => 'cms_to_host', 'last_sync_status' => 'success']);
    }
}
