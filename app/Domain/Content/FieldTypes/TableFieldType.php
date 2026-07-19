<?php
namespace App\Domain\Content\FieldTypes;
class TableFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed { return is_array($value) ? $value : []; }
    public static function getHandle(): string { return 'table'; }
    public static function getVueComponent(): string { return 'TableField'; }
    public static function getDefaultConfig(): array { return ['default_columns' => 3]; }
}
