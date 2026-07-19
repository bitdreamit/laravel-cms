<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class BlueprintResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'handle' => $this->handle,
            'title' => $this->title,
            'type' => $this->type,
            'icon' => $this->icon,
            'tabs' => $this->tabs,
            'fields' => $this->whenLoaded('fields', fn() => $this->fields->map(fn($f) => [
                'id' => $f->id,
                'handle' => $f->handle,
                'display_label' => $f->display_label,
                'fieldtype' => $f->fieldtype,
                'config' => $f->config,
                'validation_rules' => $f->validation_rules,
                'is_localizable' => $f->is_localizable,
                'is_listable' => $f->is_listable,
                'is_sortable' => $f->is_sortable,
                'sort_order' => $f->sort_order,
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
