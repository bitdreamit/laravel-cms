<?php

namespace App\Domain\LivePreview\Services;

use App\Models\Tenant\Entry;
use Illuminate\Support\Facades\Cache;

class LivePreviewService
{
    public function generatePreviewToken(Entry $entry, array $tempData = []): string
    {
        $token = \Illuminate\Support\Str::random(64);
        $previewData = [
            'entry_id' => $entry->id,
            'temp_data' => $tempData,
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
            'user_id' => auth()->id(),
        ];

        Cache::put("preview:{$token}", $previewData, now()->addMinutes(15));
        return $token;
    }

    public function getPreviewData(string $token): ?array
    {
        $data = Cache::get("preview:{$token}");
        if (! $data) return null;

        if (now()->parse($data['expires_at'])->isPast()) {
            Cache::forget("preview:{$token}");
            return null;
        }

        return $data;
    }

    public function renderPreview(string $token): string
    {
        $previewData = $this->getPreviewData($token);
        if (! $previewData) abort(404, 'Preview token expired or invalid.');

        $entry = Entry::find($previewData['entry_id']);
        if (! $entry) abort(404);

        // Apply temporary data overrides
        $originalData = $entry->data;
        $entry->data = array_merge($originalData, $previewData['temp_data']);

        // Render the entry's template
        $template = $entry->template ?: 'default';
        $theme = app('current.theme');

        $html = view("theme::pages.{$template}", [
            'entry' => $entry,
            'is_preview' => true,
        ])->render();

        // Restore original data
        $entry->data = $originalData;

        // Add preview banner
        $banner = '<div style="position:fixed;top:0;left:0;right:0;background:#f59e0b;color:white;padding:0.5rem;text-align:center;z-index:9999;">PREVIEW MODE — Unsaved changes</div>';
        $html = str_replace('<body>', '<body>' . $banner, $html);

        return $html;
    }

    public function invalidatePreviewToken(string $token): void
    {
        Cache::forget("preview:{$token}");
    }
}
