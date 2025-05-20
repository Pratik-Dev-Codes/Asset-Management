<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupOldMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove old migration files and keep only the consolidated one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $migrationsPath = database_path('migrations');
        $files = File::files($migrationsPath);
        $keepFiles = [
            '2025_05_19_000000_consolidated_database_schema.php',
            '0000_00_00_000000_remove_old_migrations.php',
        ];

        $removedCount = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();
            if (!in_array($filename, $keepFiles) && $filename !== '.gitignore') {
                File::delete($file->getPathname());
                $this->info("Removed old migration: {$filename}");
                $removedCount++;
            }
        }

        $this->info("\nRemoved {$removedCount} old migration files.");
        $this->info('Only the consolidated migration files remain.');

        return 0;
    }
}
