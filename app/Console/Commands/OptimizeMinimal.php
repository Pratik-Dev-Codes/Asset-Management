<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OptimizeMinimal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:minimal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the application with minimal memory usage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting minimal optimization...');

        // Disable debug mode
        config(['app.debug' => false]);

        // Clear configuration cache
        $this->callCommand('config:clear');

        // Clear route cache
        $this->callCommand('route:clear');

        // Clear view cache
        $this->callCommand('view:clear');

        // Clear compiled files
        $this->callCommand('clear-compiled');

        // Clear application cache
        $this->callCommand('cache:clear');

        // Optimize the application
        $this->callCommand('optimize');

        // Cache the configuration
        $this->callCommand('config:cache');

        // Cache the routes
        $this->callCommand('route:cache');

        // Cache the views
        $this->callCommand('view:cache');

        $this->info('Optimization completed successfully!');

        return 0;
    }

    /**
     * Call an Artisan command with memory limit.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    protected function callCommand($command, $parameters = [])
    {
        $this->info("Running: {$command}...");

        // Run the command in a separate process to isolate memory usage
        $exitCode = Artisan::call($command, $parameters);

        if ($exitCode !== 0) {
            $this->error("Failed to run: {$command}");
        } else {
            $this->info("Completed: {$command}");
        }

        return $exitCode;
    }
}
