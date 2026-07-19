<?php

namespace App\Domain\Content\Services;

use App\Models\Tenant\Entry;
use App\Models\Tenant\Collection;
use Illuminate\Support\Facades\File;
use ZipArchive;

class StaticSiteGenerator
{
    public function generate(string $outputDir = null, array $options = []): string
    {
        $tenant = tenant();
        $outputDir = $outputDir ?? storage_path("app/exports/{$tenant->slug}-" . now()->format('Y-m-d-His'));

        File::makeDirectory($outputDir, 0755, true, true);

        // 1. Generate entry pages
        $entries = Entry::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->with(['collection', 'site'])
            ->get();

        $routes = [];

        foreach ($entries as $entry) {
            $html = $this->renderEntry($entry, $options);
            $path = $this->getEntryPath($entry, $outputDir);
            File::makeDirectory(dirname($path), 0755, true, true);
            File::put($path, $html);
            $routes[] = ['url' => $this->getEntryUrl($entry), 'path' => $path];
        }

        // 2. Generate collection index pages
        $collections = Collection::where('tenant_id', $tenant->id)->get();
        foreach ($collections as $collection) {
            $html = $this->renderCollectionIndex($collection, $options);
            $path = "{$outputDir}/{$collection->handle}/index.html";
            File::makeDirectory(dirname($path), 0755, true, true);
            File::put($path, $html);
        }

        // 3. Generate home page
        File::put("{$outputDir}/index.html", $this->renderHome($options));

        // 4. Generate sitemap.xml
        File::put("{$outputDir}/sitemap.xml", $this->generateSitemap($routes));

        // 5. Generate robots.txt
        File::put("{$outputDir}/robots.txt", $this->generateRobotsTxt());

        // 6. Copy assets
        $this->copyAssets($outputDir);

        // 7. Generate manifest
        File::put("{$outputDir}/manifest.json", json_encode([
            'tenant' => $tenant->name,
            'generated_at' => now()->toIso8601String(),
            'entry_count' => $entries->count(),
            'routes' => $routes,
        ], JSON_PRETTY_PRINT));

        // 8. Zip if requested
        if ($options['zip'] ?? true) {
            $zipPath = $outputDir . '.zip';
            $this->zipDirectory($outputDir, $zipPath);
            return $zipPath;
        }

        return $outputDir;
    }

    protected function renderEntry(Entry $entry, array $options): string
    {
        $template = $entry->template ?: 'entry-show';
        return view("theme::pages.{$template}", ['entry' => $entry])->render();
    }

    protected function renderCollectionIndex(Collection $collection, array $options): string
    {
        $entries = $collection->entries()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('theme::pages.collection-index', [
            'collection' => $collection,
            'entries' => $entries,
        ])->render();
    }

    protected function renderHome(array $options): string
    {
        $entries = Entry::where('tenant_id', tenant('id'))
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->take(6)
            ->get();

        return view('theme::pages.home', ['entries' => $entries])->render();
    }

    protected function getEntryPath(Entry $entry, string $baseDir): string
    {
        $collection = $entry->collection;
        $pattern = $collection?->route_pattern ?: '/{slug}';

        $path = str_replace('{slug}', $entry->slug, $pattern);
        $path = trim($path, '/');

        return "{$baseDir}/{$path}/index.html";
    }

    protected function getEntryUrl(Entry $entry): string
    {
        $collection = $entry->collection;
        $pattern = $collection?->route_pattern ?: '/{slug}';
        return str_replace('{slug}', $entry->slug, $pattern);
    }

    protected function generateSitemap(array $routes): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $domain = app('current.domain');
        $baseUrl = $domain ? "https://{$domain->domain}" : url('/');

        foreach ($routes as $route) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$baseUrl}{$route['url']}</loc>\n";
            $xml .= "    <lastmod>" . now()->toIso8601String() . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    protected function generateRobotsTxt(): string
    {
        return "User-agent: *\nAllow: /\nSitemap: /sitemap.xml\n";
    }

    protected function copyAssets(string $outputDir): void
    {
        $theme = app('current.theme');
        if (! $theme) return;

        $assetsPath = "{$theme->path}/assets";
        if (File::isDirectory($assetsPath)) {
            File::copyDirectory($assetsPath, "{$outputDir}/assets");
        }

        // Copy uploaded assets
        $storagePath = storage_path('app/public');
        if (File::isDirectory($storagePath)) {
            File::copyDirectory($storagePath, "{$outputDir}/storage");
        }
    }

    protected function zipDirectory(string $source, string $destination): void
    {
        $zip = new ZipArchive();
        $zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source));
        foreach ($files as $file) {
            if (! $file->isDir()) {
                $relativePath = substr($file->getPathname(), strlen($source) + 1);
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
        $zip->close();
    }
}
