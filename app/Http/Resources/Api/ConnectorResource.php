<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ConnectorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'connector_type' => $this->connector_type,
            'base_url' => $this->base_url,
            'webhook_url' => $this->webhook_url,
            'subscribed_events' => $this->subscribed_events,
            'syncable_collections' => $this->syncable_collections,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
