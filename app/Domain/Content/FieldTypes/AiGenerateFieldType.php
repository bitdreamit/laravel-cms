<?php
namespace App\Domain\Content\FieldTypes;
class AiGenerateFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed { return is_string($value) ? $value : ''; }
    public function toVueComponentProps(mixed $value, array $config = []): array {
        $props = parent::toVueComponentProps($value, $config);
        $props['ai_enabled'] = tenant_has_feature('ai_rag') || config('ai.enabled_for_tenants', true);
        $props['prompt_template'] = $config['prompt_template'] ?? 'blog-post-generator';
        return $props;
    }
    public static function getHandle(): string { return 'ai_generate'; }
    public static function getVueComponent(): string { return 'AiGenerateField'; }
    public static function getDefaultConfig(): array { return ['model' => 'gpt-4o', 'prompt_template' => 'blog-post-generator', 'target_field' => null, 'max_tokens' => 2000, 'temperature' => 0.7]; }
}
