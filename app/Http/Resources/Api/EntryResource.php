<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'data' => $this->data,
            'template' => $this->template,
            'published_at' => $this->published_at?->toISOString(),
            'collection' => [
                'id' => $this->collection_id,
            ],
            'site_id' => $this->site_id,
            'author_id' => $this->author_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
