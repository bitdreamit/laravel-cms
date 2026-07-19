<?php

namespace Platform\CmsConnector\Models;

use Illuminate\Database\Eloquent\Model;

class CmsConnectorEventLog extends Model
{
    protected $table = 'cms_connector_event_log';
    protected $fillable = ['event_id', 'event_type', 'payload', 'received_at', 'processed_at', 'processing_error'];
    protected $casts = ['received_at' => 'datetime', 'processed_at' => 'datetime', 'payload' => 'array'];
}
