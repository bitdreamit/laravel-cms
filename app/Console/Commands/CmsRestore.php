<?php

namespace App\Console\Commands;

use App\Domain\Backup\Services\BackupService;
use Illuminate\Console\Command;

class CmsRestore extends Command
{
    protected $signature = 'cms:restore {path : Path to backup file} {--force : Skip confirmation}';
    protected $description = 'Restore CMS from a backup file.';

    public function handle(BackupService $backupService): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Backup file not found: {$path}");
            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->warn('⚠️  WARNING: This will OVERWRITE all current data!');
            $this->warn('   This action cannot be undone.');
            $this->newLine();

            if (! $this->confirm('Are you sure you want to restore from this backup?')) {
                $this->info('Restore cancelled.');
                return self::SUCCESS;
            }

            if (! $this->confirm('FINAL CONFIRMATION: Type yes to proceed')) {
                $this->info('Restore cancelled.');
                return self::SUCCESS;
            }
        }

        $this->info('Restoring from backup...');
        $backupService->restore($path, ['force' => true]);

        $this->info('✓ Restore complete!');
        $this->warn('Run `php artisan optimize:clear` to clear caches.');

        return self::SUCCESS;
    }
}
