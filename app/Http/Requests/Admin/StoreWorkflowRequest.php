<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'description' => 'nullable|string',
            'trigger_event' => 'required|in:entry.created,entry.updated,entry.submitted_for_review,entry.published,manual',
            'trigger_collections' => 'array',
            'definition' => 'required|array',
            'definition.nodes' => 'required|array',
            'is_active' => 'boolean',
        ];
    }
}
