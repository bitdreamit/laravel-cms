<?php
namespace App\Domain\Content\FieldTypes;
class FontPickerFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed { return is_string($value) ? $value : ($config['default'] ?? 'Inter'); }
    public static function getHandle(): string { return 'font_picker'; }
    public static function getVueComponent(): string { return 'FontPickerField'; }
    public static function getDefaultConfig(): array { return ['google_fonts' => true, 'custom_fonts' => [], 'preview_text' => 'The quick brown fox', 'default' => 'Inter']; }
}
