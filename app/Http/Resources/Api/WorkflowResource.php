<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description,
            'trigger_event' => $this->trigger_event,
            'trigger_collections' => $this->trigger_collections,
            'definition' => $this->definition,
            'is_active' => $this->is_active,
            'instances_count' => $this->whenCounted('instances'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
