<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'collection_id' => 'required|uuid',
            'blueprint_id' => 'nullable|uuid',
            'site_id' => 'nullable|uuid',
            'status' => 'in:draft,published,scheduled',
            'data' => 'nullable|array',
            'published_at' => 'nullable|date',
            'scheduled_at' => 'nullable|date|after:now',
            'template' => 'nullable|string|max:100',
            'parent_id' => 'nullable|uuid',
            'sort_order' => 'integer|min:0',
        ];
    }
}
