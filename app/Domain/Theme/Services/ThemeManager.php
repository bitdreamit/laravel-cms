<?php

namespace App\Domain\Theme\Services;

use App\Models\Central\Theme;
use App\Models\Central\Tenant;
use App\Models\Tenant\ThemeCustomization;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ZipArchive;

class ThemeManager
{
    public function __construct(protected Filesystem $files) {}

    public function install(string $zipPath): Theme
    {
        $tempDir = storage_path('app/theme-temp/' . Str::uuid());
        $this->files->makeDirectory($tempDir, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Cannot open theme zip file.');
        }
        $zip->extractTo($tempDir);
        $zip->close();

        // Find theme.json
        $themeJsonPath = $this->findThemeJson($tempDir);
        if (! $themeJsonPath) {
            $this->files->deleteDirectory($tempDir);
            throw new \RuntimeException('theme.json not found in zip.');
        }

        $themeData = json_decode($this->files->get($themeJsonPath), true);
        if (! $themeData || ! isset($themeData['slug'])) {
            throw new \RuntimeException('Invalid theme.json format.');
        }

        $themeDir = dirname($themeJsonPath);
        $destPath = base_path("themes/{$themeData['slug']}");

        if ($this->files->exists($destPath)) {
            $this->files->deleteDirectory($tempDir);
            throw new \RuntimeException("Theme directory already exists: {$themeData['slug']}");
        }

        $this->files->moveDirectory($themeDir, $destPath);
        $this->files->deleteDirectory($tempDir);

        $theme = Theme::create([
            'id' => Str::uuid(),
            'name' => $themeData['name'],
            'slug' => $themeData['slug'],
            'version' => $themeData['version'] ?? '1.0.0',
            'description' => $themeData['description'] ?? '',
            'author' => $themeData['author'] ?? 'Unknown',
            'author_url' => $themeData['author_url'] ?? null,
            'parent_id' => isset($themeData['parent']) ? Theme::where('slug', $themeData['parent'])->value('id') : null,
            'type' => 'custom',
            'path' => $destPath,
            'settings_schema' => $themeData['settings_schema'] ?? [],
            'supported_features' => $themeData['supported_features'] ?? [],
            'min_cms_version' => $themeData['min_cms_version'] ?? null,
            'tags' => $themeData['tags'] ?? [],
            'is_active' => true,
        ]);

        event(new \App\Domain\Theme\Events\ThemeInstalled($theme));

        return $theme;
    }

    public function uninstall(Theme $theme): void
    {
        if (Tenant::where('current_theme_id', $theme->id)->exists()) {
            throw new \RuntimeException('Cannot uninstall: theme is in use by tenants.');
        }

        if ($this->files->exists($theme->path)) {
            $this->files->deleteDirectory($theme->path);
        }

        $theme->delete();
    }

    public function activate(Tenant $tenant, Theme $theme): void
    {
        $tenant->update(['current_theme_id' => $theme->id]);
        \Illuminate\Support\Facades\Cache::forget("theme:{$tenant->id}:settings");

        event(new \App\Domain\Theme\Events\ThemeActivated($theme, $tenant));
    }

    public function duplicate(Theme $theme, string $newName): Theme
    {
        $newSlug = $theme->slug . '-child-' . Str::random(6);
        $newPath = base_path("themes/{$newSlug}");

        $this->copyDirectory($theme->path, $newPath);

        $themeJsonPath = "{$newPath}/theme.json";
        $themeData = json_decode($this->files->get($themeJsonPath), true);
        $themeData['name'] = $newName;
        $themeData['slug'] = $newSlug;
        $themeData['parent'] = $theme->slug;
        $themeData['type'] = 'custom';
        $this->files->put($themeJsonPath, json_encode($themeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $childTheme = Theme::create([
            'id' => Str::uuid(),
            'name' => $newName,
            'slug' => $newSlug,
            'version' => '1.0.0',
            'description' => "Child theme of {$theme->name}",
            'author' => $theme->author,
            'parent_id' => $theme->id,
            'type' => 'custom',
            'path' => $newPath,
            'settings_schema' => $theme->settings_schema,
            'supported_features' => $theme->supported_features,
            'tags' => $theme->tags,
            'is_active' => true,
        ]);

        return $childTheme;
    }

    public function export(Theme $theme): string
    {
        $zipPath = storage_path("app/theme-exports/{$theme->slug}-{$theme->version}.zip");

        $this->files->makeDirectory(dirname($zipPath), 0755, true);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($theme->path));
        foreach ($files as $file) {
            if (! $file->isDir()) {
                $relativePath = substr($file->getPathname(), strlen($theme->path) + 1);
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
        $zip->close();

        return $zipPath;
    }

    protected function findThemeJson(string $dir): ?string
    {
        if ($this->files->exists("{$dir}/theme.json")) return "{$dir}/theme.json";
        foreach ($this->files->directories($dir) as $subdir) {
            if ($this->files->exists("{$subdir}/theme.json")) return "{$subdir}/theme.json";
        }
        return null;
    }

    protected function copyDirectory(string $src, string $dst): void
    {
        $this->files->makeDirectory($dst, 0755, true);
        foreach ($this->files->allFiles($src) as $file) {
            $relativePath = substr($file->getPathname(), strlen($src) + 1);
            $destFile = "{$dst}/{$relativePath}";
            $this->files->makeDirectory(dirname($destFile), 0755, true, true);
            $this->files->copy($file->getPathname(), $destFile);
        }
    }
}
