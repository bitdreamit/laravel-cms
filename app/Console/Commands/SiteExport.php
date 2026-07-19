<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SiteExport extends Command
{
    protected $signature = 'site:export {--tenant= : Tenant ID} {--output= : Output directory}';
    protected $description = 'Export the tenant site as a static HTML bundle.';

    public function handle(): int
    {
        $tenantId = $this->option('tenant') ?? tenant('id');
        if (! $tenantId) {
            $this->error('No tenant context. Use --tenant option.');
            return self::FAILURE;
        }

        $tenant = \App\Models\Central\Tenant::find($tenantId);
        if (! $tenant) {
            $this->error("Tenant not found.");
            return self::FAILURE;
        }

        $outputDir = $this->option('output') ?? storage_path("app/exports/{$tenant->slug}-" . now()->format('Y-m-d-His'));

        $this->info("Exporting site for tenant: {$tenant->name}");
        $this->info("Output directory: {$outputDir}");

        File::makeDirectory($outputDir, 0755, true);

        \Stancl\Tenancy\Tenancy::runForMultiple($tenant, function () use ($outputDir) {
            $entries = \App\Models\Tenant\Entry::where('tenant_id', tenant('id'))
                ->where('status', 'published')
                ->get();

            $count = 0;
            foreach ($entries as $entry) {
                $html = view('public.entry-show', [
                    'entry' => $entry,
                    'collection' => $entry->collection,
                ])->render();

                File::put("{$outputDir}/{$entry->slug}.html", $html);
                $count++;
            }

            // Generate index page
            $indexHtml = view('public.home', ['entries' => $entries])->render();
            File::put("{$outputDir}/index.html", $indexHtml);

            $this->info("Exported {$count} entries + index.html");
        });

        $this->info("✓ Export complete: {$outputDir}");
        return self::SUCCESS;
    }
}
