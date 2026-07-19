<?php

namespace App\Domain\Seo\Services;

use App\Models\Tenant\Entry;

class SeoService
{
    public function generateMetaTags(Entry $entry): array
    {
        $data = $entry->data ?? [];
        $seoData = $data['seo'] ?? [];

        return [
            'title' => $seoData['meta_title'] ?? $entry->title,
            'description' => $seoData['meta_description'] ?? substr($data['body'] ?? '', 0, 160),
            'og_title' => $seoData['og_title'] ?? $entry->title,
            'og_description' => $seoData['og_description'] ?? $seoData['meta_description'] ?? null,
            'og_image' => $seoData['og_image'] ?? null,
            'canonical_url' => $seoData['canonical_url'] ?? '/' . $entry->slug,
            'noindex' => $seoData['noindex'] ?? false,
            'schema_type' => $seoData['schema_type'] ?? 'Article',
            'schema_json_ld' => $this->generateJsonLd($entry, $seoData),
        ];
    }

    public function generateJsonLd(Entry $entry, array $seoData = []): array
    {
        $data = $entry->data ?? [];
        $schemaType = $seoData['schema_type'] ?? 'Article';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'headline' => $entry->title,
            'datePublished' => $entry->published_at?->toIso8601String(),
            'dateModified' => $entry->updated_at?->toIso8601String(),
            'url' => '/' . $entry->slug,
        ];

        if (! empty($data['body'])) {
            $schema['articleBody'] = $data['body'];
        }

        if (! empty($seoData['og_image'])) {
            $schema['image'] = $seoData['og_image'];
        }

        return $schema;
    }

    public function generateSitemap(): string
    {
        $entries = Entry::where('tenant_id', tenant('id'))
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($entries as $entry) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>/" . htmlspecialchars($entry->slug) . "</loc>\n";
            $xml .= "    <lastmod>" . $entry->updated_at?->toIso8601String() . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
