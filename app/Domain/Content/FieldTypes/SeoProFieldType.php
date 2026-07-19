<?php
namespace App\Domain\Content\FieldTypes;
class SeoProFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed {
        $defaults = ['meta_title' => '', 'meta_description' => '', 'og_image' => null, 'canonical_url' => '', 'noindex' => false, 'schema_type' => 'Article'];
        if (!is_array($value)) return $defaults;
        return array_merge($defaults, $value);
    }
    public function toApiResource(mixed $value, array $config = []): mixed { return $this->cast($value, $config); }
    public static function getHandle(): string { return 'seo_pro'; }
    public static function getVueComponent(): string { return 'SeoProField'; }
    public static function getDefaultConfig(): array { return ['meta_title' => ['max_length' => 60], 'meta_description' => ['max_length' => 160], 'schema_type' => ['Article','BlogPosting','NewsArticle','Product','Event','Organization','Person','VideoObject']]; }
}
