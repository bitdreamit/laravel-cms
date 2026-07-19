<?php
namespace App\Domain\Content\FieldTypes;
class GridFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed {
        if (!is_array($value)) return [];
        $min = $config['min_rows'] ?? 0;
        $max = $config['max_rows'] ?? 0;
        if ($max > 0 && count($value) > $max) $value = array_slice($value, 0, $max);
        return $value;
    }
    public static function getHandle(): string { return 'grid'; }
    public static function getVueComponent(): string { return 'GridField'; }
    public static function getDefaultConfig(): array { return ['fields' => [], 'min_rows' => 0, 'max_rows' => 0, 'mode' => 'table']; }
}
