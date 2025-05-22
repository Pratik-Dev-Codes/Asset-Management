<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearAllCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all caches and optimize the application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Clearing all caches...');
        
        $commands = [
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
            'event:clear',
            'optimize:clear',
        ];

        foreach ($commands as $command) {
            $this->info("Running: {$command}");
            Artisan::call($command);
            $this->line(Artisan::output());
        }

        $this->info('All caches cleared successfully!');
        return 0;
    }
}
