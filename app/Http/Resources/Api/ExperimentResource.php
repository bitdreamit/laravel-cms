<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ExperimentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description,
            'experiment_type' => $this->experiment_type,
            'status' => $this->status,
            'traffic_allocation' => $this->traffic_allocation,
            'goal_type' => $this->goal_type,
            'goal_config' => $this->goal_config,
            'min_sample_size' => $this->min_sample_size,
            'confidence_threshold' => $this->confidence_threshold,
            'start_at' => $this->start_at?->toISOString(),
            'end_at' => $this->end_at?->toISOString(),
            'winning_variant_id' => $this->winning_variant_id,
            'variants' => $this->whenLoaded('variants'),
            'assignments_count' => $this->whenCounted('assignments'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
