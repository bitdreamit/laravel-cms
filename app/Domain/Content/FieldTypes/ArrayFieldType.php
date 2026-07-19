<?php
namespace App\Domain\Content\FieldTypes;
class ArrayFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed { return is_array($value) ? $value : []; }
    public static function getHandle(): string { return 'array'; }
    public static function getVueComponent(): string { return 'ArrayField'; }
    public static function getDefaultConfig(): array { return ['mode' => 'key_value']; }
}
