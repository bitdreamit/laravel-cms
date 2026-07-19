<?php

namespace App\Domain\Ai\Services;

class ContentAnalyzer
{
    public function analyzeSeo(string $content, string $title = '', string $metaDescription = ''): array
    {
        $checks = [];

        // Title length check
        $titleLength = strlen($title);
        $checks['title_length'] = [
            'value' => $titleLength,
            'status' => $titleLength >= 30 && $titleLength <= 60 ? 'good' : ($titleLength < 30 ? 'warning' : 'warning'),
            'message' => $titleLength < 30 ? 'Title is too short (recommended: 30-60 chars)' : ($titleLength > 60 ? 'Title is too long (recommended: 30-60 chars)' : 'Title length is optimal'),
        ];

        // Meta description check
        $descLength = strlen($metaDescription);
        $checks['meta_description_length'] = [
            'value' => $descLength,
            'status' => $descLength >= 120 && $descLength <= 160 ? 'good' : 'warning',
            'message' => $descLength < 120 ? 'Meta description too short (recommended: 120-160 chars)' : ($descLength > 160 ? 'Meta description too long (recommended: 120-160 chars)' : 'Meta description length is optimal'),
        ];

        // Content word count
        $wordCount = str_word_count(strip_tags($content));
        $checks['word_count'] = [
            'value' => $wordCount,
            'status' => $wordCount >= 300 ? 'good' : 'warning',
            'message' => $wordCount < 300 ? 'Content is too short (recommended: 300+ words)' : 'Content length is good',
        ];

        // Heading structure
        $headingCount = preg_match_all('/<h[1-6][^>]*>/i', $content);
        $checks['headings'] = [
            'value' => $headingCount,
            'status' => $headingCount > 0 ? 'good' : 'warning',
            'message' => $headingCount === 0 ? 'No headings found — add H1-H6 tags for better structure' : 'Heading structure present',
        ];

        // Readability (Flesch-Kincaid)
        $readability = $this->fleschKincaid($content);
        $checks['readability'] = [
            'value' => round($readability, 1),
            'status' => $readability >= 60 ? 'good' : 'warning',
            'message' => $readability < 60 ? 'Content may be difficult to read' : 'Content readability is good',
        ];

        $score = count(array_filter($checks, fn($c) => $c['status'] === 'good')) / count($checks) * 100;
        return ['score' => round($score), 'checks' => $checks];
    }

    protected function fleschKincaid(string $text): float
    {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text);
        $sentences = array_filter($sentences, fn($s) => trim($s));
        $words = str_word_count($text);
        $syllables = 0;
        $wordList = str_word_count($text, 1);
        foreach ($wordList as $word) { $syllables += $this->countSyllables($word); }

        if (count($sentences) === 0 || $words === 0) return 0;
        return 206.835 - (1.015 * ($words / count($sentences))) - (84.6 * ($syllables / $words));
    }

    protected function countSyllables(string $word): int
    {
        $word = strtolower($word);
        $count = preg_match_all('/[aeiouy]+/', $word);
        if ($count === 0) return 1;
        if (preg_match('/^.*e$/', $word) && $count > 1) $count--;
        return max(1, $count);
    }
}
