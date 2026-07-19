<?php
namespace App\Domain\Content\FieldTypes;
class ReplicatorFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed {
        if (!is_array($value)) return [];
        $maxSets = $config['max_sets'] ?? 0;
        if ($maxSets > 0 && count($value) > $maxSets) $value = array_slice($value, 0, $maxSets);
        return $value;
    }
    public static function getHandle(): string { return 'replicator'; }
    public static function getVueComponent(): string { return 'ReplicatorField'; }
    public static function getDefaultConfig(): array { return ['sets' => [], 'collapse' => false, 'max_sets' => 0]; }
}
