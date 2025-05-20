<?php

namespace App\Providers;

use App\Broadcasting\BroadcastNotificationChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the custom broadcast channel as a singleton
        $this->app->singleton('custom.broadcast.channel', function ($app) {
            return new \App\Broadcasting\BroadcastNotificationChannel;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register the broadcast channel for private user notifications
        Broadcast::channel('user.{userId}', function ($user, $userId) {
            return (int) $user->id === (int) $userId;
        });

        // Register the broadcast channel for notifications
        Broadcast::channel('notifications.{userId}', function ($user, $userId) {
            return (int) $user->id === (int) $userId;
        });

        // Extend the notification channels
        $this->app->extend('notifications', function ($service, $app) {
            $service->extend('broadcast', function () use ($app) {
                return $app->make('custom.broadcast.channel');
            });

            return $service;
        });

        // Register the broadcast routes
        $this->registerBroadcastRoutes();
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

        // Register the broadcast routes with authentication middleware
        Broadcast::routes([
            'middleware' => ['auth:api'],
            'prefix' => 'api/broadcasting',
        ]);
    }
}
