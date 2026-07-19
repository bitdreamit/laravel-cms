<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CmsInstall extends Command
{
    protected $signature = 'cms:install';
    protected $description = 'Run the full CMS V4 installation.';

    public function handle(): int
    {
        $this->info('Starting CMS V4 installation...');
        $this->newLine();

        // Run migrations
        $this->info('1. Running migrations...');
        $this->call('migrate');

        // Run seeders
        $this->info('2. Running seeders...');
        $this->call('db:seed');

        // Create storage symlink
        $this->info('3. Creating storage symlink...');
        $this->call('storage:link');

        // Clear caches
        $this->info('4. Clearing caches...');
        $this->call('optimize:clear');

        $this->newLine();
        $this->info('✓ CMS V4 installation complete!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('  1. Add domains to your /etc/hosts file (e.g., platform.test, advmedi.test)');
        $this->info('  2. Login at http://platform.test/admin');
        $this->info('  3. Default credentials: admin@platform.test / password');
        $this->info('');
        $this->info('Documentation: https://github.com/your-org/laravel-cms-v4');

        return self::SUCCESS;
    }
}
