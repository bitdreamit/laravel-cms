<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollectionRequest extends StoreCollectionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        foreach ($rules as $field => $rule) {
            if (str_starts_with($rule, 'required')) {
                $rules[$field] = 'sometimes|' . $rule;
            }
        }
        return $rules;
    }
}
