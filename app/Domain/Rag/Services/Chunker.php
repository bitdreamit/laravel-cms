<?php

namespace App\Domain\Rag\Services;

class Chunker
{
    /**
     * Split text into chunks of approximately $chunkSize tokens with $overlap tokens of overlap.
     *
     * @return array<int, array{text: string, chunk_index: int, metadata: array}>
     */
    public function chunk(string $text, int $chunkSize = 500, int $overlap = 50, array $metadata = []): array
    {
        if (empty(trim($text))) {
            return [];
        }

        // Split on sentence boundaries first
        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [$text];

        $chunks = [];
        $currentChunk = '';
        $currentTokens = 0;
        $chunkIndex = 0;

        foreach ($sentences as $sentence) {
            $sentenceTokens = $this->estimateTokens($sentence);

            // If a single sentence exceeds chunk size, split it further on words
            if ($sentenceTokens > $chunkSize) {
                $words = explode(' ', $sentence);
                $wordChunk = '';
                $wordTokens = 0;
                foreach ($words as $word) {
                    $wordToken = $this->estimateTokens($word . ' ');
                    if ($wordTokens + $wordToken > $chunkSize && ! empty($wordChunk)) {
                        $chunks[] = [
                            'text' => trim($wordChunk),
                            'chunk_index' => $chunkIndex++,
                            'metadata' => $metadata,
                        ];
                        // Add overlap
                        $overlapWords = explode(' ', $wordChunk);
                        $wordChunk = implode(' ', array_slice($overlapWords, -min($overlap, count($overlapWords)))) . ' ';
                        $wordTokens = $this->estimateTokens($wordChunk);
                    }
                    $wordChunk .= $word . ' ';
                    $wordTokens += $wordToken;
                }
                if (! empty(trim($wordChunk))) {
                    $chunks[] = [
                        'text' => trim($wordChunk),
                        'chunk_index' => $chunkIndex++,
                        'metadata' => $metadata,
                    ];
                }
                $currentChunk = '';
                $currentTokens = 0;
                continue;
            }

            if ($currentTokens + $sentenceTokens > $chunkSize && ! empty($currentChunk)) {
                $chunks[] = [
                    'text' => trim($currentChunk),
                    'chunk_index' => $chunkIndex++,
                    'metadata' => $metadata,
                ];

                // Add overlap: keep last $overlap tokens worth of sentences
                $overlapText = $this->getLastOverlapTokens($currentChunk, $overlap);
                $currentChunk = $overlapText . ' ' . $sentence;
                $currentTokens = $this->estimateTokens($currentChunk);
            } else {
                $currentChunk .= ' ' . $sentence;
                $currentTokens += $sentenceTokens;
            }
        }

        if (! empty(trim($currentChunk))) {
            $chunks[] = [
                'text' => trim($currentChunk),
                'chunk_index' => $chunkIndex,
                'metadata' => $metadata,
            ];
        }

        return $chunks;
    }

    /**
     * Estimate token count (rough: 1 token ≈ 4 chars in English).
     */
    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    protected function getLastOverlapTokens(string $text, int $overlap): string
    {
        $words = explode(' ', $text);
        $overlapWords = array_slice($words, -$overlap);
        return implode(' ', $overlapWords);
    }
}
