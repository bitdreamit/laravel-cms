<?php

namespace App\Domain\Content\FieldTypes;

class SelectFieldType extends BaseFieldType
{
    public function cast(mixed $value, array $config = []): mixed
    {
        $multiple = $config['multiple'] ?? false;
        if ($multiple) {
            return is_array($value) ? $value : (empty($value) ? [] : [$value]);
        }
        return is_array($value) ? ($value[0] ?? null) : $value;
    }

    public function toApiResource(mixed $value, array $config = []): mixed
    {
        return $this->cast($value, $config);
    }

    public static function getHandle(): string { return 'select'; }
    public static function getVueComponent(): string { return 'SelectField'; }

    public static function getDefaultConfig(): array
    {
        return [
            'options' => [],
            'multiple' => false,
            'taggable' => false,
            'searchable' => false,
            'clearable' => false,
        ];
    }
}
