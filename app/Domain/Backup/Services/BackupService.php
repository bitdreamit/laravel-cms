<?php

namespace App\Domain\Backup\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BackupService
{
    public function createFullBackup(string $disk = 'local'): string
    {
        $timestamp = now()->format('Y-m-d-His');
        $backupName = "cms-backup-{$timestamp}";
        $tempPath = storage_path("app/backups/{$backupName}");
        File::makeDirectory($tempPath, 0755, true);

        // 1. Database dump
        $this->dumpDatabase($tempPath . '/database.sql');

        // 2. Uploaded files
        $this->backupFiles($tempPath . '/files');

        // 3. Theme files
        $this->backupThemes($tempPath . '/themes');

        // 4. Config snapshot
        $this->backupConfig($tempPath . '/config.json');

        // 5. Manifest
        $this->createManifest($tempPath, $backupName);

        // 6. Zip it
        $zipPath = storage_path("app/backups/{$backupName}.zip");
        $this->zipDirectory($tempPath, $zipPath);
        File::deleteDirectory($tempPath);

        // 7. Move to target disk
        $finalPath = "backups/{$backupName}.zip";
        if ($disk !== 'local') {
            $content = File::get($zipPath);
            Storage::disk($disk)->put($finalPath, $content);
            File::delete($zipPath);
            return Storage::disk($disk)->path($finalPath);
        }

        return $zipPath;
    }

    public function createTenantBackup(string $tenantId): string
    {
        $tenant = \App\Models\Central\Tenant::find($tenantId);
        if (! $tenant) throw new \RuntimeException("Tenant not found");

        $timestamp = now()->format('Y-m-d-His');
        $backupName = "tenant-{$tenant->slug}-{$timestamp}";
        $tempPath = storage_path("app/backups/{$backupName}");
        File::makeDirectory($tempPath, 0755, true);

        // Tenant-specific data
        $tenantData = [
            'tenant' => $tenant->toArray(),
            'domains' => $tenant->domains()->get()->toArray(),
            'tenant_users' => DB::table('tenant_users')->where('tenant_id', $tenantId)->get()->toArray(),
        ];

        // Run within tenant context for tenant-scoped tables
        tenancy()->run($tenant, function () use ($tempPath, $tenantId) {
            $tables = ['entries', 'collections', 'blueprints', 'taxonomies', 'terms', 'globals',
                      'navigations', 'forms', 'form_submissions', 'assets', 'redirects',
                      'workflows', 'workflow_instances', 'experiments', 'segments',
                      'personalization_rules', 'theme_customizations'];

            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $data = DB::table($table)->where('tenant_id', $tenantId)->get();
                    File::put("{$tempPath}/{$table}.json", $data->toJson(JSON_PRETTY_PRINT));
                }
            }
        });

        File::put("{$tempPath}/tenant_data.json", json_encode($tenantData, JSON_PRETTY_PRINT));

        $zipPath = storage_path("app/backups/{$backupName}.zip");
        $this->zipDirectory($tempPath, $zipPath);
        File::deleteDirectory($tempPath);

        return $zipPath;
    }

    public function restore(string $backupPath, array $options = []): void
    {
        if (! File::exists($backupPath)) {
            throw new \RuntimeException("Backup file not found: {$backupPath}");
        }

        $tempPath = storage_path('app/backups/restore-' . Str::uuid());
        File::makeDirectory($tempPath, 0755, true);

        $zip = new ZipArchive();
        $zip->open($backupPath);
        $zip->extractTo($tempPath);
        $zip->close();

        if (File::exists("{$tempPath}/database.sql")) {
            $this->restoreDatabase("{$tempPath}/database.sql", $options);
        }

        if (File::isDirectory("{$tempPath}/files")) {
            $this->restoreFiles("{$tempPath}/files");
        }

        File::deleteDirectory($tempPath);
    }

    public function listBackups(string $disk = 'local'): array
    {
        $files = Storage::disk($disk)->files('backups');
        return collect($files)->filter(fn($f) => str_ends_with($f, '.zip'))->map(function ($file) use ($disk) {
            return [
                'name' => basename($file),
                'path' => $file,
                'size' => Storage::disk($disk)->size($file),
                'size_human' => $this->formatBytes(Storage::disk($disk)->size($file)),
                'created_at' => \Carbon\Carbon::createFromTimestamp(Storage::disk($disk)->lastModified($file)),
            ];
        })->sortByDesc('created_at')->values()->toArray();
    }

    public function deleteBackup(string $path, string $disk = 'local'): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    public function pruneOldBackups(int $keepDays = 30, string $disk = 'local'): int
    {
        $cutoff = now()->subDays($keepDays);
        $deleted = 0;

        foreach ($this->listBackups($disk) as $backup) {
            if ($backup['created_at']->lt($cutoff)) {
                $this->deleteBackup($backup['path'], $disk);
                $deleted++;
            }
        }

        return $deleted;
    }

    protected function dumpDatabase(string $path): void
    {
        $driver = DB::getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dbName = config('database.connections.' . config('database.default') . '.database');
        $dbHost = config('database.connections.' . config('database.default') . '.host');
        $dbUser = config('database.connections.' . config('database.default') . '.username');
        $dbPass = config('database.connections.' . config('database.default') . '.password');

        $command = match($driver) {
            'mysql' => "mysqldump --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} > {$path}",
            'pgsql' => "PGPASSWORD={$dbPass} pg_dump --host={$dbHost} --username={$dbUser} {$dbName} > {$path}",
            'sqlite' => "cp " . database_path('database.sqlite') . " {$path}",
            default => null,
        };

        if ($command) {
            exec($command, $output, $returnCode);
            if ($returnCode !== 0) {
                // Fallback: dump as JSON
                $this->dumpDatabaseAsJson($path);
            }
        }
    }

    protected function dumpDatabaseAsJson(string $path): void
    {
        $tables = DB::select("SHOW TABLES");
        $dump = [];
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $dump[$tableName] = DB::table($tableName)->get()->toArray();
        }
        File::put($path, json_encode($dump, JSON_PRETTY_PRINT));
    }

    protected function backupFiles(string $path): void
    {
        $source = storage_path('app/public');
        if (File::isDirectory($source)) {
            File::copyDirectory($source, $path);
        }
    }

    protected function backupThemes(string $path): void
    {
        $source = base_path('themes');
        if (File::isDirectory($source)) {
            File::copyDirectory($source, $path);
        }
    }

    protected function backupConfig(string $path): void
    {
        $config = [
            'cms_version' => config('cms.version'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_driver' => DB::getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME),
            'backup_date' => now()->toIso8601String(),
            'tenants_count' => \App\Models\Central\Tenant::count(),
        ];
        File::put($path, json_encode($config, JSON_PRETTY_PRINT));
    }

    protected function createManifest(string $path, string $name): void
    {
        $manifest = [
            'name' => $name,
            'created_at' => now()->toIso8601String(),
            'cms_version' => config('cms.version'),
            'type' => 'full',
        ];
        File::put("{$path}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
    }

    protected function restoreDatabase(string $sqlPath, array $options): void
    {
        if (! ($options['force'] ?? false)) {
            throw new \RuntimeException('Database restore requires explicit confirmation. Pass force=true to proceed.');
        }

        $driver = DB::getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dbName = config('database.connections.' . config('database.default') . '.database');
        $dbHost = config('database.connections.' . config('database.default') . '.host');
        $dbUser = config('database.connections.' . config('database.default') . '.username');
        $dbPass = config('database.connections.' . config('database.default') . '.password');

        $command = match($driver) {
            'mysql' => "mysql --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} < {$sqlPath}",
            'pgsql' => "PGPASSWORD={$dbPass} psql --host={$dbHost} --username={$dbUser} {$dbName} < {$sqlPath}",
            default => null,
        };

        if ($command) exec($command);
    }

    protected function restoreFiles(string $path): void
    {
        $dest = storage_path('app/public');
        if (File::isDirectory($path)) {
            File::copyDirectory($path, $dest);
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

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
