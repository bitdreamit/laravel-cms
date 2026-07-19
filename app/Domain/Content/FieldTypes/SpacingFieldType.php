<?php
namespace App\Domain\Content\FieldTypes;
class SpacingFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed { return is_array($value) ? $value : []; }
    public static function getHandle(): string { return 'spacing'; }
    public static function getVueComponent(): string { return 'SpacingField'; }
    public static function getDefaultConfig(): array { return ['properties' => ['margin','padding'], 'sides' => ['all'], 'units' => ['px','rem','em'], 'default' => []]; }
}
