<?php

namespace App\Domain\Content\FieldTypes;

class RevealerFieldType extends BaseFieldType
{
    public function cast(mixed $value, array $config = []): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }

    public static function getHandle(): string { return 'revealer'; }
    public static function getVueComponent(): string { return 'RevealerField'; }

    public static function getDefaultConfig(): array
    {
        return match('revealer') {
            'text' => ['input_type' => 'revealer', 'character_limit' => null, 'placeholder' => ''],
            'textarea' => ['character_limit' => null, 'rows' => 5],
            'markdown' => ['toolbar_buttons' => ['bold','italic','link','heading','list','quote','code']],
            'code' => ['mode' => 'php', 'theme' => 'monokai', 'indent_type' => 'tab'],
            'color' => ['swatches' => [], 'alpha' => false, 'default' => '#000000'],
            'toggle' => ['default' => false],
            'date' => ['mode' => 'single', 'time_enabled' => false, 'format' => 'Y-m-d'],
            'slug' => ['from' => 'title', 'separator' => '-'],
            'integer' => ['min' => null, 'max' => null, 'step' => 1],
            'float' => ['min' => null, 'max' => null, 'decimals' => 2],
            'range' => ['min' => 0, 'max' => 100, 'step' => 1],
            'radio' => ['options' => [], 'inline' => false],
            'checkboxes' => ['options' => [], 'inline' => false],
            'button_group' => ['options' => []],
            'select' => ['options' => [], 'multiple' => false, 'taggable' => false, 'searchable' => false],
            default => [],
        };
    }
}
