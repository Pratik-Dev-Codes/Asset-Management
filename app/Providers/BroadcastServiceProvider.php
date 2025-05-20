<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the broadcast manager
        $this->app->register(\Illuminate\Broadcasting\BroadcastServiceProvider::class);
        
        // Register the broadcast manager instance
        $this->app->singleton(\Illuminate\Broadcasting\BroadcastManager::class, function ($app) {
            return $app->make(\Illuminate\Broadcasting\BroadcastManager::class);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Set the broadcast driver from the environment
        $broadcastDriver = env('BROADCAST_DRIVER', 'log');
        config(['broadcasting.default' => $broadcastDriver]);
        
        // Log the broadcast driver being used
        if (function_exists('info')) {
            info("Broadcast driver set to: " . $broadcastDriver);
        }
        
        // Register broadcast routes with authentication middleware
        $this->registerBroadcastRoutes();

        // Load the broadcast channels
        require base_path('routes/channels.php');
    }
    
    /**
     * Register the broadcast routes.
     *
     * @return void
     */
    protected function registerBroadcastRoutes()
    {
        // Only register broadcast routes if we're not running in the console
        if ($this->app->runningInConsole()) {
            return;
        }
        Broadcast::routes([
            'middleware' => ['auth:api'],
            'prefix' => 'api/broadcasting',
        ]);
    }
}
