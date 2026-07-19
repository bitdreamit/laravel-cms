<?php

namespace App\Domain\Content\FieldTypes;

class AssetsFieldType extends BaseFieldType
{
    public function cast(mixed $value, array $config = []): mixed
    {
        if (empty($value)) return [];
        $value = is_array($value) ? $value : [$value];
        $maxFiles = $config['max_files'] ?? 0;
        if ($maxFiles > 0 && count($value) > $maxFiles) {
            $value = array_slice($value, 0, $maxFiles);
        }
        return $value;
    }

    public function render(mixed $value, array $config = []): string
    {
        $assets = $this->cast($value, $config);
        if (empty($assets)) return '';
        $urls = array_map(fn($a) => is_array($a) ? ($a['url'] ?? '') : (string)$a, $assets);
        return implode(', ', $urls);
    }

    public function toApiResource(mixed $value, array $config = []): mixed
    {
        return $this->cast($value, $config);
    }

    public static function getHandle(): string { return 'assets'; }
    public static function getVueComponent(): string { return 'AssetField'; }

    public static function getDefaultConfig(): array
    {
        return [
            'container' => 'main',
            'max_files' => 0,
            'restrict' => false,
            'allow_uploads' => true,
        ];
    }
}
