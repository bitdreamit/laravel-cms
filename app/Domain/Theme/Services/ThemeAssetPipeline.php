<?php

namespace App\Domain\Theme\Services;

use App\Models\Central\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeAssetPipeline
{
    public function compile(Theme $theme): void
    {
        $distPath = "{$theme->path}/dist";
        File::makeDirectory($distPath . '/css', 0755, true, true);
        File::makeDirectory($distPath . '/js', 0755, true, true);

        // Compile CSS (simple copy for now — in production, use Vite or Laravel Mix)
        $cssPath = "{$theme->path}/assets/css/theme.css";
        if (File::exists($cssPath)) {
            $css = File::get($cssPath);
            $minified = $this->minifyCss($css);
            File::put("{$distPath}/css/theme.min.css", $minified);
        }

        // Compile JS
        $jsPath = "{$theme->path}/assets/js/theme.js";
        if (File::exists($jsPath)) {
            $js = File::get($jsPath);
            File::put("{$distPath}/js/theme.min.js", $js);
        }
    }

    public function version(Theme $theme, string $path): string
    {
        $fullPath = "{$theme->path}/{$path}";
        if (! File::exists($fullPath)) return $path;

        $hash = hash_file('md5', $fullPath);
        $hash = substr($hash, 0, 8);

        return "{$path}?v={$hash}";
    }

    public function cdnUrl(string $path): string
    {
        $cdnUrl = config('themes.asset_pipeline.cdn_url');
        $cdnEnabled = config('themes.asset_pipeline.cdn_enabled', false);

        if ($cdnEnabled && $cdnUrl) {
            return rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
        }
        return '/' . ltrim($path, '/');
    }

    protected function minifyCss(string $css): string
    {
        // Simple minification — remove comments, whitespace
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        $css = str_replace([' {', '{ '], '{', $css);
        $css = str_replace([' }', '} '], '}', $css);
        $css = str_replace([' :', ': '], ':', $css);
        $css = str_replace([' ;', '; '], ';', $css);
        $css = str_replace([' ,', ', '], ',', $css);
        return trim($css);
    }
}
