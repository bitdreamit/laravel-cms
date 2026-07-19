<?php

namespace App\Domain\Collab\Services;

use App\Models\Tenant\CollabSession;
use App\Models\Tenant\Entry;

class DocumentPersister
{
    public function persist(CollabSession $session): void
    {
        $entry = Entry::find($session->entry_id);
        if (! $entry) return;

        $documentState = $session->yjs_document_state;
        $fieldHandle = $session->field_handle;

        // Convert Yjs document to field value based on field type
        $fieldValue = $this->convertToFieldValue($documentState, $fieldHandle, $entry);

        $data = $entry->data ?? [];
        $data[$fieldHandle] = $fieldValue;

        $entry->update(['data' => $data]);
    }

    protected function convertToFieldValue(string $documentState, string $fieldHandle, Entry $entry): mixed
    {
        // In a real implementation, this would use the Yjs library to convert
        // the binary document state to the appropriate field format:
        // - For bard: convert Yjs XML fragment to ProseMirror JSON → HTML
        // - For markdown: convert Yjs text to markdown string
        // - For textarea/text: convert Yjs text to plain string

        $blueprintField = $entry->blueprint?->fields()?->where('handle', $fieldHandle)->first();

        return match ($blueprintField?->fieldtype) {
            'bard' => $this->yjsToHtml($documentState),
            'markdown', 'textarea', 'text' => $this->yjsToText($documentState),
            default => $documentState,
        };
    }

    protected function yjsToText(string $documentState): string
    {
        // Decode the Yjs document to plain text
        // In production, use the actual Yjs library
        if (empty($documentState)) return '';
        // Simple fallback: decode as base64 and strip binary
        $decoded = base64_decode($documentState, true);
        return $decoded ? trim(preg_replace('/[^\x20-\x7E\n\r\t]/', '', $decoded)) : '';
    }

    protected function yjsToHtml(string $documentState): string
    {
        $text = $this->yjsToText($documentState);
        if (empty($text)) return '';
        return '<p>' . nl2br(htmlspecialchars($text)) . '</p>';
    }
}
