<?php

namespace App\Domain\Content\Services;

use App\Domain\Content\Contracts\FieldTypeInterface;
use Illuminate\Support\Arr;

class FieldTypeRegistry
{
    protected array $types = [];
    protected static ?self $instance = null;

    public function __construct()
    {
        $this->registerDefaults();
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function register(string $handle, string $class): void
    {
        if (! in_array(FieldTypeInterface::class, class_implements($class) ?: [], true)) {
            throw new \InvalidArgumentException("{$class} must implement FieldTypeInterface");
        }
        $this->types[$handle] = $class;
    }

    public function get(string $handle): ?FieldTypeInterface
    {
        $class = $this->types[$handle] ?? null;
        if (! $class) return null;
        return app($class);
    }

    public function has(string $handle): bool
    {
        return isset($this->types[$handle]);
    }

    public function all(): array
    {
        return $this->types;
    }

    public function handles(): array
    {
        return array_keys($this->types);
    }

    protected function registerDefaults(): void
    {
        // Batch A: Core text & media
        $this->register('text', \App\Domain\Content\FieldTypes\TextFieldType::class);
        $this->register('textarea', \App\Domain\Content\FieldTypes\TextareaFieldType::class);
        $this->register('markdown', \App\Domain\Content\FieldTypes\MarkdownFieldType::class);
        $this->register('select', \App\Domain\Content\FieldTypes\SelectFieldType::class);
        $this->register('toggle', \App\Domain\Content\FieldTypes\ToggleFieldType::class);
        $this->register('date', \App\Domain\Content\FieldTypes\DateFieldType::class);
        $this->register('slug', \App\Domain\Content\FieldTypes\SlugFieldType::class);
        $this->register('assets', \App\Domain\Content\FieldTypes\AssetsFieldType::class);

        // Batch B: Structured
        $this->register('replicator', \App\Domain\Content\FieldTypes\ReplicatorFieldType::class);
        $this->register('grid', \App\Domain\Content\FieldTypes\GridFieldType::class);
        $this->register('table', \App\Domain\Content\FieldTypes\TableFieldType::class);
        $this->register('array', \App\Domain\Content\FieldTypes\ArrayFieldType::class);
        $this->register('group', \App\Domain\Content\FieldTypes\GroupFieldType::class);
        $this->register('sets', \App\Domain\Content\FieldTypes\SetsFieldType::class);

        // Batch C: Relationships
        $this->register('relationship', \App\Domain\Content\FieldTypes\RelationshipFieldType::class);
        $this->register('entries', \App\Domain\Content\FieldTypes\EntriesFieldType::class);
        $this->register('terms', \App\Domain\Content\FieldTypes\TermsFieldType::class);
        $this->register('users', \App\Domain\Content\FieldTypes\UsersFieldType::class);

        // Batch D: Remaining text & media
        $this->register('bard', \App\Domain\Content\FieldTypes\BardFieldType::class);
        $this->register('code', \App\Domain\Content\FieldTypes\CodeFieldType::class);
        $this->register('color', \App\Domain\Content\FieldTypes\ColorFieldType::class);
        $this->register('link', \App\Domain\Content\FieldTypes\LinkFieldType::class);
        $this->register('video', \App\Domain\Content\FieldTypes\VideoFieldType::class);

        // Batch E: Special
        $this->register('template', \App\Domain\Content\FieldTypes\TemplateFieldType::class);
        $this->register('revealer', \App\Domain\Content\FieldTypes\RevealerFieldType::class);
        $this->register('section', \App\Domain\Content\FieldTypes\SectionFieldType::class);
        $this->register('hidden', \App\Domain\Content\FieldTypes\HiddenFieldType::class);
        $this->register('integer', \App\Domain\Content\FieldTypes\IntegerFieldType::class);
        $this->register('float', \App\Domain\Content\FieldTypes\FloatFieldType::class);
        $this->register('range', \App\Domain\Content\FieldTypes\RangeFieldType::class);
        $this->register('button_group', \App\Domain\Content\FieldTypes\ButtonGroupFieldType::class);
        $this->register('radio', \App\Domain\Content\FieldTypes\RadioFieldType::class);
        $this->register('checkboxes', \App\Domain\Content\FieldTypes\CheckboxesFieldType::class);
        $this->register('seo_pro', \App\Domain\Content\FieldTypes\SeoProFieldType::class);

        // Batch F: Theme-aware
        $this->register('theme_color', \App\Domain\Content\FieldTypes\ThemeColorFieldType::class);
        $this->register('font_picker', \App\Domain\Content\FieldTypes\FontPickerFieldType::class);
        $this->register('spacing', \App\Domain\Content\FieldTypes\SpacingFieldType::class);
        $this->register('border', \App\Domain\Content\FieldTypes\BorderFieldType::class);
        $this->register('background', \App\Domain\Content\FieldTypes\BackgroundFieldType::class);

        // Batch G: AI
        $this->register('ai_generate', \App\Domain\Content\FieldTypes\AiGenerateFieldType::class);
    }
}
