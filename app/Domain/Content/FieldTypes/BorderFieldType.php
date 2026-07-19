<?php
namespace App\Domain\Content\FieldTypes;
class BorderFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed { return is_array($value) ? $value : []; }
    public static function getHandle(): string { return 'border'; }
    public static function getVueComponent(): string { return 'BorderField'; }
    public static function getDefaultConfig(): array { return ['width' => 1, 'style' => 'solid', 'color' => '#000000', 'radius' => 0, 'sides' => ['all']]; }
}
