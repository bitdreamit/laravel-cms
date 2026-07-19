<?php

namespace Platform\CmsConnector\Models;

use Illuminate\Database\Eloquent\Model;

class CmsConnectorSyncState extends Model
{
    protected $table = 'cms_connector_sync_state';
    protected $fillable = ['syncable_type', 'syncable_id', 'cms_entry_id', 'cms_entry_slug', 'last_synced_at', 'last_sync_direction', 'last_sync_status', 'conflict_data'];
    protected $casts = ['last_synced_at' => 'datetime', 'conflict_data' => 'array'];
}
