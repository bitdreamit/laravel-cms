<?php
namespace App\Domain\Content\FieldTypes;
class BackgroundFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed {
        if (!is_array($value)) return ['type' => 'color', 'value' => '#ffffff'];
        return $value;
    }
    public static function getHandle(): string { return 'background'; }
    public static function getVueComponent(): string { return 'BackgroundField'; }
    public static function getDefaultConfig(): array { return ['type' => 'color', 'position' => 'center', 'size' => 'cover', 'repeat' => 'no-repeat', 'default' => ['type' => 'color', 'value' => '#ffffff']]; }
}
