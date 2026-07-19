<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'description' => 'nullable|string',
            'route_pattern' => 'nullable|string|max:255',
            'template' => 'nullable|string|max:100',
            'structure_mode' => 'in:flat,tree',
            'max_depth' => 'integer|min:1|max:10',
            'default_status' => 'in:draft,published',
            'seo_settings' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'is_searchable' => 'boolean',
        ];
    }
}
