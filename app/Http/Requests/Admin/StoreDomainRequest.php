<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => 'required|string|max:255|unique:domains,domain',
            'is_primary' => 'boolean',
            'theme_id' => 'nullable|uuid|exists:themes,id',
            'site_id' => 'nullable|uuid|exists:sites,id',
            'default_collection_handle' => 'nullable|string|max:100',
            'route_prefix' => 'nullable|string|max:50',
            'config' => 'nullable|array',
            'status' => 'nullable|in:active,parked,redirect_only',
            'redirect_target' => 'nullable|string|max:255',
            'analytics_property_id' => 'nullable|string|max:100',
        ];
    }
}
