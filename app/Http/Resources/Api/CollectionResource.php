<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description,
            'route_pattern' => $this->route_pattern,
            'template' => $this->template,
            'structure_mode' => $this->structure_mode,
            'max_depth' => $this->max_depth,
            'default_status' => $this->default_status,
            'seo_settings' => $this->seo_settings,
            'is_searchable' => $this->is_searchable,
            'entries_count' => $this->whenCounted('entries'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
