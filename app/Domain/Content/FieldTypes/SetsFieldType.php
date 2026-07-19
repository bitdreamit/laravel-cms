<?php
namespace App\Domain\Content\FieldTypes;
class SetsFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed { return is_array($value) ? $value : []; }
    public static function getHandle(): string { return 'sets'; }
    public static function getVueComponent(): string { return 'SetsField'; }
    public static function getDefaultConfig(): array { return []; }
}
