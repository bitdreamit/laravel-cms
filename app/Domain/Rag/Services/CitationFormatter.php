<?php

namespace App\Domain\Rag\Services;

use App\Models\Tenant\Entry;
use App\Models\Tenant\RagDocument;

class CitationFormatter
{
    /**
     * Format the answer to include inline citations.
     */
    public function format(string $answer, array $retrievedDocuments): string
    {
        // Add sources section at the end
        $sources = "\n\n**Sources:**\n";
        $seenEntries = [];

        foreach ($retrievedDocuments as $i => $result) {
            $doc = $result['document'];
            if (in_array($doc->entry_id, $seenEntries)) continue;
            $seenEntries[] = $doc->entry_id;

            $entry = Entry::find($doc->entry_id);
            if (! $entry) continue;

            $sources .= ($i + 1) . ". [{$entry->title}](/entries/{$entry->slug})\n";
        }

        return $answer . $sources;
    }
}
