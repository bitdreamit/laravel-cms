<?php

namespace App\Domain\Content\Services;

use App\Models\Tenant\Blueprint;
use App\Models\Tenant\BlueprintField;
use Illuminate\Support\Str;

class BlueprintService
{
    public function createBlueprint(array $data): Blueprint
    {
        return Blueprint::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'handle' => $data['handle'],
            'title' => $data['title'],
            'type' => $data['type'] ?? 'collection',
            'icon' => $data['icon'] ?? null,
            'tabs' => $data['tabs'] ?? [],
            'created_by' => auth()->id(),
        ]);
    }

    public function addField(Blueprint $blueprint, array $fieldData): BlueprintField
    {
        return BlueprintField::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'blueprint_id' => $blueprint->id,
            'handle' => $fieldData['handle'],
            'display_label' => $fieldData['display_label'] ?? ucfirst($fieldData['handle']),
            'instructions' => $fieldData['instructions'] ?? null,
            'fieldtype' => $fieldData['fieldtype'],
            'config' => $fieldData['config'] ?? [],
            'validation_rules' => $fieldData['validation_rules'] ?? null,
            'is_localizable' => $fieldData['is_localizable'] ?? false,
            'is_listable' => $fieldData['is_listable'] ?? true,
            'is_sortable' => $fieldData['is_sortable'] ?? false,
            'conditional_logic' => $fieldData['conditional_logic'] ?? null,
            'sort_order' => $fieldData['sort_order'] ?? 0,
        ]);
    }

    public function validateDataAgainstBlueprint(Blueprint $blueprint, array $data): array
    {
        $errors = [];
        foreach ($blueprint->fields as $field) {
            $value = $data[$field->handle] ?? null;
            if ($field->validation_rules) {
                $rules = explode('|', $field->validation_rules);
                foreach ($rules as $rule) {
                    if ($rule === 'required' && empty($value)) {
                        $errors[$field->handle] = "{$field->display_label} is required.";
                    }
                }
            }
        }
        return $errors;
    }
}
