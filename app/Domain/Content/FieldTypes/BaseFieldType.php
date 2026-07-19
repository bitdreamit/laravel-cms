<?php

namespace App\Domain\Content\FieldTypes;

use App\Domain\Content\Contracts\FieldTypeInterface;

abstract class BaseFieldType implements FieldTypeInterface
{
    public function render(mixed $value, array $config = []): string
    {
        return is_string($value) ? $value : (string) $value;
    }

    public function validate(mixed $value, array $config = []): array
    {
        $errors = [];
        $rules = $config['validation_rules'] ?? '';
        if (str_contains($rules, 'required') && empty($value)) {
            $errors[] = 'This field is required.';
        }
        $max = $config['character_limit'] ?? null;
        if ($max && is_string($value) && strlen($value) > $max) {
            $errors[] = "Maximum length is {$max} characters.";
        }
        return $errors;
    }

    public function cast(mixed $value, array $config = []): mixed
    {
        return $value;
    }

    public function toApiResource(mixed $value, array $config = []): mixed
    {
        return $value;
    }

    public function toVueComponentProps(mixed $value, array $config = []): array
    {
        return [
            'value' => $value,
            'config' => $config,
            'handle' => static::getHandle(),
            'component' => static::getVueComponent(),
        ];
    }

    public static function getDefaultConfig(): array
    {
        return [];
    }

    public static function getConfigValidationRules(): array
    {
        return [];
    }
}
