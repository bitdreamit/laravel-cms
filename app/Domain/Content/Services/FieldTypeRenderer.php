<?php

namespace App\Domain\Content\Services;

use App\Models\Tenant\Blueprint;
use App\Models\Tenant\BlueprintField;
use App\Models\Tenant\Entry;
use Illuminate\Support\Str;

class FieldTypeRenderer
{
    public function __construct(protected FieldTypeRegistry $registry) {}

    /**
     * Render all fields for an entry based on its blueprint.
     */
    public function renderEntryFields(Entry $entry): array
    {
        $blueprint = $entry->blueprint;
        if (! $blueprint) return [];

        $rendered = [];
        foreach ($blueprint->fields as $field) {
            $value = $entry->data[$field->handle] ?? null;
            $rendered[$field->handle] = $this->renderField($field, $value);
        }
        return $rendered;
    }

    /**
     * Render a single field.
     */
    public function renderField(BlueprintField $field, mixed $value): array
    {
        $fieldType = $this->registry->get($field->fieldtype);

        if (! $fieldType) {
            return [
                'handle' => $field->handle,
                'label' => $field->display_label,
                'value' => $value,
                'rendered' => (string) $value,
                'error' => "Unknown fieldtype: {$field->fieldtype}",
            ];
        }

        return [
            'handle' => $field->handle,
            'label' => $field->display_label,
            'fieldtype' => $field->fieldtype,
            'value' => $fieldType->cast($value, $field->config ?? []),
            'rendered' => $fieldType->render($value, $field->config ?? []),
            'config' => $field->config,
            'instructions' => $field->instructions,
            'is_listable' => $field->is_listable,
            'vue_props' => $fieldType->toVueComponentProps($value, $field->config ?? []),
        ];
    }

    /**
     * Validate entry data against its blueprint.
     */
    public function validateEntryData(Entry $entry): array
    {
        $blueprint = $entry->blueprint;
        if (! $blueprint) return [];

        $errors = [];
        foreach ($blueprint->fields as $field) {
            $value = $entry->data[$field->handle] ?? null;
            $fieldType = $this->registry->get($field->fieldtype);

            if ($fieldType) {
                $fieldErrors = $fieldType->validate($value, $field->config ?? []);
                if (! empty($fieldErrors)) {
                    $errors[$field->handle] = $fieldErrors;
                }
            }
        }
        return $errors;
    }

    /**
     * Cast all entry data according to blueprint field types.
     */
    public function castEntryData(Entry $entry): array
    {
        $blueprint = $entry->blueprint;
        if (! $blueprint) return $entry->data ?? [];

        $casted = [];
        foreach ($blueprint->fields as $field) {
            $value = $entry->data[$field->handle] ?? null;
            $fieldType = $this->registry->get($field->fieldtype);

            if ($fieldType) {
                $casted[$field->handle] = $fieldType->cast($value, $field->config ?? []);
            } else {
                $casted[$field->handle] = $value;
            }
        }
        return $casted;
    }
}
