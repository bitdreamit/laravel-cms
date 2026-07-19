<?php

namespace App\Domain\Content\FieldTypes;

class BardFieldType extends BaseFieldType
{
    public function cast(mixed $value, array $config = []): mixed
    {
        if (empty($value)) return $config['save_html'] ?? false ? '' : [];

        $saveHtml = $config['save_html'] ?? false;
        if ($saveHtml && is_array($value)) {
            return $this->toHtml($value);
        }
        if (is_string($value) && ! $saveHtml) {
            return $this->htmlToArray($value);
        }
        return $value;
    }

    public function render(mixed $value, array $config = []): string
    {
        if (is_string($value)) return $value;
        if (is_array($value)) return $this->toHtml($value);
        return '';
    }

    public function toApiResource(mixed $value, array $config = []): mixed
    {
        $saveHtml = $config['save_html'] ?? false;
        if ($saveHtml) {
            return $this->render($value, $config);
        }
        return $this->cast($value, $config);
    }

    protected function toHtml(array $content): string
    {
        $html = '';
        foreach ($content as $node) {
            $type = $node['type'] ?? '';
            $html .= match($type) {
                'heading' => '<h' . ($node['attrs']['level'] ?? 1) . '>' . $this->renderInline($node['content'] ?? []) . '</h' . ($node['attrs']['level'] ?? 1) . '>',
                'paragraph' => '<p>' . $this->renderInline($node['content'] ?? []) . '</p>',
                'bullet_list' => '<ul>' . $this->renderListItems($node['content'] ?? []) . '</ul>',
                'ordered_list' => '<ol>' . $this->renderListItems($node['content'] ?? []) . '</ol>',
                'blockquote' => '<blockquote>' . $this->renderInline($node['content'] ?? []) . '</blockquote>',
                'code_block' => '<pre><code>' . ($node['content'][0]['text'] ?? '') . '</code></pre>',
                'image' => '<img src="' . ($node['attrs']['src'] ?? '') . '" alt="' . ($node['attrs']['alt'] ?? '') . '">',
                'set' => $this->renderSet($node),
                default => '',
            };
        }
        return $html;
    }

    protected function renderInline(array $content): string
    {
        $html = '';
        foreach ($content as $node) {
            if (($node['type'] ?? '') === 'text') {
                $text = $node['text'] ?? '';
                $marks = $node['marks'] ?? [];
                foreach ($marks as $mark) {
                    $text = match($mark['type']) {
                        'bold' => "<strong>{$text}</strong>",
                        'italic' => "<em>{$text}</em>",
                        'code' => "<code>{$text}</code>",
                        'link' => '<a href="' . ($mark['attrs']['href'] ?? '#') . '">' . $text . '</a>',
                        default => $text,
                    };
                }
                $html .= $text;
            }
        }
        return $html;
    }

    protected function renderListItems(array $content): string
    {
        $html = '';
        foreach ($content as $node) {
            if (($node['type'] ?? '') === 'list_item') {
                $html .= '<li>' . $this->renderInline($node['content'][0]['content'] ?? []) . '</li>';
            }
        }
        return $html;
    }

    protected function renderSet(array $node): string
    {
        $setType = $node['attrs']['type'] ?? 'unknown';
        $fields = $node['attrs']['values'] ?? [];
        return '<div class="set set-' . $setType . '">' . json_encode($fields) . '</div>';
    }

    protected function htmlToArray(string $html): array
    {
        if (empty(trim($html))) return [];
        return [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => strip_tags($html)]]]];
    }

    public static function getHandle(): string { return 'bard'; }
    public static function getVueComponent(): string { return 'BardField'; }

    public static function getDefaultConfig(): array
    {
        return [
            'toolbar_buttons' => ['bold','italic','underline','link','heading','bulletList','orderedList','blockquote','codeBlock','image'],
            'sets' => [],
            'save_html' => false,
            'word_count' => true,
            'fullscreen' => true,
        ];
    }
}
