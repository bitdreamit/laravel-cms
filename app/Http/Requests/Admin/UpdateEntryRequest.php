<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
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
