<?php

namespace App\Domain\Ai\Services;

class PromptTemplateEngine
{
    public function load(string $templateName): ?string
    {
        $path = config('ai.prompt_templates_path') . "/{$templateName}.md";
        if (! file_exists($path)) return null;
        return file_get_contents($path);
    }

    public function render(string $templateName, array $variables = []): ?string
    {
        $template = $this->load($templateName);
        if (! $template) return null;

        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }
        return $template;
    }

    public function listTemplates(): array
    {
        $path = config('ai.prompt_templates_path');
        if (! is_dir($path)) return [];
        $files = glob("{$path}/*.md");
        return array_map(fn($f) => basename($f, '.md'), $files);
    }
}
