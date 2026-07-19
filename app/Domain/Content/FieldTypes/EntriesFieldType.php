<?php
namespace App\Domain\Content\FieldTypes;
class EntriesFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed {
        $max = $config['max_items'] ?? 0;
        if (!is_array($value)) $value = empty($value) ? [] : [$value];
        if ($max > 0 && count($value) > $max) $value = array_slice($value, 0, $max);
        return $value;
    }
    public static function getHandle(): string { return 'entries'; }
    public static function getVueComponent(): string { return 'EntriesField'; }
    public static function getDefaultConfig(): array { return ['collections' => [], 'max_items' => 0]; }
}
