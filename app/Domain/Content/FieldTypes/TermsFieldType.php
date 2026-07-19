<?php
namespace App\Domain\Content\FieldTypes;
class TermsFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed {
        $max = $config['max_items'] ?? 0;
        if (!is_array($value)) $value = empty($value) ? [] : [$value];
        if ($max > 0 && count($value) > $max) $value = array_slice($value, 0, $max);
        return $value;
    }
    public static function getHandle(): string { return 'terms'; }
    public static function getVueComponent(): string { return 'TermsField'; }
    public static function getDefaultConfig(): array { return ['taxonomies' => [], 'max_items' => 0]; }
}
