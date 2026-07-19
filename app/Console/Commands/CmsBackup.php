<?php

namespace App\Console\Commands;

use App\Domain\Backup\Services\BackupService;
use Illuminate\Console\Command;

class CmsBackup extends Command
{
    protected $signature = 'cms:backup {--tenant= : Tenant ID for per-tenant backup} {--list : List backups} {--prune= : Delete backups older than N days} {--disk=local : Storage disk}';
    protected $description = 'Create, list, or prune CMS backups.';

    public function handle(BackupService $backupService): int
    {
        if ($this->option('list')) {
            $this->listBackups($backupService);
            return self::SUCCESS;
        }

        if ($days = $this->option('prune')) {
            $deleted = $backupService->pruneOldBackups((int) $days, $this->option('disk'));
            $this->info("Deleted {$deleted} backups older than {$days} days.");
            return self::SUCCESS;
        }

        $disk = $this->option('disk');

        if ($tenantId = $this->option('tenant')) {
            $this->info("Creating tenant backup for {$tenantId}...");
            $path = $backupService->createTenantBackup($tenantId);
        } else {
            $this->info('Creating full platform backup...');
            $path = $backupService->createFullBackup($disk);
        }

        $this->info("✓ Backup created: {$path}");
        $this->info('  Size: ' . $this->formatBytes(filesize($path)));

        return self::SUCCESS;
    }

    protected function listBackups(BackupService $backupService): void
    {
        $backups = $backupService->listBackups($this->option('disk'));

        if (empty($backups)) {
            $this->info('No backups found.');
            return;
        }

        $this->table(
            ['Name', 'Size', 'Created'],
            collect($backups)->map(fn($b) => [
                $b['name'],
                $b['size_human'],
                $b['created_at']->format('Y-m-d H:i:s'),
            ])->toArray()
        );
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
