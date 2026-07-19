<?php

namespace Platform\CmsConnector\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncModelToCmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $modelClass, public int $modelId, public string $event) {}

    public function handle(\Platform\CmsConnector\Bridges\ModelSyncBridge $bridge): void
    {
        $model = $this->modelClass::find($this->modelId);
        if (! $model) { if ($this->event === 'deleted') { /* delete from CMS */ } return; }
        $bridge->sync($model);
    }
}
