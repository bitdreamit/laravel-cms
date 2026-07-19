<?php

namespace App\Domain\Content\Contracts;

interface FieldTypeInterface
{
    /**
     * Render the field for display (read-only value formatting).
     */
    public function render(mixed $value, array $config = []): string;

    /**
     * Validate the field value against config rules.
     */
    public function validate(mixed $value, array $config = []): array;

    /**
     * Cast the field value to its proper PHP type.
     */
    public function cast(mixed $value, array $config = []): mixed;

    /**
     * Transform the field value for API resource output.
     */
    public function toApiResource(mixed $value, array $config = []): mixed;

    /**
     * Get Vue component props for rendering the field in the admin UI.
     */
    public function toVueComponentProps(mixed $value, array $config = []): array;

    /**
     * Get the fieldtype's machine name (e.g. 'text', 'bard', 'assets').
     */
    public static function getHandle(): string;

    /**
     * Get the Vue component name that renders this fieldtype.
     */
    public static function getVueComponent(): string;

    /**
     * Get the default config for this fieldtype.
     */
    public static function getDefaultConfig(): array;

    /**
     * Get the validation rules for this fieldtype's config.
     */
    public static function getConfigValidationRules(): array;
}
