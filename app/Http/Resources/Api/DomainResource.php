<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'is_primary' => $this->is_primary,
            'is_wildcard' => $this->is_wildcard,
            'wildcard_parent' => $this->wildcard_parent,
            'ssl_status' => $this->ssl_status,
            'ssl_expires_at' => $this->ssl_expires_at?->toISOString(),
            'dns_verification_status' => $this->dns_verification_status,
            'dns_verified_at' => $this->dns_verified_at?->toISOString(),
            'theme_id' => $this->theme_id,
            'theme' => $this->whenLoaded('theme', fn() => [
                'id' => $this->theme->id,
                'name' => $this->theme->name,
                'slug' => $this->theme->slug,
            ]),
            'site_id' => $this->site_id,
            'default_collection_handle' => $this->default_collection_handle,
            'route_prefix' => $this->route_prefix,
            'config' => $this->config,
            'status' => $this->status,
            'redirect_target' => $this->redirect_target,
            'analytics_property_id' => $this->analytics_property_id,
            'last_request_at' => $this->last_request_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
