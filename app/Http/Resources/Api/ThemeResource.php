<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ThemeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'parent_id' => $this->parent_id,
            'screenshot_path' => $this->screenshot_path,
            'supported_features' => $this->supported_features,
            'tags' => $this->tags,
            'settings_schema' => $this->settings_schema,
            'installed_count' => $this->installed_count,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
