# Adding FieldTypes

## Overview

The CMS V4 FieldType engine is polymorphic — adding a new fieldtype requires only:
1. One PHP class implementing `FieldTypeInterface`
2. One Vue component for the admin UI
3. Registration in `FieldTypeRegistry`

## Step 1: Create the FieldType Class

```php
<?php

namespace App\Domain\Content\FieldTypes;

class MyCustomFieldType extends BaseFieldType
{
    public function cast(mixed $value, array $config = []): mixed
    {
        // Convert the stored value to its proper PHP type
        return is_string($value) ? trim($value) : $value;
    }

    public function validate(mixed $value, array $config = []): array
    {
        $errors = [];
        // Add custom validation logic
        if (empty($value) && str_contains($config['validation_rules'] ?? '', 'required')) {
            $errors[] = 'This field is required.';
        }
        return $errors;
    }

    public function render(mixed $value, array $config = []): string
    {
        // Return the value formatted for display
        return (string) $value;
    }

    public static function getHandle(): string { return 'my_custom'; }
    public static function getVueComponent(): string { return 'MyCustomField'; }

    public static function getDefaultConfig(): array
    {
        return ['placeholder' => '', 'max_length' => 255];
    }
}
```

## Step 2: Register the FieldType

In `app/Domain/Content/Services/FieldTypeRegistry.php`, add to the `registerDefaults()` method:

```php
$this->register('my_custom', \App\Domain\Content\FieldTypes\MyCustomFieldType::class);
```

## Step 3: Create the Vue Component

Create `resources/js/components/field-types/MyCustomField.vue`:

```vue
<template>
  <div class="field-my-custom">
    <label v-if="label" class="field-label">{{ label }}</label>
    <input
      :value="modelValue"
      :placeholder="config.placeholder || ''"
      :maxlength="config.max_length || null"
      @input="$emit('update:modelValue', $event.target.value)"
      class="text-input"
    />
    <p v-if="config.instructions" class="field-instructions">{{ config.instructions }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  config: { type: Object, default: () => ({}) },
});
defineEmits(['update:modelValue']);
</script>

<style scoped>
.field-label { display: block; font-weight: 600; margin-bottom: 0.25rem; }
.text-input { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; }
</style>
```

## Step 4: Add to Blueprint Builder Palette

In `resources/js/pages/admin/blueprints/Builder.vue`, add to the `fieldTypes` array:

```javascript
{ handle: 'my_custom', label: 'My Custom', icon: '★' },
```

## Step 5: Write Tests

```php
it('validates my_custom fieldtype', function () {
    $fieldType = app(\App\Domain\Content\Services\FieldTypeRegistry::class)->get('my_custom');
    
    $errors = $fieldType->validate('', ['validation_rules' => 'required']);
    expect($errors)->not->toBeEmpty();
    
    $errors = $fieldType->validate('value', ['validation_rules' => 'required']);
    expect($errors)->toBeEmpty();
});

it('casts my_custom fieldtype value', function () {
    $fieldType = app(\App\Domain\Content\Services\FieldTypeRegistry::class)->get('my_custom');
    expect($fieldType->cast('  hello  '))->toBe('hello');
});
```

## FieldType Interface Methods

| Method | Purpose |
|---|---|
| `render($value, $config)` | Return the value formatted for public display |
| `validate($value, $config)` | Return array of validation error strings (empty = valid) |
| `cast($value, $config)` | Convert stored value to proper PHP type |
| `toApiResource($value, $config)` | Transform value for API JSON output |
| `toVueComponentProps($value, $config)` | Return props array for the Vue component |
| `getHandle()` | Return the machine name (e.g. 'my_custom') |
| `getVueComponent()` | Return the Vue component name (e.g. 'MyCustomField') |
| `getDefaultConfig()` | Return default config array |
| `getConfigValidationRules()` | Return validation rules for the config itself |

## Available Config Types

In `settings_schema`, the following control types are supported in the Live Customizer:

| Type | UI Control |
|---|---|
| `color` | Color picker with opacity |
| `font_picker` | Searchable font dropdown |
| `range` | Slider with value display |
| `select` | Dropdown |
| `toggle` | Switch |
| `image` | Asset picker modal |
| `text` | Text input |
| `code` | Code editor (Monaco) |
| `background` | Composite picker (color/image/gradient) |

## Tips

- Extend `BaseFieldType` to get sensible defaults for all interface methods
- Override only the methods you need to customize
- Use the `FieldTypeRenderer` service to render all fields for an entry dynamically
- All fieldtypes automatically support conditional logic (show/hide based on other field values)
