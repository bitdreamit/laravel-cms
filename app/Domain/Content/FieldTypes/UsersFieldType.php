<?php
namespace App\Domain\Content\FieldTypes;
class UsersFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed {
        $max = $config['max_items'] ?? 0;
        if (!is_array($value)) $value = empty($value) ? [] : [$value];
        if ($max > 0 && count($value) > $max) $value = array_slice($value, 0, $max);
        return $value;
    }
    public static function getHandle(): string { return 'users'; }
    public static function getVueComponent(): string { return 'UsersField'; }
    public static function getDefaultConfig(): array { return ['roles' => [], 'max_items' => 0]; }
}
