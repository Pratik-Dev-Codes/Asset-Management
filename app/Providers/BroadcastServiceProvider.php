<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Disable custom broadcast channel registration
        $this->app->singleton('custom.broadcast.channels', function ($app) {
            return [
                // Channel registrations are disabled
            ];
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disable broadcasting routes
        // Broadcast::routes([
        //     'middleware' => ['auth:api'],
        //     'prefix' => 'api/broadcasting',
        // ]);

        // Disable channel loading
        // require base_path('routes/channels.php');

        // Disable custom broadcast channels
        // $channels = $this->app->make('custom.broadcast.channels');
        // foreach ($channels as $name => $channel) {
        //     Broadcast::channel($name.'.{id}', function ($user, $id) use ($channel) {
        //         return $this->app->make($channel)->authorize($user, $id);
        //     });
        // }
    }
}
