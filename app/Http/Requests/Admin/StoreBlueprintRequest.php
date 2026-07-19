<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlueprintRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'type' => 'in:collection,global,taxonomy,navigation,form,user,theme_setting',
            'icon' => 'nullable|string|max:50',
            'tabs' => 'nullable|array',
        ];
    }
}
