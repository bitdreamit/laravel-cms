<?php
namespace App\Domain\Content\FieldTypes;
class ThemeColorFieldType extends BaseFieldType {
    public function cast(mixed $value, array $config = []): mixed {
        if (empty($value)) return $config['default'] ?? '#000000';
        return $value;
    }
    public function toVueComponentProps(mixed $value, array $config = []): array {
        $props = parent::toVueComponentProps($value, $config);
        $theme = app()->bound('current.theme') ? (app()->bound('current.theme') ? app('current.theme') : null) : null;
        if ($theme) {
            $props['theme_palette'] = data_get($theme->settings_schema, 'branding.settings', []);
        }
        return $props;
    }
    public static function getHandle(): string { return 'theme_color'; }
    public static function getVueComponent(): string { return 'ThemeColorField'; }
    public static function getDefaultConfig(): array { return ['format' => 'hex', 'opacity' => false, 'default' => '#2563eb']; }
}
