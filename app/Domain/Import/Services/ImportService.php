<?php

namespace App\Domain\Import\Services;

use App\Models\Tenant\ImportJob;
use App\Models\Tenant\Entry;
use App\Models\Tenant\Collection;
use Illuminate\Support\Str;
use SimpleXMLElement;

class ImportService
{
    public function importWordPress(ImportJob $job): void
    {
        $job->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $path = storage_path('app/' . $job->source_file);
            $content = file_get_contents($path);

            // Parse WordPress XML (WXR format)
            $xml = new SimpleXMLElement($content);

            $items = $xml->channel->item;
            $total = count($items);
            $job->update(['total_items' => $total]);

            $collection = Collection::where('tenant_id', $job->tenant_id)
                ->where('handle', $job->collection_handle)
                ->first();

            if (! $collection) {
                throw new \RuntimeException("Collection not found: {$job->collection_handle}");
            }

            $processed = 0;
            $failed = 0;
            $errors = [];

            foreach ($items as $item) {
                try {
                    $namespaces = $item->getNamespaces(true);
                    $wp = $item->children($namespaces['wp'] ?? 'wp');
                    $content = $item->children($namespaces['content'] ?? 'content');
                    $dc = $item->children($namespaces['dc'] ?? 'dc');

                    $postType = (string) ($wp->post_type ?? 'post');
                    if (! in_array($postType, ['post', 'page'])) continue;

                    $status = (string) ($wp->status ?? 'publish');
                    $entryStatus = $status === 'publish' ? 'published' : 'draft';

                    Entry::create([
                        'id' => Str::uuid(),
                        'tenant_id' => $job->tenant_id,
                        'collection_id' => $collection->id,
                        'title' => (string) $item->title,
                        'slug' => (string) ($wp->post_name ?? Str::slug((string) $item->title)),
                        'status' => $entryStatus,
                        'data' => [
                            'body' => (string) ($content->encoded ?? ''),
                        ],
                        'published_at' => $entryStatus === 'published' ? now() : null,
                        'created_by' => $job->user_id,
                    ]);

                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "Item {$processed}: " . $e->getMessage();
                }

                $job->update(['processed_items' => $processed, 'failed_items' => $failed]);
            }

            $job->update([
                'status' => $processed > 0 ? 'completed' : 'failed',
                'completed_at' => now(),
                'error_log' => $errors,
            ]);

        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_log' => [$e->getMessage()],
            ]);
        }
    }

    public function importCsv(ImportJob $job): void
    {
        $job->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $path = storage_path('app/' . $job->source_file);
            $handle = fopen($path, 'r');
            $headers = fgetcsv($handle);
            $total = 0;
            while (fgetcsv($handle) !== false) $total++;
            rewind($handle);
            fgetcsv($handle); // skip header

            $job->update(['total_items' => $total]);

            $collection = Collection::where('tenant_id', $job->tenant_id)
                ->where('handle', $job->collection_handle)
                ->firstOrFail();

            $processed = 0;
            $failed = 0;
            $errors = [];

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    $data = array_combine($headers, $row);

                    Entry::create([
                        'id' => Str::uuid(),
                        'tenant_id' => $job->tenant_id,
                        'collection_id' => $collection->id,
                        'title' => $data['title'] ?? 'Untitled',
                        'slug' => $data['slug'] ?? Str::slug($data['title'] ?? uniqid()),
                        'status' => 'published',
                        'data' => array_diff_key($data, ['title' => 1, 'slug' => 1]),
                        'published_at' => now(),
                        'created_by' => $job->user_id,
                    ]);
                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "Row {$processed}: " . $e->getMessage();
                }

                $job->update(['processed_items' => $processed, 'failed_items' => $failed]);
            }

            fclose($handle);

            $job->update([
                'status' => 'completed',
                'completed_at' => now(),
                'error_log' => $errors,
            ]);

        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_log' => [$e->getMessage()],
            ]);
        }
    }

    public function importJson(ImportJob $job): void
    {
        $job->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $path = storage_path('app/' . $job->source_file);
            $content = file_get_contents($path);
            $data = json_decode($content, true);

            $items = $data['entries'] ?? $data;
            $total = count($items);
            $job->update(['total_items' => $total]);

            $collection = Collection::where('tenant_id', $job->tenant_id)
                ->where('handle', $job->collection_handle)
                ->firstOrFail();

            $processed = 0;
            $failed = 0;
            $errors = [];

            foreach ($items as $item) {
                try {
                    Entry::create([
                        'id' => Str::uuid(),
                        'tenant_id' => $job->tenant_id,
                        'collection_id' => $collection->id,
                        'title' => $item['title'] ?? 'Untitled',
                        'slug' => $item['slug'] ?? Str::slug($item['title'] ?? uniqid()),
                        'status' => $item['status'] ?? 'published',
                        'data' => $item['data'] ?? [],
                        'published_at' => now(),
                        'created_by' => $job->user_id,
                    ]);
                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "Item {$processed}: " . $e->getMessage();
                }

                $job->update(['processed_items' => $processed, 'failed_items' => $failed]);
            }

            $job->update([
                'status' => 'completed',
                'completed_at' => now(),
                'error_log' => $errors,
            ]);

        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_log' => [$e->getMessage()],
            ]);
        }
    }
}
