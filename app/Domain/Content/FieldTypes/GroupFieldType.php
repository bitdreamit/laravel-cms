<?php
namespace App\Domain\Content\FieldTypes;
class GroupFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed { return is_array($value) ? $value : []; }
    public static function getHandle(): string { return 'group'; }
    public static function getVueComponent(): string { return 'GroupField'; }
    public static function getDefaultConfig(): array { return ['fields' => []]; }
}
